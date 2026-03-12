# Release Checklist

Use this checklist before each release. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

**Hardening and release gate:** For full acceptance criteria, severity classification, waiver rules, and sign-off requirements, see [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (spec §59.14, §59.15, §60.2–60.8).

## Code and Tests

- [ ] Lint passes: `npm run lint:php` (or equivalent).
- [ ] Code is auto-fixed where appropriate: `npm run fix:php`.
- [ ] All unit and integration tests pass.
- [ ] Plugin Check is run; critical and warning findings are addressed.
- [ ] Compatibility matrix is executed and updated: [compatibility-matrix.md](compatibility-matrix.md) (WP/PHP/dependency combinations; release-note snippet).
- [ ] Migration/upgrade matrix is executed and updated: [migration-coverage-matrix.md](migration-coverage-matrix.md) (supported transitions, import/restore, retry; release-note migration/compatibility notes).

## Documentation

- [ ] Changelog is updated.
- [ ] README is updated (installation, requirements, known changes).
- [ ] Release notes include compatibility notes per §58.6 (tested WP/PHP range, required plugins, limitations; see [compatibility-matrix.md](compatibility-matrix.md) §9).
- [ ] Release notes include migrations or compatibility notes per §58.6 (table/export schema, same-major import, breaking changes if any; see [migration-coverage-matrix.md](migration-coverage-matrix.md) §7).
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
