# Release process

FormGuard follows Semantic Versioning after the first stable release.

## First public release checklist

1. Merge the release pull request into `main`.
2. Confirm the `CI` workflow is green on PHP 8.2, 8.3, 8.4, and 8.5, including PHPStan and `composer audit`.
3. Run locally:

   ```bash
   composer validate --strict
   composer check
   composer audit
   ```

4. Review `CHANGELOG.md` and move the relevant entries from `Unreleased` into a dated `1.0.0` section.
5. Create an **annotated** Git tag from the exact reviewed `main` commit:

   ```bash
   git checkout main
   git pull --ff-only
   git tag -a v1.0.0 -m "FormGuard v1.0.0"
   git push origin v1.0.0
   ```

6. Create a GitHub Release from `v1.0.0` using the changelog notes.
7. Sign in to Packagist and submit the public repository URL:

   ```text
   https://github.com/roseshayan/form-guard
   ```

   The Composer package name is read from `composer.json` and must resolve to `roseshayan/form-guard`.

8. Enable automatic Packagist updates from GitHub. Configure a GitHub webhook using the Packagist API token from the maintainer profile and send push events to Packagist's GitHub endpoint.
9. Verify the stable release from a clean directory:

   ```bash
   mkdir form-guard-install-test
   cd form-guard-install-test
   composer require roseshayan/form-guard:^1.0
   composer show roseshayan/form-guard
   ```

10. Confirm Packagist displays the correct source commit for `1.0.0` and that the package page has no validation/security warnings.
11. Remove the temporary pre-Packagist VCS installation note from the README after the stable package is confirmed live.

## Packagist API alternative

Packagist also exposes an authenticated package-creation API. Do not commit or paste the token into this repository.

Example from a trusted local terminal or secret-managed CI environment:

```bash
curl -X POST \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer PACKAGIST_USERNAME:PACKAGIST_MAIN_API_TOKEN' \
  'https://packagist.org/api/create-package' \
  -d '{"repository":"https://github.com/roseshayan/form-guard"}'
```

Package creation requires the **main** Packagist API token. Package updates may use a safe token. Prefer the Packagist UI for the first publication unless there is a reason to automate it.

## Stable tags are immutable

Packagist stable version references are immutable. Once `v1.0.0` has been published, do **not** delete, move, or recreate that tag to fix a mistake.

If `1.0.0` is wrong, fix the code and release `1.0.1`. Packagist intentionally blocks stable retags so every user receives the same commit for the same version.

Treat tagging as a production deployment: review the exact commit before creating the tag.

## Versioning policy

- PATCH: bug fixes with no documented API break.
- MINOR: backward-compatible rules/features.
- MAJOR: public API or documented semantic breaks.

Do not put a hardcoded package version in `composer.json`; Composer derives stable versions from Git tags and dev versions from branches.

## Maintainer security

For a public dependency, maintainer-account security is part of package security:

- enable MFA on GitHub and Packagist;
- do not store Packagist tokens in the repository;
- use the least-privileged/safe token for update automation when possible;
- protect `main` and require CI before merge once the project has external users;
- never force-move a published stable tag.

## Release quality gate

Do not tag a release when any of these are true:

- CI is red or missing on a supported PHP version;
- the README documents behavior that is not tested;
- a new built-in rule is missing tests or rule-reference documentation;
- `composer validate --strict` fails;
- `composer audit` reports an unresolved relevant advisory;
- a known validation bypass or file-validation security issue is unresolved;
- a release tag points at a commit other than the reviewed `main` commit.
