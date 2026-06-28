# Contributing to the Trading Card API PHP SDK

Thanks for your interest in contributing! This guide explains how to set up a
development environment, the coding and testing standards the project enforces,
and the pull-request process. Everything here reflects the tooling already wired
into the repository (Docker/Make, Laravel Pint, PHPStan/larastan, Pest, per-PR
changelog fragments, and CI) — it introduces no new process.

## Code of Conduct

This project adheres to a [Code of Conduct](CODE_OF_CONDUCT.md). By
participating, you are expected to uphold it. Please report unacceptable
behavior as described there.

## Getting Help & Asking Questions

- **Questions and ideas** — start a thread in
  [GitHub Discussions](https://github.com/cardtechie/tradingcardapi-sdk-php/discussions).
- **Support** — see [SUPPORT.md](SUPPORT.md) for the best place to get help.
- **Bugs and feature requests** — open an issue using the templates under
  [`.github/ISSUE_TEMPLATE/`](.github/ISSUE_TEMPLATE) (bug report or feature
  request). See [Issue Reporting](#issue-reporting) below.

Please open an issue or start a discussion before submitting a substantial pull
request, so the change can be scoped and agreed on first.

## Development Environment Setup

The project uses a Docker-based development environment, so the only host
prerequisites are Docker itself and (optionally) `make`.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose
- [Make](https://www.gnu.org/software/make/) (optional — provides the
  convenience targets below; you can run the underlying `docker compose`
  commands directly instead)
- PHP 8.2+ and Composer are **not** required on the host — they run inside the
  `dev` container.

### Targeted runtime

The SDK targets:

- PHP 8.2 or higher
- Laravel 10, 11, or 12
- GuzzleHTTP 7.5 or higher

### First-time setup

```bash
# Clone the repository
git clone https://github.com/cardtechie/tradingcardapi-sdk-php.git
cd tradingcardapi-sdk-php

# Build and start the dev container
make up

# Install Composer dependencies inside the container
make install
```

`make up` builds and starts the `dev` service defined in
[`docker-compose.yaml`](docker-compose.yaml); `make install` runs
`composer install` inside it. Run `make help` to list every available target,
or `make shell` to open a shell in the container.

## Coding Standards

### Code style (PSR-12 via Laravel Pint)

Code style is enforced by [Laravel Pint](https://laravel.com/docs/pint). The
configuration in [`pint.json`](pint.json) uses the `laravel` preset (PSR-12
compatible) and additionally requires `declare(strict_types=1);` at the top of
every PHP file.

```bash
make format        # auto-format the codebase
make format-check  # verify formatting without changing files (what CI runs)
```

### Static analysis (PHPStan / larastan)

Static analysis runs through PHPStan with the larastan extension, configured in
[`phpstan.neon.dist`](phpstan.neon.dist) (with `phpstan-baseline.neon` for
pre-existing findings). New code must not introduce new analysis errors.

```bash
make analyse
```

### Conventions

- **Strict types** — every PHP file declares `declare(strict_types=1);`
  (enforced by Pint).
- **PSR-4 autoloading** — source lives under `src/` in the
  `CardTechie\TradingCardApiSdk\` namespace; tests live under `tests/` in the
  `CardTechie\TradingCardApiSdk\Tests\` namespace (see
  [`composer.json`](composer.json)).
- **Laravel package conventions** — the SDK is a Laravel package built on
  `spatie/laravel-package-tools`; follow the existing service-provider, facade,
  and resource patterns.

## Testing Requirements

The test suite uses [Pest](https://pestphp.com/) and lives under `tests/`.

```bash
make test           # run the full suite
make test-coverage  # run with a coverage report
```

Expectations:

- **Add or update tests** for any change in behavior — this is a required item
  on the [pull-request checklist](.github/PULL_REQUEST_TEMPLATE.md).
- New features need tests covering the public surface they add; bug fixes should
  include a regression test.
- Tests must pass locally before you open a PR.

### Pre-commit hooks (optional but recommended)

The repository ships a [`.pre-commit-config.yaml`](.pre-commit-config.yaml) that
runs `php -l`, PHPStan, the Pint format check, and the Pest suite on commit
(plus generic hygiene hooks like trailing-whitespace and merge-conflict checks).
Install [pre-commit](https://pre-commit.com/) and run `pre-commit install` to
enable them.

## Branching & Versioning

The project follows a git-flow-style branching model:

- Create a **feature branch** off `develop`.
- Feature branches merge into `develop` (beta releases are cut from here).
- `release/*` branches stabilize a release.
- `main` holds production releases.

Versions are **tag-managed** — there is intentionally **no `version` field in
`composer.json`**. Do not add one. See
[`docs/VERSION-MANAGEMENT.md`](docs/VERSION-MANAGEMENT.md) for the branch-aware
versioning model and the release process.

## Changelog Fragments

Every PR adds **one** changelog fragment instead of editing the shared
`## [Unreleased]` section of `CHANGELOG.md`. This keeps concurrent PRs from
conflicting on the changelog.

Create a file at:

```text
changelog.d/<num>-<type>.md
```

- `<num>` — the issue number the change closes.
- `<type>` — one of `added`, `changed`, `fixed`, `security`, `removed`,
  `deprecated`.

The fragment body is a single imperative, sentence-case line ending in a period
(an optional single caveat sub-bullet is allowed). For example,
`changelog.d/245-added.md`:

```markdown
- **[Issue #245]** Adopt per-PR `changelog.d/` changelog fragments with a CI presence gate.
```

See [`changelog.d/README.md`](changelog.d/README.md) for the full convention.
Fragments are collated into `CHANGELOG.md` at release time, not per PR.

## Pull Request Process

1. Fork the repository (or branch directly if you have access) and create a
   feature branch off `develop`.
2. Make your changes, adding or updating tests as needed.
3. Add a changelog fragment under `changelog.d/` (see above).
4. Run the full quality gate locally:

   ```bash
   make check     # runs tests + static analysis + format check
   ```

5. Open a pull request against `develop`. Fill out the
   [pull-request template](.github/PULL_REQUEST_TEMPLATE.md) checklist, which
   asks you to confirm:
   - the related issue is linked,
   - tests were added or updated,
   - a changelog fragment is present,
   - `make check` passed locally,
   - no `version` field was added to `composer.json`.
6. Link the issue your PR resolves (e.g. `Closes #123`).

## Code Review Process

- Pull requests are reviewed by the maintainers listed in
  [`CODEOWNERS`](CODEOWNERS).
- CI must be green before a PR can merge. The automated quality gates include
  the tests (`run-tests.yml`), code-quality checks (`code-quality.yml`, covering
  Pint and PHPStan), and the changelog-fragment presence check
  (`changelog-fragment-check.yml`).
- Address review feedback by pushing additional commits to the same branch;
  reviewers will re-review and resolve threads as they are handled.

## Issue Reporting

Use [GitHub Issues](https://github.com/cardtechie/tradingcardapi-sdk-php/issues)
with the appropriate template:

- **Bug report** — include reproduction steps, expected vs. actual behavior, and
  your PHP/Laravel/SDK versions.
- **Feature request** — describe the use case and the desired behavior.

For questions that are not bugs or feature requests, prefer
[GitHub Discussions](https://github.com/cardtechie/tradingcardapi-sdk-php/discussions).

## Security

Please do **not** report security vulnerabilities through public issues. Follow
the project's [Security Policy](../../security/policy) for responsible
disclosure.

---

Thanks again for contributing! 💛
