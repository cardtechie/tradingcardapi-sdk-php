#!/bin/bash

#
# Collate changelog.d/*.md fragments into the "## [Unreleased]" section of
# CHANGELOG.md, grouped under the matching "### <Type>" heading, then delete the
# consumed fragments. Invoked by build/commit-changelog-back.sh ahead of the
# [Unreleased] -> [VERSION] promotion, so the assembled entries become the new
# versioned section's content.
#
# Per-PR fragments live at changelog.d/<issue>-<type>.md where <type> is one of
# added | changed | deprecated | removed | fixed | security (canonical
# Keep-a-Changelog order; see VALID_TYPES below). Each fragment body
# is a single Keep-a-Changelog list line (a `- ...` bullet plus at most one
# indented caveat sub-bullet); the type comes from the filename, not the body.
#
# Idempotent: a clean no-op when changelog.d/ holds no fragments. Output
# respects MD024 { siblings_only: true } by merging into an existing same-type
# subsection rather than duplicating headings.
#
# Ported from cardtechie/cardtechie-site PR #505 (tradingcardapi-sdk-php #406).
# Invoked by build/commit-changelog-back.sh, which the Build and Release
# workflow runs after every merge to main. Two additions for this repo: every
# entry in changelog.d/ other than README.md, .gitkeep and .markdownlint.json
# must be named <num>-<type>.md, and a misnamed one fails collation instead of
# being skipped (a skipped fragment would never reach CHANGELOG.md).
#
# Avoids bash 4 associative arrays (macOS ships bash 3.2) — buckets are six
# plain variables keyed by the fixed type set.
#
# Usage: build/collate-changelog.sh [--changelog FILE] [--fragments-dir DIR]
#

set -euo pipefail

CHANGELOG_FILE="CHANGELOG.md"
FRAGMENTS_DIR="changelog.d"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --changelog)
            CHANGELOG_FILE="$2"
            shift 2
            ;;
        --fragments-dir)
            FRAGMENTS_DIR="$2"
            shift 2
            ;;
        --help|-h)
            echo "Usage: $0 [--changelog FILE] [--fragments-dir DIR]"
            exit 0
            ;;
        *)
            echo "❌ Error: Unknown argument '$1'" >&2
            exit 1
            ;;
    esac
done

# The fixed Keep-a-Changelog type set. Order here is the canonical subsection
# order used when creating a missing subsection.
VALID_TYPES="added changed deprecated removed fixed security"

# Per-type accumulators (avoid associative arrays for bash 3.2 portability).
BUCKET_ADDED=""
BUCKET_CHANGED=""
BUCKET_DEPRECATED=""
BUCKET_REMOVED=""
BUCKET_FIXED=""
BUCKET_SECURITY=""

# Append a body to the bucket variable for a given type.
append_bucket() {
    local type="$1" body="$2" sep=""
    case "$type" in
        added)      [[ -n "$BUCKET_ADDED" ]] && sep=$'\n';      BUCKET_ADDED="${BUCKET_ADDED}${sep}${body}" ;;
        changed)    [[ -n "$BUCKET_CHANGED" ]] && sep=$'\n';    BUCKET_CHANGED="${BUCKET_CHANGED}${sep}${body}" ;;
        deprecated) [[ -n "$BUCKET_DEPRECATED" ]] && sep=$'\n'; BUCKET_DEPRECATED="${BUCKET_DEPRECATED}${sep}${body}" ;;
        removed)    [[ -n "$BUCKET_REMOVED" ]] && sep=$'\n';    BUCKET_REMOVED="${BUCKET_REMOVED}${sep}${body}" ;;
        fixed)      [[ -n "$BUCKET_FIXED" ]] && sep=$'\n';      BUCKET_FIXED="${BUCKET_FIXED}${sep}${body}" ;;
        security)   [[ -n "$BUCKET_SECURITY" ]] && sep=$'\n';   BUCKET_SECURITY="${BUCKET_SECURITY}${sep}${body}" ;;
    esac
}

is_valid_type() {
    local t="$1" v
    for v in $VALID_TYPES; do
        [[ "$t" == "$v" ]] && return 0
    done
    return 1
}

# Bookkeeping files that live in changelog.d/ but are never fragments.
is_bookkeeping() {
    case "$1" in
        README.md|.gitkeep|.markdownlint.json) return 0 ;;
    esac
    return 1
}

