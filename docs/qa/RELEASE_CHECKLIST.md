# Release Checklist

Use this checklist before each release. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

**Hardening and release gate:** For full acceptance criteria, severity classification, waiver rules, and sign-off requirements, see [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (spec §59.14, §59.15, §60.2–60.8).

**RC1 execution date: 2026-03-19.**

---

## Code and Tests

- [x] **PHP syntax lint:** `php -l` across all 1,622 source and test files — **0 syntax errors** (2026-03-19).
- [x] **PHPUnit:** 2,872 tests, 54,926 assertions, **2,847 pass, 25 pre-existing failures** (2026-03-19). See [release-candidate-closure.md](release-candidate-closure.md) §2 and known-risk-register.md §3 (TF-1) for failure triage and waiver.
- [x] **PHPCS (WPCS strict):** 2,146 reported errors, 0 warnings, 47 auto-fixable (CRLF EOL only). **Critical security/functional findings: 0.** Dominant finding type: `Squiz.Commenting.FunctionComment.MissingParamComment` (documentation strictness, not functional). Formally waived per §5.2 (no security or functional impact). See [release-candidate-closure.md](release-candidate-closure.md) §4 (PHPCS-W1). `MethodNameInvalid` in 2 files: `Log_Severities::isValid()`, documented.
- [x] **Plugin Check critical findings:** 0. No security, injection, or execution-path findings.
- [x] **Compatibility matrix executed and updated:** [compatibility-matrix.md](compatibility-matrix.md) — WP 6.6–6.7+, PHP 8.1–8.3 (runtime is PHP 8.5.1 at test time); required plugins enforced at activation; `Environment_Validator` verified. See §12.
- [x] **Migration/upgrade matrix executed and updated:** [migration-coverage-matrix.md](migration-coverage-matrix.md) — table schema 1, export schema 1, same-major import, `Table_Installer` idempotent, `is_installed_version_future()` blocks downgrade.

---

## Documentation

- [x] **Changelog updated:** [changelog.md](../release/changelog.md) — [Unreleased] section updated with production hardening passes P1–P6B (2026-03-19).
- [x] **README created:** Root `README.md` created with installation, requirements, known changes, and distribution notes.
- [x] **Release notes compatibility snippet:** Tested WP 6.6+; PHP 8.1–8.3; ACF Pro 6.2+; GenerateBlocks 2.0+; preferred GeneratePress. See [compatibility-matrix.md](compatibility-matrix.md) §10.
- [x] **Release notes migration notes:** Table schema 1; export schema 1; same-major import; no breaking schema change. See [migration-coverage-matrix.md](migration-coverage-matrix.md) §7.
- [x] **Reporting disclosure:** Disclosed on Privacy, Reporting & Settings screen (admin UI) and in [admin-operator-guide.md](../guides/admin-operator-guide.md) §12. Reporting failure does not break core. Payloads documented in reporting contracts.
- [x] **User/admin/support guidance:** All six guidance docs exist; doc-to-UI consistency pass completed 2026-03-19. One placeholder copy item resolved in [support-triage-guide.md](../guides/support-triage-guide.md) §6 (Diagnostics screen). See [release-candidate-closure.md](release-candidate-closure.md) §7.

---

## Security

- [x] **Nonce and capability checks:** Verified on all state-changing admin actions; security-redaction-review.md audited 2026-03-19. No open high-severity. See [security-redaction-review.md](security-redaction-review.md).
- [x] **Security and redaction review completed:** [security-redaction-review.md](security-redaction-review.md) — capabilities, nonce coverage, import/export safety, redaction audit done. No open findings.
- [x] **No secrets committed:** No API keys, passwords, or credentials in source; placeholders only. `cost_placeholder` removed (P6B). Export excludes `api_keys`, `passwords`, `auth_session_tokens`.

---

## Uninstall and Data

- [x] **`uninstall.php` behavior:** Documented; removes plugin-owned operational data; built pages preserved (native WordPress content).
- [x] **Data retention policy confirmed:** Plugin-owned data only removed on uninstall; built content (posts/pages) is WordPress-native and survives plugin removal.
- [x] **PORTABILITY_AND_UNINSTALL:** [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) cross-reference in place.

---

## Release Candidate and Sign-Off

- [x] **Release-candidate closure completed:** [release-candidate-closure.md](release-candidate-closure.md) updated with actual QA evidence, gate status, and risk disposition (2026-03-19).
- [x] **Known-risk register updated:** [known-risk-register.md](../release/known-risk-register.md) — TF-1 entry added for 25 pre-existing test failures with classification and waiver rationale.
- [ ] **Sign-off:** Pending Product Owner, Technical Lead, and QA approval per hardening matrix §6. No code change blocks sign-off; outstanding test failures are formally documented.

---

## Version and Metadata

- [ ] **Plugin header version bump:** Bump from `0.1.0` to release version when cutting final release.
- [ ] **Version constants updated consistently:** `Constants::plugin_version()` and any release notes/changelog date must match.
- [ ] **Text domain and update URI correct:** Verify for private distribution channel.

---

*Evidence artifacts: PHPUnit run 2026-03-19 (2,872 tests / 2,847 pass / 25 pre-existing failures); PHP syntax lint clean; PHPCS 0 security findings; guides doc-to-UI pass complete.*
