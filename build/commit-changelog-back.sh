#!/bin/bash

#
# Trading Card API PHP SDK - Collate the changelog and commit it back (#406)
#
# Ported from cardtechie/cardtechie-site PR #505 (its issue #465).
#
# Runs in the Build and Release workflow on every push to `main`, AFTER the merge,
# so a PR never writes its own `## [X.Y.Z]` section. Collation is serialized by
# main's commit order (plus the workflow's concurrency group), which is what
# lets two open PRs never claim the same version.
#
# Steps:
#   1. build/collate-changelog.sh folds every changelog.d/ fragment into
#      `## [Unreleased]` and removes the consumed fragments.
#   2. `## [Unreleased]` is promoted to `## [VERSION] - <UTC date>` and a fresh,
#      empty `## [Unreleased]` is added above it. The compare links at the
#      bottom of CHANGELOG.md are updated: `[Unreleased]` now compares from
#      VERSION, and a `[VERSION]` link is added (prev...VERSION).
#   3. CHANGELOG.md plus the fragment deletions are committed as
#      cardtechie-ops-bot[bot] with the subject
#      `chore(release): collate CHANGELOG for VERSION`, and pushed. There is
#      deliberately no `[skip ci]`: the push must start the back-merge
#      workflow (back-merge-stable-to-develop.yml) so develop receives the
#      collated CHANGELOG. Build and Release skips itself through its
#      job-level guard instead.
#
# A clean no-op (exit 0, nothing committed, release=false) when changelog.d/
# holds no fragment, even if `## [Unreleased]` already has content. Refuses to run when CHANGELOG.md already has a `## [VERSION]`
# section, so a re-run can never write the same version twice — UNLESS that
# section was written by this script's own commit-back AND that commit is still
# HEAD, in which case a committing run RESUMES: it writes nothing and reports release=true, so a
# Build Release rerun can tag and publish a version whose commit-back already
# landed on main but whose later steps failed.
#
# Empty (whitespace-only) fragments are rejected before anything is touched.
# The collator would consume one while adding no content, and with nothing
# else pending that deletion would never be committed.
#
# A fragment whose first non-blank, non-heading line already appears verbatim in
# CHANGELOG.md is also rejected (issue #403): it was collated once already and
# collating it again would print the entry twice. The error names the file;
# delete it once you have confirmed the entry is in CHANGELOG.md.
#
# `--output FILE` appends `release=true|false` to FILE (Build and Release passes
# $GITHUB_OUTPUT). release=true means CHANGELOG.md at HEAD carries a
# `## [VERSION]` section to tag and publish.
#
# `make changelog-collate VERSION=X.Y.Z` is the manual / recovery path and
# calls this script with --no-commit, so the promotion logic lives in one place.
#
# `--is-collation-commit SUBJECT AUTHOR` exits 0 when the pair names this
# script's own commit-back. It is the canonical form of the Build and Release
# self-trigger guard and is what build/collate-changelog.test.sh pins.
#
# `--check-fragments` runs only the fragment content validation (empty,
# heading-only, headings, symlinks, names) and exits 0 when every fragment
# would be accepted. The PR-time Changelog Fragment Check runs it.
#
# Bash 3.2-safe (macOS), matching build/collate-changelog.sh.
#
# Usage:
#   build/commit-changelog-back.sh VERSION [--branch BRANCH] [--no-commit] [--no-push] [--output FILE]
#   build/commit-changelog-back.sh --is-collation-commit SUBJECT AUTHOR
#   build/commit-changelog-back.sh --check-fragments
#

set -euo pipefail

# The anchored subject prefix and author of the commit-back. The Build Release
# self-trigger guard matches BOTH halves; a substring match on the phrase alone
# would skip collation for any human commit that merely mentions it.
COMMIT_SUBJECT_PREFIX="chore(release): collate CHANGELOG for "
# The cardtechie-ops-bot GitHub App's bot user (id from
# `gh api "/users/cardtechie-ops-bot[bot]" --jq .id`). Build Release pushes with
# that App's installation token, so the commit is attributed to the same actor.
BOT_NAME="cardtechie-ops-bot[bot]"
BOT_EMAIL="229868027+cardtechie-ops-bot[bot]@users.noreply.github.com"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