# Every other entry must follow the <num>-<type>.md convention. A misnamed file
# (e.g. 412-fix.md, notes.md, 412-added.txt) would otherwise be silently left
# behind and its entry would never reach CHANGELOG.md. Refuse before touching
# anything.
if [[ -d "$FRAGMENTS_DIR" ]]; then
    shopt -s nullglob dotglob
    BAD_NAMES=()
    for entry in "$FRAGMENTS_DIR"/*; do
        name="$(basename "$entry")"
        is_bookkeeping "$name" && continue
        if [[ ! "$name" =~ ^[0-9]+-(added|changed|fixed|security|removed|deprecated)\.md$ ]]; then
            BAD_NAMES+=("$entry")
        fi
    done
    shopt -u nullglob dotglob
    if [[ ${#BAD_NAMES[@]} -gt 0 ]]; then
        echo "❌ Error: changelog.d/ entries not named <num>-<type>.md: ${BAD_NAMES[*]}" >&2
        echo "   Rename each to <issue>-<type>.md (type: ${VALID_TYPES}), or delete it." >&2
        exit 1
    fi
fi

# Nothing to do if the fragments directory is missing or holds no *.md files.
shopt -s nullglob
ALL_MD=("$FRAGMENTS_DIR"/*.md)
shopt -u nullglob

# README.md is documentation, not a fragment — never collate or delete it.
FRAGMENTS=()
for frag in ${ALL_MD[@]+"${ALL_MD[@]}"}; do
    [[ "$(basename "$frag")" == "README.md" ]] && continue
    FRAGMENTS+=("$frag")
done

if [[ ${#FRAGMENTS[@]} -eq 0 ]]; then
    echo "ℹ️  No changelog fragments in ${FRAGMENTS_DIR}/ — nothing to collate."
    exit 0
fi

# Only regular, checked-out files are fragments. A symlink named like a
# fragment would make the reads below follow it, publishing whatever it points
# at (a runner file, /proc/self/environ) as release notes. Refuse before
# touching anything. -L is tested first because -f follows symlinks.
for frag in "${FRAGMENTS[@]}"; do
    if [[ -L "$frag" || ! -f "$frag" ]]; then
        echo "❌ Error: changelog fragment '${frag}' is not a regular file (symlink or other) — refusing to collate." >&2
        exit 1
    fi
done

if [[ ! -f "$CHANGELOG_FILE" ]]; then
    echo "❌ Error: ${CHANGELOG_FILE} not found" >&2
    exit 1
fi

# Guard against silent data loss: the awk rewrite only emits bucketed fragment
# content inside the "## [Unreleased]" section. If that heading is absent the
# fragments would pass through unconsumed yet still be git rm'd at the end,
# destroying the entries. Fail fast before touching anything.
if ! grep -q '^## \[Unreleased\]' "$CHANGELOG_FILE"; then
    echo "❌ Error: ${CHANGELOG_FILE} has no '## [Unreleased]' section — refusing to collate." >&2
    echo "   Fragments would be deleted without landing in the changelog." >&2
    exit 1
fi

echo "📋 Collating ${#FRAGMENTS[@]} changelog fragment(s) into ${CHANGELOG_FILE}..."

CONSUMED=()
for frag in "${FRAGMENTS[@]}"; do
    base="$(basename "$frag" .md)"
    # Filename shape: <issue>-<type>. The type is the final dash-delimited token.
    type="$(printf '%s' "${base##*-}" | tr '[:upper:]' '[:lower:]')"

    if ! is_valid_type "$type"; then
        echo "❌ Error: fragment '${frag}' has unrecognized type '${type}'." >&2
        echo "   Expected <issue>-<type>.md with type in: ${VALID_TYPES}" >&2
        exit 1
    fi

    # Strip a single trailing blank line; keep interior structure (sub-bullets).
    body="$(sed -e '${/^$/d;}' "$frag")"
    if [[ -z "$(printf '%s' "$body" | tr -d '[:space:]')" ]]; then
        echo "⚠️  Fragment '${frag}' is empty — consuming it without adding content." >&2
    else
        append_bucket "$type" "$body"
    fi
    CONSUMED+=("$frag")
done

# Rebuild CHANGELOG.md: walk the Unreleased section, append bucketed lines into
# the matching existing ### subsection, and create any missing subsections at
# the end of the Unreleased block in canonical order.
TEMP_OUT="$(mktemp "${TMPDIR:-/tmp}/collate-changelog.XXXXXX")"
BUCKET_DIR="$(mktemp -d "${TMPDIR:-/tmp}/collate-changelog-buckets.XXXXXX")"
trap 'rm -f "$TEMP_OUT"; rm -rf "$BUCKET_DIR"' EXIT

# Hand the buckets to awk through files, one per type. Not -v (quoting hazards
# with multi-line bodies) and not the environment: Linux caps every single
# argv/envp string at 128 KiB (MAX_ARG_STRLEN), so a large bucket exported for
# ENVIRON[] makes execve fail with E2BIG and awk never starts (exit 126).
write_bucket() {
    [[ -n "$2" ]] && printf '%s\n' "$2" > "${BUCKET_DIR}/$1"
    return 0
}
write_bucket added      "$BUCKET_ADDED"
write_bucket changed    "$BUCKET_CHANGED"
write_bucket deprecated "$BUCKET_DEPRECATED"
write_bucket removed    "$BUCKET_REMOVED"
write_bucket fixed      "$BUCKET_FIXED"
write_bucket security   "$BUCKET_SECURITY"

awk -v bucket_dir="$BUCKET_DIR" '
function title_case(s) {
    return toupper(substr(s, 1, 1)) substr(s, 2)
}
# Emit the buffered lines of the current subsection (heading + existing body),
# then append the new bullets for this type, then a single trailing blank line. Trailing
# blank lines in the buffer are dropped so the appended bullets stay contiguous
# with the existing list (one list, not two), keeping the result MD032/MD012-clean.
function flush_subsection(   i, last) {
    if (!have_sub) return
    # Find the last non-blank buffered line.
    last = sub_n
    while (last > 0 && sub_buf[last] ~ /^[[:space:]]*$/) last--
    for (i = 1; i <= last; i++) print sub_buf[i]
    if (cur_type != "" && (cur_type in additions)) {
        printf "%s\n", additions[cur_type]
        delete additions[cur_type]
    }
    print ""   # exactly one blank line after the subsection
    have_sub = 0
    sub_n = 0
    cur_type = ""
}
function create_missing(   i, t) {
    # Emit any still-unconsumed subsections, in canonical order.
    for (i = 1; i <= 6; i++) {
        t = order[i]
        if (t in additions) {
            printf "### %s\n\n", title_case(t)
            printf "%s\n\n", additions[t]
            delete additions[t]
        }
    }
}
BEGIN {
    in_unreleased = 0
    have_sub = 0
    sub_n = 0
    cur_type = ""
    split("added changed deprecated removed fixed security", order, " ")
    # A missing file means an empty bucket. Lines are rejoined with "\n" and
    # no trailing newline, the exact string the bash bucket held.
    for (i = 1; i <= 6; i++) {
        f = bucket_dir "/" order[i]
        n = 0
        while ((getline line < f) > 0) {
            additions[order[i]] = (n++ ? additions[order[i]] "\n" : "") line
        }
        close(f)
    }
}
/^## \[Unreleased\]/ {
    print
    in_unreleased = 1
    next
}
in_unreleased == 1 && /^## \[/ {
    # Leaving Unreleased: flush the active subsection, then create missing ones.
    flush_subsection()
    create_missing()
    in_unreleased = 0
    print
    next
}
in_unreleased == 1 && /^### / {
    # New subsection heading: flush the prior subsection, then start buffering
    # this one (heading is buffered as line 1 of the new subsection).
    flush_subsection()
    have_sub = 1
    sub_n = 1
    sub_buf[1] = $0
    heading = $0
    sub(/^### /, "", heading)
    cur_type = tolower(heading)
    next
}
in_unreleased == 1 && have_sub == 1 {
    # Buffer body lines of the active subsection.
    sub_n++
    sub_buf[sub_n] = $0
    next
}
{
    print
}
END {
    if (in_unreleased == 1) {
        flush_subsection()
        create_missing()
    }
}
' "$CHANGELOG_FILE" > "$TEMP_OUT"

# Collapse any run of blank lines to a single blank line so the inserted content
# stays MD012-clean even when a subsection already ended with a blank line
# before our appended bullets.
awk '/^$/ { if (blank++ < 1) print; next } { blank = 0; print }' "$TEMP_OUT" > "$CHANGELOG_FILE"
rm -f "$TEMP_OUT"
rm -rf "$BUCKET_DIR"
trap - EXIT

# Remove the consumed fragments. Use git rm when the file is tracked so the
# deletion is staged for the release commit; fall back to plain rm otherwise.
for frag in "${CONSUMED[@]}"; do
    if git ls-files --error-unmatch "$frag" >/dev/null 2>&1; then
        # -f because the fragment's body has already been captured into the
        # CHANGELOG; we intentionally discard the file regardless of index state.
        git rm --quiet -f "$frag"
    else
        rm -f "$frag"
    fi
done

echo "✅ Collated ${#CONSUMED[@]} fragment(s) into ${CHANGELOG_FILE} and removed them from ${FRAGMENTS_DIR}/."
