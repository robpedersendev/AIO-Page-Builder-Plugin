# Styling Subsystem Security Checklist

**Upstream:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md) §9, [styling-sanitization-rules.md](styling-sanitization-rules.md), [styling-security-review.md](styling-security-review.md).  
**Purpose:** Operational checklist for verifying styling-related security before release or after changes.

---

## 1. Mutation Paths (Save / Update)

| Check | Location | Expected |
|-------|----------|----------|
| Global token save | Global_Style_Token_Settings_Screen | Capability check before render; nonce on POST save/reset; payload passed through normalizer and sanitizer; only Style_Validation_Result valid persisted. |
| Global component overrides save | Same screen / repository | Same as above; component override keys validated against component spec. |
| Per-entity style save (section) | Section_Template_Detail_Screen::process_entity_style_save | Capability (MANAGE_SECTION_TEMPLATES) checked; nonce verified; POST payload normalized and sanitized; only valid result persisted. |
| Per-entity style save (page template) | Page_Template_Detail_Screen::process_entity_style_save | Capability (MANAGE_PAGE_TEMPLATES) checked; nonce verified; same pipeline as section. |
| Styling restore | Restore_Pipeline restore_category('styling') | Decoded JSON from package normalized and sanitized; only valid global/entity data written; invalid data skipped and logged. No raw CSS or unsanitized values persisted. |

---

## 2. Output / Emission Paths

| Check | Location | Expected |
|-------|----------|----------|
| Frontend base styles | Frontend_Style_Enqueue_Service | Inline CSS built from Global_Token_Variable_Emitter and Global_Component_Override_Emitter only; both use registry-validated data; no user-supplied raw CSS. |
| Page-level styles | Page_Style_Emitter | Reads from Entity_Style_Payload_Repository (already persisted via sanitizer); emits only variable declarations from validated payload. |
| Section-level styles | Section_Style_Emitter | Same as page; payload from repository only. |
| Preview styling | Preview_Style_Context_Builder | Uses same emitters or repository data; no raw CSS from request. |

---

## 3. Prohibited Patterns (Values)

Per styling-sanitization-rules.md, any value containing the following must be **rejected** (not stored, not emitted):

- `url(`
- `expression(`
- `javascript:`
- `vbscript:`
- `data:`
- `<`
- `>`
- `{`
- `}`

No arbitrary selectors, raw CSS text, or `<style>` content may be stored or emitted.

---

## 4. Capability and Nonce Summary

| Action | Capability | Nonce |
|--------|------------|-------|
| Save global style tokens | Screen capability (manage options / styling) | aio_global_style_tokens_save / aio_global_style_tokens_reset |
| Save entity style (section) | MANAGE_SECTION_TEMPLATES | aio_entity_style_save |
| Save entity style (page template) | MANAGE_PAGE_TEMPLATES | aio_entity_style_save |
| Restore (includes styling) | IMPORT_DATA (at restore entry) | Via import flow |

---

## 5. Export / Restore

| Check | Expected |
|-------|----------|
| Export styling | Reads from options (data previously persisted via sanitizer); no raw user input in export path. |
| Restore styling | Package JSON decoded then normalized and sanitized; only valid data written; invalid global or per-payload data skipped. |
| Support bundle | Styling included only as stored option data; redaction per support bundle rules; no secrets in styling. |

---

## 6. QA Checklist (Styling Security)

- [ ] Unsafe value (e.g. `url(javascript:...)`, `expression(1)`, `<script>`) in global token save is rejected and not persisted.
- [ ] Unsafe value in per-entity style form is rejected and not persisted.
- [ ] Restore of a package containing tampered styling (unsafe value in global or entity JSON) does not persist the unsafe value; valid payloads still restore.
- [ ] User without MANAGE_SECTION_TEMPLATES cannot persist entity style for a section template (e.g. direct POST with valid nonce from another session).
- [ ] User without MANAGE_PAGE_TEMPLATES cannot persist entity style for a page template.
- [ ] Preview and front-end emission do not output raw user-supplied CSS or arbitrary selectors.
- [ ] No styling-related debug or internal data (e.g. validation errors with raw input) leaked to non-admin or in export/restore prompts.

---

*Use with [styling-security-review.md](styling-security-review.md) for findings and risk register.*
