#!/usr/bin/env bash

# Regression harness for build/version.sh (issue #325).
#
# version.sh derives a release version from the current branch, the latest git
# tag, and (for main) CHANGELOG.md. It has no PHP; the repo's tests/ tree is
# entirely Pest, so nothing guarded the branch-selection or CHANGELOG-read/
# fallback logic against regression before this harness. The original #186 bug
# (a 0.2.0 release emitting 0.1.19) reached Packagist and had to be hand-fixed,
# so a guard on this script has concrete value.
#
# Each case stands up a throwaway git repo (mktemp -d) with controlled
# tags/commits/CHANGELOG state, runs version.sh from inside it via the
# --branch=/--main simulation flags, and asserts the emitted version. Because
# version.sh resolves branch from the flag and git state + CHANGELOG.md against
# the cwd, running from inside the temp repo fully isolates each case. All temp
# repos live under one base dir that is removed on exit.
#
# Run locally with `bash build/version.test.sh` or `make test-version`.
# Exits non-zero if any assertion fails.

# Fail fast on any unexpected command failure: this is a regression guard, so a
# broken setup command (mktemp, git init/tag/commit) must abort rather than run
# assertions against a half-built repo and emit a misleading result. Note bash's
# errexit is not inherited by command substitutions unless inherit_errexit is set
# (bash 4.4+), and this harness must stay runnable under macOS's bash 3.2 — so the
# one setup helper that runs inside $( ) (new_repo) fails fast explicitly below.
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
VERSION_SH="$SCRIPT_DIR/version.sh"

if [[ ! -f "$VERSION_SH" ]]; then
    echo "ERROR: cannot find version.sh next to this harness ($VERSION_SH)" >&2
    exit 2
fi

# version.sh's main-branch path compares the CHANGELOG version against the latest
# tag with `sort -V` (version sort). Current macOS and GNU coreutils both support
# it, but an older/pure-BSD `sort` does not — there it would silently mis-order
# versions and make the main-branch cases fail with a confusing, unrelated-looking
# assertion diff. Preflight it here so an unsupported `sort` fails fast with a
# clear message instead.
if ! printf '0.1.0\n0.2.0\n' | sort -V >/dev/null 2>&1; then
    echo "ERROR: 'sort -V' (version sort) is not supported by this environment's sort;" >&2
    echo "       version.sh's main-branch version comparison requires it." >&2
    echo "       Install GNU coreutils (e.g. 'brew install coreutils' and use gsort) or run on a platform whose sort supports -V." >&2
    exit 2
fi

PASS=0
FAIL=0

TMP_ROOT=$(mktemp -d "${TMPDIR:-/tmp}/version-test.XXXXXX")
cleanup() { rm -rf "$TMP_ROOT"; }
trap cleanup EXIT

# Create a fresh, isolated git repo and print its path.
# Runs inside a command substitution ($(new_repo)), where errexit is NOT in
# effect (no inherit_errexit under bash 3.2), so chain the setup explicitly and
# return non-zero on any failure. The non-zero return propagates out of the
# command substitution and trips the caller's `set -e`, aborting the harness
# instead of yielding a path to a half-initialised repo.
new_repo() {
    local dir
    dir=$(mktemp -d "$TMP_ROOT/repo.XXXXXX") &&
    git -C "$dir" init -q &&
    git -C "$dir" config user.email "test@example.com" &&
    git -C "$dir" config user.name "version.sh test" &&
    git -C "$dir" config commit.gpgsign false &&
    git -C "$dir" config tag.gpgsign false || return 1
    printf '%s' "$dir"
}

commit() { git -C "$1" commit -q --allow-empty -m "${2:-commit}"; }
tag()    { git -C "$1" tag "$2"; }

# Write a CHANGELOG.md at the repo root with the given top versioned section.
# $2 is the version string for the newest "## [x.y.z]" heading; omit to write an
# Unreleased-only (unparseable) changelog.
write_changelog() {
    local dir="$1" version="${2:-}"
    {
        echo "# Changelog"
        echo ""
        echo "## [Unreleased]"
        echo ""
        if [[ -n "$version" ]]; then
            echo "## [$version] - 2026-01-01"
            echo ""
            echo "- Something."
        fi
    } > "$dir/CHANGELOG.md"
}

# Run version.sh inside the repo, returning only stdout. stderr is captured and
# suppressed on success (so warnings/parse notices do not pollute the asserted
# value), but surfaced to the harness's own stderr when version.sh exits
# non-zero, so a regression that makes the script fail is diagnosable instead of
# silently yielding an empty/parse-failed value that fails an assertion with no
# clue why.
run_version() {
    local err="$TMP_ROOT/run_version.stderr"
    local out rc=0
    out=$( cd "$1" && bash "$VERSION_SH" "$2" 2>"$err" ) || rc=$?
    if (( rc != 0 )); then
        printf 'run_version: version.sh exited %d for [%s] in %s; stderr follows:\n' \
            "$rc" "$2" "$1" >&2
        cat "$err" >&2
    fi
    rm -f "$err"
    printf '%s' "$out"
    return "$rc"
}

