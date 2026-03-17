# Industry Subsystem Drift Detection Contract (Prompt 561)

**Spec:** contract consistency audit docs; maturity and maintenance docs; release gate docs.  
**Status:** Contract. Defines the **internal** drift-detection model that flags when contracts, schemas, docs, report assumptions, and seeded asset practices begin diverging across the industry subsystem. No implementation of the detector in this prompt; no auto-fixes.

---

## 1. Purpose

- **Integrity guardrail:** As the subsystem grows, documentation drift, authoring-practice drift, and asset-convention drift become meaningful risks. This contract defines what "drift" means and how it is evidenced so maintainers can reason about it explicitly.
- **Advisory and internal:** Drift detection is for maintenance and release review only. No public exposure; no hidden auto-remediation. Contracts and schemas remain the authoritative references.
- **Bounded and explainable:** Drift types and severity are fixed and documented. Evidence sources are identified so detection remains auditable.

---

## 2. Types of drift

| Drift type | What is measured | Examples |
|------------|------------------|----------|
| **Contract drift** | Code or runtime behavior diverges from a documented contract (e.g. industry-pack-extension-contract, overlay schema, export/restore contract). | New extension path not in approved seams; overlay region used that contract forbids; export payload shape changed without contract update. |
| **Schema drift** | Runtime data or definitions diverge from a documented schema (e.g. industry-pack-schema, industry-profile-schema, overlay schemas). | Pack definition missing required field; profile stores key not in schema; overlay shape differs from schema. |
| **Docs drift** | Documentation (runbooks, checklists, catalogs, appendices) is out of date with implementation or with other docs. | Authoring guide references removed file path; release checklist step no longer applies; catalog lists deprecated key without note. |
| **Report-assumption drift** | Report generators or dashboards assume shapes, keys, or registries that have changed. | Completeness report assumes dimension set that no longer exists; health check expects registry method that was renamed. |
| **Seeded-asset / convention drift** | Builtin or seeded assets (overlays, bundles, packs, rules) diverge from documented conventions or from each other. | One pack uses token_preset_ref and another uses style_preset_key; overlay files use inconsistent region ordering; bundle definitions skip optional fields that other bundles set. |

Each type may have type-specific evidence sources (see §4). Severity distinguishes severe (blocking or high-risk) from minor (cosmetic or low-impact) — see §3.

---

## 3. Severity: severe vs minor

- **Severe drift:** Contract or schema violation that could cause runtime failure, data loss, or security/consistency bypass; or doc/report drift that would mislead release or support decisions. Must be flagged for immediate review. Examples: pack schema required field missing in builtin pack; export contract says X but code emits Y; release checklist claims a step that no longer runs.
- **Minor drift:** Inconsistency that does not affect correctness or release decisions but should be cleaned up. Examples: two docs use different terminology for the same concept; one overlay file uses a comment style others don't; catalog is missing a recently added key. Flag for next maintenance window.

Severity is assigned per finding; a single run may report both severe and minor items. No automatic remediation; human decides fix, defer, or waive.

---

## 4. Evidence sources for drift detection

| Source | What it provides | Drift use |
|--------|------------------|-----------|
| **Contract documents** | Authoritative shape and behavior (extension seams, schema fields, allowed overlay regions). | Compare code or definitions against contract; flag when code adds seam not in contract or uses disallowed shape. |
| **Schema documents** | Required/optional fields, types, enums. | Compare registry load output or export payload against schema; flag missing required, unknown field, or type mismatch. |
| **Registry implementations** | Actual load behavior, method signatures, returned shapes. | Compare report or dashboard expectations (e.g. get_for_industry return shape) to actual; flag when report assumes old shape. |
| **Builtin / seeded definitions** | Pack files, overlay files, bundle files, rule files. | Compare conventions across files (naming, optional fields, status values); flag outliers. |
| **Docs and runbooks** | Checklists, guides, catalogs, appendices. | Compare doc content to code paths, file paths, and contract references; flag broken links, outdated steps, or missing new items. |
| **Report generators and view models** | Expected keys, dimensions, and data sources. | Compare report code to current registries and contracts; flag when report uses deprecated or renamed API. |

Evidence sources are inputs to a drift detector; the detector does not mutate contracts, schemas, or assets. It produces a **drift report** (findings with type, severity, evidence refs, explanation, suggested review path).

---

## 5. Operational use

- **Maintenance:** Run drift detection (when implemented) as part of maintenance cycles or before major changes. Use findings to update docs, align code to contracts, or update contracts after deliberate change.
- **Release review:** Drift report can feed release gate or pre-release checklist: "No severe drift" or "Severe drift items resolved or waived." Does not replace existing gate criteria; it adds an integrity check.
- **Contract consistency:** When auditing contract consistency (e.g. ensuring all extension seams are documented, all schema fields are implemented), drift report can surface gaps. Resolve by updating contract or code, not by auto-fix.

---

## 6. Relation to existing artifacts

- **Contracts and schemas:** Remain authoritative. Drift detection compares against them; it does not overwrite them.
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) and pre-release pipeline may reference drift report as optional or required evidence. Policy is runbook-defined.
- **Maintenance checklist:** [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) can include a step to run drift detection and address findings.
- **Linting:** Industry definition linters (e.g. schema validation, ref resolution) may overlap with schema/contract drift. Drift detection is broader (docs, report assumptions, cross-asset conventions) and may integrate with lint output in a future implementation.

---

## 7. References

- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Maintenance baseline.
- [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) — Capability areas and maturity.
- [industry-subsystem-roadmap-contract.md](industry-subsystem-roadmap-contract.md) — Extension seams and approved behavior.
