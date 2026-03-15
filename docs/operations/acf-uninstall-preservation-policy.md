# ACF Uninstall Preservation Policy

**Audience**: Operators, support, release.  
**Governs**: Uninstall behavior for ACF integration; value retention vs group preservation.  
**Spec**: §17.10; PORTABILITY_AND_UNINSTALL; **acf-uninstall-retention-contract.md**.

---

## 1. Policy summary

- **Default uninstall is non-destructive.** The plugin does not delete saved ACF field values, assignment map data, or built content when uninstalled.
- **Field values** (post meta) are **retained by default**. Pages keep their section field data so content survives.
- **Field groups** (the definitions that make fields editable in the editor) are **runtime-only** unless an explicit preservation step is used. After uninstall, ACF will no longer show those groups; values remain in the database.
- **Preservation** (keeping groups editable after uninstall) is **optional** and **admin-initiated**. It may be done by a pre-uninstall handoff (creating native ACF groups) or by exporting group definitions for later use. The plugin does not perform handoff or export automatically on uninstall unless a future feature explicitly does so and documents it.

---

## 2. What is preserved without action

- Saved ACF field values in post meta.
- Assignment map (which section groups were assigned to which page).
- Built pages and content (per PORTABILITY_AND_UNINSTALL).

---

## 3. What requires explicit preservation

- **Editable field groups** after uninstall: if operators want to continue editing section fields in ACF after removing the plugin, they must use a supported preservation path (handoff to native ACF groups or export bundle), when implemented and documented. Until then, uninstall leaves values in place but group definitions are no longer registered.

---

## 4. What may be removed on uninstall

- Plugin-only operational data: options, transients, cron entries, and similar used solely for plugin operation (e.g. caches, reporting state). Exact list is defined in uninstall implementation and contract; no user content or ACF values are removed by default.

---

## 5. Safe failure

- If any preservation or handoff step fails, the system must favor **retaining data** (e.g. not deleting post meta or assignment map). No silent mass deletion of user or ACF data.

---

## 6. Cross-references

- **acf-uninstall-retention-contract.md**: Full contract (runtime vs persistent groups, retained values, handoff strategy, exclusions).
- **acf-uninstall-inventory-contract.md**: How plugin-owned groups and values are identified for handoff/uninstall.
- **PORTABILITY_AND_UNINSTALL**: General plugin uninstall policy.
