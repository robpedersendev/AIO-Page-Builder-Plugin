# Industry Pack Subsystem — Lifecycle Regression Guard

**Spec**: industry-lifecycle-hardening-contract; industry-pack-extension-contract; PORTABILITY_AND_UNINSTALL. **Prompt**: 380.

This document defines QA procedures and regression guards for the Industry Pack subsystem across uninstall, multisite, CLI/scripted behavior, and pack/recommendation stability. Use for release verification and after changes to industry lifecycle or recommendation logic.

---

## 1. Uninstall retention/removal

| Check | Procedure | Pass condition |
|-------|-----------|----------------|
| Industry options removed | On a test site with industry profile and applied preset set, run plugin uninstall (delete plugin). Inspect options (e.g. via DB or a small script): `Option_Names::INDUSTRY_PROFILE` and `Option_Names::APPLIED_INDUSTRY_PRESET` (and any documented industry transient keys) must be removed. | Options no longer present after uninstall. |
| Built content preserved | Before uninstall, create at least one page built with industry-guided flow (e.g. industry selected, template chosen). After uninstall, page and post meta remain; no corruption. | Page and content intact. |
| No fatal on uninstall | Run uninstall with industry subsystem having been used (profile set, preset applied). No PHP fatal or uncaught exception. | Uninstall completes; no error. |
| Policy alignment | Confirm removed list matches industry-lifecycle-hardening-contract §1 and PORTABILITY_AND_UNINSTALL. | Docs and behavior align. |

**Evidence**: Record test date, WP version, and result (pass/fail/waived). Add to release checklist when industry is in scope.

---

## 2. Multisite scoping (if multisite supported)

| Check | Procedure | Pass condition |
|-------|-----------|----------------|
| Profile per-site | On multisite, set industry profile on site A; switch to site B. Profile on B is independent (empty or different). | No cross-site profile leakage. |
| Cache per-site | Where industry caches exist (e.g. recommendation cache), verify cache keys include blog id or equivalent so site A does not read site B’s cache. | No cross-site cache read. |
| Export/restore site-local | Export from site A; switch to site B; restore. Restore applies to site B only; site A unchanged. | Restore scoped to current site. |

**Evidence**: If multisite is in scope, run and record per [template-ecosystem-multisite-site-isolation-report.md](template-ecosystem-multisite-site-isolation-report.md) or equivalent; extend with industry-specific rows above.

---

## 3. CLI / scripted behavior

| Check | Procedure | Pass condition |
|-------|-----------|----------------|
| Read industry state | In WP-CLI or a script that loads WordPress and the plugin, read industry profile and/or run a recommendation resolver (e.g. with a known industry_key). | No fatal; result deterministic and consistent with admin behavior. |
| Unknown industry_key | Set profile to unknown industry_key (or simulate); run recommendation resolver. | Safe fallback (neutral/generic); no crash. |
| No unsafe mutation | If CLI commands exist that mutate industry state, verify they enforce capability and do not bypass validation. | No undocumented or unsafe mutation path. |

**Evidence**: Manual QA; document “CLI/scripted read and fallback verified” and date. No automated test required by this doc unless added elsewhere.

---

## 4. Pack validation and recommendation fallback

| Check | Procedure | Pass condition |
|-------|-----------|----------------|
| Valid pack loads | All built-in packs (see industry-pack-catalog) load and are available from registry. | Registry returns expected packs. |
| Invalid pack rejected | Simulate loading a pack with invalid version_marker or missing required field (e.g. in a test or temporary definition). | Pack rejected or skipped; no fatal; valid packs still load. |
| Resolver no profile | Run section and page template recommendation resolvers with no industry profile (or empty profile). | Neutral/generic result; no exception. |
| Resolver unknown industry | Run resolvers with profile industry_key that does not exist in registry. | Safe fallback; no crash. |
| Restore unsupported version | Restore a profiles bundle where industry.json has schema_version set to an unsupported value (e.g. `99`). | Industry restore skipped; log present; rest of profiles restore succeeds. |
| Substitute engine empty | Call substitute suggestion engine for section/template when there are no recommended candidates (e.g. all discouraged). | Empty list returned; no error. |

**Evidence**: Unit tests preferred for resolver and substitute engine; pack validation can be unit or manual. Restore test can be manual or integration. Record in industry-subsystem-acceptance-report or release checklist when applicable.

---

## 5. Cross-references

- [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) — policy and rules.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — release gate; reference this guard when industry is in scope.
- [industry-subsystem-acceptance-report.md](industry-subsystem-acceptance-report.md) — broader acceptance; lifecycle rows can be added here.
- [known-risk-register.md](../release/known-risk-register.md) — IND-1, IND-2; mitigations include lifecycle and safe fallback.
