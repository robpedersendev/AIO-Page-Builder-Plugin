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
- [ ] User/admin/support guidance: [admin-operator-guide.md](../guides/admin-operator-guide.md), [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md), [support-triage-guide.md](../guides/support-triage-guide.md) exist and doc-to-UI consistency pass is done (see [release-candidate-closure.md](release-candidate-closure.md) §7).

## Security

- [ ] Nonce and capability checks verified on all new or modified REST/AJAX endpoints.
- [ ] Security and redaction review completed: [security-redaction-review.md](security-redaction-review.md) (capability, nonce, import/export safety, redaction).
- [ ] No secrets committed; placeholders are used and documented.

## Uninstall and Data

- [ ] `uninstall.php` behavior is verified.
- [ ] Data retention policy is confirmed (preserve built content; remove only plugin-owned operational data).
- [ ] See [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).

## Release Candidate and Sign-Off

- [ ] Release-candidate closure completed: [release-candidate-closure.md](release-candidate-closure.md) (performance posture, QA evidence, gate status, release-note inputs).
- [ ] Known-risk register updated: [known-risk-register.md](../release/known-risk-register.md).
- [ ] Sign-off obtained per hardening matrix §6 (Product Owner, Technical Lead, QA; Security where applicable).

## Version and Metadata

- [ ] Plugin header version is bumped.
- [ ] Any version constants or references are updated consistently.
- [ ] Text domain and update URI are correct for the distribution channel.
