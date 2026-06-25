# Release Runbook (Maintainers)

This is the concrete, step-by-step procedure for cutting a release of the
Trading Card API PHP SDK. It documents the **automated** release pipeline
(`.github/workflows/build-release.yml`), the secrets it depends on, how to
trigger a release manually, how to verify the result, and how to roll back.

For the conceptual versioning model (branch-aware version generation, version
patterns per branch type), see [VERSION-MANAGEMENT.md](VERSION-MANAGEMENT.md).
This runbook is the operational companion: *how to actually ship*.

## TL;DR

1. Merge release-ready code into `main`.
2. The **Build and Release** workflow fires automatically on the push to
   `main`, generates the next stable version (`X.Y.Z`), runs the test suite,
   creates a GitHub Release with generated notes, and pings Packagist.
3. Verify the [GitHub Release](https://github.com/cardtechie/tradingcardapi-sdk-php/releases)
   and the [Packagist page](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php).

There is no separate "publish" command — **merging to `main` is the release
trigger.**

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

- **`version`** — runs `build/version.sh --branch=<ref>` to compute the next
  version from the latest git tag and the current branch. On `main` with a
  stable `X.Y.Z` result it sets `is_release=true` and `should_release=true`.
- **`test`** — runs the full PHPUnit suite via the reusable `run-tests.yml`
  workflow. The `release` job will not run unless tests pass.
- **`release`** — generates release notes with
  `build/generate-release-notes.sh` (AI-assisted when `CLAUDE_API_KEY` is set,
  template fallback otherwise) and creates a GitHub Release tagged with the
  generated version. The tag is created by the release action itself; you do
  **not** tag by hand for the normal flow.
- **`packagist`** — pings the Packagist update API so the published package
  picks up the new tag. Gated on `is_release == 'true'` **and**
  `github.ref == 'refs/heads/main'`, so only stable releases off `main` update
  Packagist.
- **`notify`** — writes a release summary to the workflow run's job summary.

### What version gets cut

Version generation is branch-aware (`build/version.sh`). For the release flow
the relevant case is `main`:

- On `main`, exactly on the latest tag → the version equals that tag. Note that
  `should_release` only checks for a stable `X.Y.Z` result on `main` — it does
  **not** compare against existing tags, so the `release` job will still run and
  attempt to (re)create a GitHub Release for that tag. Avoid pushing to `main`
  with no new commits since the last tag unless you intend that.
- On `main`, with N commits since the latest tag → the **patch** is
  incremented (`X.Y.Z` → `X.Y.(Z+1)`).

To preview what would be generated before merging:

```bash
bash build/version.sh --branch=main      # next stable version off main
bash build/version.sh --branch=develop   # next beta (X.Y+1.0.beta-N)
make changelog-preview                    # commits that would be included
```

> **Minor/major bumps.** `version.sh` only auto-increments the patch on `main`.
> To cut a new minor or major release, create and push the target tag yourself
> (see *Manual / emergency tagging* below), then run the workflow on `main` via
> `workflow_dispatch` — `version.sh` reads the just-pushed tag and generates the
> matching version. The **Build and Release** workflow triggers only on pushes
> to `main` and `workflow_dispatch`; pushing a tag alone does **not** start it.

## Required repository secrets

Configure these under **Settings → Secrets and variables → Actions**. None are
strictly required for the workflow to *run* — the pipeline degrades gracefully
when any are absent (release notes fall back to a template; the Packagist ping
no-ops with a logged message). For a *fully* working release the two Packagist
secrets are needed to auto-update the published package, while `CLAUDE_API_KEY`
is optional and only upgrades release-note generation from template to
AI-assisted.

| Secret | Used by | Required? | Purpose |
| --- | --- | --- | --- |
| `PACKAGIST_USERNAME` | `packagist` job | For Packagist auto-update | Packagist account username for the update API call. |
| `PACKAGIST_TOKEN` | `packagist` job | For Packagist auto-update | Packagist API token paired with the username. |
| `CLAUDE_API_KEY` | `release` job (release-note generation) | Optional | Enables AI-generated release notes. Without it, `generate-release-notes.sh` falls back to a template-based summary (still well-formed markdown). |

`GITHUB_TOKEN` is provided automatically by Actions and is used by the release
job to create the GitHub Release — you do not configure it.

## Cutting a release (normal flow)

1. Ensure `develop` is green and contains everything you want to ship.
2. Open a PR from `develop` (or a `release/X.Y.Z` branch) into `main` and merge
   it once CI passes. The **Release Validation** check
   (`changelog-check.yml`) enforces that PRs to `main` carry a
   `## [X.Y.Z]` section in `CHANGELOG.md` and that `composer.json` has **no**
   hardcoded `version` field.
3. The push to `main` triggers **Build and Release** automatically. Watch it in
   the **Actions** tab.
4. Verify the result (see *Verifying a release* below).

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

- **Bad release notes only:** edit the GitHub Release body in place
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
