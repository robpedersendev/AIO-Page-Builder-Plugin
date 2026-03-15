# ACF Conditional Registration — Acceptance Report

**Prompts**: 281–292 (ACF registration performance retrofit)  
**Contracts**: acf-conditional-registration-contract.md, large-scale-acf-lpagery-binding-contract.md §6.2–6.3, acf-page-visibility-contract.md

---

## 1. Objective

Verify that the heavy-load regression (full ACF registration on every request) is fixed while field values, LPagery compatibility, assignment-map behavior, and editor-visible group behavior remain unchanged.

---

## 2. QA checklist (contexts)

| Context | Expected behavior | Verification |
|--------|-------------------|--------------|
| **Front-end** | No ACF registration; no section definition load for ACF. | No `register_all()` or bulk section load on front-end requests. |
| **Existing-page edit** (post.php?post=X&action=edit, page) | Only that page's assigned section-owned groups registered; resolution from assignment map; optional section-key cache. | Same groups visible in editor as before; no full registration path. |
| **New-page edit** (post-new.php?post_type=page) | When template/composition chosen: only those sections' groups registered; otherwise no groups. | Template/composition picker drives which groups appear; no full registration. |
| **Non-page admin** (Dashboard, plugins, etc.) | Zero groups registered; no full registration. | No bulk load; `run_registration()` returns 0. |

---

## 3. Contract and invariant checks

| Area | Requirement | Verification |
|------|-------------|--------------|
| **Field values** | Post meta and ACF field read/write unchanged. | Same keys and values on front-end and in editor after retrofit. |
| **LPagery** | Token naming, token maps, injection, validation, fallbacks unchanged. | LPagery binding contract §6.2–6.3; no token or naming drift. |
| **Assignment map** | Authority and semantics unchanged; assign_from_template / assign_from_composition. | Assignment map remains source of truth; section-key cache invalidates on assignment change. |
| **Editor-visible groups** | Same groups shown for a given page as before retrofit. | Existing pages show same ACF groups in editor; new pages show groups for chosen template/composition. |

---

## 4. Before/after (heavy-load elimination)

| Metric | Before | After |
|--------|--------|-------|
| Front-end acf/init | register_all() → get_all_blueprints() → list_all_definitions(9999) | Skip registration; no section load. |
| Admin page edit | Same bulk path as front-end | Scoped: assignment map + group_key→section_key; optional cache. |
| Non-page admin | Same bulk path | No registration (0 groups). |

Evidence: [acf-blueprint-bulk-load-elimination-report.md](acf-blueprint-bulk-load-elimination-report.md), [acf-registration-performance-impact-analysis.md](acf-registration-performance-impact-analysis.md). Benchmark: [acf-registration-benchmark-protocol.md](acf-registration-benchmark-protocol.md).

---

## 5. Acceptance criteria (summary)

- [ ] Front-end: no registration path; no full-registration in generic requests.
- [ ] Existing-page edit: selective registration only; same groups as before for given page.
- [ ] New-page edit: template/composition-aware registration or none.
- [ ] Non-page admin: no full registration; zero groups.
- [ ] Field values and LPagery behavior unchanged.
- [ ] Assignment map authority and deterministic registration preserved.
- [ ] Section-key cache (optional) invalidates on assignment change; stale cache fails safe to correct resolution.

---

*Run manual QA against the checklist above and record pass/fail per context. Link this report in release gate and release-review packet.*
