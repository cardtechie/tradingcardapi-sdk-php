#!/usr/bin/env bash

# Regression harness for build/collate-changelog.sh and
# build/commit-changelog-back.sh (issue #406).
#
# Both scripts are bash with no PHP, so the Pest suite cannot reach them. Each
# case stands up a throwaway git repo (mktemp -d) with a bare `origin`, copies
# the two scripts into its build/ directory (commit-changelog-back.sh resolves
# the project root from its own location), and asserts on CHANGELOG.md, the
# fragments left behind, the commits made, and the release=true|false output.
#
# Run locally with `bash build/collate-changelog.test.sh` or
# `make test-changelog`. Exits non-zero if any assertion fails. Stays runnable
# under macOS's bash 3.2, like build/version.test.sh.

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
COLLATE_SH="$SCRIPT_DIR/collate-changelog.sh"
COMMIT_BACK_SH="$SCRIPT_DIR/commit-changelog-back.sh"

for f in "$COLLATE_SH" "$COMMIT_BACK_SH"; do
    if [[ ! -f "$f" ]]; then
        echo "ERROR: cannot find $f next to this harness" >&2
        exit 2
    fi
done

BOT_NAME="cardtechie-ops-bot[bot]"
SUBJECT_PREFIX="chore(release): collate CHANGELOG for "
REPO_URL="https://github.com/cardtechie/tradingcardapi-sdk-php"

PASS=0
FAIL=0

TMP_ROOT=$(mktemp -d "${TMPDIR:-/tmp}/collate-test.XXXXXX")
cleanup() { rm -rf "$TMP_ROOT"; }
trap cleanup EXIT

pass() { PASS=$((PASS + 1)); printf 'ok   - %s\n' "$1"; }
fail() { FAIL=$((FAIL + 1)); printf 'FAIL - %s\n' "$1"; [[ -n "${2:-}" ]] && printf '         %s\n' "$2"; return 0; }

assert_eq() {
    if [[ "$2" == "$3" ]]; then pass "$1"; else fail "$1" "expected: [$2] actual: [$3]"; fi
}
assert_contains() {
    if [[ "$3" == *"$2"* ]]; then pass "$1"; else fail "$1" "expected substring: [$2] in: [$3]"; fi
}
assert_not_contains() {
    if [[ "$3" != *"$2"* ]]; then pass "$1"; else fail "$1" "unexpected substring: [$2]"; fi
}

