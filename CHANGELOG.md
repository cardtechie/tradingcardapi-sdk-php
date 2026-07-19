# Changelog

All notable changes to `Trading Card API PHP SDK` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

<!-- New entries go to changelog.d/<issue>-<type>.md fragments, not here. At
release time those fragments are collated into a versioned section. That
collation is manual today — make changelog-update does not read changelog.d/
fragments yet. See changelog.d/README.md. -->

## [0.3.0] - 2026-07-18

### Added

- **[Issue #155]** Set names now include a hobby-convention serial suffix (`/X`, `1/1`); documented how to read the raw `serial` value versus the formatted `name`.
- **[Issue #325]** Add an automated regression test harness for `build/version.sh` version derivation.
- **[Issue #329]** Add a one-way workflow that back-merges `main` into `develop`, opening a PR on conflict. Replaces the bidirectional sync; `develop` → `main` promotion returns to the manual release/PR process.
- **[Issue #332]** Add `page`/`per_page`/`format` passthrough to `Set::checklist()` and a new `Set::checklistV2()` method (with a `ChecklistV2Response` DTO) exposing the richer V2 checklist endpoint with `include` support.

### Changed

- **[Issue #293]** Verify the `windows-latest` CI test leg passes with the broad extension setup.

### Fixed

- **[Issue #186]** Derive the `main`-branch release version in `build/version.sh` from the latest `CHANGELOG.md` section instead of always incrementing the patch, so minor/major releases tag correctly (falls back to patch-increment when the CHANGELOG cannot be parsed).
- **[Issue #297]** Run the pre-PR gate's docker commands as the host user with writable `HOME`/`COMPOSER_HOME` so they no longer leave root-owned `vendor/` in the bind-mounted workspace.
- **[Issue #331]** Switch every resource's `update()` from PUT to PATCH so updates stop returning HTTP 405.
- **[Issue #333]** Repoint Team and Player `listDeleted()`/`deleted()` off the non-existent `/deleted` routes onto `?filter[status]=deleted` and `?include_trashed=true`, fixing 404s.

## [0.2.26] - 2026-06-30

### Changed

- **[Issue #303]** Document that top-level meta/links describe the whole response and attach to the main parsed object only; included relationship models intentionally do not carry them.

## [0.2.25] - 2026-06-29

### Changed

- **[Issue #288]** Type the SDK's array generics and ratchet PHPStan from level 5 to level 6.
- Bump `guzzlehttp/guzzle` from 7.12.1 to 7.12.3.
- Bump `shivammathur/setup-php` from 2.37.1 to 2.37.2.

## [0.2.24] - 2026-06-29

### Added

- **[Issue #309]** Add a markdown-linting gate to CI with a shared `.markdownlint.jsonc` config and `make lint-md` / `make fix-md` helpers.

## [0.2.23] - 2026-06-29

### Changed

- **[Issue #307]** Scope the code-quality coverage job to pull requests and develop pushes so it no longer re-runs on main merges.

## [0.2.22] - 2026-06-28

### Fixed

- **[Issue #289]** Attach top-level meta/links to the main object in the non-static `Response` constructor path so `getMeta()`/`getLinks()` is consistent across both parsing entrypoints.

## [0.2.21] - 2026-06-28

### Security

- **[Issue #94]** Add a `SECURITY.md` policy documenting private vulnerability reporting, response and disclosure timelines, and the supported-versions policy.

## [0.2.20] - 2026-06-28

### Added

- **[Issue #93]** Add a root `CONTRIBUTING.md` covering setup, coding standards, testing, and the PR and changelog-fragment process.

## [0.2.19] - 2026-06-28

### Changed

- **[Issue #280]** Derive the sync workflow's conflict-PR branch name and title from the resolved source/target branches for cross-repo portability.

## [0.2.18] - 2026-06-28

### Added

- **[Issue #154]** Document the `serial` property on the `Set` model docblock for IDE autocomplete.

## [0.2.17] - 2026-06-28

### Changed

- **[Issue #107]** De-duplicate the post-merge test matrix on `main`, add Composer caching, and cancel superseded CI runs.

## [0.2.16] - 2026-06-28

### Changed

- **[Issue #286]** Migrate README examples from the deprecated `getList()` to `all()` and document the `RateLimitException` constructor realignment.

## [0.2.15] - 2026-06-28

### Added

- **[Issue #277]** Implement `Playerteam::lookup()` as find-or-create against the `/v1/playerteams` API resource.

### Fixed

- **[Issue #278]** Validate empty JSON:API collection responses instead of rejecting them.

## [0.2.14] - 2026-06-27

### Changed

- **[Issue #284]** Restrict the opt-in retry middleware to idempotent HTTP methods by default (set `retry.retry_non_idempotent => true` to retry POST/PATCH).

## [0.2.13] - 2026-06-27

### Added

- **[Issue #242]** Add a workspace-safe `.claude/pre-pr-gate.md` declaring the repo's pre-PR test and lint gate as one-off `docker run` commands.

### Changed

- **[Issue #263]** Correct overstated README badges and marketing claims, and add missing community-health files (Code of Conduct, Support, PR template, CODEOWNERS, issue forms).

## [0.2.12] - 2026-06-27

### ⚠️ Breaking Changes

- **[Issue #258]** Resource responses are now normalized into typed DTOs; code reading raw object properties off these responses must switch to the DTO accessors.
- **[Issue #259]** `RateLimitException`'s constructor was realigned with its base class; callers using positional constructor args must switch to named args.

### Changed

- **[Issue #257]** Tighten `composer.json` packaging metadata (dev-only factory autoload, stable minimum-stability, support block, keywords, dev-develop branch alias).
- **[Issue #258]** Normalize resource responses into predictable, self-documenting typed DTOs. (See Breaking Changes.)
- **[Issue #259]** Standardize resource method signatures: a uniform `list()` paginator plus an `all()` raw-Collection accessor, consistent `create()`/`update()` arity, and filled-in `delete()` verbs. (See Breaking Changes and Deprecated.)
- **[Issue #260]** Add `@property` coverage to all models, convert the request trait and exception hierarchy to native typed properties, and guard `json_encode` false-returns.
- **[Issue #262]** Harden release CI: migrate off the archived `actions/create-release`, run the PHP×Laravel matrix on `develop` PRs, and restrict dependabot auto-merge to semver-patch updates.

### Deprecated

- **[Issue #259]** `getList()` is deprecated in favor of `all()`; it still works and delegates to `all()`, so migrate at your convenience — callers are not broken.

### Fixed

- **[Issue #261]** Player getters now propagate API/network exceptions instead of masking failures as empty collections, and `Model::__call` throws `BadMethodCallException` on unknown methods. Callers that relied on an empty collection on failure should wrap calls in try/catch.

## [0.2.11] - 2026-06-27

### Changed

- **[Issue #256]** Declare `strict_types=1` across the SDK and enforce it via a committed `pint.json` rule.

## [0.2.10] - 2026-06-27

### ⚠️ Breaking Changes

- **[Issue #255]** `Set::workflow($id)` is removed; use `$api->internal()->workflow()->getForSet($id)` with `internal`-scoped credentials.

### Changed

- **[Issue #255]** Move the per-set workflow read onto `Internal\Resources\Workflow::getForSet()`, completing the public/internal separation.

## [0.2.9] - 2026-06-27

### Changed

- **[Issue #254]** Complete `.gitattributes` export-ignore so `composer require` ships only runtime assets, not dev/CI/infra files.

## [0.2.8] - 2026-06-27

### Fixed

- **[Issue #253]** Fix `Response` meta/links bleeding across separate parses by attaching them to each parsed result instead of shared static state.

## [0.2.7] - 2026-06-27

### Added

- **[Issue #252]** Add default HTTP timeouts and an opt-in retry/backoff middleware honoring `Retry-After` for 429/5xx (enable via `TRADINGCARDAPI_RETRY_ENABLED`).

## [0.2.6] - 2026-06-27

### ⚠️ Breaking Changes

- **[Issue #214]** `TradingCardApi::workflow()` and `TradingCardApi::auditLog()` are removed; use `$api->internal()->workflow()` and `$api->internal()->auditLog()`. Credentials must carry the `internal` OAuth scope.

### Added

- **[Issue #100]** Add a maintainer release runbook (`docs/RELEASING.md`) and verify the build-release / Packagist automation end-to-end.
- **[Issue #200]** Add `AuditLogSchema` to enable response validation for audit log endpoints.
- **[Issue #210]** Add an `agent_id` filter param to audit log queries.
- **[Issue #212]** Add a CI guardrail enforcing SHA-pinned GitHub Actions references, and pin all existing workflow actions.
- **[Issue #233]** Add `WorkflowSchema` and `SetTodoSchema` and wire the workflow/set-todos endpoints into response validation.
- **[Issue #245]** Adopt per-PR `changelog.d/` changelog fragments with a CI presence gate, ending `## [Unreleased]` merge conflicts.
- **[Issue #248]** Add a GitHub Actions workflow that auto-syncs `develop` and `main`. (Superseded in 0.3.0 by #329.)

### Changed

- **[Issue #203]** Direct Claude to create GitHub issues via the `create_cross_repo_issues` MCP tool instead of `gh issue create`.
- **[Issue #214]** Move workflow, set-todo, and audit-log resources into the `Internal\` namespace behind a new `internal()` accessor. (See Breaking Changes.)
- **[Issue #244]** Repoint genre `listDeleted()` and `deleted($id)` off the deprecated v1 literal-segment routes onto the JSON:API query-parameter endpoints.

### Fixed

- **[Issue #201]** Unify the memory assertion threshold in `ValidationPerformanceTest` to prevent false failures in Docker.
- **[Issue #237]** Quote redirect targets, drop the undefined `inputs.claude_api_key` branch, and consolidate step-summary writes in the build-release workflow to clear shellcheck/actionlint findings.
- **[Issue #246]** Resolve the Set genre relationship via the JSON:API include linkage instead of the removed flat `genre_id` attribute, so `Set::genre()` works again.

### Security

- **[Issue #238]** Pass `github.head_ref`/`github.base_ref` through a step-level `env:` block in the release-validation workflow to close a script-injection vector.
- **[Issue #251]** Redact Authorization headers, OAuth `client_secret`, and `access_token` from exception context and logs, and stop serializing raw auth response bodies.

## [0.2.5] - 2026-04-14

### Changed

- Bump `actions/github-script` from 7 to 9 (#197).
- Bump `dependabot/fetch-metadata` from 2 to 3 (#196).

## [0.2.4] - 2026-04-14

### Added

- Audit log resource for listing, filtering, and creating audit events (#195)

## [0.2.3] - 2026-04-13

### Added

- Review status support for the Workflow resource with enums and convenience helpers (#188)

## [0.2.2] - 2026-04-11

### Security

- Updated `league/commonmark` from 2.8.1 to 2.8.2 to address moderate-severity embed extension `allowed_domains` bypass (#192)

### Changed

- Updated `ramsey/composer-install` GitHub Action from v3 to v4 in CI workflows (#192)

## [0.2.1] - 2026-04-11

### Added

- Auto-assign sprint workflow to assign new issues to the current sprint iteration on the org project board (#190)

## [0.2.0] - 2026-03-22

### Added

- `Workflow` resource with `actionableSets()` method for `GET /v1/workflow/actionable-sets` endpoint (#167)
- `workflow()` method to `Set` resource for `GET /v1/sets/{id}/workflow` endpoint (#166)
- `updateSetTodo(string $todoId, array $attributes)` method to `Workflow` resource for `PATCH /v1/set-todos/{id}` endpoint (#179)
- `bulkInitializeWorkflow(array $params = [])` method to `Workflow` resource for `POST /v1/workflow/bulk-initialize` endpoint (#179)
- `getBulkInitializeStatus(string $jobId)` method to `Workflow` resource for `GET /v1/workflow/bulk-initialize/{job_id}` endpoint (#179)
- `getSetTodos(string $setId)` method to `Workflow` resource for `GET /v1/workflow/sets/{id}/todos` endpoint (#181)

### Changed

- Centralized token cache key derivation: extracted `buildTokenCacheKey()` static method on the `ApiRequest` trait and updated all test files to use a shared `tokenCacheKey()` helper (#171)
- Updated `phpunit/phpunit` constraint to include v11 and v12 (`^10.0|^11.0|^12.0`)
- Updated `pestphp/pest` constraint to include v3 (`^2.0|^3.0`)
- Updated `pestphp/pest-plugin-laravel` constraint to include v3 (`^2.0|^3.0`)
- Upgraded Docker development environment from PHP 8.1 to PHP 8.4
- Updated GitHub Actions `actions/checkout` from v5 to v6
- Updated `stefanzweifel/git-auto-commit-action` from v6 to v7
- Ran `composer update` to refresh all dependency lock file entries (PHPUnit 11.5.50, Pest 3.8.6, Larastan 3.9.3, PHPStan 2.1.40, Laravel 12)
- Bumped minimum PHP requirement from 8.1 to 8.2 (PHP 8.1 reached EOL in November 2024 and Pest/paratest dropped 8.1 support)

### Fixed

- Sub-resource endpoints (`/v1/sets/{id}/workflow`, `/v1/sets/{id}/checklist`, etc.) no longer trigger JSON:API validation — `extractResourceType()` now returns `null` for paths with 3+ segments after the version prefix, preventing `ValidationException` in `strict_mode: true` (#170)
- `setAuthInfo()` auth plumbing now wired into `retrieveToken()`: PAT tokens used directly (no OAuth request), instance OAuth credentials respected over config, `$scope` stored and included in cache key to prevent scope collisions (#169)

## [0.1.18] - 2026-01-22

### Added

- `ConflictException` for HTTP 409 responses with `duplicate()` factory method

## [0.1.17] - 2026-01-03

### Fixed

- **SetSource JSON:API type correction** - Fixed request type from `set-sources` to `set_sources` to match API expectations (Issue #158)

## [0.1.16] - 2026-01-02

### Fixed

- **Subsets type handling** - Fixed Response class to handle plural `subsets` type from API responses, preventing "Unknown model type" errors on subsets view

## [0.1.15] - 2025-12-31

### Added

- **SetSource Resource Support** - New resource for managing set data sources (Issue #156)
  - Added `SetSource` model with `set()` relationship method
  - Added `SetSource` resource with full CRUD operations: `get()`, `list()`, `create()`, `update()`, `delete()`
  - Added `forSet($setId)` method to retrieve all sources for a specific set
  - Added `SetSourceSchema` for API response validation
  - Added `sources()` relationship method to `Set` model
  - Added `setSource()` accessor method to `TradingCardApi` class
  - Set sources track where checklist data, metadata, and images come from (e.g., Beckett, TCDB, CardboardConnection)

### Changed

- **Response Type Normalization** - Improved handling of hyphenated API types
  - Added `normalizeType()` method to `Response` class for consistent type-to-class mapping
  - Supports hyphenated types like `set-sources` converting to `SetSource` model class
  - Consolidated special type handling (parentset, subset, checklist) into single method
  - Added `ALLOWED_MODEL_TYPES` whitelist for security validation

## [0.1.14] - 2025-12-20

### Added

- **is_variation Support for Set Model** - New boolean field to distinguish variations from parallels (Issue #151)
  - Added `is_variation` property to Set model for API response handling
  - Added validation rules in SetSchema for single and collection responses
  - Variations are sets that share card numbers with base sets but have different visual treatments (e.g., Tin Type, Chrome)

## [0.1.13] - 2025-12-13

### Added

- **OnCardable Trait for Player and Team Models** - Enables independent oncard relationships (Issue #148)
  - Added `OnCardable` trait to `Player` model with `onCardable()` and `prepare()` methods
  - Added `OnCardable` trait to `Team` model with `onCardable()` and `prepare()` methods
  - Allows cards to have direct player-only or team-only associations without requiring a Playerteam relationship
  - Supports both UUID and name-based lookups in `prepare()` method

## [0.1.12] - 2025-12-01

### Added

- **Stats Endpoint Support** - New methods for entity count tracking and analytics (Issue #144)
  - `Stats::getCounts()` - Get current counts for all entity types (total, published, draft, archived)
  - `Stats::getSnapshots(array $filters = [])` - Get historical snapshots with date range filtering
  - `Stats::getGrowth(string $period = '7d')` - Get growth metrics (daily/weekly/monthly changes)
  - New DTOs for type-safe responses:
    - `CountsResponse` with `EntityCount` objects
    - `SnapshotsResponse` with `Snapshot` objects
    - `GrowthResponse` with `GrowthMetric` objects
  - Helper methods `getByEntityType()` for easy access to specific entity metrics

## [0.1.11] - 2025-11-30

### Fixed

- **Content-Type Header for Mutating Requests** - Fixed JSON:API Content-Type header for POST/PUT/PATCH requests
  - Changed `ApiRequest` trait to send `Content-Type: application/vnd.api+json` for mutating requests (POST, PUT, PATCH)
  - Previously sent `application/json` causing 415 Unsupported Media Type errors
  - GET and DELETE requests no longer send Content-Type header (not needed for requests without body)
  - Custom Content-Type headers can still override the default if needed
  - Fixes issue #139 - Resolves admin application Dusk test failures

## [0.1.10] - 2025-10-30

### Added

- **OAuth Scope Configuration Support** - Configurable OAuth scopes for fine-grained API access control
  - Added `scope` configuration option to `config/tradingcardapi.php`
  - Default scope: `read:published` for backwards compatibility
  - Supports space-separated multiple scopes (e.g., `read:all-status write delete`)
  - Environment variable: `TRADINGCARDAPI_SCOPE`
  - Updated `ApiRequest::retrieveToken()` to request configured scopes instead of empty string

### Enhanced

- **OAuth Token Authentication** - Modified OAuth token request to use configured scopes
  - Changed `src/Resources/Traits/ApiRequest.php:120` from hardcoded empty scope to configurable scope
  - Enables write operations, delete operations, and access to draft/archived content
  - Unblocks admin applications requiring elevated permissions

### Documentation

- Added OAuth Scopes section to README.md with comprehensive examples
- Documented all available scopes and their purposes
- Provided configuration examples for different use cases (read-only, admin, content management)

## [0.1.9] - 2025-09-28

### Added

- **Complete Year Parent/Child Relationship Support** - Full hierarchical year functionality
  - `Year::parent()` method for retrieving parent year relationship
  - `Year::children()` method for retrieving child year relationships
  - `Year::hasParent()` helper method for checking parent existence
  - `Year::hasChildren()` helper method for checking child existence
  - `Year::getDisplayName()` method for consistent display across applications
  - `Year::listParents()` resource method for filtering parent years
  - `Year::listChildren($parentId)` resource method for filtering child years

### Enhanced

- **YearSchema Field Mapping** - Resolved field mapping inconsistencies for admin integration
  - Added `name` field validation to support database schema requirements
  - Added `parent_year` field validation for relationship functionality
  - Added `YearSchema::getCollectionRules()` method for bulk operation validation
  - Maintained backward compatibility with existing `year` and `description` fields

### Fixed

- **Year Resource Pagination Crash** - Added defensive handling for missing meta property
  - Fixed division by zero error when API response lacks pagination metadata
  - Added fallback pagination values using request params and response data
  - Applied consistent pagination handling matching other SDK resources
- Year resource integration gaps preventing admin interface migration
- Field mapping inconsistencies between API database and SDK schema
- Missing validation rules for Year parent relationships

## [0.1.8] - 2025-09-28

### Fixed

- Manufacturer resource pagination crash when API response missing meta property
- Added defensive handling for missing pagination metadata in Manufacturer::list() method
- Added collection validation rules for Manufacturer schema to support array responses
- Fixed ManufacturerSchema validation failing on collection endpoints

### Added

- ManufacturerSchema::getCollectionRules() method for proper collection response validation
- Enhanced defensive pagination handling with multi-level isset() checks

## [0.1.7] - 2025-09-28

### Fixed

- Brand resource pagination crash when API response missing meta property
- Added defensive handling for missing pagination metadata in Brand::list() method

## [0.1.6] - 2025-09-27

### Added

- Complete Team resource CRUD operations (get, update, delete, list, listDeleted, deleted)

### Fixed

- API pagination handling when meta property is missing

## [0.1.5] - 2025-09-27

### Added

- Enhanced create() method for Team resource with relationship support

## [0.1.4] - 2025-09-27

### Fixed

- **Packagist Publishing Issues** - Removed hardcoded version from composer.json to prevent webhook failures
  - Eliminates 403 errors when publishing to Packagist
  - Follows industry standard practice used by Laravel, Guzzle, and Spatie packages
  - Ensures automatic version detection through git tags

### Added

- **Release Management Documentation** - Comprehensive guide for future releases
  - Documents proper release process to prevent Packagist issues
  - Explains why hardcoded versions cause publishing problems
  - Provides step-by-step release workflow

## [0.1.3] - 2025-09-27

### Added

- **Complete Player Resource Support** - Full CRUD operations for Player entities
  - `TradingCardApiSdk::player()->get($id)` - Get player by ID
  - `TradingCardApiSdk::player()->list($params)` - List players with pagination
  - `TradingCardApiSdk::player()->create($data)` - Create new players
  - `TradingCardApiSdk::player()->update($id, $data)` - Update existing players
  - `TradingCardApiSdk::player()->delete($id)` - Delete players
  - `TradingCardApiSdk::player()->listDeleted()` - List deleted players
  - `TradingCardApiSdk::player()->deleted($id)` - Get deleted player by ID

- **Player Model Relationships** - Access related data through Player models
  - `$player->getParent()` - Get parent player (for aliases)
  - `$player->getAliases()` - Get all alias players
  - `$player->getTeams()` - Get associated teams
  - `$player->getPlayerteams()` - Get playerteam relationships
  - `$player->getCards()` - Get all cards featuring this player
  - `$player->isAlias()` - Check if player is an alias
  - `$player->hasAliases()` - Check if player has aliases

- **Enhanced Player Model Attributes**
  - `$player->full_name` - Automatically formatted full name
  - `$player->last_name_first` - Last name first format for display

### Enhanced

- **Improved Response Validation** - Better handling of API responses and error detection
- **Enhanced Error Handling** - Graceful fallbacks when API calls fail

## [0.1.2] - 2025-09-21

### Changed

- Replaced automated changelog updates with manual PR validation requirement

## [0.1.1] - 2025-09-21

### Added

- Added missing `list()` method to Card resource for paginated card listings

## [0.1.0] - 2025-09-15

### Added

- Initial stable release of Trading Card API PHP SDK
- Complete SDK implementation with all core Trading Card API endpoints
- Enhanced error handling with specific exception classes
  - Base TradingCardApiException with common properties and methods
  - AuthenticationException for 401 authentication failures
  - AuthorizationException for 403 permission errors
  - ValidationException for 422 validation errors with field-level details
  - RateLimitException for 429 rate limiting with timing information
  - ResourceNotFoundException with specific subclasses (CardNotFoundException, PlayerNotFoundException, SetNotFoundException)
  - NetworkException for connection and network-related errors
  - ServerException for 5xx server errors
- ErrorResponseParser service for intelligent error response parsing
- Comprehensive test coverage for all exception scenarios
- Full PHPStan compliance and Laravel Pint code styling
- Support for Laravel 10.x, 11.x, and 12.x (actively maintained versions)
- Complete API resource implementations (Cards, Players, Sets, Teams, etc.)
- Response validation and schema handling
- Professional documentation and error handling examples
- Support for PHP 8.1, 8.2, 8.3, and 8.4
- Comprehensive GitHub Actions workflows for CI/CD
- Automated testing across multiple PHP and Laravel versions

### Changed

- Updated build system to match API repository's sophisticated process
- Enhanced version management with PHP/Composer integration
- Improved release automation and documentation generation
- Removed Laravel 9.x support (end of life February 2024)
- Updated GitHub Actions to latest compatible versions

### Fixed

- Version script compatibility issues with no git tags scenario
- Changelog formatting and markdown linting compliance
- Code quality and styling issues for production readiness
- Test matrix compatibility issues with Laravel 11+ and prefer-lowest strategy
- PHPStan static analysis errors in ErrorResponseParser

[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.3.0...HEAD
[0.3.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.26...0.3.0
[0.2.26]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.25...0.2.26
[0.2.25]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.24...0.2.25
[0.2.24]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.23...0.2.24
[0.2.23]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.22...0.2.23
[0.2.22]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.21...0.2.22
[0.2.21]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.20...0.2.21
[0.2.20]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.19...0.2.20
[0.2.19]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.18...0.2.19
[0.2.18]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.17...0.2.18
[0.2.17]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.16...0.2.17
[0.2.16]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.15...0.2.16
[0.2.15]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.14...0.2.15
[0.2.14]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.13...0.2.14
[0.2.13]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.12...0.2.13
[0.2.12]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.11...0.2.12
[0.2.11]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.10...0.2.11
[0.2.10]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.9...0.2.10
[0.2.9]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.8...0.2.9
[0.2.8]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.7...0.2.8
[0.2.7]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.6...0.2.7
[0.2.6]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.5...0.2.6
[0.2.5]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.4...0.2.5
[0.2.4]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.18...0.2.0
[0.1.18]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.17...0.1.18
[0.1.17]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.16...0.1.17
[0.1.16]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.15...0.1.16
[0.1.15]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.14...0.1.15
[0.1.14]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.13...0.1.14
[0.1.13]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.12...0.1.13
[0.1.12]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.11...0.1.12
[0.1.11]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.10...0.1.11
[0.1.10]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.9...0.1.10
[0.1.9]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.8...0.1.9
[0.1.8]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.7...0.1.8
[0.1.7]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.6...0.1.7
[0.1.6]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/0.1.0
