# Release Runbook (Maintainers)

This is the concrete, step-by-step procedure for cutting a release of the
Trading Card API PHP SDK. It documents the **automated** release pipeline
(`.github/workflows/build-release.yml`), the secrets it depends on, how to
trigger a release manually, how to verify the result, and how to roll back.

For the conceptual versioning model (branch-aware version generation, version
patterns per branch type), see [VERSION-MANAGEMENT.md](VERSION-MANAGEMENT.md).
This runbook is the operational companion: *how to actually ship*.

## TL;DR

1. Every PR adds a `changelog.d/<issue>-<type>.md` fragment. No PR edits
   `CHANGELOG.md` or writes a `## [X.Y.Z]` section.
2. Merge into `main`. The **Build and Release** workflow runs the tests,
   collates the pending fragments into a new `## [X.Y.Z]` section, commits that
   back to `main` as **cardtechie-ops-bot**, tags the commit-back `X.Y.Z`,
   creates the GitHub Release with that section as its notes, and pings
   Packagist.
3. Verify the [GitHub Release](https://github.com/cardtechie/tradingcardapi-sdk-php/releases)
   and the [Packagist page](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php).

There is no separate "publish" command: **merging to `main` is the release
trigger.** A merge with no pending fragments releases nothing; its changes ship
with the next merge that adds a fragment.

## How a release is cut

The pipeline lives in `.github/workflows/build-release.yml` and runs as a chain
of jobs:

```text
push to main (or workflow_dispatch)
        │
        ▼
   version  ──►  test  ──►  release  ──►  packagist
                                  └──►  notify
```

- **`version`** — runs `build/version.sh --branch=<ref>` to plan the next
  version. On `main` with a stable `X.Y.Z` result it sets `should_release=true`.
- **`test`** — runs the full test suite via the reusable `run-tests.yml`
  workflow. The `release` job will not run unless tests pass, so nothing is
  committed back to `main` from a red build.
- **`release`** — in order:
  1. mints a **cardtechie-ops-bot** GitHub App token (`contents: write`, this
     repository only) from the `CARDTECHIE_BOT` / `CARDTECHIE_BOT_PRIVATE_KEY`
     org secrets;
  2. checks out the tip of `main` without persisting credentials;
  3. recomputes the version on that tip (warns if `main` moved since the
     `version` job);
  4. runs `build/commit-changelog-back.sh`, which collates `changelog.d/` into
     `## [X.Y.Z] - <date>`, updates the compare links, commits it as
     `cardtechie-ops-bot[bot]` with the subject
     `chore(release): collate CHANGELOG for X.Y.Z`, and pushes it to `main`;
  5. extracts that section as the release notes;
  6. creates the GitHub Release and the `X.Y.Z` tag **on the commit-back SHA**.

  Steps 5 and 6 run only when step 4 reports `release=true`. With no pending
  fragments it reports `release=false` and nothing is tagged or published.
- **`packagist`** — pings the Packagist update API. Runs only when the
  `release` job actually published (`release == 'true'`) off `main`.
- **`notify`** — writes a release summary, or "nothing released", to the run's
  job summary.

The commit-back carries **no `[skip ci]`**, so its push starts
`back-merge-stable-to-develop.yml` and `develop` receives the collated
`CHANGELOG.md`. Build and Release does not run again for it: both the `version`
and `release` jobs skip a push whose head commit subject starts with
`chore(release): collate CHANGELOG for ` **and** whose pusher is
`cardtechie-ops-bot[bot]`.

### Write path to `main`

`main` has classic branch protection that requires a pull request. The
cardtechie-ops-bot App (Integration 1882441) is listed in its
`bypass_pull_request_allowances`, which is what lets the commit-back push
directly. `GITHUB_TOKEN` and `PROJECT_TOKEN` are never used for this push.

If the push is rejected, the `release` job **fails** and nothing is tagged,
released or sent to Packagist, so a release never ships without its notes. The
error names the likely causes: the bypass allowance was removed, a ruleset was
added on `main` without the App as a bypass actor, or `main` moved during the
run (non-fast-forward). Fix the cause and re-run the workflow.

### Re-running after a failure (resume)

If a run fails **after** the commit-back landed (for example the GitHub Release
step), re-run the workflow. `version.sh` sees `## [X.Y.Z]` newer than the
latest tag and re-emits `X.Y.Z`, and `commit-changelog-back.sh` sees that HEAD
is its own commit-back for that version, so it writes nothing and reports
`release=true`. The tag and release are then created on that commit.

The resume only works while the commit-back is still the tip of `main`. If
another merge landed after it, the script refuses (rather than tagging later
code as `X.Y.Z`); tag and release the commit-back by hand instead.

### What version gets cut

`build/version.sh --branch=main` decides, in this order:

1. HEAD exactly on the latest tag → that tag.
2. `CHANGELOG.md`'s newest `## [X.Y.Z]` is newer than the latest tag → that
   version (the resume case above).
3. Otherwise, from the pending fragments: any `changelog.d/<num>-added.md` →
   next **minor**; anything else → next **patch**.

A **major** release is a deliberate act: create and push the tag yourself (see
*Manual / emergency tagging* below).

To preview what would be generated before merging:

```bash
bash build/version.sh --branch=main      # next stable version off main
bash build/version.sh --branch=develop   # next beta (X.Y+1.0.beta-N)
```

## Required repository secrets

| Secret | Used by | Required? | Purpose |
| --- | --- | --- | --- |
| `CARDTECHIE_BOT` | `release` job | **Yes** | cardtechie-ops-bot App id (org secret). Mints the token for the commit-back push. |
| `CARDTECHIE_BOT_PRIVATE_KEY` | `release` job | **Yes** | cardtechie-ops-bot App private key (org secret). |
| `PACKAGIST_USERNAME` | `packagist` job | For Packagist auto-update | Packagist account username for the update API call. |
| `PACKAGIST_TOKEN` | `packagist` job | For Packagist auto-update | Packagist API token paired with the username. |

Without the two App secrets the `release` job fails before anything is pushed.
Without the Packagist secrets the ping no-ops with a logged message.
`GITHUB_TOKEN` is provided automatically and is used only to create the GitHub
Release.

## Cutting a release (normal flow)

1. Ensure `develop` is green and contains everything you want to ship, each
   change with its `changelog.d/` fragment.
2. Open a PR from `develop` (or a `release/X.Y.Z` / `hotfix/*` branch) into
   `main` and merge it once CI passes. The **Release Validation** check
   (`changelog-check.yml`) fails a PR into `main` that adds a `## [X.Y.Z]`
   section to `CHANGELOG.md`, and one that adds a hardcoded `version` to
   `composer.json`.
3. The push to `main` triggers **Build and Release** automatically. Watch it in
   the **Actions** tab.
4. Verify the result (see *Verifying a release* below), including the bot's
   `chore(release): collate CHANGELOG for X.Y.Z` commit on `main`.

### Manual / recovery collation

`make changelog-collate VERSION=X.Y.Z` runs the same script with `--no-commit`:
it collates, promotes and stages, and leaves the commit to you. Use it only to
recover from a broken automated run.

## Triggering a release manually (workflow_dispatch)

The workflow's `on:` block includes `workflow_dispatch`, so it can be run
on demand without a new push:

- **GitHub UI:** Actions → **Build and Release** → **Run workflow** → pick the
  branch (`main` for a real release) → **Run workflow**.
- **GitHub CLI:**

  ```bash
  gh workflow run build-release.yml --ref main
  ```

A manual run still goes through the same `version → test → release → packagist`
gating, so only a stable `X.Y.Z` off `main` produces a real release and a
Packagist update.

## Verifying a release

After the workflow completes:

1. **GitHub Release** — confirm a release tagged `X.Y.Z` exists at
   <https://github.com/cardtechie/tradingcardapi-sdk-php/releases> with
   populated, well-formed release notes:

   ```bash
   gh release view X.Y.Z --repo cardtechie/tradingcardapi-sdk-php
   ```

2. **Packagist** — confirm the new version appears on
   <https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php>. The
   `packagist` job pings the update API; Packagist may take a short while to
   reflect the new tag. If it lags, you can trigger an update from the package
   page ("Update" button) or re-run the `packagist` job.

3. **Installable** — confirm the release is consumable:

   ```bash
   composer require cardtechie/tradingcardapi-sdk-php:^X.Y.Z
   ```

4. **Workflow summary** — the `notify` job writes a release summary (version,
   type, test/release results, install snippet, links) to the run's job summary
   in the Actions tab.

## Rollback

There is no "un-release" button; recover forward.

- **Bad release notes only:** edit the GitHub Release body in place, and fix
  the section in `CHANGELOG.md` with a normal PR
  (`gh release edit X.Y.Z --notes-file notes.md`) — the published package is
  unaffected.
- **Bad release (broken code shipped):** cut a new patch release with the fix.
  Do **not** delete the tag/release if anyone may have already pulled it;
  deleting a published tag breaks consumers who pinned it and can desync
  Packagist. Prefer shipping `X.Y.(Z+1)`.
- **Packagist out of sync:** trigger an update from the Packagist package page,
  or re-run the workflow's `packagist` job. The update API call is idempotent.
- **Yanking a truly broken version** (last resort): mark the GitHub Release as
  a draft/pre-release and follow up with a corrected patch. Removing a Packagist
  version is destructive to downstream pins — avoid unless the release is
  genuinely unusable and freshly published.

## Notes for maintainers

- **`composer.json` carries no `version` field on purpose.** Versions come from
  git tags; `build/version.sh` deliberately stopped writing the version into
  `composer.json` to avoid Packagist publishing conflicts (see
  [VERSION-MANAGEMENT.md](VERSION-MANAGEMENT.md)). The Release Validation check
  fails any PR to `main` that reintroduces a hardcoded version.
- **Tags are not prefixed with `v`** — use `X.Y.Z` (e.g. `0.3.0`), not
  `vX.Y.Z`. The version script tolerates a legacy `v`-prefixed tag, but new
  tags must be bare.
- **Manual / emergency tagging** (for minor/major bumps or out-of-band
  releases):

  ```bash
  git tag -a 0.3.0 -m "Release 0.3.0"
  git push origin 0.3.0
  ```

  Pushing a tag does **not** start the workflow on its own — **Build and
  Release** triggers only on pushes to `main` and `workflow_dispatch`. After
  pushing the tag, run the workflow on `main` via `workflow_dispatch`
  (`gh workflow run build-release.yml --ref main`); `version.sh` picks up the
  new tag and the `release` job builds the matching release.
