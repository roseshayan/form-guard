# Contributing to FormGuard

Thanks for helping improve FormGuard. The project intentionally keeps a small public API and avoids framework dependencies, so new features should justify their maintenance cost.

## Development setup

```bash
git clone https://github.com/roseshayan/form-guard.git
cd form-guard
composer install
composer check
```

`composer check` runs the PHPUnit test suite and PHPStan.

## Pull request expectations

Before opening a pull request:

1. Keep changes focused. Do not mix unrelated refactors and features.
2. Add or update tests for every behavior change.
3. Update `docs/rules.md` when adding or changing a built-in rule.
4. Update `README.md` when the public API or installation flow changes.
5. Run `composer check` locally.
6. Avoid new runtime dependencies unless the capability cannot be implemented safely with PHP itself.

## Adding a built-in rule

A built-in rule normally requires all of the following:

- add the rule name and implementation to `src/Rules/BuiltInRules.php`;
- add a default error message in `src/FormValidator.php`;
- add focused tests under `tests/`;
- document exact semantics and edge cases in `docs/rules.md`.

Unknown rules intentionally throw `InvalidRuleException`, so a rule is not complete until it is registered in the built-in rule list.

## Design principles

- Validation should not mutate user data.
- Output escaping and SQL escaping do not belong in the validator.
- Configuration mistakes should fail loudly.
- `validated()` must remain a whitelist, not a copy of arbitrary input.
- File validation must prefer server-detected MIME information over client-provided metadata.
- Avoid global mutable state so FormGuard remains safe in long-running PHP processes.
- Prefer explicit behavior over clever implicit coercion.

## Backward compatibility

After the first stable `1.x` release, public API breaks require a new major version. Internal refactoring is welcome as long as documented behavior and tests remain stable.

## Reporting security issues

Do not open a public issue containing exploit details. Follow `SECURITY.md` instead.
