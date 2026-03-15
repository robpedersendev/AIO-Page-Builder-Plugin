# ACF Conditional Registration — Diagnostics Checklist

**Prompt**: 291 (ACF conditional-registration diagnostics and instrumentation)  
**Contract**: acf-conditional-registration-contract.md

---

## 1. Purpose

Verify that conditional-registration diagnostics are bounded, support-usable, and free of sensitive data. Use for support triage and debugging registration path issues.

---

## 2. Diagnostics payload (internal only)

| Field | Description |
|-------|-------------|
| `mode` | `front_end_skip`, `existing_page`, `new_page`, or `non_page_admin`. |
| `section_key_count` | Number of section keys resolved (0 when skipped or non-page admin). |
| `cache_used` | Whether the section-key cache was used for this resolution. |
| `full_registration_invoked` | Whether `register_all()` was run (should be false in normal flow). |

**Access**: `ACF_Registration_Diagnostics_Service::get_last_registration()`. Only recorded when `is_admin()`; not stored on front-end.

---

## 3. Verification checklist

- [ ] **Modes**: In admin, existing-page edit shows `mode = existing_page` and non-zero `section_key_count` when page has assignments; new-page edit shows `new_page` when template/composition is chosen; non-page admin (e.g. Dashboard) shows `non_page_admin` and `section_key_count = 0`.
- [ ] **Cache**: Repeated load of same existing-page edit uses cache on second load (`cache_used = true` when cache hit).
- [ ] **No full registration in normal flow**: `full_registration_invoked` remains false for standard admin page loads.
- [ ] **No sensitive data**: Last registration payload contains only mode, counts, and booleans; no field values, post content, or user data.
- [ ] **Bounded**: Single last-registration record per request; no unbounded log growth.
- [ ] **Front-end**: Diagnostics are not recorded on front-end (no registration run); no public exposure of diagnostics output.

---

## 4. Support usage

- To confirm which path ran: read `get_last_registration()['mode']`.
- To confirm cache is helping: compare `cache_used` on first vs repeated load of same page edit.
- To detect unexpected full registration: check `full_registration_invoked` (investigate if true in normal admin).

---

*Internal use only. Do not expose diagnostics in public APIs or logs.*
