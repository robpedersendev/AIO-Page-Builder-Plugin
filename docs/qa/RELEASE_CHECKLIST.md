# Release Checklist

Use this checklist before each release. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

**Hardening and release gate:** For full acceptance criteria, severity classification, waiver rules, and sign-off requirements, see [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (spec §59.14, §59.15, §60.2–60.8).

**Latest evidence refresh: 2026-03-21** (git `ca94de0`; commands from `plugin/` unless noted).

---

## Code and Tests

- [x] **PHP syntax lint:** Recursive `php -l` on **1,711** files under `plugin/src` and `plugin/tests` — **0** parse errors (2026-03-21).
- [x] **PHPUnit:** `vendor/bin/phpunit -c phpunit.xml.dist` — **exit 0**. **3,056** tests, **55,458** assertions; PHPUnit reported **5** skipped, **8** deprecations; summary line **OK, but there were issues!** Runtime **PHP 8.5.1**. See [release-candidate-closure.md](release-candidate-closure.md) §2.
- [ ] **PHPCS (WPCS, `phpcs.xml.dist`):** `php vendor/bin/phpcs --standard=phpcs.xml.dist src --report=summary` — **exit 2**. **9** errors, **11** warnings in **12** files; PHPCBF reported **11** auto-fixable in that summary (2026-03-21). `php vendor/bin/phpcs --standard=phpcs.xml.dist tests --report=summary` — **exit 0** (**476** test files; ~55s). Not a repo-wide green gate until `src/` is clean or formally waived with owner sign-off.
- [ ] **Plugin Check:** `composer run plugin-check:summarize` on `tools/plugin-check/output/plugin-check-report.json` — **exit 0** (summarizer only). Report totals: **253** ERROR, **690** WARNING, **194** files with at least one finding (2026-03-21). Treat as **open quality debt** per project policy; do not claim “zero critical” without a fresh run and triage.
- [ ] **PHPStan:** `composer run phpstan` — **exit 1** (2026-03-21). Parallel worker hit configured **512M** memory limit; PHPStan reported incomplete analysis (“Result is incomplete because of severe errors”). Increase `--memory-limit` / adjust `composer.json` script and re-run for a complete baseline.
- [x] **Compatibility matrix:** [compatibility-matrix.md](compatibility-matrix.md) — spec baseline unchanged; **automated** evidence: `Environment_Validator_Test` and full PHPUnit above. **Live** WP×PHP matrix cells still require recorded manual/E2E runs when cutting a release.
- [x] **Migration/upgrade matrix:** [migration-coverage-matrix.md](migration-coverage-matrix.md) — table schema **1**, export schema **1**, same-major import, `Table_Installer` idempotent, `is_installed_version_future()` blocks downgrade; **automated** coverage via PHPUnit (see matrix §8). Manual scenario column in §4 remains **execute and record** for operator sign-off.

---

## Documentation

- [x] **Changelog updated:** [changelog.md](../release/changelog.md) — [Unreleased] includes evidence/tooling notes as of 2026-03-21.
- [x] **README created:** Root `README.md` with installation, requirements, known changes, and distribution notes.
- [x] **Release notes compatibility snippet:** Tested WP 6.6+; PHP 8.1–8.3 (CI target); local PHPUnit at PHP **8.5.1**; ACF Pro 6.2+; GenerateBlocks 2.0+; preferred GeneratePress. See [compatibility-matrix.md](compatibility-matrix.md) §10.
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

- [x] **Release-candidate closure updated:** [release-candidate-closure.md](release-candidate-closure.md) — QA evidence and gate status aligned with **2026-03-21** measured runs (PHPUnit pass with skips/deprecations; PHPCS/Plugin Check/PHPStan not green).
- [x] **Known-risk register updated:** [known-risk-register.md](../release/known-risk-register.md) — TF-1 superseded; tooling debt entries for PHPCS, Plugin Check, PHPStan incompleteness.
- [ ] **Sign-off:** Pending Product Owner, Technical Lead, and QA per hardening matrix §6. **Open:** PHPCS (`src/`), Plugin Check report triage, PHPStan complete run; PHPUnit skips/deprecations tracked in closure doc.

---

## Version and Metadata

- [ ] **Plugin header version bump:** Bump from `0.1.0` to release version when cutting final release.
- [ ] **Version constants updated consistently:** `Constants::plugin_version()` and any release notes/changelog date must match.
- [ ] **Text domain and update URI correct:** Verify for private distribution channel.

---

*Evidence artifacts (2026-03-21): PHPUnit exit 0 (3,056 tests; 5 skipped; 8 deprecations); PHP syntax 0 errors / 1,711 files; PHPCS `src/` exit 2 (9 errors, 11 warnings); PHPCS `tests/` exit 0; Plugin Check summarize on archived JSON (253 ERR / 690 WARN); PHPStan exit 1 (memory-limited incomplete run).*
