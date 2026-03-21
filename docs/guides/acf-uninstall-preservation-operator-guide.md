# ACF Uninstall Preservation — Operator Guide

**Audience:** Site administrators and operators planning or performing plugin uninstall.  
**Spec:** acf-uninstall-retention-contract, acf-native-handoff-contract, PORTABILITY_AND_UNINSTALL.  
**Purpose:** Explain what is retained by default, what requires a handoff to preserve editable field groups, and how to avoid losing editor continuity.  
**Knowledge base:** [FILE_MAP.md](../kb/FILE_MAP.md) §16.

---

## 1. Two different things: values vs field groups

| Concept | Meaning | After uninstall (no handoff) |
|--------|---------|------------------------------|
| **Saved ACF values** | The data stored in the database for section fields (headlines, CTAs, images, etc.) on built pages. | **Retained.** Post meta is not deleted. Page content remains in the database. |
| **Editable field groups** | The ACF field group definitions that make those fields visible and editable in the page editor. | **Gone.** The plugin registers them only at runtime. When the plugin is removed, ACF no longer has those group definitions, so the editor will not show those fields. |

**Important:** Values (content) stay. The ability to **edit** those values in the usual ACF panels goes away unless you complete the handoff before uninstall.

---

## 2. What is retained by default (no action needed)

- **Saved ACF field values** in post meta. Built page content stays in the database.
- **Assignment map** (which section groups were assigned to which page). Not deleted on uninstall.
- **Built pages** (post type `page`). Never deleted by the plugin.
- **Any native ACF field groups** (including handed-off groups, if you ran handoff). Stored by ACF; plugin does not remove them.

See [acf-uninstall-retained-data-matrix.md](../operations/acf-uninstall-retained-data-matrix.md) for the full list.

---

## 3. What requires explicit preservation: editable field groups

If you want editors to **continue seeing and editing** section fields (ACF groups) after the plugin is removed, you must run the **handoff** before uninstall. The handoff:

- Takes the plugin’s runtime ACF group definitions (one per section).
- Creates equivalent **native** ACF field groups (stored in ACF’s database).
- Uses **page** location so those groups stay visible on page edit screens.
- Does **not** change or delete any saved values.

**Without handoff:** Values remain, but the field groups disappear from the editor. Content is still in the database; editing it in the usual ACF UI will no longer be possible unless you re-add field groups manually or reinstall the plugin.

---

## 4. Operator workflow: preserving editable field groups before uninstall

1. **Decide** whether you need editors to keep editing section fields after uninstall.
   - If **no**: Uninstall as usual. Values and pages remain; field groups will no longer appear in the editor.
   - If **yes**: Continue to step 2.

2. **Run the handoff** (admin-only, before uninstall).
   - Use the approved handoff entry point (e.g. tool or pre-uninstall step when implemented). The handoff generator materializes all plugin-owned runtime groups into native ACF groups.
   - Ensure ACF Pro is active; handoff uses ACF’s import API.

3. **Verify** handoff success using the [ACF uninstall preservation verification](../qa/acf-uninstall-preservation-verification.md) checklist (e.g. group count, visibility on a page edit screen).

4. **Then** proceed with uninstall. Built pages, saved values, and the newly created native ACF groups will remain. Only plugin-owned operational data (options, caches, registries, etc.) is removed.

---

## 5. What happens if you uninstall without handoff

- **Built pages:** Remain. No deletion.
- **Saved ACF values:** Remain in post meta. No deletion.
- **Section ACF field groups in the editor:** No longer shown. They were registered only by the plugin at runtime; once the plugin is gone, ACF has no definition for them.
- **Result:** Content is still in the database and can be read by themes or custom code, but the standard ACF editing UI for those section fields will not be available unless you run handoff before uninstall or recreate groups manually.

---

## 6. What gets removed on uninstall (plugin-owned only)

- Plugin options (settings, reporting state, profile, etc.).
- Plugin custom tables (e.g. build plans, queue).
- Plugin CPTs (section templates, page templates, compositions, etc.).
- Scheduled events (e.g. heartbeat).
- ACF section-key cache transients (`aio_acf_sk_*`). No other transients or post meta are removed.

Again, see [acf-uninstall-retained-data-matrix.md](../operations/acf-uninstall-retained-data-matrix.md) for the full retained-vs-removed list.

---

## 7. Safe failure

Uninstall is **non-destructive by default**. If something goes wrong, the plugin does not delete saved values or handed-off field groups. When in doubt, data is retained.

---

## 8. Cross-references

| Need | Doc |
|------|-----|
| Retained vs removed data (exact list) | [acf-uninstall-retained-data-matrix.md](../operations/acf-uninstall-retained-data-matrix.md) |
| Policy summary | [acf-uninstall-preservation-policy.md](../operations/acf-uninstall-preservation-policy.md) |
| Contract (values, groups, handoff) | [acf-uninstall-retention-contract.md](../contracts/acf-uninstall-retention-contract.md) |
| Handoff behavior and marker | [acf-native-handoff-contract.md](../contracts/acf-native-handoff-contract.md) |
| General uninstall policy | [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) |
| QA verification before uninstall | [acf-uninstall-preservation-verification.md](../qa/acf-uninstall-preservation-verification.md) |
