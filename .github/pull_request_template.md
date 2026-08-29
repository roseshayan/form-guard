## Summary

Describe what changed and why.

## Validation impact

- Which rules/API behavior changed?
- Are there backward-compatibility implications?

## Checklist

- [ ] Tests cover the behavior change.
- [ ] `composer check` passes locally.
- [ ] `docs/rules.md` is updated for rule changes.
- [ ] `README.md` is updated for public API changes.
- [ ] No validation concern was mixed with output escaping/storage concerns.
- [ ] File-validation changes do not trust client MIME type as authoritative.
