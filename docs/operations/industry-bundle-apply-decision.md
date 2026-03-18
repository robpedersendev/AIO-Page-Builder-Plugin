# Industry Bundle JSON Import — Apply Behavior Decision

**Date:** 2025-03-18  
**Status:** Accepted  
**Sources:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md), [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md), [security-privacy-audit-close-report.md](../qa/security-privacy-audit-close-report.md), [industry-pack-bundle-format-contract.md](../contracts/industry-pack-bundle-format-contract.md), [industry-pack-import-conflict-contract.md](../contracts/industry-pack-import-conflict-contract.md).

---

## 1. Objective

Resolve the product/spec status of industry bundle JSON import **apply** behavior: whether confirm/import shall persist bundle content to the site, or remain preview-only.

---

## 2. Current state (pre-decision)

- **Bundle preview:** Implemented. User uploads JSON; upload validated (size, MIME, structure); bundle parsed; conflict analysis via `Industry_Pack_Import_Conflict_Service`; preview and conflicts shown in `Industry_Bundle_Import_Preview_Screen`.
- **Apply:** Not implemented. No handler persists bundle content to industry registries/storage. UI states that applying bundle content is not yet supported and directs users to Import / Export for full restore.
- **SPR-007:** Apply intentionally deferred; ledger and closeout report state that the (master) spec does not define apply semantics.

---

## 3. Spec and architecture findings

### 3.1 Master spec

- The master spec **does not** define industry bundle JSON apply. It defines:
  - Import/Export domain: export package creation, manifest, **ZIP** structure, import validation, restore sequencing, conflict handling (§4.14).
  - Use of ZIP archives for settings exports, artifact bundles, restore packages (§9.9).
- The master spec **does not** reference `industry-pack-bundle-format-contract` or `industry-pack-import-conflict-contract`.
- Industry pack extension layer is in scope (§0.4) but bundle **import apply** is not explicitly called out.

### 3.2 Contract layer

- **industry-pack-bundle-format-contract:** Defines portable pack bundle format (manifest, categories: packs, starter_bundles, style_presets, etc.). §6 states that pack bundle **import applies to industry registries/overlays**; conflict resolution is defined in industry-pack-import-conflict-contract.
- **industry-pack-import-conflict-contract:** Defines conflict types (duplicate_key, version, missing_dependency, invalid_payload, etc.), resolution policies per category (replace/skip/merge), conflict result shape, **validation before apply**, **safe failure** (unresolved error-level → do not apply). Consumer: “Bundle import flow (admin-only) calls the service to analyze, presents conflicts to operator (or uses default policy), then **applies** only objects with final_outcome = applied.”

**Conclusion:** The **contracts** define implementation-ready apply semantics (what content, where it is written, overwrite/merge/conflict behavior, validation, safe failure). The **master spec** does not currently define or require bundle apply; the gap is spec-level, not contract-level.

---

## 4. Decision

**Outcome: A — Bundle apply is in scope.**

- **Rationale:** The contracts already specify what can be written, where, overwrite/merge/conflict behavior, validation, and rollback/safe failure. The only missing link is the master spec explicitly requiring the feature and referencing those contracts. Adding a spec note does that; implementation can then proceed against the existing contracts.
- **Scope of apply:** Apply semantics are defined in implementation-ready terms by:
  - [industry-pack-bundle-format-contract.md](../contracts/industry-pack-bundle-format-contract.md) — bundle structure, categories, validation, relationship to full export/restore.
  - [industry-pack-import-conflict-contract.md](../contracts/industry-pack-import-conflict-contract.md) — conflict detection, resolution policies per category, validation-before-apply, safe failure, auditability.

---

## 5. Required semantics (implementation-ready, from contracts)

| Aspect | Definition (source) |
|--------|---------------------|
| **What bundle content can be written** | Categories in bundle manifest: packs, starter_bundles, style_presets, cta_patterns, seo_guidance, lpagery_rules, section_helper_overlays, page_one_pager_overlays, question_packs, site_profile (optional). Per-category payloads are definition objects; no executable content, no secrets. |
| **Where it is written** | Industry registries/overlays (pack registry, starter bundle registry, style preset registry, overlay registries, profile/store for site_profile). Full export/restore pipeline remains separate; pack bundle apply does not bypass it. |
| **Overwrite/merge/conflict** | Per industry-pack-import-conflict-contract: duplicate_key → replace or skip (operator choice per category); newer_version → replace; older_version → skip (or replace with override); invalid_payload → skip; missing_dependency → warn/skip or fail per severity. All resolutions auditable (final_outcome per object). |
| **Validation** | Manifest required (bundle_version, schema_version, created_at, included_categories); bundle_version must be supported; per-object schema validation at apply time; invalid payloads skipped and recorded. |
| **Rollback/recovery** | No single-command rollback specified in contracts. Safe failure: unresolved error-level conflict MUST NOT apply any change for that category or MUST abort entire bundle import with clear message. Apply only objects with final_outcome = applied. |

---

## 6. Spec note / revision

A spec revision note is added so the master spec explicitly includes industry bundle apply and defers detailed semantics to the contracts. See [industry-bundle-apply-spec-note.md](industry-bundle-apply-spec-note.md).

---

## 7. UX and docs (after implementation)

- **UI:** Preview step remains; a confirmed “Apply” action (after conflict resolution) persists bundle content per conflict contract. UX must not imply apply is optional or unsupported once implemented.
- **Docs:** Admin-facing copy should state that industry bundle import (JSON) supports preview and apply; apply writes to industry registries/overlays per conflict resolution; for full site backup/restore, use Import / Export (ZIP).

---

## 8. If outcome had been B (out of scope)

If the decision had been to keep apply **out of scope**, the following would be the intended product state:

- **UX wording to remain true:** “This is a preview only. Applying industry bundle content is not yet supported. To restore plugin data, use [Import / Export].”
- **Docs:** Industry bundle import screen is preview-only; apply is not implemented; for full restore use the Import / Export flow (ZIP).
- **Ledger/closeout:** SPR-007 remains “Intentionally deferred”; no implementation acceptance criteria for apply.

This outcome was not chosen; it is documented here for traceability only.
