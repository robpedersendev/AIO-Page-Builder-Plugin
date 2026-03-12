# Release Checklist

Use this checklist before each release. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

**Hardening and release gate:** For full acceptance criteria, severity classification, waiver rules, and sign-off requirements, see [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (spec §59.14, §59.15, §60.2–60.8).

## Code and Tests

- [ ] Lint passes: `npm run lint:php` (or equivalent).
- [ ] Code is auto-fixed where appropriate: `npm run fix:php`.
- [ ] All unit and integration tests pass.
- [ ] Plugin Check is run; critical and warning findings are addressed.

## Documentation

- [ ] Changelog is updated.
- [ ] README is updated (installation, requirements, known changes).
- [ ] If reporting is implemented: disclosure is present in admin docs, settings, and help content.

## Security

- [ ] Nonce and capability checks verified on all new or modified REST/AJAX endpoints.
- [ ] No secrets committed; placeholders are used and documented.

## Uninstall and Data

- [ ] `uninstall.php` behavior is verified.
- [ ] Data retention policy is confirmed (preserve built content; remove only plugin-owned operational data).
- [ ] See [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).

## Version and Metadata

- [ ] Plugin header version is bumped.
- [ ] Any version constants or references are updated consistently.
- [ ] Text domain and update URI are correct for the distribution channel.
