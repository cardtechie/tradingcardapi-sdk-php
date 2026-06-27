# Pre-PR Gate

This file declares the pre-PR test and lint gate for this repository. It is
consumed by the picklewagon-mcp runner's `/work-issue` Step 9, which runs
**exactly** the commands inside the single fenced `bash` block below, in order.
Every other line in this file (this preamble, the headings, the prose, and the
fence markers themselves) is documentation and is **not** executed.

## Why one-off `docker run` (not `make test`)

This repo's `make test` runs `docker compose exec dev composer test` against the
long-running shared-name `dev` container. That container bind-mounts the
operator's daily-work clone (`docker-compose.yaml` mounts `.:/var/www/app`), not
the runner's dispatched workspace clone — so `make test` would test the wrong
code. The gate therefore uses **one-off `docker run` commands bound to `$WS`**
(the runner workspace CWD), against a deterministically tagged image the gate
builds itself. This is the workspace-safe form required by the runner.

This is an SDK package (Testbench-bootstrapped), not an app: there is no
`.env` / `.env.local`, and tests bootstrap via `vendor/autoload.php`. The gate
therefore **omits** the `--env-file` flag the canonical app recipe uses —
passing a non-existent env file would hard-fail the gate.

## The gate

```bash
WS="$(pwd)"
docker build -t tradingcardapi-sdk-php:gate .
docker run --rm -v "$WS":/var/www/app:cached -w /var/www/app tradingcardapi-sdk-php:gate composer install --no-interaction --prefer-dist
docker run --rm -v "$WS":/var/www/app:cached -w /var/www/app tradingcardapi-sdk-php:gate ./vendor/bin/pest
docker run --rm -v "$WS":/var/www/app:cached -w /var/www/app tradingcardapi-sdk-php:gate ./vendor/bin/pint --test
```

## Guardrails

- **Start the Docker daemon if it is down** and run the gate; do not skip it. An
  un-runnable local gate must be resolved, not waved through. Only if Docker is
  genuinely unavailable on the host may the gate be deferred to CI — and that
  must be narrated as a real, named fallback, never as "PHP isn't here."
- **Never run `docker compose down`** — it kills the operator's running dev
  environment.
- **Never `make up` / `docker compose exec` against the shared-name `dev`
  container** to run the gate; it bind-mounts the operator's daily-work clone,
  not the runner workspace. Use the one-off `docker run` commands above, which
  build the image build-only (`docker build`) and never touch the operator's
  running container.
