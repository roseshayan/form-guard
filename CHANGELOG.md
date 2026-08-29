# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and the project follows Semantic Versioning after the first stable release.

## [Unreleased]

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
