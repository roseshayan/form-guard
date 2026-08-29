# Reliability gates

FormGuard treats CI and release automation as part of the public API contract.

## Pull-request gates

Every pull request is expected to pass:

- PHPUnit on PHP 8.2, 8.3, 8.4, and 8.5;
- strict Composer metadata validation;
- dependency audit;
- PHPStan;
- PHP-CS-Fixer dry-run checks;
- Xdebug/Clover coverage with an 85% statement-coverage floor;
- backward-compatibility analysis against the latest stable tag.

The backward-compatibility tool is installed in isolation on PHP 8.5 so that its own PHP requirement does not raise FormGuard's PHP 8.2 runtime minimum.

## Release gate

Stable releases must use the `Release` GitHub Actions workflow. The workflow validates the exact selected `main` commit before creating an annotated tag.

The final release smoke test exports the package with `git archive`, installs the exported tree with `composer install --no-dev`, verifies that the primary `Validator` class is autoloadable, and runs representative generic and Iranian validation rules.

A failed release workflow creates no stable tag.

## Coverage policy

The current coverage floor is 85% statement coverage. It is a minimum safety floor, not a target. New behavior should be tested at the behavior and boundary level instead of adding assertions merely to satisfy the percentage.

## Tag policy

Published stable tags are append-only. Never move or recreate a stable tag. If a release is wrong, publish the next patch release.
