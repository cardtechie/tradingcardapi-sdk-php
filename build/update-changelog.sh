#!/bin/bash

#
# Updates CHANGELOG.md with new version information for PHP SDK
# Usage: update-changelog.sh <version> [<previous-version>]
# Adapted from Trading Card API build system for PHP SDK usage
#

set -e

VERSION="$1"
PREVIOUS_VERSION="$2"
CHANGELOG_FILE="CHANGELOG.md"

if [[ -z "${VERSION}" ]]; then
    echo "ERROR: Version is required"
    echo "Usage: update-changelog.sh <version> [<previous-version>]"
    exit 1
fi

if [[ ! -f "${CHANGELOG_FILE}" ]]; then
    echo "ERROR: ${CHANGELOG_FILE} not found"
    exit 1
fi

# If no previous version specified, try to find the latest tag
if [[ -z "${PREVIOUS_VERSION}" ]]; then
    PREVIOUS_VERSION=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
fi

# Create temporary files
TEMP_CHANGELOG=$(mktemp)
NEW_ENTRY=$(mktemp)

# Extract existing unreleased content first
UNRELEASED_CONTENT=$(sed -n '/^## \[Unreleased\]/,/^## \[/p' "$CHANGELOG_FILE" | sed '$d' | tail -n +3)

# Generate the new changelog entry
echo "## [${VERSION}] - $(date +%Y-%m-%d)" > "$NEW_ENTRY"
echo "" >> "$NEW_ENTRY"

# If there's existing unreleased content, use that instead of git commits
if [[ -n "$(echo "$UNRELEASED_CONTENT" | grep -v '^[[:space:]]*$')" ]]; then
    echo "$UNRELEASED_CONTENT" >> "$NEW_ENTRY"
    echo "" >> "$NEW_ENTRY"
