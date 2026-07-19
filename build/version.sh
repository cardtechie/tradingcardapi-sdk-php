#!/bin/bash

# PHP SDK Version Generation Script
# Adapted from Trading Card API build system for Composer/PHP usage

# Check for branch simulation flags
SIMULATE_BRANCH=""
if [[ "$1" == "--master" ]] || [[ "$1" == "--main" ]]; then
    SIMULATE_BRANCH="main"
elif [[ "$1" =~ ^--branch=(.+)$ ]]; then
    SIMULATE_BRANCH="${BASH_REMATCH[1]}"
fi

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "0.1.0-nogit"
    exit 0
fi

# Get current branch
branch=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")

# Override branch if simulation flag is set
if [[ -n "$SIMULATE_BRANCH" ]]; then
    branch="$SIMULATE_BRANCH"
fi

# Get the latest tag
latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "")

# If no tags exist, use development version
if [[ -z "$latest_tag" ]]; then
    commit_count=$(git rev-list --count HEAD 2>/dev/null || echo "1")
    echo "0.1.0-dev.${commit_count}"
    exit 0
fi

# Parse version from latest tag
if [[ "$latest_tag" =~ ^v?([0-9]+)\.([0-9]+)\.([0-9]+) ]]; then
    major="${BASH_REMATCH[1]}"
    minor="${BASH_REMATCH[2]}"
    patch="${BASH_REMATCH[3]}"
else
    echo "ERROR: Could not parse version from tag: $latest_tag" >&2
    echo "0.1.0-parse-error"
    exit 1
fi

# Count commits since latest tag
commits_since_tag=$(git rev-list --count "${latest_tag}..HEAD" 2>/dev/null || echo "0")

# Generate version based on branch
case "$branch" in
    main|master)
        # Main branch: derive the intended release version from CHANGELOG.md.
        # The patch-only increment used previously cannot tell a patch release
        # from a minor/major one, so a 0.2.0 release once emitted 0.1.19 (#186).
        # Read the newest versioned section from CHANGELOG.md; the ^## \[[0-9]
        # anchor skips the leading "## [Unreleased]" heading. Resolve the file
        # via the git top-level so the read works regardless of the current
        # working directory (every other step here uses cwd-agnostic git).
        repo_root=$(git rev-parse --show-toplevel 2>/dev/null || echo ".")
        changelog_version=$(grep -m1 '^## \[[0-9]' "$repo_root/CHANGELOG.md" 2>/dev/null | sed 's/## \[\([^]]*\)\].*/\1/')
        # Only trust the CHANGELOG version when it is strictly newer than the
        # latest tag. If it is equal to or behind the tag (fragments not yet
        # collated), using it would re-emit a released version or regress it and
        # cause a duplicate/invalid tag - fall back to a patch bump instead.
        changelog_is_newer=""
        if [[ "$changelog_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            newest=$(printf '%s\n%s\n' "$major.$minor.$patch" "$changelog_version" | sort -V | tail -n1)
            if [[ "$newest" == "$changelog_version" && "$changelog_version" != "$major.$minor.$patch" ]]; then
                changelog_is_newer="yes"
            fi
        fi
        if [[ "$commits_since_tag" -eq 0 ]]; then
            # Exact tag match - emit the tag itself
            version="$major.$minor.$patch"
        elif [[ -n "$changelog_is_newer" ]]; then
            # Commits after the tag (release being prepared) - use the version
            # documented in CHANGELOG.md so minor/major bumps tag correctly
            version="$changelog_version"
        else
            # Fallback: CHANGELOG missing/unparseable, or not ahead of the latest
            # tag - next patch version
            version="$major.$minor.$((patch + 1))"
        fi
        ;;
    develop)
        # Develop branch: beta version
        if [[ "$commits_since_tag" -eq 0 ]]; then
            # On exact tag - create next minor beta
            version="$major.$((minor + 1)).0.beta-1"
        else
            # Beta version with commit count
            version="$major.$((minor + 1)).0.beta-$commits_since_tag"
        fi
        ;;
    release/*)
        # Release branch: RC version
        # Extract semver from end of branch name (handles formats like "183-phpsdk-0.2.0" or "0.2.0")
        branch_suffix=${branch#release/}
        if [[ "$branch_suffix" =~ ([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
            branch_version="${BASH_REMATCH[1]}"
            # Find existing RC tags for this version
            rc_count=$(git tag -l "${branch_version}.rc-*" | wc -l | tr -d ' ')
            next_rc=$((rc_count + 1))
            version="${branch_version}.rc-${next_rc}"
        else
            version="$major.$((minor + 1)).0.rc-$commits_since_tag"
        fi
        ;;
    hotfix/*)
        # Hotfix branch: patch with hotfix suffix
        branch_name=${branch#hotfix/}
        clean_name=$(echo "$branch_name" | sed 's/[^a-zA-Z0-9]//g')
        version="$major.$minor.$((patch + 1))-hotfix.${clean_name}.${commits_since_tag}"
        ;;
    feature/*|bug/*|bugfix/*)
        # Feature branches: development version with alpha
        version="$major.$minor.$patch-alpha.${commits_since_tag}"
        ;;
    *)
        # Unknown branch type
        echo "WARNING: Unsupported branch type '$branch', using development version" >&2
        version="$major.$minor.$patch-dev.${commits_since_tag}"
        ;;
esac

# Output the version
echo "$version"

# Set environment variables if in CI/CD
if [[ -n "${CI}" ]] || [[ -n "${GITHUB_ACTIONS}" ]]; then
    {
        echo "PHP_SDK_VERSION=$version"
        echo "BUILD_DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        echo "BUILD_COMMIT=$(git rev-parse HEAD)"
    } >> "$GITHUB_ENV" 2>/dev/null || true
    
    # Note: We no longer update composer.json version to prevent Packagist publishing issues
    # Git tags are used for version management instead of hardcoded values
fi

exit 0