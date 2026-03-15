# ACF Uninstall Preservation — Verification Checklist

**Purpose:** Verify that value retention and (when used) handoff behave as documented before and after uninstall.  
**Audience:** QA, support, operators.  
**Spec:** acf-uninstall-retention-contract, acf-native-handoff-contract, acf-uninstall-retained-data-matrix.

---

## 1. Before uninstall: value retention expectations

| # | Check | Expected | Notes |
|---|--------|----------|--------|
| 1.1 | Built pages exist (post type `page`) | Pages created by the builder are present. | Uninstall must not delete them. |
| 1.2 | ACF field values in post meta | Values for section fields (e.g. headline, CTA) are stored in post meta for those pages. | Uninstall must not delete post meta for built pages. |
| 1.3 | Assignment map / group assignment | Plugin has recorded which ACF groups are assigned to which page (for runtime registration). | Per contract, assignment map is retained by default (not removed on uninstall). |

**Pass criteria:** Confirm built pages and sample post meta exist; document sample page ID and meta keys for post-uninstall comparison.

---

## 2. Before uninstall: handoff completion (if preserving editable groups)

| # | Check | Expected | Notes |
|---|--------|----------|--------|
| 2.1 | Handoff entry point available | Admin can run the handoff (tool or pre-uninstall step as implemented). | Handoff generator must be invoked; it does not run automatically. |
| 2.2 | ACF Pro active | ACF is installed and active. | Handoff uses `acf_import_field_group`; fails gracefully if ACF unavailable. |
| 2.3 | Handoff result | Result shows `imported` count and no critical errors. | Check for `skipped_existing` (unrelated groups not overwritten) and `errors` array. |
| 2.4 | Native groups present after handoff | In ACF → Field Groups (or equivalent), groups with keys `group_aio_*` exist and are stored (not local-only). | Confirms materialization to persistent/native ACF storage. |
| 2.5 | Group location | Handed-off groups use location “Page” (post type page). | So they appear on page edit screens after plugin removal. |
| 2.6 | Field names unchanged | Handed-off group field names match pre-handoff (e.g. headline, cta). | Ensures existing post meta values remain addressable. |

**Pass criteria:** Handoff runs without fatal errors; expected number of groups imported; at least one page edit screen shows the handed-off groups.

---

## 3. After uninstall: value retention

| # | Check | Expected | Notes |
|---|--------|----------|--------|
| 3.1 | Built pages still exist | Same page IDs (or same URLs/titles) as before. | No deletion of post type `page`. |
| 3.2 | Post meta for section fields | Sample meta keys (e.g. field names used by section fields) still have values on a built page. | No deletion of ACF value meta. |
| 3.3 | Front-end display | Built page(s) still render; section content (headlines, CTAs, etc.) still visible if theme/block uses that meta. | Values are in DB; rendering depends on theme/code. |

**Pass criteria:** No loss of built pages or of stored ACF values in post meta.

---

## 4. After uninstall: preserved field groups (if handoff was run)

| # | Check | Expected | Notes |
|---|--------|----------|--------|
| 4.1 | Native ACF groups still exist | Groups with keys `group_aio_*` still appear in ACF → Field Groups. | They are owned by ACF, not the plugin. |
| 4.2 | Page edit screen shows groups | Editing a built page shows the section ACF groups and fields. | Location is “Page”; no plugin required. |
| 4.3 | Values load in editor | Field values (headline, CTA, etc.) appear in the ACF fields when editing the page. | Field names unchanged; post meta maps correctly. |
| 4.4 | Edit and save | Changing a value and saving updates post meta; front-end reflects change. | Full editor continuity. |

**Pass criteria:** Editors can open built pages and see, edit, and save section fields without the plugin.

---

## 5. After uninstall: no destructive cleanup

| # | Check | Expected | Notes |
|---|--------|----------|--------|
| 5.1 | No post meta wiped | ACF value meta keys on built pages are still present. | Uninstall must not delete them. |
| 5.2 | No ACF field groups deleted | Any native ACF groups (including handed-off) are still in ACF storage. | Plugin does not touch ACF CPTs. |
| 5.3 | Only plugin-owned data removed | Plugin options, plugin CPTs, plugin tables, ACF cache transients (aio_acf_sk_*) may be removed. | Per retained-data matrix. |

**Pass criteria:** Only documented plugin-owned data is removed; no accidental deletion of values or native groups.

---

## 6. Manual QA summary

- **Value retention:** Document one built page ID and 2–3 meta keys; verify before uninstall and again after. Values must remain.
- **Handoff (optional):** Run handoff; verify group count and visibility on one page edit; uninstall; verify groups still visible and editable on that page.
- **Disclosure:** Confirm operator guide and Privacy/Uninstall messaging clearly state that values are retained by default and that editable field groups require handoff.

---

## 7. Cross-references

- [acf-uninstall-preservation-operator-guide.md](../guides/acf-uninstall-preservation-operator-guide.md)
- [acf-uninstall-retained-data-matrix.md](../operations/acf-uninstall-retained-data-matrix.md)
- [acf-uninstall-retention-contract.md](../contracts/acf-uninstall-retention-contract.md)
- [acf-native-handoff-contract.md](../contracts/acf-native-handoff-contract.md)
