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

Fragments are collated into the human-readable `CHANGELOG.md` **once, at the
release step**. Collation reads every `changelog.d/<num>-<type>.md` fragment,
groups the lines under the correct `### Added` / `### Changed` / `### Fixed` /
`### Security` / `### Removed` / `### Deprecated` headings in the new versioned
section, writes `CHANGELOG.md`, and deletes the collated fragments. See
[`docs/VERSION-MANAGEMENT.md`](../docs/VERSION-MANAGEMENT.md) for this repo's
release flow.

> **This is a manual step today — no tooling reads fragments yet.** Automated
> collation is deferred to the Release workflow milestone (see #672), and the
> current release tooling (`build/update-changelog.sh` / `make changelog-update`)
> does **not** consume `changelog.d/` fragments — it works off the existing
> `## [Unreleased]` content and/or git commits. Until automation lands, fragments
> accumulate here and the release operator collates them by hand (or with a
> throwaway script) when cutting a version, then deletes the collated fragments.
> The goal of the ship-now slice is to stop the conflict generation at the
> source; automating release assembly follows.

The `.gitkeep` file keeps this directory tracked in git when no fragments are
pending.
