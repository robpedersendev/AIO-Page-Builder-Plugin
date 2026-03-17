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

## 6. Audit finding ledger and remediation tracking (Prompt 585A)

Before running any implementation-audit prompt (586+), the **audit finding ledger and remediation tracking system** must be in place:

- **Ledger:** [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md) — Human-readable ledger; finding ID format (IND-AUD-NNNN); severity and status taxonomy; grouping dimensions; release-blocker rules.
- **Workflow:** [industry-audit-remediation-workflow.md](industry-audit-remediation-workflow.md) — Run audit → record findings (or no-finding) → triage → group into remediation prompts → implement → verify → close.
- **Machine-readable:** `plugin/docs/internal/industry-audit-findings.json` (findings), `plugin/docs/internal/industry-remediation-tracker.json` (remediation entries). Schemas: [industry-audit-finding-schema.md](../schemas/industry-audit-finding-schema.md), [industry-remediation-entry-schema.md](../schemas/industry-remediation-entry-schema.md).

Every audit prompt run must **append or update** the ledger (or JSON) before it is considered complete. Every remediation (fix) prompt later created must reference at least one finding_id from the ledger.

---

## 7. Alignment with greenfield closure and roadmap

- **Greenfield closure:** [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) defines the boundary. Implementation-audit starts **after** that closure; it does not extend greenfield capability layers unless explicitly scoped elsewhere.
- **Archive map:** [industry-greenfield-prompt-archive-map.md](industry-greenfield-prompt-archive-map.md) (Prompt 570) summarizes prompt ranges by capability cluster, authoritative contracts, and the transition into implementation-audit. Use it to trace capability to prompt range and to confirm which artifacts are authoritative.
- **Optional backlog:** [industry-optional-late-stage-greenfield-backlog.md](industry-optional-late-stage-greenfield-backlog.md) (Prompt 585) lists Prompts 571–584 as optional; implementation-audit is the next priority, not these items.
- **Roadmap:** [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) and [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) remain the authority for what may and may not be added. Audit prompts must not normalize unsafe shortcuts or reduced review rigor.
- **Service map:** [industry-pack-service-map.md](../contracts/industry-pack-service-map.md) and bootstrap (Industry_Packs_Module) are the source of truth for container keys and dependency flow. **[industry-implementation-audit-service-map.md](industry-implementation-audit-service-map.md)** (Prompt 586) is the **implementation** service map: real entrypoints, registries, resolvers, storage, admin screens, preview/Build Plan/export/reporting paths, and audit hotspots. Audit prompts 587+ should reference the implementation service map for real code locations.

---

## 8. References

- [industry-implementation-audit-service-map.md](industry-implementation-audit-service-map.md) — Implementation service map (Prompt 586); real entrypoints and paths for audits 587+.
- [industry-bootstrap-audit-report.md](../qa/industry-bootstrap-audit-report.md) — Bootstrap and container audit (Prompt 587).
- [industry-registry-audit-report.md](../qa/industry-registry-audit-report.md) — Registry load and validation audit (Prompt 588).
- [industry-profile-audit-report.md](../qa/industry-profile-audit-report.md) — Industry Profile persistence and resolver audit (Prompt 589).
- [industry-admin-save-flow-audit-report.md](../qa/industry-admin-save-flow-audit-report.md) — Onboarding/settings admin save-flow audit (Prompt 590).
- [industry-section-recommendation-audit-report.md](../qa/industry-section-recommendation-audit-report.md) — Section recommendation engine audit (Prompt 591).
- [industry-page-template-recommendation-audit-report.md](../qa/industry-page-template-recommendation-audit-report.md) — Page template recommendation engine audit (Prompt 592).
- [industry-starter-bundle-audit-report.md](../qa/industry-starter-bundle-audit-report.md) — Starter bundle resolution and overlay audit (Prompt 593).
- [industry-doc-composition-audit-report.md](../qa/industry-doc-composition-audit-report.md) — Helper-doc and page one-pager composition audit (Prompt 594).
- [industry-preview-detail-audit-report.md](../qa/industry-preview-detail-audit-report.md) — Preview and detail resolver audit (Prompt 595).
- [industry-build-plan-scoring-audit-report.md](../qa/industry-build-plan-scoring-audit-report.md) — Build Plan scoring and explanation audit (Prompt 596).
- [industry-build-plan-execution-boundary-audit-report.md](../qa/industry-build-plan-execution-boundary-audit-report.md) — Build Plan conversion and execution-boundary audit (Prompt 597).
- [industry-what-if-simulation-audit-report.md](../qa/industry-what-if-simulation-audit-report.md) — What-if simulation integrity audit (Prompt 598).
- [industry-ai-planner-audit-report.md](../qa/industry-ai-planner-audit-report.md) — AI planner and prompt-pack audit (Prompt 599).
- [industry-styling-subsystem-audit-report.md](../qa/industry-styling-subsystem-audit-report.md) — Styling subsystem audit (Prompt 600).
- [industry-lpagery-audit-report.md](../qa/industry-lpagery-audit-report.md) — LPagery planning and binding audit (Prompt 601).
- [industry-conflict-caution-override-audit-report.md](../qa/industry-conflict-caution-override-audit-report.md) — Conflict, caution, and override-system audit (Prompt 602).
- [industry-shared-fragment-audit-report.md](../qa/industry-shared-fragment-audit-report.md) — Shared fragment resolver and adoption audit (Prompt 603).
- [industry-export-restore-uninstall-audit-report.md](../qa/industry-export-restore-uninstall-audit-report.md) — Export, restore, deactivation, and uninstall audit (Prompt 604).
- [industry-scaffold-promotion-audit-report.md](../qa/industry-scaffold-promotion-audit-report.md) — Scaffold, incomplete-state, and promotion-readiness audit (Prompt 605).
- [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md) — Finding ledger (585A); must exist before audit 586+.
- [industry-audit-remediation-workflow.md](industry-audit-remediation-workflow.md) — Audit → record → triage → remediate → verify.
- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md)
- [industry-greenfield-prompt-archive-map.md](industry-greenfield-prompt-archive-map.md) — Greenfield prompt archival map (Prompt 570); prompt ranges, authoritative contracts, transition point.
- [industry-optional-late-stage-greenfield-backlog.md](industry-optional-late-stage-greenfield-backlog.md) — Optional late-stage greenfield backlog (Prompt 585); Prompts 571–584; audit remains next priority.
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md)
- [industry-pack-service-map.md](../contracts/industry-pack-service-map.md)
- [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md)
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md)
- [industry-sandbox-promotion-workflow.md](industry-sandbox-promotion-workflow.md)
