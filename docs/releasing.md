# Release process

FormGuard follows Semantic Versioning. Stable Composer versions are derived from Git tags and must never be moved after publication.

## Normal release flow

1. Prepare a release PR against `main`.
2. Update `CHANGELOG.md` for the version being released.
3. Merge only after the `CI` workflow is green.
4. Confirm the final `main` commit contains exactly the intended release changes.
5. In GitHub, open **Actions → Release → Run workflow**.
6. Select the `main` branch and enter a stable tag such as `v1.0.2`.
7. Let the workflow complete. Do not create the tag manually.
8. Confirm the GitHub Release exists and Packagist receives the new tag through the configured GitHub hook.
9. Verify the public install from a clean directory:

   ```bash
   mkdir form-guard-install-test
   cd form-guard-install-test
   composer require "roseshayan/form-guard:^1.0"
   composer show roseshayan/form-guard
   ```

## What the Release workflow protects

The release workflow refuses to publish from anything except `main` and requires a strict `vMAJOR.MINOR.PATCH` version. Before a tag exists, it runs:

- `composer validate`;
- PHPUnit;
- PHPStan;
- PHP-CS-Fixer in dry-run mode;
- `composer audit`;
- the coverage quality gate;
- Roave Backward Compatibility Check;
- a production export using `git archive`;
- a `composer install --no-dev` inside the exported package;
- a runtime smoke test for `RoseShayan\\FormGuard\\Validator` and representative Iranian validation rules.

Only after all of those checks pass does the workflow create an annotated tag pointing at the exact checked `main` commit and publish the GitHub Release.

This ordering is intentional: a release tag is a production deployment, not a build trigger that may later fail.

## Stable tags are immutable

Once Packagist has published a stable version, never delete, move, or recreate its tag. If a published version is wrong, fix the issue and release the next patch version.

The initial `v1.0.0` release demonstrated why this matters: it was tagged against a pre-merge commit. The corrected code was therefore published as `v1.0.1` instead of retagging `v1.0.0`.

## Versioning policy

- PATCH: backward-compatible bug fixes and release/tooling corrections.
- MINOR: backward-compatible features and new validation rules.
- MAJOR: public API or documented semantic breaks.

Do not put a hardcoded version in `composer.json`.

## Backward compatibility

CI runs Roave Backward Compatibility Check against the latest stable tag. The BC checker is deliberately installed in an isolated Composer home on PHP 8.5 instead of being added to FormGuard's root `require-dev`, because current Roave releases require newer PHP than FormGuard's PHP 8.2 minimum.

A BC failure is not automatically a bug: an intentional public API break belongs in a new major release. For a patch or minor release, however, an unexpected BC failure blocks the release.

## Coverage policy

CI produces Clover coverage with Xdebug and enforces a minimum statement-coverage floor. The initial floor is 85%. Treat this as a ratchet: it may increase as the suite improves, but it should not be lowered merely to make a failing change pass.

Coverage is not sufficient by itself. New rules must still include meaningful positive, negative, boundary, malformed-input, and normalization tests where applicable.

## Maintainer security

For a public dependency, maintainer-account security is part of package security:

- enable MFA on GitHub and Packagist;
- never commit Packagist or GitHub tokens;
- keep the Packagist GitHub hook enabled;
- protect `main` and stable tags with GitHub Rulesets;
- block force-pushes and tag deletion where possible;
- never bypass a failed release quality gate by manually creating the same stable tag.

## Release quality gate

Do not publish when any of these are true:

- CI is red or missing on a supported PHP version;
- Composer metadata validation fails;
- `composer audit` reports an unresolved relevant advisory;
- PHPUnit, PHPStan, code style, coverage, or BC checks fail;
- the exported package cannot install with `--no-dev`;
- the exported package does not expose the documented public entry point;
- a new built-in rule is missing tests or rule-reference documentation;
- a known validation bypass or file-validation security issue is unresolved.