else
    # Only categorize commits if there's no manual unreleased content
    FEATURES=()
    FIXES=()
    ADDED=()
    CHANGED=()
    DEPRECATED=()
    REMOVED=()
    SECURITY=()
    
    # Track processed commits to avoid duplicates
    declare -A PROCESSED_COMMITS
    
    # Get commit range
    if [[ -n "${PREVIOUS_VERSION}" ]]; then
        COMMIT_RANGE="${PREVIOUS_VERSION}..HEAD"
    else
        COMMIT_RANGE="HEAD"
    fi
    
    # Get commits in range
    if [[ -n "${PREVIOUS_VERSION}" ]]; then
        COMMITS=$(git log --pretty=format:"%s" ${COMMIT_RANGE} | grep -v "^$")
    else
        COMMITS=$(git log --pretty=format:"%s" -10 | grep -v "^$")
    fi
    
    # Process each commit
    while IFS= read -r subject; do
        [[ -z "$subject" ]] && continue
        
        # Skip duplicate commits
        if [[ -n "${PROCESSED_COMMITS[$subject]}" ]]; then
            continue
        fi
        PROCESSED_COMMITS[$subject]=1
        
        # Skip meta-commits to avoid recursion and noise
        if [[ "$subject" =~ ^chore:\ update\ CHANGELOG\.md\ for\ version ]]; then
            continue
        fi
        
        # Skip ALL meta-commits related to changelog, filtering, fixing, enhancing the automation itself
        if [[ "$subject" =~ (Clean up|Fix duplicate|Add changelog|Update changelog|Consolidate|Fix changelog|Restore changelog|Enhance.*filter|Enhance.*commit|meta-commit|Fix YAML syntax|Trigger CI workflows|Resolve.*merge conflict|Update changelog format) ]]; then
            continue
        fi
        
        # Skip version bump and workflow meta-commits  
        if [[ "$subject" =~ (Version bump|Auto-update project|Update.*from automated|Generated.*workflow|Comprehensive fix|Fix.*corruption|Fix.*duplication) ]]; then
            continue
        fi
        
        # Skip functionality restoration and script enhancement commits
        if [[ "$subject" =~ (Restore.*functionality|Fix.*existing versions|Enhance.*script) ]]; then
            continue
        fi
        
        # Skip README and documentation maintenance commits
        if [[ "$subject" =~ (Restore README|Update readme|Remove duplicate.*readme|Fix.*README) ]]; then
            continue
        fi
        
        # Skip merge commits and branch management
        if [[ "$subject" =~ ^Merge\ (branch|pull\ request) ]]; then
            continue
        fi
        
        # Categorize based on commit message patterns
        case "$subject" in
            "feat"*|"add"*|"Add"*|"Feat"*|"Implement"*|"implement"*)
                ADDED+=("- $subject")
                ;;
            "fix"*|"Fix"*|"hotfix"*|"Hotfix"*|"bug"*|"Bug"*)
                FIXES+=("- $subject")
                ;;
            "update"*|"Update"*|"change"*|"Change"*|"modify"*|"Modify"*)
                CHANGED+=("- $subject")
                ;;
            "deprecate"*|"Deprecate"*|"deprecated"*|"Deprecated"*)
                DEPRECATED+=("- $subject")
                ;;
            "remove"*|"Remove"*|"delete"*|"Delete"*)
                REMOVED+=("- $subject")
                ;;
            "security"*|"Security"*|"sec"*|"Sec"*)
                SECURITY+=("- $subject")
                ;;
            *)
                # Try to categorize by content
                if [[ "$subject" =~ [Nn]ew|[Aa]dd ]]; then
                    ADDED+=("- $subject")
                elif [[ "$subject" =~ [Ff]ix|[Bb]ug ]]; then
                    FIXES+=("- $subject")
                else
                    CHANGED+=("- $subject")
                fi
                ;;
        esac
    done <<< "$COMMITS"
    
    # Function to add properly formatted section with markdown linting compliance
    add_changelog_section() {
        local section_title="$1"
        local -n items_ref=$2
        
        if [[ ${#items_ref[@]} -gt 0 ]]; then
            echo "### $section_title" >> "$NEW_ENTRY"
            echo "" >> "$NEW_ENTRY"  # Blank line after heading (MD022)
            for item in "${items_ref[@]}"; do
                echo "$item" >> "$NEW_ENTRY"
            done
            echo "" >> "$NEW_ENTRY"  # Blank line after list (MD032)
        fi
    }
    
    # Build changelog entry in Keep a Changelog format with proper markdown formatting
    add_changelog_section "Added" ADDED
    add_changelog_section "Changed" CHANGED  
    add_changelog_section "Deprecated" DEPRECATED
    add_changelog_section "Removed" REMOVED
    add_changelog_section "Fixed" FIXES
    add_changelog_section "Security" SECURITY
    
fi  # Close the unreleased content check

# Check if this version already exists in the changelog
VERSION_EXISTS=$(grep -c "^## \[${VERSION}\]" "$CHANGELOG_FILE" || true)

if [[ "$VERSION_EXISTS" -gt 0 ]]; then
    echo "🔄 Updating existing version ${VERSION} entry with latest changes..." >&2
    
    # Replace existing version section with updated content
    {
        # Copy everything before the existing version
        sed -n "1,/^## \[${VERSION}\]/p" "$CHANGELOG_FILE" | sed '$d'
        
        # Add the new entry
        cat "$NEW_ENTRY"
        
        # Get everything after the current version section
        # Find the line number of the current version, then get everything after the next version section
        VERSION_LINE=$(grep -n "^## \[${VERSION}\]" "$CHANGELOG_FILE" | head -1 | cut -d: -f1)
        if [[ -n "$VERSION_LINE" ]]; then
            # Get content after current version, look for next version section
            tail -n +$((VERSION_LINE + 1)) "$CHANGELOG_FILE" | awk '
            BEGIN { found_next = 0 }
            /^## \[/ { found_next = 1 }
            found_next { print }
            '
        fi
    } > "$TEMP_CHANGELOG"
else
    echo "➕ Adding new version ${VERSION} entry..." >&2
    
    # Version doesn't exist - add normally after unreleased
    {
        # Copy header and add empty unreleased section
        sed '/^## \[Unreleased\]/,$d' "$CHANGELOG_FILE"
        echo "## [Unreleased]"
        echo ""
        
        # Add the new entry
        cat "$NEW_ENTRY"
        
        # Add the rest of the changelog (starting from the first existing version)
        sed -n '/^## \[[0-9]/,$p' "$CHANGELOG_FILE"
    } > "$TEMP_CHANGELOG"
fi

# Beta cleanup: Remove beta entries for final release versions
if [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "🧹 Detected final release version ($VERSION) - cleaning up beta entries..."
    
    # Create temp file for beta cleanup
    TEMP_BETA_CLEANUP=$(mktemp)
    
    # Process the changelog to remove beta entries for this version
    # This removes entire sections from "## [X.Y.Z.beta-N]" to the next "## [" section
    awk -v version="$VERSION" '
    BEGIN { 
        in_beta_section = 0
        beta_pattern = "^## \\[" version "\\.beta-"
    }
    {
        # Check if we are starting a beta section for this version
        if ($0 ~ beta_pattern) {
            in_beta_section = 1
            beta_entries_found = 1
            next
        }
        
        # Check if we hit the next section (end of current beta section)
        if (in_beta_section && /^## \[/) {
            in_beta_section = 0
        }
        
        # Print line if we are not in a beta section
        if (!in_beta_section) {
            print $0
        }
    }
    END {
        if (beta_entries_found) {
            print "Removed beta entries for version " version > "/dev/stderr"
        }
    }
    ' "$TEMP_CHANGELOG" > "$TEMP_BETA_CLEANUP"
    
    # Replace the temp changelog with the cleaned version
    mv "$TEMP_BETA_CLEANUP" "$TEMP_CHANGELOG"
    
    echo "✅ Beta cleanup completed for version $VERSION"
fi

# Update the changelog links section at the bottom
if [[ -n "${PREVIOUS_VERSION}" ]]; then
    # Update the comparison links for PHP SDK repository
    sed -i.bak "s|\[Unreleased\]:.*|[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/${VERSION}...HEAD|" "$TEMP_CHANGELOG"
    
    # Add the new version link before the last line
    if ! grep -q "\\[${VERSION}\\]:" "$TEMP_CHANGELOG"; then
        echo "[${VERSION}]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/${PREVIOUS_VERSION}...${VERSION}" >> "$TEMP_CHANGELOG"
    fi
else
    # First release
    sed -i.bak "s|\[Unreleased\]:.*|[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/${VERSION}...HEAD|" "$TEMP_CHANGELOG"
    echo "[${VERSION}]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/${VERSION}" >> "$TEMP_CHANGELOG"
fi

# Replace the original changelog
mv "$TEMP_CHANGELOG" "$CHANGELOG_FILE"

# Clean up markdown formatting to ensure linting compliance
fix_changelog_linting() {
    local temp_file=$(mktemp)
    
    # Fix missing blank lines around headings and lists
    awk '
    BEGIN { prev_line = ""; in_list = 0 }
    {
        current_line = $0
        
        # Detect if we are starting a list
        if (current_line ~ /^- / && prev_line !~ /^$/ && prev_line !~ /^- /) {
            if (prev_line !~ /^### /) {
                print ""  # Add blank line before list
            }
            in_list = 1
        }
        # Detect if we are ending a list
        else if (prev_line ~ /^- / && current_line !~ /^- / && current_line !~ /^  /) {
            in_list = 0
            if (current_line !~ /^$/) {
                print ""  # Add blank line after list
            }
        }
        
        # Add blank line after headings if missing
        if (prev_line ~ /^### / && current_line !~ /^$/) {
            print ""
        }
        
        print current_line
        prev_line = current_line
    }
    ' "$CHANGELOG_FILE" > "$temp_file"
    
    # Remove multiple consecutive blank lines (keep only single blank lines)
    awk '/^$/ { if (blank_count < 1) print; blank_count++ } !/^$/ { blank_count = 0; print }' "$temp_file" > "$CHANGELOG_FILE"
    
    rm -f "$temp_file"
}

# Apply markdown linting fixes
fix_changelog_linting

# Clean up
rm -f "$NEW_ENTRY" "${CHANGELOG_FILE}.bak"

echo "✅ Updated ${CHANGELOG_FILE} with version ${VERSION}"

# Show the changes
echo ""
echo "📝 New changelog entry:"
echo "=========================="
sed -n "/^## \[${VERSION}\]/,/^## \[/p" "$CHANGELOG_FILE" | sed '$d'