show_usage() {
    echo "Usage: $0 VERSION [--branch BRANCH] [--no-commit] [--no-push] [--output FILE]"
    echo "       $0 --is-collation-commit SUBJECT AUTHOR"
    echo "       $0 --check-fragments"
    echo ""
    echo "Collate changelog.d/ fragments, promote [Unreleased] to [VERSION] - DATE,"
    echo "and commit the result back to BRANCH (default: main)."
    echo ""
    echo "  --branch BRANCH   Branch to push the commit-back to (default: main)"
    echo "  --no-commit       Collate, promote, and stage only; do not commit"
    echo "  --no-push         Commit but do not push"
    echo "  --output FILE     Append release=true|false to FILE (e.g. \$GITHUB_OUTPUT)"
}

# Validate every changelog.d/ fragment's content without touching anything.
# Shared by the commit-back below and by the PR-time Changelog Fragment Check
# (--check-fragments), so a bad fragment fails its PR instead of the release.
# The name, symlink and heading rules come from the collator's own --check
# mode rather than being restated here.
validate_fragments() {
    # Reject fragments with no release-note content BEFORE the collator touches
    # anything: an empty file, or one holding only blank lines and headings (e.g. a
    # bare `### Added`). The collator would consume it while adding no content;
    # with nothing else pending, [Unreleased] stays empty (the count below ignores
    # headings), the no-op path returns without committing, and the deletion never
    # reaches main, so every later push repeats the same no-op.
    shopt -s nullglob
    EMPTY_FRAGMENTS=()
    NON_REGULAR=()
    for frag in changelog.d/*.md; do
        [[ "$(basename "$frag")" == "README.md" ]] && continue
        # A symlink (or any non-regular path) named like a fragment would be
        # followed by the reads below and by the collator, committing whatever it
        # points at into CHANGELOG.md and the release notes. -L first: -f follows
        # symlinks. collate-changelog.sh carries the same guard for direct callers.
        if [[ -L "$frag" || ! -f "$frag" ]]; then
            NON_REGULAR+=("$frag")
            continue
        fi
        # Single-stream check: a `grep | grep -q` pipeline under pipefail can
        # SIGPIPE the first grep when grep -q exits early, misreading a valid
        # fragment as empty. awk reads the whole file before deciding.
        if ! awk '!/^[[:space:]]*#/ && /[^[:space:]]/ { found = 1 } END { exit !found }' "$frag"; then
            EMPTY_FRAGMENTS+=("$frag")
        fi
    done
    shopt -u nullglob
    if [[ ${#NON_REGULAR[@]} -gt 0 ]]; then
        echo "❌ Error: changelog fragment(s) that are not regular files (symlink or other): ${NON_REGULAR[*]}" >&2
        echo "   Replace each with a plain file holding its changelog line, then re-run." >&2
        return 1
    fi
    if [[ ${#EMPTY_FRAGMENTS[@]} -gt 0 ]]; then
        echo "❌ Error: changelog fragment(s) with no release-note line: ${EMPTY_FRAGMENTS[*]}" >&2
        echo "   Give each one a changelog line (e.g. '- **[Issue #N]** ...'), or delete it, then re-run." >&2
        return 1
    fi

    # Name, symlink and heading rules: the collator's own validator.
    bash "${PROJECT_ROOT}/build/collate-changelog.sh" --check
}

if [[ "${1:-}" == "--check-fragments" ]]; then
    if [[ $# -ne 1 ]]; then
        echo "❌ Error: --check-fragments takes no arguments" >&2
        exit 2
    fi
    cd "$PROJECT_ROOT"
    validate_fragments || exit 1
    echo "✅ changelog.d/ fragments are valid."
    exit 0
fi

if [[ "${1:-}" == "--is-collation-commit" ]]; then
    if [[ $# -ne 3 ]]; then
        echo "❌ Error: --is-collation-commit takes SUBJECT and AUTHOR" >&2
        exit 2
    fi
    if [[ "$2" == "${COMMIT_SUBJECT_PREFIX}"* && "$3" == "$BOT_NAME" ]]; then
        exit 0
    fi
    exit 1
fi

VERSION=""
BRANCH="main"
DO_COMMIT=true
DO_PUSH=true
OUTPUT_FILE=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch)
            if [[ $# -lt 2 || "$2" == -* ]]; then
                echo "❌ Error: --branch requires a value" >&2
                exit 1
            fi
            BRANCH="$2"
            shift 2
            ;;
        --no-commit)
            DO_COMMIT=false
            shift
            ;;
        --no-push)
            DO_PUSH=false
            shift
            ;;
        --output)
            if [[ $# -lt 2 || "$2" == -* ]]; then
                echo "❌ Error: --output requires a value" >&2
                exit 1
            fi
            OUTPUT_FILE="$2"
            shift 2
            ;;
        --help|-h)
            show_usage
            exit 0
            ;;
        -*)
            echo "❌ Error: Unknown argument '$1'" >&2
            show_usage >&2
            exit 1
            ;;
        *)
            if [[ -n "$VERSION" ]]; then
                echo "❌ Error: unexpected extra argument '$1'" >&2
                exit 1
            fi
            VERSION="$1"
            shift
            ;;
    esac
done

if [[ -z "$VERSION" ]]; then
    echo "❌ Error: VERSION is required" >&2
    show_usage >&2
    exit 1
fi

# Validate the version. This also keeps it safe to interpolate into the sed
# expressions below.
if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9-]+(\.[0-9]+)?)?$ ]]; then
    echo "❌ Error: Invalid version format: $VERSION" >&2
    exit 1
fi

# Resolve a relative --output against the caller's directory before the cd.
if [[ -n "$OUTPUT_FILE" && "$OUTPUT_FILE" != /* ]]; then
    OUTPUT_FILE="${PWD}/${OUTPUT_FILE}"
fi

# Record whether HEAD carries a `## [VERSION]` section to tag and publish.
write_result() {
    if [[ -n "$OUTPUT_FILE" ]]; then
        echo "release=$1" >> "$OUTPUT_FILE"
    fi
}

cd "$PROJECT_ROOT"

if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Error: Not in a git repository" >&2
    exit 1
fi

CHANGELOG_FILE="CHANGELOG.md"
if [[ ! -f "$CHANGELOG_FILE" ]]; then
    echo "❌ Error: ${CHANGELOG_FILE} not found" >&2
    exit 1
fi

# True when HEAD itself is this script's own commit-back for VERSION. Only the
# branch tip counts: if anything landed after the commit-back, HEAD carries
# later code and fragments that VERSION's notes do not describe, so tagging it
# as VERSION would mislabel the release.
#
# Author name and subject are free text anyone can put on a commit, so they
# are a pre-filter only. What makes a resume safe is the commit's CONTENT:
#   - HEAD has exactly one parent and that parent has no `## [VERSION]`
#     section, i.e. HEAD is the commit that introduced it (a PR cannot: the
#     Changelog Fragment gate rejects any PR that adds a version heading); and
#   - HEAD touches nothing outside CHANGELOG.md and changelog.d/, so tagging
#     it releases exactly its parent's code plus the notes, which is what the
#     failed run would have released.
head_is_own_collation_commit() {
    local author subject parents path
    IFS=$'\t' read -r author subject < <(git log -1 --format='%an%x09%s' HEAD 2>/dev/null) || return 1
    [[ "$author" == "$BOT_NAME" && "$subject" == "${COMMIT_SUBJECT_PREFIX}${VERSION}" ]] || return 1

    parents=$(git rev-list --parents -n 1 HEAD 2>/dev/null | wc -w | tr -d ' ')
    [[ "$parents" == "2" ]] || return 1
    # Read the parent's changelog into a variable first rather than piping
    # `git show` into `grep -q`: under pipefail, grep's early exit can SIGPIPE
    # git show, the pipeline returns 141, and the check would read as "parent
    # lacks the section" exactly when it has it.
    local parent_changelog
    parent_changelog=$(git show "HEAD^:${CHANGELOG_FILE}" 2>/dev/null) || return 1
    if grep -q "^## \[${VERSION_RE}\]" <<< "$parent_changelog"; then
        return 1
    fi

    while IFS= read -r path; do
        [[ -z "$path" ]] && continue
        case "$path" in
            "$CHANGELOG_FILE"|changelog.d/*) ;;
            *) return 1 ;;
        esac
    done < <(git diff-tree --no-commit-id --name-only -r HEAD^ HEAD)
    return 0
}

# Escape the dots so `0.2.1` cannot match `0x2y1`.
VERSION_RE="${VERSION//./\\.}"
if grep -q "^## \[${VERSION_RE}\]" "$CHANGELOG_FILE"; then
    # Resume: an earlier run committed (and pushed) this section, then failed
    # before tagging. Re-collating is refused, but the section is ours, so the
    # release can go ahead from HEAD. Fragments merged since stay pending for
    # the next version. The manual --no-commit path keeps the hard refusal.
    if [[ "$DO_COMMIT" == "true" ]] && head_is_own_collation_commit; then
        echo "♻️  ${CHANGELOG_FILE} already has the '## [${VERSION}]' section from an earlier commit-back at HEAD — resuming the release without re-collating."
        write_result true
        exit 0
    fi
    echo "❌ Error: ${CHANGELOG_FILE} already has a '## [${VERSION}]' section — refusing to write it twice." >&2
    if [[ "$DO_COMMIT" == "true" ]]; then
        echo "   A resume only runs when HEAD is that version's own commit-back. If ${BRANCH}" >&2
        echo "   moved past it, tag and release that commit-back by hand instead of re-running." >&2
    fi
    exit 1
fi

# The compare links at the bottom of CHANGELOG.md are rewritten after the
# promotion, and the previous version comes from the existing
# `[Unreleased]: <base>/compare/<prev>...HEAD` link. If that link is missing,
# fail now rather than guess the previous version later.
UNRELEASED_LINK=$(grep -m1 -E '^\[Unreleased\]: .+/compare/[^/]+\.\.\.HEAD$' "$CHANGELOG_FILE" || true)
if [[ -z "$UNRELEASED_LINK" ]]; then
    echo "❌ Error: ${CHANGELOG_FILE} has no '[Unreleased]: <repo>/compare/<prev>...HEAD' link — refusing to guess the previous version." >&2
    echo "   Add the link at the bottom of ${CHANGELOG_FILE}, then re-run." >&2
    exit 1
fi
LINK_BASE="${UNRELEASED_LINK#\[Unreleased\]: }"
LINK_BASE="${LINK_BASE%/compare/*}"
PREV_VERSION="${UNRELEASED_LINK##*/compare/}"
PREV_VERSION="${PREV_VERSION%...HEAD}"
if [[ "$PREV_VERSION" == "$VERSION" ]]; then
    echo "❌ Error: the [Unreleased] link already compares from ${VERSION} — refusing to link ${VERSION} to itself." >&2
    exit 1
