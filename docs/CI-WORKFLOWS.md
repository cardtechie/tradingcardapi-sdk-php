# CI/CD Workflow Topology and Optimizations

This document describes the GitHub Actions workflows that run on this repository,
with a focus on the workflows triggered by a merge to `main`, and the
optimizations applied to keep post-merge CI fast and inexpensive without
weakening any quality gate.

## Workflows that trigger on `push: main`

| Workflow | File | Runs on `push: main`? | What it does |
| --- | --- | --- | --- |
| Build and Release | `build-release.yml` | Yes | Generates the version, runs the reusable test matrix (`uses: ./.github/workflows/run-tests.yml`), and — for release versions — creates the GitHub release and updates Packagist. |
| Code Quality | `code-quality.yml` | Yes | PHPStan static analysis, Laravel Pint code-style check, a Pest run with coverage (`--min=80`), and a markdownlint markdown-style check. |
| Enforce SHA-Pinned Actions | `action-pins.yml` | Yes | Fails if any `uses:` reference is not pinned to a full 40-char commit SHA. |
| Run Tests | `run-tests.yml` | **No (changed)** | Standalone test matrix. Now triggers only on `push: develop`, all `pull_request` events, and `workflow_call`. |

Other workflows (`changelog-check.yml`, `changelog-fragment-check.yml`,
`assign-sprint.yml`, `dependabot-auto-merge.yml`,
`fix-php-code-style-issues.yml`, `sync-develop-and-stable.yml`) are scoped to
pull requests, issues, or other events and are not part of the post-merge `main`
path.

## The prior double-matrix waste

Before this change, every merge to `main` ran the **full test matrix twice**:

- `run-tests.yml` fired on `push: [main, develop]` and ran 6 jobs (2 OS x 3 PHP
  versions on Laravel 12).
- `build-release.yml` also fired on `push: [main]` and invoked the *same* matrix
  again via `uses: ./.github/workflows/run-tests.yml` — another 6 jobs.

That meant ~12 test jobs for a single merge to `main`, with the second set being
pure duplication.

## Optimization strategy

### 1. De-duplicate the post-merge test matrix

`run-tests.yml` no longer triggers on `push: main` — it triggers on
`push: develop`, on `pull_request` to `main`/`develop`, and via `workflow_call`.

**The release workflow (`build-release.yml`) is now the sole post-merge test
gate for `main`:** its `test` job calls `run-tests.yml` as a reusable workflow,
so the full matrix still runs once on every merge to `main`. PRs targeting
`main` continue to run the full matrix via the `pull_request` trigger, so no
coverage is lost before merge.

This roughly halves the test-job count on a `main` merge (from ~12 to ~6).

### 2. Composer dependency caching

The `run-tests.yml` matrix jobs previously fetched every Composer dependency
from scratch on every run. They now use `actions/cache` (SHA-pinned) keyed on
the OS, PHP version, Laravel version, and a hash of `composer.json`. The cache
path is resolved at runtime via `composer config cache-files-dir` so it is
correct on both the Ubuntu and Windows matrix legs. `restore-keys` provide a
prefix fallback so a changed `composer.json` still benefits from a partial cache.

### 3. Cancel superseded runs (concurrency)

- `run-tests.yml` and `code-quality.yml` declare a `concurrency` group keyed on
  workflow + ref with `cancel-in-progress: true`. When a new push lands on a
  branch with an in-flight run, the older run is cancelled so CI resources are
  not spent on superseded commits.
- `build-release.yml` declares a `concurrency` group keyed on the ref with
  `cancel-in-progress: false`. Release runs are **serialized but never
  cancelled** — a release must run to completion (version generation, tests,
  GitHub release, Packagist update). Because `build-release.yml` is the
  serialization point on `main`, the `cancel-in-progress: true` on the reusable
  `run-tests.yml` it invokes never interrupts a release mid-flight.

## Quality gates preserved

None of the optimizations weaken a quality gate:

- The full test matrix still runs on every PR and once per `main` merge (via the
  release workflow's reusable `test` job).
- PHPStan, Laravel Pint, and coverage (`--min=80`) in `code-quality.yml` are
  unchanged.
- A `markdown-lint` job in `code-quality.yml` runs
  [markdownlint](https://github.com/igorshubovych/markdownlint-cli) over all
  tracked `*.md` files (ignoring `vendor`, `node_modules`, and `changelog.d`)
  using the root [`.markdownlint.jsonc`](../.markdownlint.jsonc) ruleset, which
  is aligned with the parent `tradingcardapi-api` repo. Run it locally with
  `make lint-md` (or `make fix-md` to auto-fix). Its `actions/setup-node` and
  `actions/checkout` steps are SHA-pinned to satisfy `action-pins.yml`.
- `action-pins.yml` still enforces SHA-pinned actions; the new `actions/cache`
  reference is pinned to a full 40-char SHA.
- Changelog-fragment and release validation are unchanged.

## Operator notes

- If a branch-protection **required status check** on `main` is pinned to the
  standalone *Run Tests* workflow's job names (rather than to the
  `Build and Release` workflow's reusable `test` job), removing the `push: main`
  trigger from `run-tests.yml` could leave that required check unreported on
  post-merge pushes. PR-level required checks are unaffected. Confirm no
  `main`-push-scoped required check depends on the standalone *Run Tests*
  workflow name.
- `code-quality.yml`'s `coverage` job still re-runs the Pest suite on every
  `main`/`develop` push and PR. Scoping it to PRs only (or `develop` pushes
  only) would cut more time, but it is a quality gate and was intentionally left
  unchanged here pending operator preference.
