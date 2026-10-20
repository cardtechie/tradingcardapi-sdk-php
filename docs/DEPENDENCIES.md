# Dependency Scope Reference

This document explains why `laravel/framework` appears in the **runtime**
`packages` array of this repository's `composer.lock`, why that placement makes
Dependabot report some advisories with `scope: runtime` even though installing
this SDK never adds the affected package to a consumer's dependency tree, and
why neither a full `composer update` nor an edit to `composer.json` can change
it.

It exists because the question has already been raised twice during dependency
sweeps ([#353](https://github.com/cardtechie/tradingcardapi-sdk-php/issues/353),
[#359](https://github.com/cardtechie/tradingcardapi-sdk-php/issues/359)) and the
intuitive fix — "run a full `composer update` so the resolver recomputes the
scope" — has been executed and does not work. Read this before opening another
lock-relocation issue.

## Runtime vs dev scope in `composer.lock`

Composer performs **one** dependency resolution over `require` plus
`require-dev`, then splits the single result into two arrays in the lock file:

- `packages` — the runtime set, installed by `composer install --no-dev`.
- `packages-dev` — the additional packages installed only in a dev checkout.

A package lands in `packages` if it is reachable from the `require` graph. It
lands in `packages-dev` only if it is reachable *exclusively* from
`require-dev`. Reachability is what decides placement, and reachability includes
`replace` relationships — which is the whole story here.

Crucially, this split describes **this repository's own lock file**, which is a
development artifact. Consumers of a library never read a library's lock file;
they resolve against its `composer.json`. See
[What this means for consumers](#what-this-means-for-consumers).

## Why `laravel/framework` is runtime-scoped

`composer.json`'s `require` block contains a genuine runtime requirement on
`illuminate/contracts`:

```json
"require": {
    "php": "^8.2",
    "guzzlehttp/guzzle": "^7.5.0",
    "illuminate/contracts": "^10.0|^11.0|^12.0",
    "spatie/laravel-package-tools": "^1.13.0"
}
```

`laravel/framework` declares a `replace` block covering 37 sub-split packages,
including:

```json
"replace": {
    "illuminate/contracts": "self.version"
}
```

Because the dev toolchain (`orchestra/testbench`, `orchestra/workbench`,
`pestphp/pest-plugin-laravel`) pulls the monolithic `laravel/framework` into the
resolution, composer satisfies the **runtime** `illuminate/contracts`
requirement with that replacement rather than installing the standalone
`illuminate/contracts` package. Verifiable in the current lock file:

- `laravel/framework` is present in `packages`, absent from `packages-dev`.
- `illuminate/contracts` is present in **neither** array — nothing installs it
  standalone.
- `vendor/illuminate/` does not exist; `vendor/laravel/framework/` does.

`laravel/framework` therefore satisfies a runtime requirement, and composer
places it in the runtime array by its own rules. It is **not** misfiled, and the
lock file is not stale.

Everything `laravel/framework` itself requires — `league/commonmark`,
`nesbot/carbon`, `symfony/*`, and the rest — inherits runtime scope by the same
reachability rule.

## Why the obvious fixes do not work

Both proposed remedies were executed in a clean room and both produced no
relocation.

### Experiment 1 — a full `composer update`

Resolving this repository's unmodified `composer.json` from scratch resolves to
a **newer** `laravel/framework` (v12.69.1 at the time of writing, versus
v12.64.0 in the committed lock) and still yields 74 runtime packages / 76 dev
packages, with `laravel/framework` in the runtime array and `league/commonmark`
runtime-scoped. A full update recomputes versions, not scope placement — the
placement was already correct.

### Experiment 2 — removing `illuminate/contracts` from `require`

Resolving again with `illuminate/contracts` deleted from `require` entirely
returns a runtime array that is **identical in both package names and versions**
— still 74 runtime / 76 dev, still `laravel/framework` in `packages`.

The reason is `spatie/laravel-package-tools`, a genuine and non-negotiable
runtime dependency of this SDK, which itself requires:

```json
"require": {
    "illuminate/contracts": "^10.0|^11.0|^12.0|^13.0",
    "php": "^8.1"
}
```

That transitive runtime requirement is replaced by `laravel/framework` exactly
as the direct one was. The placement is unreachable from this repository: it
would only change if `spatie/laravel-package-tools` dropped its
`illuminate/contracts` dependency (upstream, outside our control), or if the dev
toolchain stopped pulling `laravel/framework` (impossible — `orchestra/testbench`
*is* the test harness).

### Reproducing these results

Do **not** run `composer update` against the real `composer.lock` to check this.
Reproduce in a throwaway directory instead:

```bash
# Experiment 1: unmodified composer.json
mkdir -p /tmp/scope-check && cp composer.json /tmp/scope-check/
docker run --rm -v /tmp/scope-check:/app -w /app composer:2 \
  composer update --no-install --no-scripts

# Inspect which array holds laravel/framework
python3 -c "
import json
l = json.load(open('/tmp/scope-check/composer.lock'))
print('runtime:', len(l['packages']), 'dev:', len(l['packages-dev']))
print('laravel/framework runtime:',
      'laravel/framework' in [p['name'] for p in l['packages']])
"
```

For experiment 2, repeat with `illuminate/contracts` removed from the copied
`composer.json`'s `require` block.

## What this means for consumers

Nothing. Published consumers install this SDK from Packagist and resolve against
its `composer.json`, never against this repository's lock file:

- A **Laravel application** already supplies its own `laravel/framework`, which
  satisfies the `illuminate/contracts` constraint via the same `replace`.
- A **non-Laravel application** installs the small standalone
  `illuminate/contracts` package, which pulls in no framework and no
  `league/commonmark`.

Installing the SDK therefore adds no framework-transitive package —
`league/commonmark`, `symfony/console`, or any other — to a consumer's
dependency tree. A Laravel application does install those packages, but it
installs them through its own `laravel/framework` requirement, exactly as it
would without this SDK. The runtime blast radius that this repository's lock
file appears to describe is a property of the SDK's own development
environment, not something the SDK adds to any consumer's install.

## Triaging Dependabot advisories

When Dependabot reports an advisory against this repository with
`scope: runtime`:

1. **Check whether the package is reachable only through `laravel/framework`.**
   If it is (`league/commonmark`, `nesbot/carbon`, `symfony/*`, `monolog/*`, and
   the rest of the framework's tree), the `runtime` label is a reporting
   artifact of the `replace` mechanism described above — not a signal that
   consumers are exposed.
2. **Assess it against the dev toolchain instead.** The real exposure is CI and
   local development: does the advisory affect code paths exercised by the test
   suite or the build? That is the question worth answering.
3. **Still take the update.** Keeping the lock current is cheap and closes the
   alert; the SDK's own dependency sweeps do exactly this. Just size the urgency
   by the dev-toolchain impact, not by the `runtime` label.
4. **Do not open a lock-relocation issue.** Moving `laravel/framework` to
   `packages-dev` is not achievable from this repository — see
   [Why the obvious fixes do not work](#why-the-obvious-fixes-do-not-work).

Packages in `require` that are genuinely consumer-facing —
`guzzlehttp/guzzle`, `spatie/laravel-package-tools`, and their own transitive
dependencies — are unaffected by any of this and should be triaged as ordinary
runtime advisories.
