# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and the project follows Semantic Versioning after the first stable release.

## [Unreleased]

### Added

- `Validator` as the primary public entry point while retaining `FormValidator` compatibility.
- Nested dot-notation and wildcard validation.
- `ErrorBag` with per-field and aggregate error access.
- `ValidationException` and `validateOrFail()`.
- Strict `InvalidRuleException` handling for unknown/malformed rules.
- Custom messages and human-readable attribute names.
- Inline callable validation rules without global mutable registries.
- Presence, type, format, size, comparison, date, JSON, IP, UUID, Iranian mobile, and flow-control rules.
- Uploaded file abstraction with MIME detection, image, size, MIME, and extension rules.
- PHPUnit coverage for core, nested, wildcard, custom, and file behavior.
- PHPStan static analysis.
- GitHub Actions CI for PHP 8.2 through 8.5.
- Complete README, rule reference, examples, security policy, and contributing guide.

### Changed

- Minimum PHP version from 8.1 to 8.2 because PHP 8.1 is end-of-life.
- Validation no longer mutates or HTML-escapes input.
- `validated()` now returns only rule-whitelisted data and throws after failed validation.
- Rule evaluation has been separated from validation orchestration for maintainability.

### Removed

- The broken root `example.php` that bypassed Composer autoloading.
- Implicit `htmlspecialchars()` sanitization from validation.