# Write the standard CHANGELOG: an empty [Unreleased], one released version
# with a Fixed subsection, and compare links.
write_changelog() {
    cat > "$1/CHANGELOG.md" <<EOF
# Changelog

## [Unreleased]

## [0.3.6] - 2026-10-22

### Fixed

- **[Issue #100]** An old fix.

[Unreleased]: ${REPO_URL}/compare/0.3.6...HEAD
[0.3.6]: ${REPO_URL}/compare/0.3.5...0.3.6
EOF
}

# Create a repo with a bare origin, the two scripts, a CHANGELOG and an initial
# pushed commit on main. Prints the working-copy path. Runs in a command
# substitution (no errexit under bash 3.2), so every step is chained.
new_repo() {
    local base work
    base=$(mktemp -d "$TMP_ROOT/case.XXXXXX") &&
    git init -q --bare "$base/origin.git" &&
    git init -q "$base/work" &&
    work="$base/work" &&
    git -C "$work" config user.email "test@example.com" &&
    git -C "$work" config user.name "collate test" &&
    git -C "$work" config commit.gpgsign false &&
    git -C "$work" checkout -q -b main &&
    git -C "$work" remote add origin "$base/origin.git" &&
    mkdir -p "$work/build" "$work/changelog.d" &&
    cp "$COLLATE_SH" "$COMMIT_BACK_SH" "$work/build/" &&
    echo "# Changelog fragments" > "$work/changelog.d/README.md" &&
    : > "$work/changelog.d/.gitkeep" &&
    write_changelog "$work" &&
    git -C "$work" add -A &&
    git -C "$work" commit -q -m "initial" &&
    git -C "$work" push -q origin main 2>/dev/null || return 1
    printf '%s' "$work"
}

# Add and commit fragment $2 with body $3.
add_fragment() {
    printf '%s\n' "$3" > "$1/changelog.d/$2"
    git -C "$1" add -A
    git -C "$1" commit -q -m "add $2"
}

# Run commit-changelog-back.sh in repo $1 with the remaining args. Sets RC,
# OUT (stdout+stderr) and RESULT (the release= value written to --output).
run_commit_back() {
    local repo="$1"; shift
    local outfile="$repo/../gh_output"
    rm -f "$outfile"
    RC=0
    OUT=$(cd "$repo" && bash build/commit-changelog-back.sh "$@" --output "$outfile" 2>&1) || RC=$?
    RESULT=$(grep '^release=' "$outfile" 2>/dev/null || true)
}

commit_count() { git -C "$1" rev-list --count HEAD; }

# --- 1. collation groups by type and merges into existing subsections -------
repo=$(new_repo)
# Seed [Unreleased] with an existing Fixed entry to prove the merge.
sed -i.bak 's/^## \[Unreleased\]$/## [Unreleased]\
\
### Fixed\
\
- **[Issue #200]** Already pending./' "$repo/CHANGELOG.md" && rm -f "$repo/CHANGELOG.md.bak"
git -C "$repo" commit -q -am "seed unreleased"
add_fragment "$repo" 201-fixed.md "- **[Issue #201]** Fix two."
add_fragment "$repo" 202-added.md "- **[Issue #202]** Add one."
add_fragment "$repo" 203-security.md "- **[Issue #203]** Patch one."
(cd "$repo" && bash build/collate-changelog.sh >/dev/null)
section=$(sed -n '/^## \[Unreleased\]/,/^## \[0\.3\.6\]/p' "$repo/CHANGELOG.md")
assert_eq "collate: one Fixed heading in [Unreleased]" "1" "$(grep -c '^### Fixed' <<< "$section")"
assert_contains "collate: existing Fixed entry kept" "**[Issue #200]** Already pending." "$section"
assert_contains "collate: new fixed merged" "**[Issue #201]** Fix two." "$section"
assert_contains "collate: added subsection created" "### Added" "$section"
assert_contains "collate: security subsection created" "### Security" "$section"
assert_eq "collate: fragments consumed" "" "$(find "$repo/changelog.d" -name '[0-9]*' -print)"
assert_eq "collate: README and .gitkeep kept" "yes" "$([[ -f "$repo/changelog.d/README.md" && -f "$repo/changelog.d/.gitkeep" ]] && echo yes || echo no)"

# --- 1b. [Unreleased] as the last section: new subsections land before links -
repo=$(new_repo)
cat > "$repo/CHANGELOG.md" <<EOF
# Changelog

## [Unreleased]

### Fixed

- **[Issue #300]** Already pending.

[Unreleased]: ${REPO_URL}/commits/HEAD
EOF
git -C "$repo" commit -q -am "first-release changelog"
add_fragment "$repo" 301-fixed.md "- **[Issue #301]** Fix two."
add_fragment "$repo" 302-added.md "- **[Issue #302]** Add one."
(cd "$repo" && bash build/collate-changelog.sh >/dev/null)
before_links=$(sed '/^\[Unreleased\]:/,$d' "$repo/CHANGELOG.md")
assert_contains "last-section: new fixed before links" "**[Issue #301]** Fix two." "$before_links"
assert_contains "last-section: added subsection before links" "### Added" "$before_links"
assert_contains "last-section: added entry before links" "**[Issue #302]** Add one." "$before_links"
assert_eq "last-section: links are the final line" "[Unreleased]: ${REPO_URL}/commits/HEAD" "$(tail -n 1 "$repo/CHANGELOG.md")"

# --- 2. empty changelog.d -> release=false, no commit ------------------------
repo=$(new_repo)
before=$(commit_count "$repo")
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "no-op: exit 0" "0" "$RC"
assert_eq "no-op: release=false" "release=false" "$RESULT"
assert_eq "no-op: no commit" "$before" "$(commit_count "$repo")"

# No fragment, but a line already under [Unreleased]: still release=false.
repo=$(new_repo)
sed -i.bak 's/^## \[Unreleased\]$/## [Unreleased]\
\
- Hand-left line./' "$repo/CHANGELOG.md" && rm -f "$repo/CHANGELOG.md.bak"
git -C "$repo" commit -q -am "hand-left unreleased line"
before=$(commit_count "$repo")
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "no fragments + [Unreleased] content: release=false" "release=false" "$RESULT"
assert_eq "no fragments + [Unreleased] content: no commit" "$before" "$(commit_count "$repo")"
assert_not_contains "no fragments + [Unreleased] content: not promoted" "## [0.3.7]" "$(cat "$repo/CHANGELOG.md")"

# A heading-led fragment whose entry is already collated is still refused. Since
# headings are rejected outright, that refusal now comes from the shared
# fragment validator, before the duplicate check is reached.
repo=$(new_repo)
add_fragment "$repo" 406-changed.md "- **[Issue #406]** Collate fragments after merge."
run_commit_back "$repo" 0.3.7 --branch main
printf '### Fixed\n\n- **[Issue #406]** Collate fragments after merge.\n' > "$repo/changelog.d/407-fixed.md"
git -C "$repo" add -A && git -C "$repo" commit -q -m "heading-led duplicate"
run_commit_back "$repo" 0.3.8 --branch main
assert_eq "heading-led duplicate: exit 1" "1" "$RC"
assert_contains "heading-led duplicate: refused" "containing a Markdown heading" "$OUT"

# --- 3. full commit-back: promote, links, bot commit, push -------------------
repo=$(new_repo)
add_fragment "$repo" 406-changed.md "- **[Issue #406]** Collate fragments after merge."
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "commit-back: exit 0" "0" "$RC"
assert_eq "commit-back: release=true" "release=true" "$RESULT"
assert_contains "commit-back: version section" "## [0.3.7] - " "$(cat "$repo/CHANGELOG.md")"
assert_eq "commit-back: fresh empty [Unreleased] above" "## [Unreleased]" "$(grep -m1 '^## \[' "$repo/CHANGELOG.md")"
assert_contains "commit-back: [Unreleased] link from new version" "[Unreleased]: ${REPO_URL}/compare/0.3.7...HEAD" "$(cat "$repo/CHANGELOG.md")"
assert_contains "commit-back: new version link" "[0.3.7]: ${REPO_URL}/compare/0.3.6...0.3.7" "$(cat "$repo/CHANGELOG.md")"
links=$(grep -E '^\[' "$repo/CHANGELOG.md" | tr '\n' '|')
assert_eq "commit-back: link order" "[Unreleased]: ${REPO_URL}/compare/0.3.7...HEAD|[0.3.7]: ${REPO_URL}/compare/0.3.6...0.3.7|[0.3.6]: ${REPO_URL}/compare/0.3.5...0.3.6|" "$links"
assert_eq "commit-back: author is the bot" "$BOT_NAME" "$(git -C "$repo" log -1 --format=%an)"
assert_eq "commit-back: subject" "${SUBJECT_PREFIX}0.3.7" "$(git -C "$repo" log -1 --format=%s)"
assert_not_contains "commit-back: no [skip ci]" "[skip ci]" "$(git -C "$repo" log -1 --format=%B)"
assert_eq "commit-back: pushed to origin/main" "$(git -C "$repo" rev-parse HEAD)" "$(git -C "$repo" ls-remote origin refs/heads/main | cut -f1)"
assert_eq "commit-back: fragment deleted in the commit" "D	changelog.d/406-changed.md" "$(git -C "$repo" show --name-status --format= HEAD | grep changelog.d/)"

# --- 4. running twice gives no duplicate entries (#403) ----------------------
run_commit_back "$repo" 0.3.8 --branch main
assert_eq "twice: second run is a clean no-op" "release=false" "$RESULT"
assert_eq "twice: entry appears once" "1" "$(grep -cF '**[Issue #406]** Collate fragments after merge.' "$repo/CHANGELOG.md")"

# --- 5. resume: HEAD is own commit-back -> release=true, no new commit -------
before=$(commit_count "$repo")
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "resume: exit 0" "0" "$RC"
assert_eq "resume: release=true" "release=true" "$RESULT"
assert_eq "resume: no new commit" "$before" "$(commit_count "$repo")"
assert_contains "resume: says resuming" "resuming the release" "$OUT"

# --- 6. resume refused once main moved past the commit-back ------------------
echo "code" > "$repo/file.txt"
git -C "$repo" add file.txt && git -C "$repo" commit -q -m "later work"
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "resume refused when HEAD moved: exit 1" "1" "$RC"
assert_eq "resume refused: no release= line" "" "$RESULT"

# --- 7. foreign existing section fails ----------------------------------------
repo=$(new_repo)
sed -i.bak 's/^## \[Unreleased\]$/## [Unreleased]\
\
## [0.3.7] - 2026-10-24\
\
- Hand-written./' "$repo/CHANGELOG.md" && rm -f "$repo/CHANGELOG.md.bak"
git -C "$repo" commit -q -am "hand-written section"
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "foreign section: exit 1" "1" "$RC"
assert_contains "foreign section: refuses" "refusing to write it twice" "$OUT"

# Commit metadata alone cannot spoof a resume: a bot-authored commit with the
# exact subject that also touches code is refused.
repo=$(new_repo)
sed -i.bak 's/^## \[Unreleased\]$/## [Unreleased]\
\
## [0.3.7] - 2026-10-24\
\
- Spoofed./' "$repo/CHANGELOG.md" && rm -f "$repo/CHANGELOG.md.bak"
echo "code" > "$repo/file.txt"
git -C "$repo" add -A
git -C "$repo" -c user.name="$BOT_NAME" commit -q -m "${SUBJECT_PREFIX}0.3.7"
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "spoofed resume: exit 1" "1" "$RC"

# --- 8. refusals ---------------------------------------------------------------
refuse_case() {
    local label="$1" setup="$2" needle="$3" repo before
    repo=$(new_repo)
    eval "$setup"
    before=$(commit_count "$repo")
    run_commit_back "$repo" 0.3.7 --branch main
    assert_eq "refuse $label: exit 1" "1" "$RC"
    assert_contains "refuse $label: message" "$needle" "$OUT"
    assert_eq "refuse $label: no release= line" "" "$RESULT"
    assert_eq "refuse $label: no commit" "$before" "$(commit_count "$repo")"
}
refuse_case "empty fragment" \
    ': > "$repo/changelog.d/1-fixed.md"' "no release-note line"
refuse_case "heading-only fragment" \
    'printf "### Fixed\n\n" > "$repo/changelog.d/1-fixed.md"' "no release-note line"
refuse_case "symlinked fragment" \
    'echo secret > "$repo/../secret.txt"; ln -s "$repo/../secret.txt" "$repo/changelog.d/1-fixed.md"' "not regular files"
refuse_case "badly named fragment" \
    'echo "- **[Issue #1]** X." > "$repo/changelog.d/1-fix.md"' "not named <num>-<type>.md"
refuse_case "non-md fragment" \
    'echo "- **[Issue #1]** X." > "$repo/changelog.d/1-fixed.txt"' "not named <num>-<type>.md"
refuse_case "already-collated fragment (#403)" \
    'echo "- **[Issue #100]** An old fix." > "$repo/changelog.d/100-fixed.md"' "already collated"
refuse_case "missing [Unreleased] link" \
    'sed -i.bak "/^\[Unreleased\]: /d" "$repo/CHANGELOG.md"; rm -f "$repo/CHANGELOG.md.bak"; echo "- **[Issue #1]** X." > "$repo/changelog.d/1-fixed.md"' "refusing to guess the previous version"

refuse_case "heading-bearing fragment" \
    'printf "### Fixed\n\n- **[Issue #1]** X.\n" > "$repo/changelog.d/1-fixed.md"' "containing a Markdown heading"

# A heading-bearing fragment is refused by the collator called directly too,
# and CHANGELOG.md and the fragment are left untouched.
repo=$(new_repo)
printf '### Fixed\n\n- **[Issue #1]** X.\n' > "$repo/changelog.d/1-fixed.md"
before_cl=$(cat "$repo/CHANGELOG.md")
rc=0; out=$(cd "$repo" && bash build/collate-changelog.sh 2>&1) || rc=$?
assert_eq "collator direct: heading-bearing fails" "1" "$rc"
assert_contains "collator direct: names the heading file" "1-fixed.md" "$out"
assert_eq "collator direct: CHANGELOG.md untouched" "$before_cl" "$(cat "$repo/CHANGELOG.md")"
assert_eq "collator direct: fragment kept" "yes" "$([[ -f "$repo/changelog.d/1-fixed.md" ]] && echo yes || echo no)"

# A badly named fragment is also refused by the collator called directly.
repo=$(new_repo)
echo "- **[Issue #1]** X." > "$repo/changelog.d/notes.md"
rc=0; out=$(cd "$repo" && bash build/collate-changelog.sh 2>&1) || rc=$?
assert_eq "collator direct: badly named fails" "1" "$rc"
assert_contains "collator direct: names the file" "notes.md" "$out"

# --- 8b. --check-fragments (the PR-time gate) runs the same validation --------
# Each refusal the commit-back makes on fragment content is made by
# --check-fragments too, without touching CHANGELOG.md or the fragments.
check_case() {
    local label="$1" setup="$2" needle="$3" repo before_cl rc out
    repo=$(new_repo)
    eval "$setup"
    before_cl=$(cat "$repo/CHANGELOG.md")
    rc=0; out=$(cd "$repo" && bash build/commit-changelog-back.sh --check-fragments 2>&1) || rc=$?
    assert_eq "check $label: exit 1" "1" "$rc"
    assert_contains "check $label: message" "$needle" "$out"
    assert_eq "check $label: CHANGELOG.md untouched" "$before_cl" "$(cat "$repo/CHANGELOG.md")"
}
check_case "empty fragment" \
    ': > "$repo/changelog.d/1-fixed.md"' "no release-note line"
check_case "heading-only fragment" \
    'printf "### Fixed\n\n" > "$repo/changelog.d/1-fixed.md"' "no release-note line"
check_case "heading-bearing fragment" \
    'printf "### Fixed\n\n- **[Issue #1]** X.\n" > "$repo/changelog.d/1-fixed.md"' "containing a Markdown heading"
check_case "symlinked fragment" \
    'echo secret > "$repo/../secret.txt"; ln -s "$repo/../secret.txt" "$repo/changelog.d/1-fixed.md"' "not regular files"
check_case "badly named fragment" \
    'echo "- **[Issue #1]** X." > "$repo/changelog.d/1-fix.md"' "not named <num>-<type>.md"

repo=$(new_repo)
echo "- **[Issue #1]** X." > "$repo/changelog.d/1-fixed.md"
rc=0; out=$(cd "$repo" && bash build/commit-changelog-back.sh --check-fragments 2>&1) || rc=$?
assert_eq "check valid fragment: exit 0" "0" "$rc"
assert_eq "check valid fragment: fragment kept" "yes" "$([[ -f "$repo/changelog.d/1-fixed.md" ]] && echo yes || echo no)"

repo=$(new_repo)
rc=0; out=$(cd "$repo" && bash build/commit-changelog-back.sh --check-fragments 2>&1) || rc=$?
assert_eq "check no fragments: exit 0" "0" "$rc"

# The workflow gate calls exactly this mode.
assert_contains "check: Changelog Fragment Check runs --check-fragments" \
    "build/commit-changelog-back.sh --check-fragments" \
    "$(cat "$SCRIPT_DIR/../.github/workflows/changelog-fragment-check.yml")"

# --- 9. large valid fragment passes (SIGPIPE regression) ----------------------
repo=$(new_repo)
{
    echo "- **[Issue #500]** A large entry."
    i=0
    while [[ $i -lt 3000 ]]; do echo "  - caveat line $i with some padding text to make it long"; i=$((i + 1)); done
} > "$repo/changelog.d/500-changed.md"
git -C "$repo" add -A && git -C "$repo" commit -q -m "large fragment"
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "large fragment: exit 0" "0" "$RC"
assert_eq "large fragment: release=true" "release=true" "$RESULT"

# --- 10. rejected push fails hard, names the bypass ---------------------------
repo=$(new_repo)
cat > "$repo/../origin.git/hooks/pre-receive" <<'EOF'
#!/bin/sh
echo "protected branch" >&2
exit 1
EOF
chmod +x "$repo/../origin.git/hooks/pre-receive"
add_fragment "$repo" 406-changed.md "- **[Issue #406]** Collate fragments after merge."
run_commit_back "$repo" 0.3.7 --branch main
assert_eq "rejected push: exit 1" "1" "$RC"
assert_contains "rejected push: names the bypass" "bypass_pull_request_allowances" "$OUT"
assert_contains "rejected push: names the App" "cardtechie-ops-bot" "$OUT"
assert_contains "rejected push: not tagged" "NOT tagged or published" "$OUT"
assert_eq "rejected push: no release= line" "" "$RESULT"

# --- 11. --no-commit stages only ----------------------------------------------
repo=$(new_repo)
add_fragment "$repo" 406-changed.md "- **[Issue #406]** Collate fragments after merge."
before=$(commit_count "$repo")
run_commit_back "$repo" 0.3.7 --no-commit
assert_eq "--no-commit: exit 0" "0" "$RC"
assert_eq "--no-commit: no commit" "$before" "$(commit_count "$repo")"
assert_contains "--no-commit: CHANGELOG staged" "CHANGELOG.md" "$(git -C "$repo" diff --cached --name-only)"

# --- 12. invalid version -------------------------------------------------------
repo=$(new_repo)
run_commit_back "$repo" "1.2" --branch main
assert_eq "invalid version: exit 1" "1" "$RC"

# --- 13. --is-collation-commit ------------------------------------------------
check_guard() {
    local rc=0
    bash "$COMMIT_BACK_SH" --is-collation-commit "$1" "$2" || rc=$?
    printf '%s' "$rc"
}
assert_eq "guard: exact prefix + bot matches" "0" "$(check_guard "${SUBJECT_PREFIX}0.3.7" "$BOT_NAME")"
assert_eq "guard: human author rejected" "1" "$(check_guard "${SUBJECT_PREFIX}0.3.7" "Josh Harrison")"
assert_eq "guard: substring only rejected" "1" "$(check_guard "Revert \"${SUBJECT_PREFIX}0.3.7\"" "$BOT_NAME")"
assert_eq "guard: other bot subject rejected" "1" "$(check_guard "chore: bump deps" "$BOT_NAME")"

# --- summary -------------------------------------------------------------------
echo ""
echo "-----------------------------------------"
printf '%d passed, %d failed\n' "$PASS" "$FAIL"

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi
exit 0