assert_eq() {
    local label="$1" expected="$2" actual="$3"
    if [[ "$expected" == "$actual" ]]; then
        PASS=$((PASS + 1))
        printf 'ok   - %s\n' "$label"
    else
        FAIL=$((FAIL + 1))
        printf 'FAIL - %s\n         expected: [%s]\n         actual:   [%s]\n' \
            "$label" "$expected" "$actual"
    fi
}

# Assert that $3 (haystack) contains $2 (needle) as a substring.
assert_contains() {
    local label="$1" needle="$2" haystack="$3"
    if [[ "$haystack" == *"$needle"* ]]; then
        PASS=$((PASS + 1))
        printf 'ok   - %s\n' "$label"
    else
        FAIL=$((FAIL + 1))
        printf 'FAIL - %s\n         expected substring: [%s]\n         actual:   [%s]\n' \
            "$label" "$needle" "$haystack"
    fi
}

# --- main: CHANGELOG-read (minor/major bump) ---------------------------------
# Commit after tag 0.2.0 with a newer CHANGELOG top section -> read 0.3.0.
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
write_changelog "$repo" 0.3.0
assert_eq "main reads newer CHANGELOG version" "0.3.0" "$(run_version "$repo" --branch=main)"

# --- main: patch fallback, missing CHANGELOG ---------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
assert_eq "main patch-fallback on missing CHANGELOG" "0.2.1" "$(run_version "$repo" --branch=main)"

# --- main: patch fallback, unparseable CHANGELOG (Unreleased only) -----------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
write_changelog "$repo"
assert_eq "main patch-fallback on unparseable CHANGELOG" "0.2.1" "$(run_version "$repo" --branch=main)"

# --- main: patch fallback, CHANGELOG not newer than tag ----------------------
# CHANGELOG top equals the tag -> strictly-newer guard rejects it -> patch bump.
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
write_changelog "$repo" 0.2.0
assert_eq "main patch-fallback when CHANGELOG equals tag" "0.2.1" "$(run_version "$repo" --branch=main)"

# --- main: exact tag emits the tag itself ------------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0
write_changelog "$repo" 0.3.0
assert_eq "main on exact tag emits the tag" "0.2.0" "$(run_version "$repo" --branch=main)"

# --- main: --main alias behaves like --branch=main ---------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0
assert_eq "--main alias resolves to main branch" "0.2.0" "$(run_version "$repo" --main)"

# --- develop: exact tag -> next minor beta-1 ---------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0
assert_eq "develop on exact tag -> beta-1" "0.3.0.beta-1" "$(run_version "$repo" --branch=develop)"

# --- develop: N commits since tag -> beta-N ----------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"; commit "$repo"
assert_eq "develop with 2 commits -> beta-2" "0.3.0.beta-2" "$(run_version "$repo" --branch=develop)"

# --- release/*: no existing RC tags -> rc-1 ----------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.1.0
assert_eq "release branch, no RC tags -> rc-1" \
    "0.2.0.rc-1" "$(run_version "$repo" --branch=release/0.2.0)"

# --- release/*: existing rc-1 -> rc-2 ----------------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0.rc-1
assert_eq "release branch, existing rc-1 -> rc-2" \
    "0.2.0.rc-2" "$(run_version "$repo" --branch=release/0.2.0)"

# --- hotfix/*: patch bump with hotfix suffix ---------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
assert_eq "hotfix branch -> patch + hotfix suffix" \
    "0.2.1-hotfix.foo.1" "$(run_version "$repo" --branch=hotfix/foo)"

# --- feature/*: alpha with commit count --------------------------------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
assert_eq "feature branch -> alpha.N" \
    "0.2.0-alpha.1" "$(run_version "$repo" --branch=feature/bar)"

# --- no tags: dev version with commit count ----------------------------------
repo=$(new_repo)
commit "$repo"
assert_eq "no tags -> 0.1.0-dev.<count>" "0.1.0-dev.1" "$(run_version "$repo" --branch=main)"

# --- unknown branch type: dev version + stderr warning, exit 0 ---------------
repo=$(new_repo)
commit "$repo"; tag "$repo" 0.2.0; commit "$repo"
assert_eq "unknown branch -> dev.N" "0.2.0-dev.1" "$(run_version "$repo" --branch=weird)"
# Capture the exit code AND stderr deliberately; the `|| rc=$?` keeps a non-zero
# exit from tripping `set -e` here, since asserting on that code is the whole
# point. `2>&1 >/dev/null` routes stderr into the command substitution while
# discarding stdout, so weird_stderr holds only the warning stream.
rc=0
weird_stderr=$( cd "$repo" && bash "$VERSION_SH" --branch=weird 2>&1 >/dev/null ) || rc=$?
assert_eq "unknown branch still exits 0" "0" "$rc"
# The case claims to validate a stderr warning; actually assert it is emitted so
# a regression that drops the warning is caught rather than silently passing.
assert_contains "unknown branch warns on stderr" \
    "WARNING: Unsupported branch type 'weird'" "$weird_stderr"

# --- summary -----------------------------------------------------------------
echo ""
echo "-----------------------------------------"
printf '%d passed, %d failed\n' "$PASS" "$FAIL"

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi
exit 0
