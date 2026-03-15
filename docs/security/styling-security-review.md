# Styling Subsystem Security Review

**Scope:** Styling-related settings saves, per-entity style edits, styles_json sanitization, runtime emission, preview, export/restore, and lifecycle.  
**Contracts:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md), [styling-sanitization-rules.md](styling-sanitization-rules.md), [styling-security-checklist.md](styling-security-checklist.md).  
**Purpose:** Findings and hardening summary for the styling subsystem (Prompt 259).

---

## 1. Audit Summary

| Area | Status | Notes |
|------|--------|-------|
| Global style token save | Hardened | Capability and nonce present (Global_Style_Token_Settings_Screen); repository persists only via sanitizer result when valid. |
| Global component overrides save | Hardened | Same screen; same persist path. |
| Per-entity style save (section/page) | Hardened | Nonce verified; **explicit capability check added** in process_entity_style_save (defense in depth). Payload normalized and sanitized before persist. |
| Restore styling | Hardened | **Restore path now normalizes and sanitizes** decoded JSON before writing; invalid global or entity data skipped and logged. No unsanitized styling data persisted from package. |
| Runtime emission (frontend, page, section) | Acceptable | All emission from repository or sanitizer-approved data; no raw user input in emission path. |
| Preview styling | Acceptable | Uses same emitters/repository; no raw CSS from request. |
| Export styling | Acceptable | Reads from options (already sanitized at write); no injection path. |

---

## 2. Fixes Applied (Prompt 259)

1. **Restore_Pipeline styling restore:** Decoded styling JSON from the package is no longer written directly to options. Global settings and entity payloads are normalized and passed through Styles_JSON_Sanitizer; only valid data is persisted. Invalid global settings or individual entity payloads are skipped; a warning is logged when validation fails.
2. **Entity style save capability:** Section_Template_Detail_Screen and Page_Template_Detail_Screen now perform an explicit `current_user_can( $this->get_capability() )` check at the start of `process_entity_style_save`, in addition to the existing nonce verification and screen-level capability gate.
3. **Restore_Pipeline dependency:** Optional Styles_JSON_Normalizer and Styles_JSON_Sanitizer are injected via ExportRestore_Provider when available; when either is missing, styling restore is skipped and a warning is logged (no unsanitized write).

---

## 3. Security Rules (Styling-Specific)

- **No arbitrary CSS:** Storage and emission of raw CSS text or user-supplied selectors is forbidden. Whitelist-based token and component keys only.
- **No arbitrary selectors:** Only contract-defined selectors and token names (css-selector-contract); no injection of new structural selectors.
- **Prohibited value patterns:** Values containing `url(`, `expression(`, `javascript:`, `vbscript:`, `data:`, `<`, `>`, `{`, `}` are rejected by Styles_JSON_Sanitizer and must not be persisted or emitted.
- **Fail closed:** Invalid or malformed style input is not persisted; invalid restore data is skipped. Validation errors are bounded and safe for admin UI and logs.
- **Capability and nonce:** All styling mutation paths require appropriate capability and (where applicable) nonce verification; restore runs in IMPORT_DATA-gated context.

---

## 4. Test Coverage

- **Styles_JSON_Sanitizer_Test:** Covers valid payloads pass, invalid keys/values rejected, prohibited patterns (url, expression, angle brackets, braces) rejected, max length, entity payload validation.
- **Restore styling:** Restore_Pipeline styling restore with normalizer/sanitizer ensures only sanitized data is written; unit tests may assert that invalid payloads are not persisted (see test updates below).
- **Permission-denied:** Manual or integration QA recommended for entity style save when user lacks capability (checklist in styling-security-checklist.md).

---

## 5. Known Risks / Deferred

| ID | Description | Mitigation |
|----|-------------|------------|
| — | None deferred. | High-severity issues addressed in §2. |

Any future styling feature that accepts new input paths must be run through the same normalizer/sanitizer pipeline and documented in this review and the checklist.

---

## 6. Cross-References

- [styling-security-checklist.md](styling-security-checklist.md) — Operational checklist.
- [styling-sanitization-rules.md](styling-sanitization-rules.md) — Whitelist and prohibited patterns.
- [known-risk-register.md](../release/known-risk-register.md) — STY-1 (lifecycle); styling security hardening recorded here.
