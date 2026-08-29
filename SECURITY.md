# Security Policy

## Supported versions

Security fixes are provided for the latest stable release line. Before `1.0.0` is tagged, fixes are applied to `main`.

## Reporting a vulnerability

Please do **not** publish exploit details in a normal GitHub issue.

Use GitHub's private vulnerability reporting flow from the repository's **Security** tab when the option is available. Include:

- affected FormGuard version or commit;
- a minimal reproduction;
- expected and actual behavior;
- realistic impact;
- any suggested mitigation.

If private vulnerability reporting is not available, open a public issue containing **no vulnerability details** and request a private reporting channel.

## Scope

Useful reports include validation bypasses, unsafe file-validation behavior, unexpected inclusion of unvalidated fields, rule parsing bugs that silently disable validation, and other defects in FormGuard itself.

FormGuard does not provide CSRF protection, authentication, authorization, SQL escaping, HTML/JavaScript escaping, malware scanning, or secure file storage. Missing those application-level controls is not a vulnerability in FormGuard unless the documentation or API incorrectly claims to provide them.
