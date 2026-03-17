# Industry Subsystem Fail-Safe and Degraded-Mode Contract (Prompt 467)

**Spec**: Lifecycle hardening contracts; cache contract; import/conflict contracts; neutral-mode and subtype fallback audits.  
**Status**: Contract. Defines fail-safe and degraded-mode behavior so missing registries, invalid bundles, failed caches, or partial imports cannot destabilize the core plugin or create broken admin experiences.

---

## 1. Purpose

- **Fail-safe**: When industry assets are missing, invalid, or partially failed, the subsystem must degrade to bounded warnings and generic behavior—not cascade failures into the core plugin or break admin screens.
- **Core plugin usability**: The core plugin (Build Plans, templates, execution, admin shell) must remain usable without industry features. Industry layers are optional overlays.
- **No silent auto-repair**: Degraded mode does not hide all warnings or silently mutate data. Operators and support see appropriate warnings; repair is explicit.

---

## 2. Degraded-mode categories and severity

| Category | Description | Severity | Expected behavior |
|----------|-------------|----------|-------------------|
| **Missing primary industry** | Profile has no primary_industry_key or empty. | Info | Neutral mode: recommendations use generic library; no industry badges or filters. See industry-neutral-mode-audit.md. |
| **Unknown/invalid pack ref** | primary_industry_key not in pack registry. | Warning | Treat as no industry; resolvers return neutral/generic; diagnostics show primary_industry_pack_not_found. Profile data preserved; no clear without operator action. |
| **Missing subtype overlay** | industry_subtype_key set but subtype not in registry or wrong parent or not active. | Warning | Subtype resolver returns has_valid_subtype false; parent-only context used. See industry-subtype-fallback-audit.md. |
| **Missing/invalid bundle ref** | selected_starter_bundle_key not in registry or industry mismatch. | Warning | Bundle list and assistant fallback; health/diagnostics may report; no crash. |
| **Failed cache read** | Transient/cache get fails or returns corrupt data. | Info | Treat as cache miss; recompute; do not block. Cache contract defines safe fallback. |
| **Incomplete import** | Restore or import leaves partial/invalid refs. | Warning | Restore contract: invalid refs reported; industry restore may be partial; rest of restore proceeds. No fatal. |
| **Missing registry (bootstrap)** | Pack, subtype, or bundle registry not loaded (e.g. module disabled). | Info | Industry subsystem unavailable; diagnostics show industry_subsystem_available false; core plugin and non-industry screens work. |
| **Deprecated/inactive pack** | Profile references deprecated or inactive pack. | Warning | industry-pack-deprecation-contract: warn; show replacement_ref if set; do not auto-clear profile. |

Severity **warning** means: surface to admin/support; may appear in diagnostics, health report, or profile screen. Severity **info** means: logged or visible in diagnostics; no blocking.

---

## 3. Generic fallback for missing recommendation assets

- **Section / page template resolvers**: When profile is empty, primary_key unknown, or pack not found: return neutral/generic result (e.g. no industry fit badges; default order). No throw; no fatal. Industry_Build_Plan_Scoring_Service returns normalized output unchanged when primary_key empty.
- **Build Plan review UI**: When has_industry_data false, industry explanation section is omitted; view renders without industry block. Build Plan steps (existing page, new page, navigation, etc.) remain usable.
- **Starter bundle assistant**: When no primary industry, row hidden or empty state; no broken dropdown. When primary set but no bundles, show empty list or neutral copy.
- **Overlay composers (helper doc, one-pager)**: When industry_key or subtype_key invalid or missing, return base/generic content or empty; no crash.

---

## 4. Fallback for missing bundles, subtype overlays, and caution rules

- **Bundles**: Industry_Starter_Bundle_Registry::get_for_industry(industry, subtype) returns industry-scoped bundles when subtype invalid or empty; get(key) returns null for unknown key. Callers must handle null/empty.
- **Subtype overlays**: Subtype_Section_Helper_Overlay_Registry and Subtype_Page_OnePager_Overlay_Registry: lookup by subtype returns null when subtype invalid; composition falls back to parent-only.
- **Caution/compliance rules**: When rule registry or subtype rule registry unavailable or rule key missing, no caution applied; no error. Resolvers return empty or default.

---

## 5. Behavior when caches fail or imports are incomplete

- **Cache fail**: Industry_Read_Model_Cache_Service (or equivalent) get returns miss on failure/corrupt; caller recomputes. No exception propagated to UI; no broken screen. industry-cache-contract §6 safe fallback.
- **Import incomplete**: industry-export-restore-contract and industry-pack-import-conflict-contract: invalid or unresolved refs are reported; restore/import may skip industry or apply partial; rest of restore/import continues. No silent overwrite of valid data with invalid.

---

## 6. Surfacing warnings

- **Where**: Diagnostics snapshot (warnings array); health check report; Industry Profile screen (readiness, pack not found); override conflict detector (advisory). No public exposure.
- **Blocking**: Warnings must not block unrelated core flows (e.g. Build Plan list, template directory, execution queue). Industry-specific screens (Industry Profile, starter bundle, comparison) may show warnings prominently but must not fatal or white-screen.

---

## 7. Support and operator guidance

- **Document**: In support/runbook and operator curriculum: how to interpret diagnostics warnings (primary_industry_pack_not_found, invalid subtype, cache miss), and that core plugin remains usable when industry is degraded.
- **Diagnostics**: Industry_Diagnostics_Service and Industry_Health_Check_Service include warnings and industry_subsystem_available; support packages can include snapshot for triage.
- **No auto-repair**: Contract does not require automatic clearing of invalid refs or auto-migration. Operators fix profile or bundle selection explicitly.

---

## 8. Cross-references

- **Lifecycle**: industry-lifecycle-hardening-contract.md — regression guards, safe fallback.
- **Cache**: industry-cache-contract.md — safe fallback, invalidation.
- **Neutral mode**: docs/qa/industry-neutral-mode-audit.md.
- **Subtype fallback**: docs/qa/industry-subtype-fallback-audit.md.
- **Import**: industry-export-restore-contract.md, industry-pack-import-conflict-contract.md.
- **Deprecation**: industry-pack-deprecation-contract.md.
