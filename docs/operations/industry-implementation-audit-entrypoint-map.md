# Industry Subsystem Implementation-Audit Entrypoint Map (Prompt 569)

**Spec:** Greenfield closure report; roadmap contract; pack service map; maintenance and release docs.  
**Purpose:** Formal entrypoint map for implementation-audit prompts. Documents key codepaths, registries, screens, composition layers, scoring services, caching paths, and failure modes that audit prompts should target first. Enables dependency-aware, risk-prioritized audit prompt generation.

---

## 1. Priority audit domains

Audit work should address these domains in an order that respects dependencies. Higher priority = safety or correctness impact; lower = polish or consistency.

| Domain | Priority | Rationale |
|--------|----------|-----------|
| **Registry load and validation** | Safety-critical | Invalid or duplicate pack definitions can corrupt resolution and export. |
| **Profile persistence and export/restore** | Safety-critical | Site industry state and portability; invalid ref handling must not fatal. |
| **Capability and nonce on admin actions** | Safety-critical | All state-changing requests must be gated; no industry bypass. |
| **Resolver and cache consistency** | Correctness-critical | Wrong or stale recommendations; cache key collisions or missing invalidation. |
| **Overlay composition and allowed regions** | Correctness-critical | Wrong or leaked content; compliance and schema boundaries. |
| **Sandbox and promotion gates** | Safety-critical | No auto-promotion; dry-run and release gate must align with contracts. |
| **Admin screen data flow and fallback** | Polish | Missing services or null container must yield readable, non-fatal UI. |
| **Diagnostics and support payloads** | Polish | Bounded shape; no secrets; redaction and audit expectations. |

---

## 2. High-risk codepaths and integration seams

These are likely failure points or high-impact seams. Audit prompts should verify behavior under failure and edge cases.

| Codepath / seam | Risk | Suggested audit focus |
|------------------|------|------------------------|
| **Industry_Packs_Module bootstrap** | Registry keys not registered; wrong order; null dependencies. | Container registration order; null checks in consumers; graceful degradation when pack registry is empty. |
| **Industry_Profile_Repository read/write** | Invalid industry_key; missing pack; subtype ref to deprecated subtype. | Validation before save; fallback when pack missing; export/restore with invalid refs. |
| **Industry_Pack_Registry::get() / get_all()** | Stale or partial load; duplicate keys; status filter inconsistency. | Load semantics; duplicate-key handling; list_by_status vs get_all. |
| **Section/page recommendation resolvers** | Profile or pack null; cache key collision; neutral fallback when no pack. | Null profile/pack; cache key builder scope; fallback behavior and tests. |
| **Industry_Read_Model_Cache_Service** | TTL and invalidation; site scope; miss behavior. | Invalidation triggers; key scope; no cross-site leakage. |
| **Helper/onepager composition** | Missing overlay; wrong region; base doc missing. | Allowed regions only; missing overlay fallback; schema compliance. |
| **Industry_Author_Sandbox_Service::run_dry_run()** | Lint/health errors not surfaced; prerequisites bypass. | Error aggregation; no live mutation; prerequisite check before summary. |
| **Industry_Sandbox_Promotion_Service::check_prerequisites()** | False positive for release-ready; missing_requirements incomplete. | Alignment with dry_run result; no auto-promotion path. |
| **Admin screens (Industry_*_Screen)** | Capability check missing; nonce on POST; container null. | current_user_can before render; nonce on any form; get_view_model() with null container returns bounded data. |
| **Export/restore industry payload** | Missing profile or pack refs; schema version mismatch. | Payload shape; restore with invalid refs; uninstall cleanup. |

---

## 3. Registries and composition layers (audit targets)

