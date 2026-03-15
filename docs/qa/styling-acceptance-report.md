# Styling Subsystem — QA Acceptance Report

**Document type:** QA acceptance evidence for the styling subsystem (Option A expansion; Prompts 242–260).  
**Spec refs:** §17.10, §18, §18.11, §53.5–53.9, §59.14, §60.4, §60.5, §60.6.  
**Contract:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md).  
**Release gate:** [styling-release-gate.md](../release/styling-release-gate.md).

---

## 1. Acceptance scope

| Area | Scope | Evidence type |
|------|--------|----------------|
| **Storage** | Global style settings and per-entity payloads; schema version; option keys in Option_Names for uninstall. | Unit tests (repositories, schema); lifecycle doc. |
| **UI** | Global token and component override screens; per-entity style on Section/Page Template Detail; capability and nonce. | Manual / screen tests; security checklist. |
| **Rendering** | Frontend token/override emission; page and section style emitters; no injection into post_content. | Unit tests (emitters, enqueue); contract. |
| **Preview** | Detail and compare previews receive styling from same pipeline; synthetic data only. | Unit tests (Preview_Style_Context_Builder); compatibility. |
| **Compare** | Compare workspace shows styled preview excerpts; observational only. | Manual; operator guide. |
| **Cache** | Style cache version and invalidation on settings/entity change; restore invalidates. | Unit tests (Style_Cache_Service); restore pipeline. |
| **Export/restore** | Styling included in export; restore normalizes and sanitizes before persist; invalid data skipped. | Unit tests (sanitizer); Restore_Pipeline; security review. |
| **Security** | Capability and nonce on all mutation paths; whitelist sanitization; no arbitrary CSS/selectors; restore sanitization. | [styling-security-review.md](../security/styling-security-review.md); [styling-security-checklist.md](../security/styling-security-checklist.md). |
| **Lifecycle** | Deactivation: plugin CSS stops, content preserved. Uninstall: styling options removed, built pages preserved. Theme continuity documented. | [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md); Option_Names; known-risk STY-1. |

---

## 2. Test evidence summary

| Test suite / area | Coverage | Status |
|-------------------|----------|--------|
| Styles_JSON_Sanitizer_Test | Valid/invalid payloads; prohibited patterns; entity payload unsafe value rejected. | Pass (Prompts 252, 259). |
| Styles_JSON_Normalizer_Test | Global tokens, component overrides, entity payload normalization. | Pass. |
| Style_Validation_Result_Test | Result structure and bounded errors. | Pass. |
| Global_Style_Settings_Repository_Test | Read/write; persist via result. | Pass. |
| Entity_Style_Payload_Repository_Test | Get/persist/delete by entity type and key. | Pass. |
| Global_Style_Token_Settings_Screen_Test | Screen capability and render. | Pass. |
| Frontend_Style_Enqueue_Service_Test | Enqueue and emitter integration. | Pass. |
| Page_Style_Emitter_Test / Section_Style_Emitter_Test | Emission from payload only; no raw CSS. | Pass. |
| Preview_Style_Context_Builder_Test | Preview styling context. | Pass. |
| Style_Cache_Service_Test | Version read/invalidate. | Pass. |
| Entity_Style_Form_Builder_Test / Entity_Style_UI_State_Builder_Test | Form and UI state. | Pass. |
| Restore_Pipeline (styling) | Styling restore uses normalizer/sanitizer; invalid data not persisted. | Code path verified; manual/export-restore QA recommended. |

---

## 3. Artifact traceability

| Deliverable | Artifact |
|-------------|----------|
| Contract and specs | [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md); [css-selector-contract.md](../contracts/css-selector-contract.md); pb-style-core-spec.json, pb-style-components-spec.json, pb-style-render-surfaces-spec.json. |
| Storage and schema | Global_Style_Settings_Schema, Entity_Style_Payload_Schema; Option_Names (GLOBAL_STYLE_SETTINGS, ENTITY_STYLE_PAYLOADS, STYLE_CACHE_VERSION). |
| Sanitization | [styling-sanitization-rules.md](../security/styling-sanitization-rules.md); Styles_JSON_Normalizer; Styles_JSON_Sanitizer. |
| Lifecycle and portability | [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md); Uninstall_Cleanup_Service. |
| Security | [styling-security-checklist.md](../security/styling-security-checklist.md); [styling-security-review.md](../security/styling-security-review.md). |
| Release gate | [styling-release-gate.md](../release/styling-release-gate.md). |
| Known risks | [known-risk-register.md](../release/known-risk-register.md) §3 STY-1; §4 cross-references. |

---

## 4. Blocker and fix notes

- **Blockers:** None identified at report creation. Any failure in the release gate that blocks milestone exit must be fixed or waived per [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md).
- **Dependencies:** Styling assumes style specs (pb-style-*-spec.json) and registries loaded; ACF/GenerateBlocks required for full preview rendering (same as template library).
- **Environment:** Global and per-entity styling are optional; built pages remain meaningful without plugin CSS (spec §17.10).

---

## 5. Sign-off readiness

- [ ] All in-scope test suites executed and recorded (pass/fail/waiver).
- [ ] Release gate checklist completed; blockers vs deferred clearly marked.
- [ ] No critical/high open for styling in hardening issue register; STY-1 documented and mitigated.
- [ ] Operator and support docs updated; styling behavior and removal/override expectations clear.
- [ ] Acceptance evidence sufficient for milestone-level QA review per §60.4, §60.5.

*Update this report when gate checklist is run and when any styling-related scenario fails or is waived.*
