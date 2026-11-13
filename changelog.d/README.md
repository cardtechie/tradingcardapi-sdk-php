# Changelog fragments

This directory holds **per-PR changelog fragments**. Each PR adds one new file
here instead of editing the shared `## [Unreleased]` section of
[`CHANGELOG.md`](../CHANGELOG.md). Because every PR writes a unique new path,
concurrent PRs never conflict on the changelog — which ends the single-section
merge-conflict storm that motivated the parent design,
[picklewagon/picklewagon-mcp#672](https://github.com/picklewagon/picklewagon-mcp/issues/672).

## Why fragments

The shared `## [Unreleased]` section was a single-section contention hotspot:
every concurrent PR appended to the same block, so each merge re-conflicted the
changelog of every other open PR. One file per change — the towncrier /
changesets / reno model — makes concurrent changelog writes genuinely
conflict-free.

## Convention

One file per change:

```text
changelog.d/<num>-<type>.md
```

- `<num>` — the GitHub issue number the change closes (e.g. `245`).
- `<type>` — one of: `added`, `changed`, `fixed`, `security`, `removed`,
  `deprecated`. This maps to the Keep a Changelog heading the line is collated
  under at release time.

Example — `changelog.d/245-added.md`:

```markdown
- **[Issue #245]** Adopt per-PR `changelog.d/` changelog fragments with a CI presence gate, ending `## [Unreleased]` merge conflicts.
```

The fragment body is a **single** Keep-a-Changelog-style line (an optional one
caveat sub-bullet is allowed), following the same style rule as the main
changelog: one imperative sentence-case line ending in a period; never restate
what's in the linked issue.

## Collation into `CHANGELOG.md`

Fragments are collated **automatically, after merge to `main`**, by the
**Build and Release** workflow (`.github/workflows/build-release.yml`, #406).
Never collate inside a PR, and never hand-write a `## [X.Y.Z]` section in
`CHANGELOG.md`: the **Release Validation** check fails any PR into `main` that
adds one.

On each push to `main`, `build/commit-changelog-back.sh`:

1. refuses any fragment that is empty, heading-only, a symlink, misnamed, or
   whose line is already in `CHANGELOG.md` (it names the file); the collator
   also refuses any fragment whose body contains a heading;
2. runs `build/collate-changelog.sh`, which groups every fragment's line under
   the matching `### Added` / `### Changed` / `### Deprecated` / `### Removed` /
   `### Fixed` / `### Security` heading in `## [Unreleased]` and deletes the
   fragments;
3. promotes `## [Unreleased]` to `## [X.Y.Z] - <date>`, adds a fresh empty
   `## [Unreleased]`, and updates the compare links;
4. commits that as `cardtechie-ops-bot[bot]` and pushes it to `main`. The
   release is tagged on that commit, with the new section as its notes.

When no fragments are pending, nothing is collated and nothing is released.
The version comes from `build/version.sh`: any pending `added` fragment means
the next minor, otherwise the next patch. See
[`docs/RELEASING.md`](../docs/RELEASING.md).

`## [Unreleased]` in `CHANGELOG.md` stays empty between releases. To collate by
hand (recovery only), run `make changelog-collate VERSION=X.Y.Z`, which
collates and stages without committing.

The `.gitkeep` file keeps this directory tracked in git when no fragments are
pending.