| Component | Location | Audit focus |
|-----------|----------|-------------|
| Industry_Pack_Registry | Domain/Industry/Registry | Load, get, get_all, list_by_status; duplicate keys; status filtering. |
| Industry_Profile_Repository | Domain/Industry/Profile | Read, save, export/restore; validation and invalid ref. |
| Industry_Subtype_Registry / resolver | Domain/Industry (subtype) | Subtype resolution; parent fallback; deprecated handling. |
| Industry_Starter_Bundle_Registry | Domain/Industry/Registry | get_for_industry; bundle keys; missing bundle. |
| Industry_Section_Helper_Overlay_Registry | Overlays | Lookup by industry + section; allowed regions. |
| Industry_Page_OnePager_Overlay_Registry | Overlays | Same pattern as section. |
| Industry_Section_Recommendation_Resolver | Domain/Industry | Profile/pack input; cache; neutral fallback. |
| Industry_Page_Template_Recommendation_Resolver | Domain/Industry | Same as section. |
| Industry_Read_Model_Cache_Service | Domain/Industry or Infrastructure | get/set/delete; TTL; invalidation; key scope. |
| Industry_Helper_Doc_Composer | Domain/Industry | Compose base + overlay; allowed regions only. |
| Industry_Page_OnePager_Composer | Domain/Industry | Same as helper. |

---

## 4. Admin screens and reporting (audit targets)

| Screen / report | Audit focus |
|-----------------|-------------|
| Industry_Author_Dashboard_Screen | Capability; view model with null/missing services; links present. |
| Industry_Profile_Settings_Screen | Nonce; capability; save validation; no silent overwrite. |
| Industry_Health_Report_Screen | Read-only; capability; error/warning aggregation. |
| Industry_Pack_Family_Comparison_Screen | Bounded rows; no unbounded query. |
| Future_Industry_Readiness_Screen / Future_Subtype_Readiness_Screen | View model fallback; links; no mutation. |
| Industry_Scaffold_Promotion_Readiness_Report_Screen | Advisory only; no auto-promotion. |
| Industry_Drift_Report_Screen | Read-only; severity grouping. |
| Industry_Maturity_Delta_Report_Screen | No baseline fallback; readable. |
| Industry_Guided_Repair_Screen | Action types bounded; no auto-apply without confirmation. |
| Industry_Bundle_Import_Preview_Screen | Conflict detection; no import without user confirm. |

---

## 5. Audit cluster summary (for prompt generation)

Group audit prompts into these clusters so work is coherent and dependency-aware:

| Cluster | Scope | Example prompts |
|---------|--------|------------------|
| **Safety-critical** | Registry, profile, capability, nonce, sandbox/promotion gates. | Audit registry load and validation; audit profile save and export/restore; audit all admin POST handlers for nonce and capability; audit promotion and release gate alignment. |
| **Correctness-critical** | Resolvers, cache, overlay composition, allowed regions. | Audit resolver null and fallback behavior; audit cache key and invalidation; audit overlay composition and region boundaries. |
| **Polish** | Admin screen fallbacks, diagnostics payloads, missing-data UX. | Audit screen view models with null container; audit diagnostics schema and redaction; audit error messages and links. |

Do not use this map to add new runtime features. Use it to generate audits, tests, and documentation that verify existing behavior and close gaps.

---

## 6. Alignment with greenfield closure and roadmap

- **Greenfield closure:** [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) defines the boundary. Implementation-audit starts **after** that closure; it does not extend greenfield capability layers unless explicitly scoped elsewhere.
- **Roadmap:** [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) and [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) remain the authority for what may and may not be added. Audit prompts must not normalize unsafe shortcuts or reduced review rigor.
- **Service map:** [industry-pack-service-map.md](../contracts/industry-pack-service-map.md) and bootstrap (Industry_Packs_Module) are the source of truth for container keys and dependency flow. Audit prompts should reference them for coverage.

---

## 7. References

- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md)
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md)
- [industry-pack-service-map.md](../contracts/industry-pack-service-map.md)
- [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md)
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md)
- [industry-sandbox-promotion-workflow.md](industry-sandbox-promotion-workflow.md)
