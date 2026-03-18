# Industry Bundle Apply — Implementation Acceptance Criteria

**Decision:** [industry-bundle-apply-decision.md](industry-bundle-apply-decision.md)  
**Spec note:** [industry-bundle-apply-spec-note.md](industry-bundle-apply-spec-note.md)  
**Contracts:** [industry-pack-bundle-format-contract.md](../contracts/industry-pack-bundle-format-contract.md), [industry-pack-import-conflict-contract.md](../contracts/industry-pack-import-conflict-contract.md)

---

## 1. Apply flow and capability

- [ ] An admin-only “Apply” (or equivalent) action is available after a valid bundle has been previewed and (where applicable) conflicts have been resolved or default policy chosen.
- [ ] Apply requires the same capability as bundle preview (e.g. MANAGE_SETTINGS or plugin-defined import capability) and a dedicated nonce for the apply action.
- [ ] Apply is only offered when the preview state contains a valid, parseable bundle that passed upload and structural validation.

---

## 2. Persistence and registries

- [ ] Applied content is written to the correct industry registries/overlays per bundle categories (packs, starter_bundles, style_presets, cta_patterns, seo_guidance, lpagery_rules, section_helper_overlays, page_one_pager_overlays, question_packs, and optionally site_profile).
- [ ] Only objects with final_outcome = applied (per conflict resolution) are persisted; skipped/failed objects are not written.
- [ ] Apply does not bypass or replace the full Import/Export (ZIP) restore pipeline; it is a separate path for industry-only data.

---

## 3. Conflict resolution and validation

- [ ] Conflict analysis from the existing `Industry_Pack_Import_Conflict_Service` is used; apply uses the same conflict list and resolution (operator choice or default policy).
- [ ] All objects that will be applied pass schema validation again at apply time; invalid payloads are skipped and recorded in the result.
- [ ] Unresolved error-level conflicts (per industry-pack-import-conflict-contract) result in no apply for that category or abort of the entire bundle import with a clear, user-facing message.

---

## 4. Safety and auditability

- [ ] No silent overwrite: resolution (replace/skip) is explicit and, where required by the contract, auditable (e.g. final_outcome per object).
- [ ] Apply failure (validation, dependency, or storage error) does not leave registries in a partially applied state where feasible; otherwise the outcome is clearly communicated and logged.
- [ ] Apply action is logged (e.g. who, when, bundle version, categories applied, counts) for support and audit.

---

## 5. UX and messaging

- [ ] After apply, the user receives clear success or failure feedback (e.g. “Bundle applied” with counts, or “Apply failed: [reason]”).
- [ ] Preview state (transient) is cleared or updated after a successful apply so the UI does not suggest re-applying the same bundle without re-upload.
- [ ] Help/admin copy states that industry bundle apply writes to industry registries/overlays and that full site backup/restore is via Import / Export (ZIP).

---

## 6. Security and permissions

- [ ] Apply handler verifies nonce and capability before any persistence.
- [ ] No secrets or executable content are written from the bundle; validation rules from the bundle-format and import-conflict contracts are enforced.

---

## 7. Tests

- [ ] Unit or integration tests cover: apply with no conflicts; apply with replace/skip resolutions; apply with error-level conflict (abort or no apply for category); invalid payload at apply time (skipped, recorded); capability and nonce failure (no apply).