fi

validate_fragments || exit 1

# Nothing pending: a release is driven by fragments, never by whatever already
# sits under `## [Unreleased]`. Without this gate, a line left there by hand
# would be promoted and released on a push that added no fragment.
# Any entry other than the directory's bookkeeping files counts as pending, so
# a misnamed one still reaches the collator, which refuses it.
PENDING=0
shopt -s nullglob dotglob
for entry in changelog.d/*; do
    case "$(basename "$entry")" in
        README.md|.gitkeep|.markdownlint.json) ;;
        *) PENDING=$((PENDING + 1)) ;;
    esac
done
shopt -u nullglob dotglob
if [[ "$PENDING" -eq 0 ]]; then
    echo "ℹ️  No changelog.d/ fragments pending — nothing to release (clean no-op)."
    write_result false
    exit 0
fi

# Refuse fragments that were already collated (issue #403). The first
# non-blank, non-heading line is the entry itself (a heading such as
# `### Fixed` is skipped, as the empty-fragment check above skips it); if
# CHANGELOG.md already holds that exact line, collating it again would print
# the entry a second time. Read the line with awk (one stream, no SIGPIPE) and
# compare with a fixed-string, whole-line grep so regex metacharacters in the
# entry cannot widen the match.
shopt -s nullglob
DUPLICATE_FRAGMENTS=()
for frag in changelog.d/*.md; do
    [[ "$(basename "$frag")" == "README.md" ]] && continue
    first_line=$(awk '!/^[[:space:]]*#/ && /[^[:space:]]/ { print; exit }' "$frag")
    [[ -z "$first_line" ]] && continue
    if grep -Fxq -- "$first_line" "$CHANGELOG_FILE"; then
        DUPLICATE_FRAGMENTS+=("$frag")
    fi
done
shopt -u nullglob
if [[ ${#DUPLICATE_FRAGMENTS[@]} -gt 0 ]]; then
    echo "❌ Error: changelog fragment(s) already collated into ${CHANGELOG_FILE}: ${DUPLICATE_FRAGMENTS[*]}" >&2
    echo "   Their entries are already in ${CHANGELOG_FILE}; collating them again would duplicate them." >&2
    echo "   Delete each one (after checking its line is in ${CHANGELOG_FILE}), then re-run." >&2
    exit 1
fi

echo "🔁 Collating the changelog for ${VERSION}"

# Invoke via `bash` so a lost exec bit cannot silently skip collation.
bash "${PROJECT_ROOT}/build/collate-changelog.sh"

# Do not trust the collator's exit status alone: bash 3.2 exits 0 on a syntax
# error partway through a script, which is exactly how a broken collator once
# reported success while consuming nothing. A fragment that survives
# collation is a failure, never a no-op.
shopt -s nullglob
LEFTOVER=()
for frag in changelog.d/*.md; do
    [[ "$(basename "$frag")" == "README.md" ]] && continue
    LEFTOVER+=("$frag")
done
shopt -u nullglob
if [[ ${#LEFTOVER[@]} -gt 0 ]]; then
    echo "❌ Error: ${#LEFTOVER[@]} fragment(s) survived collation: ${LEFTOVER[*]}" >&2
    exit 1
fi

# Count content lines only: blank lines and headings (e.g. a `### Added` left
# with no bullets under it) are not release notes, so a heading-only section
# is still a clean no-op rather than an empty release.
UNRELEASED_LINES=$(sed -n '/^## \[Unreleased\]/,/^## \[/p' "$CHANGELOG_FILE" | sed '1d' | grep -v '^#' | grep -cv '^[[:space:]]*$' || true)
if [[ "$UNRELEASED_LINES" -eq 0 ]]; then
    echo "ℹ️  [Unreleased] is empty after collation — nothing to release-note (clean no-op)."
    write_result false
    exit 0
fi

RELEASE_DATE=$(date -u +%Y-%m-%d)
echo "📝 Promoting [Unreleased] → [${VERSION}] - ${RELEASE_DATE}"

# Promote only the FIRST [Unreleased] heading, then put a fresh empty one above
# it. sed -i.tmp is the portable in-place form for both GNU and BSD sed.
sed -i.tmp "1,/^## \[Unreleased\]/s/^## \[Unreleased\]/## [${VERSION}] - ${RELEASE_DATE}/" "$CHANGELOG_FILE"
sed -i.tmp "/^## \[${VERSION_RE}\] - ${RELEASE_DATE}/i\\
## [Unreleased]\\
" "$CHANGELOG_FILE"
rm -f "${CHANGELOG_FILE}.tmp"

# Update the compare links: [Unreleased] now starts from VERSION, and a new
# [VERSION] link (PREV...VERSION) goes directly below it, i.e. above the
# previous version's link.
LINKS_TMP="$(mktemp "${TMPDIR:-/tmp}/commit-changelog-back.XXXXXX")"
awk -v base="$LINK_BASE" -v ver="$VERSION" -v prev="$PREV_VERSION" '
    !done && /^\[Unreleased\]: / {
        print "[Unreleased]: " base "/compare/" ver "...HEAD"
        print "[" ver "]: " base "/compare/" prev "..." ver
        done = 1
        next
    }
    { print }
' "$CHANGELOG_FILE" > "$LINKS_TMP"
mv "$LINKS_TMP" "$CHANGELOG_FILE"

# When committing, clear the index first so nothing a caller staged earlier
# rides along in the commit-back. Under --no-commit (the manual / recovery
# path) the caller's index is left alone: the changelog and fragment deletions
# are staged on top of whatever they already staged. `git add -A` on the
# directory captures the deletions however the collator removed the files.
if [[ "$DO_COMMIT" == "true" ]] && git rev-parse --verify --quiet HEAD > /dev/null; then
    git reset -q
fi
git add -A -- "$CHANGELOG_FILE"
if [[ -e changelog.d ]] || git ls-files --error-unmatch changelog.d > /dev/null 2>&1; then
    git add -A -- changelog.d
fi

if [[ "$DO_COMMIT" != "true" ]]; then
    echo "⏭️  --no-commit: collated, promoted, and staged only."
    exit 0
fi

git -c user.name="$BOT_NAME" -c user.email="$BOT_EMAIL" \
    commit --quiet -m "${COMMIT_SUBJECT_PREFIX}${VERSION}"
echo "✅ Committed the collated CHANGELOG for ${VERSION}."

if [[ "$DO_PUSH" == "true" ]]; then
    # No `|| true`: a refused push (e.g. branch protection on main) must fail
    # the release loudly rather than ship a release with no notes.
    if ! git push origin "HEAD:${BRANCH}"; then
        echo "❌ Error: the changelog commit-back push to ${BRANCH} was rejected." >&2
        echo "   The release is NOT tagged or published, and Packagist is not updated," >&2
        echo "   so it never ships without notes. Most likely causes:" >&2
        echo "   - ${BRANCH}'s classic branch protection no longer lists cardtechie-ops-bot" >&2
        echo "     (GitHub App, Integration 1882441) in bypass_pull_request_allowances;" >&2
        echo "   - a repository or org ruleset on ${BRANCH} was added without the App as a bypass actor;" >&2
        echo "   - ${BRANCH} moved after this run checked it out (non-fast-forward)." >&2
        echo "   Fix that and re-run the Build and Release workflow." >&2
        exit 1
    fi
    echo "✅ Pushed the commit-back to ${BRANCH}."
else
    echo "⏭️  --no-push: committed only, not pushing."
fi
write_result true
