# ACF Scoped Registration — Inspection Guide

**Prompt**: 308  
**Contract**: acf-conditional-registration-contract.md

---

## 1. Purpose

Internal developer/support guide for the scoped-registration inspection utility. It shows which section keys and group keys would register for a given page ID or chosen template/composition **without** loading the full editor or running ACF registration. Reuses the same resolution logic as production.

---

## 2. Access and security

- **Internal only**: Resolve `scoped_registration_inspection_service` from the container. No public route or UI.
- **Admin/support**: Call only from admin context or support tooling with appropriate capability checks.
- **No sensitive data**: Outputs contain only keys (section_key, group_key); no field values or page content.

---

## 3. Service and methods

| Method | Context | Returns |
|--------|---------|---------|
| `inspect_for_page( int $page_id )` | Existing page | mode, section_keys, group_keys, cache_used, resolved |
| `inspect_for_new_page_template( string $template_key )` | New page with chosen template | mode, section_keys, group_keys, cache_used, resolved |
| `inspect_for_new_page_composition( string $composition_id )` | New page with chosen composition | mode, section_keys, group_keys, cache_used, resolved |

Return shape (all methods): `array{ mode: string, section_keys: list<string>, group_keys: list<string>, cache_used: bool, resolved: bool }`.

---

## 4. How to use

1. From an admin screen or support script that has access to the plugin container, resolve `scoped_registration_inspection_service`.
2. Call the appropriate method:
   - **Existing page**: `inspect_for_page( $page_id )` — same resolution as Existing_Page_ACF_Registration_Context_Resolver (assignment map + cache).
   - **New page template**: `inspect_for_new_page_template( $template_key )` — same as New_Page_ACF_Registration_Context_Resolver for template.
   - **New page composition**: `inspect_for_new_page_composition( $composition_id )` — same for composition.
3. Use the result for diagnostics: which groups would be registered, whether cache was used, whether resolution succeeded.

---

## 5. Relationship to production

- The service uses the same dependencies and logic as the production resolvers (assignment service, derivation service, group_key resolver, cache). It does **not** perform ACF registration or load the editor.
- Useful to verify “why does this page show these groups?” or “what would register for this template?” without opening the edit screen.

---

## 6. Cross-references

- acf-conditional-registration-contract.md
- Scoped_Registration_Inspection_Service.php
- template-library-support-guide.md (ACF / registration row)
