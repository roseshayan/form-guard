# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and the project follows Semantic Versioning after the first stable release.

## [Unreleased]

## [1.0.2] - 2026-08-30

### Added

- Guarded GitHub Actions release workflow that validates the exact `main` commit before creating any stable tag.
- Production archive smoke test that verifies `Validator`, Composer autoloading, and representative Iranian rules from an exported `--no-dev` package.
- PHPUnit/Xdebug coverage generation with an 85% statement-coverage quality gate.
- PHP-CS-Fixer code-style checks and local `composer lint` / `composer fix` commands.
- Roave Backward Compatibility Check in an isolated PHP 8.5 CI job.

### Changed

- `composer check` now includes code-style validation in addition to tests and PHPStan.
- Release documentation now uses the guarded Actions workflow instead of manual stable tagging.
- Development-only tooling is excluded from exported source archives.

## [1.0.1] - 2026-08-29

### Fixed

- Published the intended production-ready code after `v1.0.0` had mistakenly been tagged against a pre-merge commit. The immutable `v1.0.0` tag was left untouched and the corrected package was released as `v1.0.1`.

## [1.0.0] - 2026-08-29

### Added

- `Validator` as the primary public entry point while retaining `FormValidator` compatibility.
- Nested dot-notation and wildcard validation.
- `ErrorBag` with per-field and aggregate error access.
- `ValidationException` and `validateOrFail()`.
- Strict `InvalidRuleException` handling for unknown/malformed rules.
- Custom messages and human-readable attribute names.
- Inline callable validation rules without global mutable registries.
- Presence, type, format, size, comparison, date, JSON, IP, UUID, and flow-control rules.
- Dedicated Iranian validation rules for mobile, landline, generic phone, natural-person national code, legal-entity national ID, postal code, Sheba/IBAN, and bank card numbers.
- Persian and Arabic-Indic digit normalization inside Iranian rules without mutating validated input.
- Aliases `ir_company_id`, `ir_iban`, and `ir_bank_card_number` for discoverable Iranian APIs.
- Uploaded file abstraction with MIME detection, image, size, MIME, and extension rules.
- PHPUnit coverage for core, built-in, Iranian, nested, wildcard, custom, and file behavior.
- PHPStan static analysis.
- GitHub Actions CI for PHP 8.2 through 8.5.
- Complete README, rule reference, Iranian validation guide, examples, security policy, and contributing guide.
- Packagist-first release instructions with stable-tag immutability guidance.

### Changed

- Minimum PHP version from 8.1 to 8.2 because PHP 8.1 is end-of-life.
- Validation no longer mutates or HTML-escapes input.
- `validated()` now returns only rule-whitelisted data and throws after failed validation.
- Rule evaluation has been separated from validation orchestration for maintainability.
- Iranian rules are isolated in a dedicated evaluator instead of being mixed into generic rule logic.
- Composer metadata now includes Iranian/Persian discovery keywords and a `dev-main` branch alias for the 1.x line.

### Removed

- The broken root `example.php` that bypassed Composer autoloading.
- Implicit `htmlspecialchars()` sanitization from validation.
