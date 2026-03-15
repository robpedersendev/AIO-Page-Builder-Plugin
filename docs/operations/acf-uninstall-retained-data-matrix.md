# ACF Uninstall: Retained vs Removed Data Matrix

**Governs**: Uninstall behavior for ACF-related data (Prompt 316).  
**Spec**: acf-uninstall-retention-contract.md, acf-native-handoff-contract.md, PORTABILITY_AND_UNINSTALL.

---

## 1. Retained by default (never deleted by uninstall)

| Data | Storage | Reason |
|------|---------|--------|
| **Saved ACF field values** | Post meta (all meta keys holding values for plugin-related or any ACF fields) | User content; value retention is default per contract. |
| **Assignment map** | Plugin storage (per assignment map implementation) | Documents which groups were assigned to which page; may be needed for reference. |
| **Built pages and posts** | Post type `page`, standard posts | PORTABILITY_AND_UNINSTALL: built content preserved. |
| **Native ACF field groups** | ACF storage (DB; post type `acf-field-group`) | All ACF field groups, including handed-off groups from AIO Page Builder. Uninstall does not delete any ACF CPTs. |
| **Handed-off groups** | ACF storage (marked with `_aio_handoff_origin` = `aio_page_builder`) | Preserved like any other native ACF group; remain editable after uninstall. |

---

## 2. Removed on uninstall (plugin-owned operational data only)

| Data | Storage | Reason |
|------|---------|--------|
| **Plugin options** | Options (Option_Names::all()) | Plugin-owned settings, reporting state, profile, etc. |
| **Plugin custom tables** | Table_Names::all() | Build plans, queue, AI run artifacts, etc. |
| **Plugin CPT posts** | Object_Type_Keys::all() (section templates, page templates, compositions, build plans, etc.) | Plugin registries and operational objects. Not `page`; not `acf-field-group`. |
| **Scheduled events** | Cron | Heartbeat, reporting, etc. |
| **ACF section-key cache transients** | Transients with prefix `aio_acf_sk_p_`, `aio_acf_sk_t_`, `aio_acf_sk_c_` | Optional performance cache; correctness does not depend on it. Safe to remove. |

---

## 3. Not applicable / never touched

- **Post meta on built pages**: Not deleted. ACF values and any other meta remain.
- **ACF field group definitions**: Stored by ACF in its own CPT; plugin does not register or delete them.
- **Third-party or existing native ACF groups**: Never modified or deleted by the plugin.

---

## 4. Safe failure

If any cleanup step fails, the routine must not delete data beyond the intended scope. When in doubt, **retain**. No destructive default; no silent mass deletion of ACF values or handed-off groups.

---

## 5. Cross-references

- [acf-uninstall-retention-contract.md](../contracts/acf-uninstall-retention-contract.md)
- [acf-uninstall-preservation-policy.md](acf-uninstall-preservation-policy.md)
- [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md)
