#!/bin/bash

#
# Generates release notes for PHP SDK by analyzing git commits and changelog
# Usage: generate-release-notes.sh <version> [<previous-version>]
# Adapted from Trading Card API build system for PHP SDK usage
#

set -e

# Debug mode for GitHub Actions
if [[ "${GITHUB_ACTIONS}" == "true" ]]; then
    set -x
fi

# Check if required tools are available
if ! command -v jq >/dev/null 2>&1; then
    echo "WARNING: jq not found. Enhanced issue details will not be available." >&2
fi

if ! command -v gh >/dev/null 2>&1; then
    echo "WARNING: GitHub CLI not found. Enhanced issue details will not be available." >&2
fi

VERSION="$1"
PREVIOUS_VERSION="$2"
CHANGELOG_FILE="CHANGELOG.md"

if [[ -z "${VERSION}" ]]; then
    echo "ERROR: Version is required"
    echo "Usage: generate-release-notes.sh <version> [<previous-version>]"
    exit 1
fi

# If no previous version specified, try to find the appropriate comparison point
if [[ -z "${PREVIOUS_VERSION}" ]]; then
    # For beta releases, try to find the previous beta or release
    if [[ "$VERSION" =~ \.beta- ]]; then
        # First try to find the previous beta tag
        PREVIOUS_VERSION=$(git tag --sort=-version:refname | grep -E '\.(beta|rc)-' | head -1 2>/dev/null || echo "")
        # If no beta found, fall back to latest release tag
        if [[ -z "$PREVIOUS_VERSION" ]]; then
            PREVIOUS_VERSION=$(git tag --sort=-version:refname | grep -v -E '\.(beta|rc)-' | head -1 2>/dev/null || echo "")
        fi
    else
        # For regular releases, get the latest tag
        PREVIOUS_VERSION=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
    fi
fi

# Get commit range
if [[ -n "${PREVIOUS_VERSION}" ]]; then
    # Check if VERSION is an existing tag
    if git tag --list | grep -q "^${VERSION}$"; then
        # VERSION is an existing tag, use exact range between tags
        COMMIT_RANGE="${PREVIOUS_VERSION}..${VERSION}"
    else
        # VERSION is not a tag yet, compare against HEAD
        COMMIT_RANGE="${PREVIOUS_VERSION}..HEAD"
    fi
else
    COMMIT_RANGE="HEAD"
fi

# Start release notes (note: GitHub will add the release title)
echo ""

# Function to filter out internal/housekeeping commits
filter_internal_commits() {
    local commits="$1"
    
    # Filter out commits that should not appear in user-facing release notes
    echo "$commits" | grep -v -i \
        -e "version bump" \
        -e "update changelog" \
        -e "changelog" \
        -e "CHANGELOG\.md" \
        -e "bump to version" \
        -e "bump version" \
        -e "update version" \
        -e "version update" \
        -e "automated version" \
        -e "auto-generated" \
        -e "generate-release-notes" \
        -e "build system" \
        -e "ci:" \
        -e "build:" \
        -e "docs(readme)" \
        -e "update readme" \
        -e "regenerate docs" \
        -e "update documentation generation" \
        -e "merge branch" \
        -e "Merge pull request.*from.*version-bump" \
        -e "Merge pull request.*from.*changelog" \
        -e "prepare release" \
        || echo ""  # Return empty if no matches remain
}

