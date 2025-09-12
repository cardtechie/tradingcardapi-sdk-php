# Version Management Guide

This document explains the sophisticated version management system for the Trading Card API PHP SDK, adapted from the main API repository's professional release process.

## Overview

The PHP SDK uses an intelligent, branch-aware semantic versioning system that automatically generates appropriate version numbers based on the current git branch and commit history. This system supports the full software development lifecycle from feature branches to production releases.

## Quick Reference

```bash
# Get current version
make version

# Preview version for different branches  
make version-preview --branch=main
make version-preview --branch=develop
make version-preview --branch=release/1.2.0

# Update changelog
make changelog-update

# Generate release notes
make release-notes-preview
```

## Versioning Strategy

### Semantic Versioning Format

The SDK follows [Semantic Versioning 2.0.0](https://semver.org/):

- **MAJOR.MINOR.PATCH** - Stable releases (e.g., `1.2.3`)
- **MAJOR.MINOR.PATCH.beta-N** - Beta releases (e.g., `1.3.0.beta-5`)
- **MAJOR.MINOR.PATCH.rc-N** - Release candidates (e.g., `1.3.0.rc-2`)
- **MAJOR.MINOR.PATCH-feature.N** - Feature branches (e.g., `1.2.3-auth-improvements.4`)

### Branch-Based Versioning

| Branch Type | Version Pattern | Example | Purpose |
|-------------|-----------------|---------|---------|
| `main`/`master` | `X.Y.Z` or `X.Y.Z+1` | `1.2.3` | Production releases |
| `develop` | `X.Y+1.0.beta-N` | `1.3.0.beta-12` | Pre-release testing |
| `release/X.Y.Z` | `X.Y.Z.rc-N` | `1.3.0.rc-2` | Release candidates |
| `hotfix/*` | `X.Y.Z+1-hotfix.name.N` | `1.2.4-security-fix.3` | Critical fixes |
| `feature/*` | `X.Y.Z-feature.name.N` | `1.2.3-new-endpoint.5` | Feature development |

## Build Scripts

### `build/version.sh`

Generates version numbers based on current branch and git history.

**Usage:**
```bash
# Current branch version
./build/version.sh

# Simulate different branch
./build/version.sh --branch=main
./build/version.sh --branch=develop
./build/version.sh --branch=release/1.2.0
```

**Features:**
- Handles repositories with no tags (development mode)
- Parses existing semantic version tags
- Counts commits since last tag
- Supports all standard git branch patterns

### `build/update-changelog.sh`

Updates CHANGELOG.md with new version entries following Keep a Changelog format.

**Usage:**
```bash
# Update for current version
./build/update-changelog.sh $(./build/version.sh)

# Update for specific version
./build/update-changelog.sh "1.2.3" "1.2.2"
```

**Features:**
- Automatic commit categorization (Added, Changed, Fixed, etc.)
- Merge detection and filtering
- Markdown formatting compliance
- Link generation for GitHub comparisons

### `build/generate-release-notes.sh`

Creates comprehensive GitHub release notes with AI-powered summaries.

**Usage:**
```bash
# Generate notes for current version
./build/generate-release-notes.sh $(./build/version.sh)

# Generate for specific version
./build/generate-release-notes.sh "1.2.3" "1.2.2"
```

**Features:**
- Claude API integration for intelligent summaries (optional)
- GitHub issue/PR integration
- Categorized change sections
- Installation instructions
- Package manager links

## Release Workflows

### Development Workflow

1. **Feature Development**
   ```bash
   git checkout -b feature/new-endpoint
   # Development work...
   make version  # Shows: 1.2.3-new-endpoint.5
   ```

2. **Merge to Develop**
   ```bash
   git checkout develop
   git merge feature/new-endpoint
   make version  # Shows: 1.3.0.beta-12
   ```

3. **Update Changelog**
   ```bash
   make changelog-update
   ```

### Release Process

1. **Create Release Branch**
   ```bash
   git checkout -b release/1.3.0
   make version  # Shows: 1.3.0.rc-1
   ```

2. **Prepare Release**
   ```bash
   make changelog-update
   make release-notes-preview
   ```

3. **Merge to Main**
   ```bash
   git checkout main
   git merge release/1.3.0
   make version  # Shows: 1.3.0
   ```

### Hotfix Process

1. **Create Hotfix Branch**
   ```bash
   git checkout -b hotfix/security-fix
   make version  # Shows: 1.2.4-security-fix.1
   ```

2. **Apply Fix and Release**
   ```bash
   make changelog-update
   git checkout main
   git merge hotfix/security-fix
   ```

## Makefile Commands

### Version Management

- `make version` - Show current version
- `make version-preview --branch=<name>` - Preview version for branch
- `make changelog-update` - Update changelog for current version
- `make changelog-preview` - Preview recent changes
- `make release-notes-preview` - Generate release notes preview
- `make release-notes VERSION=x.x.x` - Generate release notes for specific version

### Branch-Specific Helpers

- `make version-bump-develop` - Prepare develop branch version
- `make version-bump-main` - Prepare main branch version

## Integration with CI/CD

### Environment Variables

When running in CI/CD (GitHub Actions), the version script automatically sets:

- `PHP_SDK_VERSION` - The generated version number
- `BUILD_DATE` - ISO timestamp of build
- `BUILD_COMMIT` - Full git commit hash

### Composer Integration

The build system automatically updates `composer.json` version field when running in CI environments:

```json
{
  "version": "1.2.3",
  "...": "..."
}
```

## Manual Overrides

### Custom Version Tags

To manually tag a version:

```bash
git tag -a "1.2.3" -m "Release version 1.2.3"
git push origin --tags
```

### Emergency Releases

For emergency releases outside the normal process:

```bash
# Create emergency tag
git tag -a "1.2.4-emergency" -m "Emergency security fix"

# Update changelog manually
./build/update-changelog.sh "1.2.4-emergency" "1.2.3"

# Generate release notes
./build/generate-release-notes.sh "1.2.4-emergency" "1.2.3"
```

## Configuration

### Claude API Integration

For AI-powered release summaries, set the environment variable:

```bash
export CLAUDE_API_KEY="your-api-key"
```

Without this key, the system falls back to template-based summaries.

### GitHub CLI Integration

For enhanced issue/PR details in release notes:

```bash
# Install GitHub CLI
brew install gh

# Authenticate
gh auth login
```

## Troubleshooting

### No Git Tags

If the repository has no tags, the system defaults to development versioning:
- Pattern: `0.1.0-dev.N` where N is the commit count

### Version Parsing Errors

The system expects semantic version tags. Ensure tags follow the pattern:
- `1.2.3` or `v1.2.3` for releases
- `1.2.3.beta-4` for pre-releases

### Changelog Conflicts

If multiple developers update the changelog simultaneously:

1. Resolve merge conflicts in the `[Unreleased]` section
2. Run `make changelog-update` to regenerate the entry
3. Review and commit the result

## Best Practices

### Commit Messages

Use conventional commit formats for better categorization:

```bash
feat: add new card search endpoint
fix: resolve authentication timeout issue
docs: update API usage examples
refactor: improve error handling logic
```

### Release Planning

1. **Feature Freeze**: Stop merging features to develop
2. **Create Release Branch**: Start release candidate process
3. **Testing Phase**: Validate release candidate functionality
4. **Documentation**: Update CHANGELOG.md and README.md
5. **Release**: Merge to main and tag

### Version Numbering Guidelines

- **Patch releases** (`X.Y.Z+1`): Bug fixes, security patches
- **Minor releases** (`X.Y+1.0`): New features, backwards compatible
- **Major releases** (`X+1.0.0`): Breaking changes, major features

## Additional Resources

- [Keep a Changelog](https://keepachangelog.com/) - Changelog format standard
- [Semantic Versioning](https://semver.org/) - Version numbering specification
- [Conventional Commits](https://www.conventionalcommits.org/) - Commit message format
- [Trading Card API Documentation](https://api.tradingcardapi.com/api/documentation) - Main API docs