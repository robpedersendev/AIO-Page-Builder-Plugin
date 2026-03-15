# ACF Legacy Page — Repair Guide

**Prompt**: 309  
**Contracts**: acf-conditional-registration-contract.md, acf-page-visibility-contract.md

---

## 1. Purpose

Operational guide for support and developers when a page has missing or inconsistent assignment data and the edit screen shows no (or wrong) section-owned ACF groups. Repair must remain admin-only and must not rely on silent full registration.

---

## 2. When to use this guide

- User reports that editing a page shows no ACF section groups, or groups do not match expectation.
- Inspection utility (`scoped_registration_inspection_service->inspect_for_page( $page_id )`) shows empty or unexpected section_keys for a page that should have groups.
- Legacy or migrated pages that were built before assignment map was fully populated.

---

## 3. Identifying the cause

1. **Inspect resolution**: Use `Scoped_Registration_Inspection_Service::inspect_for_page( $page_id )`. Check `resolved`, `section_keys`, `group_keys`. If empty, assignment or derivation failed.
2. **Assignment map**: Page may have no PAGE_TEMPLATE/PAGE_COMPOSITION ref, or no PAGE_FIELD_GROUP refs. Assignment map is the source of truth; if empty, no groups will register.
3. **Template/composition metadata**: If the page was built from a template or composition, that may be stored elsewhere (e.g. post meta). Repair may involve re-running assignment from that template/composition.

---

## 4. Repair options (admin-only)

| Action | When | How |
|--------|------|-----|
| **Reassign from template** | Page should reflect a known page template. | Call or trigger `Page_Field_Group_Assignment_Service::assign_from_template( $page_id, $template_key, $full_replace )` with appropriate capability checks. Persists assignment and fires `aio_acf_assignment_changed`. |
| **Reassign from composition** | Page should reflect a known composition. | Same pattern with `assign_from_composition( $page_id, $composition_id, $full_replace )`. |
| **No automatic bulk repair** | Do not automatically rewrite all legacy pages unless a safe, authorized repair path exists. | Prefer per-page or batched repair with user/support confirmation. |

---

## 5. What not to do

- **Do not** enable full ACF registration as a permanent fallback for pages with missing assignment. Full registration is for explicit tooling only (acf-registration-exception-matrix.md).
- **Do not** expose repair actions to unauthenticated or low-privilege users.
- **Do not** assume all pages without assignment are errors; some may be intentionally minimal or pre-build.

---

## 6. Support workflow

1. Confirm the page ID and that the user has admin/edit capability.
2. Run inspection for that page; note resolved vs empty.
3. If empty and page should have groups: determine correct template or composition (from content, history, or user). Run assign_from_template or assign_from_composition via supported admin/tooling path.
4. Re-inspect to confirm section_keys and group_keys are now populated. User refreshes edit screen to see groups.

---

## 7. Cross-references

- acf-conditional-registration-contract.md
- acf-page-visibility-contract.md
- acf-legacy-assignment-verification.md
- acf-scoped-registration-inspection-guide.md (inspection utility)
- acf-conditional-registration-support-runbook.md
