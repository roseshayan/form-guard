# Release process

FormGuard uses Semantic Versioning after the first stable release.

## First public release checklist

1. Merge the production-ready pull request into `main`.
2. Confirm the `CI` workflow is green on PHP 8.2, 8.3, 8.4, and 8.5.
3. Run locally:

   ```bash
   composer validate --strict
   composer check
   ```

4. Review `CHANGELOG.md` and move the relevant entries from `Unreleased` into a dated `1.0.0` section.
5. Create and push an annotated `v1.0.0` Git tag.
6. Create a GitHub Release from `v1.0.0` using the changelog notes.
7. Register `https://github.com/roseshayan/form-guard` on Packagist as `roseshayan/form-guard`.
8. Enable Packagist's GitHub update integration/webhook so new tags are discovered automatically.
9. Verify that a clean project can install the release:

   ```bash
   composer require roseshayan/form-guard:^1.0
   ```

10. Remove the pre-Packagist VCS installation note from the README once the Packagist package is confirmed live.

## Versioning policy

- PATCH: bug fixes with no documented API break.
- MINOR: backward-compatible rules/features.
- MAJOR: public API or documented semantic breaks.

Do not put a hardcoded package version in `composer.json`; Composer derives versions from Git tags/branches.

## Release quality gate

Do not tag a release when any of these are true:

- CI is red or missing on a supported PHP version;
- the README documents behavior that is not tested;
- a new built-in rule is missing tests or rule-reference documentation;
- `composer validate --strict` fails;
- a known validation bypass or file-validation security issue is unresolved.
