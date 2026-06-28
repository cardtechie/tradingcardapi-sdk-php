# Security Policy

The Trading Card API SDK for PHP (`cardtechie/tradingcardapi-sdk-php`) is a
published Composer package. We take the security of the SDK seriously and
appreciate responsible disclosure of any vulnerabilities you find.

## Reporting a vulnerability

Please do **not** report security vulnerabilities through public GitHub issues,
discussions, or pull requests.

Instead, use one of these private channels:

- **GitHub Private Vulnerability Reporting (preferred).** Open the repository's
  [**Security** tab](https://github.com/cardtechie/tradingcardapi-sdk-php/security)
  and click **Report a vulnerability**. This opens a private advisory visible
  only to you and the maintainers.
- **Email.** If you cannot use GitHub's reporting flow, email
  [security@cardtechie.com](mailto:security@cardtechie.com).

When reporting, please include as much of the following as you can:

- A description of the vulnerability and its potential impact.
- Steps to reproduce, or a proof-of-concept.
- The affected SDK version(s) and PHP version.
- Any suggested remediation, if you have one.

## Response times

We aim to respond promptly to every report:

- **Acknowledgement** within **3 business days** of receiving your report.
- **Initial assessment** (severity and a remediation plan) within
  **7 business days**.

If you do not receive an acknowledgement within 3 business days, please follow
up via the email channel above in case the report was missed.

## Coordinated disclosure

We follow a coordinated-disclosure process:

1. We confirm the vulnerability and determine the affected versions.
2. We develop and test a fix, and prepare a GitHub security advisory.
3. We release the fix and publish the advisory, crediting you for the report
   unless you prefer to remain anonymous.

Please keep the details of any vulnerability private until a fix has been
released. We ask for up to **90 days** to ship a fix before public disclosure
and will keep you informed of our progress throughout.

## Supported versions

The SDK is pre-1.0, so only the latest released minor line receives security
updates. The current supported line is **`0.2.x`** — always use its latest
patch release. Older minor lines do not receive security fixes — please
upgrade to the latest release to stay supported.

| Version | Supported          |
| ------- | ------------------ |
| 0.2.x (latest) | :white_check_mark: |
| < 0.2.0 | :x:                |

The SDK requires PHP `^8.2`. Security updates are published to
[Packagist](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)
and tagged in this repository.

## Scope

This policy covers the `cardtechie/tradingcardapi-sdk-php` package only. It does
**not** cover the upstream Trading Card API service itself; please report issues
in that service through its own channels.

For all non-security support, see [SUPPORT.md](SUPPORT.md).