# Function to generate Claude-powered summary
generate_claude_summary() {
    local commit_data="$1"
    local file_changes="$2"
    local changelog_content="$3"
    local commit_count="$4"
    local previous_version="$5"
    
    # Check if Claude API key is available
    if [[ -z "${CLAUDE_API_KEY}" ]]; then
        echo "This release includes various improvements and updates to the Trading Card API PHP SDK with **${commit_count} commits** since [${previous_version}](https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/${previous_version})."
        return
    fi
    
    # Prepare the prompt for Claude
    local prompt="You are helping generate a GitHub release summary for a PHP SDK project that provides a Laravel package for the Trading Card API. Analyze the commits and changes to create a specific, informative summary.

**Commit Messages:**
${commit_data}

**Files Changed:**
${file_changes}

**Changelog Content:**
${changelog_content}

**Context:** This release has ${commit_count} commits since ${previous_version}.

Please create a 2-3 sentence summary that:
- Identifies specific features, fixes, or improvements (not generic phrases)
- Mentions concrete changes like 'adds X functionality', 'fixes Y issue', 'improves Z performance'
- Highlights the most significant user-facing or developer-facing changes
- Uses precise technical language while remaining accessible
- Avoids generic phrases like 'various improvements' or 'updates and enhancements'
- Focuses on PHP/Laravel/Composer specific improvements if relevant

Focus on what actually changed in this specific release that would matter to PHP developers using this SDK.

Return only the summary text, no additional formatting or explanations."

    # Call Claude API with retry logic and exponential backoff
    local claude_response=""
    local timeout_seconds=10
    local max_retries=3
    local retry_count=0
    
    # Debug logging in GitHub Actions
    if [[ "${GITHUB_ACTIONS}" == "true" ]]; then
        echo "🤖 Attempting Claude API call for PHP SDK release summary generation..." >&2
        timeout_seconds=15  # Longer timeout in CI
    fi
    
    while [[ $retry_count -lt $max_retries ]]; do
        if [[ "${GITHUB_ACTIONS}" == "true" ]]; then
            echo "  Attempt $((retry_count + 1))/$max_retries (timeout: ${timeout_seconds}s)" >&2
        fi
        
        claude_response=$(curl -s --max-time $timeout_seconds \
            --connect-timeout 5 \
            --retry 2 \
            --retry-delay 1 \
            -X POST "https://api.anthropic.com/v1/messages" \
            -H "Content-Type: application/json" \
            -H "x-api-key: ${CLAUDE_API_KEY}" \
            -H "anthropic-version: 2023-06-01" \
            -d "{
                \"model\": \"claude-3-haiku-20240307\",
                \"max_tokens\": 200,
                \"messages\": [{
                    \"role\": \"user\",
                    \"content\": $(printf '%s' "$prompt" | jq -R -s .)
                }]
            }" 2>/dev/null || echo "")
        
        # Check if we got a valid response
        if [[ -n "$claude_response" ]] && echo "$claude_response" | jq -e '.content[0].text' >/dev/null 2>&1; then
            if [[ "${GITHUB_ACTIONS}" == "true" ]]; then
                echo "  ✅ Claude API call successful" >&2
            fi
            break
        fi
        
        # Exponential backoff: wait 2^retry_count seconds
        retry_count=$((retry_count + 1))
        if [[ $retry_count -lt $max_retries ]]; then
            local wait_time=$((2 ** retry_count))
            if [[ "${GITHUB_ACTIONS}" == "true" ]]; then
                echo "  ❌ Attempt failed, waiting ${wait_time}s before retry..." >&2
            fi
            sleep $wait_time
        fi
    done
    
    if [[ $retry_count -eq $max_retries ]] && [[ "${GITHUB_ACTIONS}" == "true" ]]; then
        echo "  ⚠️ All Claude API attempts failed, using fallback summary" >&2
    fi
    
    # Extract the summary from Claude's response
    if [[ -n "$claude_response" ]] && echo "$claude_response" | jq -e '.content[0].text' >/dev/null 2>&1; then
        local summary=$(echo "$claude_response" | jq -r '.content[0].text' | tr -d '\n' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
        if [[ -n "$summary" && "$summary" != "null" ]]; then
            echo "$summary"
            return
        fi
    fi
    
    # Fallback if Claude API fails
    echo "This release includes various improvements and updates to the Trading Card API PHP SDK with **${commit_count} commits** since [${previous_version}](https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/${previous_version})."
}

# Function to categorize commits and generate "What's New" section
generate_whats_new_section() {
    local commit_range="$1"
    local version="$2"
    
    if [[ -z "$commit_range" ]]; then
        return
    fi
    
    # Get detailed commit information and filter out internal commits
    local commits=$(git log --pretty=format:"%h|%s|%b" "$commit_range" 2>/dev/null | head -20)
    local filtered_commits=$(filter_internal_commits "$commits")
    local file_changes=$(git diff --name-only "$commit_range" 2>/dev/null)
    
    if [[ -z "$filtered_commits" ]]; then
        return
    fi
    
    # Initialize category arrays
    declare -a features=()
    declare -a bugfixes=()
    declare -a infrastructure=()
    declare -a documentation=()
    declare -a refactoring=()
    
    # Parse and categorize commits (use filtered commits)
    while IFS='|' read -r hash subject body; do
        [[ -z "$subject" ]] && continue
        
        # Convert to lowercase for matching
        local subject_lower=$(echo "$subject" | tr '[:upper:]' '[:lower:]')
        local body_lower=$(echo "$body" | tr '[:upper:]' '[:lower:]')
        local combined="$subject_lower $body_lower"
        
        # Categorize based on commit message patterns
        if [[ "$combined" =~ (add|implement|introduce|create|new|feature) ]] && [[ ! "$combined" =~ (test|doc|readme) ]]; then
            features+=("$subject")
        elif [[ "$combined" =~ (fix|resolve|correct|patch|bug|issue|error) ]]; then
            bugfixes+=("$subject")
        elif [[ "$combined" =~ (docker|ci|cd|workflow|build|deploy|github|action|pipeline|composer|packagist) ]]; then
            infrastructure+=("$subject")
        elif [[ "$combined" =~ (doc|readme|comment|documentation|guide) ]]; then
            documentation+=("$subject")
        elif [[ "$combined" =~ (refactor|cleanup|reorganize|restructure|optimize) ]]; then
            refactoring+=("$subject")
        else
            # Default categorization based on file changes
            if echo "$file_changes" | grep -q -E '\.(md|txt|rst)$'; then
                documentation+=("$subject")
            elif echo "$file_changes" | grep -q -E '(Dockerfile|docker-compose|\.github|\.ci|composer\.json|\.yml|\.yaml)'; then
                infrastructure+=("$subject")
            elif echo "$file_changes" | grep -q -E '(test|spec)'; then
                infrastructure+=("$subject")
            else
                features+=("$subject")
            fi
        fi
    done <<< "$filtered_commits"
    
    # Generate the "What's New" section
    echo "## 🆕 What's New in This Release"
    echo ""
    
    local has_content=false
    
    if [[ ${#features[@]} -gt 0 ]]; then
        echo "### ✨ Features Added"
        for feature in "${features[@]}"; do
            echo "- $feature"
        done
        echo ""
        has_content=true
    fi
    
    if [[ ${#bugfixes[@]} -gt 0 ]]; then
        echo "### 🐛 Bugs Fixed"
        for bugfix in "${bugfixes[@]}"; do
            echo "- $bugfix"
        done
        echo ""
        has_content=true
    fi
    
    if [[ ${#infrastructure[@]} -gt 0 ]]; then
        echo "### 🔧 Infrastructure & Development"
        for infra in "${infrastructure[@]}"; do
            echo "- $infra"
        done
        echo ""
        has_content=true
    fi
    
    if [[ ${#refactoring[@]} -gt 0 ]]; then
        echo "### ♻️ Code Quality & Refactoring"
        for refactor in "${refactoring[@]}"; do
            echo "- $refactor"
        done
        echo ""
        has_content=true
    fi
    
    if [[ ${#documentation[@]} -gt 0 ]]; then
        echo "### 📚 Documentation"
        for doc in "${documentation[@]}"; do
            echo "- $doc"
        done
        echo ""
        has_content=true
    fi
    
    if [[ "$has_content" == "false" ]]; then
        echo "*No categorized changes found for this release.*"
        echo ""
    fi
}

# Summary section
echo "## 📊 Summary"
echo ""

if [[ -n "${PREVIOUS_VERSION}" ]]; then
    # Count commits
    COMMIT_COUNT=$(git rev-list --count ${COMMIT_RANGE} 2>/dev/null || echo "0")
    
    # Gather data for Claude (filter out internal commits)
    COMMIT_DATA=$(git log --pretty=format:"%s" ${COMMIT_RANGE} 2>/dev/null | head -10 | grep -v "^$" || echo "")
    FILTERED_COMMIT_DATA=$(filter_internal_commits "$COMMIT_DATA")
    FILE_CHANGES=$(git diff --name-only ${COMMIT_RANGE} 2>/dev/null | head -10 || echo "")
    
    # Get changelog content
    CHANGELOG_CONTENT=""
    if [[ -f "$CHANGELOG_FILE" ]]; then
        CHANGELOG_CONTENT=$(sed -n "/^## \[${VERSION}\]/,/^## \[/p" "$CHANGELOG_FILE" | sed '$d' | tail -n +3)
        if [[ -z "$(echo "$CHANGELOG_CONTENT" | grep -v '^[[:space:]]*$')" ]]; then
            CHANGELOG_CONTENT=$(sed -n "/^## \[Unreleased\]/,/^## \[/p" "$CHANGELOG_FILE" | sed '$d' | tail -n +3)
        fi
    fi
    
    # Generate summary using Claude (use filtered commits)
    SUMMARY=$(generate_claude_summary "$FILTERED_COMMIT_DATA" "$FILE_CHANGES" "$CHANGELOG_CONTENT" "$COMMIT_COUNT" "$PREVIOUS_VERSION")
    
    echo "$SUMMARY"
    echo ""
else
    echo "This is the initial release of the Trading Card API PHP SDK."
    echo ""
fi

echo "---"
echo ""

# Generate "What's New in This Release" section
if [[ -n "${PREVIOUS_VERSION}" ]]; then
    generate_whats_new_section "$COMMIT_RANGE" "$VERSION"
fi

# What's Changed section - use changelog content only
echo "## 📝 What's Changed"
echo ""

# Extract changelog content for this version
if [[ -f "$CHANGELOG_FILE" ]]; then
    # First try to find the specific version section
    CHANGELOG_CONTENT=$(sed -n "/^## \[${VERSION}\]/,/^## \[/p" "$CHANGELOG_FILE" | sed '$d' | tail -n +3)
    
    # If no specific version found, try unreleased section (for previews)
    if [[ -z "$(echo "$CHANGELOG_CONTENT" | grep -v '^[[:space:]]*$')" ]]; then
        CHANGELOG_CONTENT=$(sed -n "/^## \[Unreleased\]/,/^## \[/p" "$CHANGELOG_FILE" | sed '$d' | tail -n +3)
    fi
    
    if [[ -n "$(echo "$CHANGELOG_CONTENT" | grep -v '^[[:space:]]*$')" ]]; then
        echo "$CHANGELOG_CONTENT"
        echo ""
    else
        echo "No changes documented in changelog for this version."
        echo ""
    fi
else
    echo "Changelog not found."
    echo ""
fi

# Check for Claude-assisted changes
CLAUDE_COMMIT_COUNT=0
if [[ -n "${PREVIOUS_VERSION}" ]]; then
    CLAUDE_COMMIT_COUNT=$(git log --pretty=format:"%b" ${COMMIT_RANGE} 2>/dev/null | grep "Generated with.*Claude Code\|Co-Authored-By: Claude" | wc -l | tr -d ' ')
    [[ -z "$CLAUDE_COMMIT_COUNT" ]] && CLAUDE_COMMIT_COUNT=0
fi

if [[ $CLAUDE_COMMIT_COUNT -gt 0 ]]; then
    echo "### 🤖 Claude Code Assistance"
    echo ""
    echo "This release includes **${CLAUDE_COMMIT_COUNT} commits** made with Claude Code assistance."
    echo ""
fi

# Function to get issue/PR details and determine change type
get_issue_details() {
    local issue_num="$1"
    
    # Skip GitHub API if tools are not available
    if ! command -v jq >/dev/null 2>&1 || ! command -v gh >/dev/null 2>&1; then
        echo "unknown|#$issue_num|Unknown"
        return 1
    fi
    
    # Try to get issue details with retry logic
    local issue_data=""
    local max_retries=2
    local retry_count=0
    
    while [[ $retry_count -lt $max_retries ]]; do
        if command -v timeout >/dev/null 2>&1; then
            issue_data=$(timeout 5 gh issue view "$issue_num" --json title,labels --repo cardtechie/tradingcardapi-sdk-php 2>/dev/null || echo "")
        else
            # Fallback without timeout command
            issue_data=$(gh issue view "$issue_num" --json title,labels --repo cardtechie/tradingcardapi-sdk-php 2>/dev/null || echo "")
        fi
        
        # Check if we got valid data
        if [[ -n "$issue_data" ]] && echo "$issue_data" | jq -e '.title' >/dev/null 2>&1; then
            break
        fi
        
        retry_count=$((retry_count + 1))
        if [[ $retry_count -lt $max_retries ]]; then
            sleep 1
        fi
    done
    
    if [[ -n "$issue_data" ]] && echo "$issue_data" | jq -e '.title' >/dev/null 2>&1; then
        local title=$(echo "$issue_data" | jq -r '.title')
        local labels=$(echo "$issue_data" | jq -r '.labels[]?.name' 2>/dev/null | tr '\n' ' ')
        local change_type=$(classify_change_type "$title" "$labels" "issue")
        echo "issue|$title|$change_type"
        return 0
    fi
    
    # Try as PR if issue failed
    local pr_data=""
    retry_count=0
    
    while [[ $retry_count -lt $max_retries ]]; do
        if command -v timeout >/dev/null 2>&1; then
            pr_data=$(timeout 5 gh pr view "$issue_num" --json title,labels --repo cardtechie/tradingcardapi-sdk-php 2>/dev/null || echo "")
        else
            # Fallback without timeout command
            pr_data=$(gh pr view "$issue_num" --json title,labels --repo cardtechie/tradingcardapi-sdk-php 2>/dev/null || echo "")
        fi
        
        # Check if we got valid data
        if [[ -n "$pr_data" ]] && echo "$pr_data" | jq -e '.title' >/dev/null 2>&1; then
            break
        fi
        
        retry_count=$((retry_count + 1))
        if [[ $retry_count -lt $max_retries ]]; then
            sleep 1
        fi
    done
    
    if [[ -n "$pr_data" ]] && echo "$pr_data" | jq -e '.title' >/dev/null 2>&1; then
        local title=$(echo "$pr_data" | jq -r '.title')
        local labels=$(echo "$pr_data" | jq -r '.labels[]?.name' 2>/dev/null | tr '\n' ' ')
        local change_type=$(classify_change_type "$title" "$labels" "pr")
        echo "pr|$title|$change_type"
        return 0
    fi
    
    # Fallback if both fail
    echo "unknown|#$issue_num|Unknown"
    return 1
}

# Function to classify change type based on title, labels, and type
classify_change_type() {
    local title="$1"
    local labels="$2"
    local type="$3"
    
    # Convert to lowercase for matching
    local title_lower=$(echo "$title" | tr '[:upper:]' '[:lower:]')
    local labels_lower=$(echo "$labels" | tr '[:upper:]' '[:lower:]')
    
    # Check labels first (most reliable)
    if [[ "$labels_lower" =~ bug ]]; then
        echo "Bugfix"
    elif [[ "$labels_lower" =~ enhancement|feature ]]; then
        echo "Feature"
    elif [[ "$labels_lower" =~ documentation ]]; then
        echo "Documentation"
    elif [[ "$labels_lower" =~ security ]]; then
        echo "Security"
    elif [[ "$labels_lower" =~ refactor ]]; then
        echo "Refactor"
    elif [[ "$labels_lower" =~ test ]]; then
        echo "Testing"
    # Check title keywords if no matching labels
    elif [[ "$title_lower" =~ ^fix|fix[[:space:]]|fixes[[:space:]]|fixed[[:space:]]|resolve|resolves|resolved ]]; then
        echo "Bugfix"
    elif [[ "$title_lower" =~ ^add|add[[:space:]]|adds[[:space:]]|added[[:space:]]|implement|implements|implemented ]]; then
        echo "Feature"
    elif [[ "$title_lower" =~ ^update|update[[:space:]]|updates[[:space:]]|updated[[:space:]]|improve|improves|improved|enhance|enhances|enhanced ]]; then
        echo "Enhancement"
    elif [[ "$title_lower" =~ ^remove|remove[[:space:]]|removes[[:space:]]|removed[[:space:]]|delete|deletes|deleted ]]; then
        echo "Removal"
    elif [[ "$title_lower" =~ ^refactor|refactor[[:space:]]|refactors[[:space:]]|refactored[[:space:]] ]]; then
        echo "Refactor"
    elif [[ "$title_lower" =~ document|documentation|readme|docs ]]; then
        echo "Documentation"
    elif [[ "$title_lower" =~ test|testing|spec ]]; then
        echo "Testing"
    elif [[ "$title_lower" =~ security|vulnerability|auth ]]; then
        echo "Security"
    else
        # Default based on type
        if [[ "$type" == "pr" ]]; then
            echo "Change"
        else
            echo "Issue"
        fi
    fi
}

# Issues Fixed section
echo "## 🐛 Issues Fixed"
echo ""

if [[ -n "${PREVIOUS_VERSION}" ]]; then
    # Extract GitHub issues from commits (filter out internal commits first)
    ALL_COMMITS=$(git log --pretty=format:"%s" ${COMMIT_RANGE} || echo "")
    FILTERED_COMMITS=$(filter_internal_commits "$ALL_COMMITS")
    ISSUES=$(echo "$FILTERED_COMMITS" | grep -oE '#[0-9]+' | sort -u || echo "")
    if [[ -n "$ISSUES" ]]; then
        declare -a issue_details=()
        
        # Collect issue details
        while IFS= read -r issue; do
            [[ -z "$issue" ]] && continue
            issue_num=$(echo "$issue" | sed 's/#//')
            
            # Get detailed info with timeout and error handling
            details=$(get_issue_details "$issue_num" || echo "unknown|#$issue_num|Unknown")
            if [[ "$details" == "unknown|#$issue_num|Unknown" ]]; then
                # Fallback to simple format
                issue_details+=("$issue_num|unknown|#$issue_num|Unknown")
            else
                issue_details+=("$issue_num|$details")
            fi
        done <<< "$ISSUES"
        
        # Process and display issue details
        printf '%s\n' "${issue_details[@]}" | while IFS='|' read -r issue_num type title change_type; do
            # Skip empty, malformed, or invalid entries
            [[ -z "$issue_num" || -z "$type" || "$issue_num" == "" || "$type" == "" ]] && continue
            # Skip entries with Unknown title and empty/Unknown change_type
            [[ "$title" == "Unknown" && ( -z "$change_type" || "$change_type" == "Unknown" ) ]] && continue
            # Skip entries where title is just the issue number (fallback case)
            [[ "$title" == "#$issue_num" ]] && continue
            
            if [[ "$type" == "unknown" ]]; then
                # Fallback format - use issue number for simple display
                echo "- [${title}](https://github.com/cardtechie/tradingcardapi-sdk-php/issues/${issue_num})"
            else
                # Enhanced format with title and change type
                if [[ "$type" == "pr" ]]; then
                    echo "- [$title](https://github.com/cardtechie/tradingcardapi-sdk-php/pull/${issue_num}) ($change_type)"
                else
                    echo "- [$title](https://github.com/cardtechie/tradingcardapi-sdk-php/issues/${issue_num}) ($change_type)"
                fi
            fi
        done | sort | uniq
        echo ""
    else
        echo "No GitHub issues were referenced in this release."
        echo ""
    fi
else
    echo "No previous version to compare against."
    echo ""
fi

# Links & Resources section
echo "## 🔗 Links & Resources"
echo ""

if [[ -n "${PREVIOUS_VERSION}" ]]; then
    echo "- 📊 **[Full Changelog](https://github.com/cardtechie/tradingcardapi-sdk-php/compare/${PREVIOUS_VERSION}...${VERSION})** - Complete git diff since last release"
else
    echo "- 📊 **[Repository](https://github.com/cardtechie/tradingcardapi-sdk-php)** - Initial release"
fi

echo "- 📦 **[Packagist Package](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)** - Composer package repository"
echo "- 📚 **[API Documentation](https://api.tradingcardapi.com/api/documentation)** - Trading Card API documentation"
echo "- 🏠 **[Main API Repository](https://github.com/cardtechie/tradingcardapi-api)** - Core Trading Card API"
echo ""

# Add deployment info
echo "## 🚀 Installation"
echo ""
echo "Install the SDK via Composer:"
echo ""
echo '```bash'
echo "composer require cardtechie/tradingcardapi-sdk-php:^${VERSION}"
echo '```'
echo ""
echo "**Version Information:**"
echo "- **PHP SDK Version**: ${VERSION}"
echo "- **Build Date**: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "- **Git Commit**: $(git rev-parse HEAD)"
echo "- **PHP Requirements**: ^8.1"
echo "- **Laravel Compatibility**: 9.x, 10.x, 11.x, 12.x"
echo ""

# Add upgrade notes if this is a major version
if [[ "$VERSION" =~ ^[0-9]+\.0\.0 ]]; then
    echo "## ⚠️ Upgrade Notes"
    echo ""
    echo "This is a major version release. Please review the breaking changes above before upgrading."
    echo ""
fi

echo "---"
echo ""
echo "*🤖 Release generated automatically by [Trading Card API PHP SDK Build System](https://github.com/cardtechie/tradingcardapi-sdk-php)*"