# Industry Pack Subsystem — Uninstall Retained Data Matrix (Prompt 396)

**Spec**: PORTABILITY_AND_UNINSTALL; industry-lifecycle-hardening-contract; industry-export-restore-contract.

This matrix documents what industry-related data is **removed** vs **preserved** when the plugin is uninstalled (delete via WordPress admin). Aligns with non-destructive uninstall policy: preserve built content; remove only plugin-owned operational data.

---

## 1. Summary

- **Removed:** All industry-related options and any industry-specific transients/caches (see §2).
- **Preserved:** Built pages, posts, post meta, and any user-created content. Industry does not own content; it guides selection. Pack and overlay definitions are code/registry and are not stored per site.
- **Safe failure:** When in doubt, retention is favored over deletion. Uninstall does not run on deactivation; only when the plugin is deleted.

---

## 2. Removed on uninstall (industry subsystem)

| Data | Option / storage | Rationale |
|------|------------------|-----------|
| Industry profile | `Option_Names::INDUSTRY_PROFILE` | Site preference; plugin-owned. |
| Applied industry preset | `Option_Names::APPLIED_INDUSTRY_PRESET` | Site preference; plugin-owned. |
| Industry section overrides | `Option_Names::INDUSTRY_SECTION_OVERRIDES` | Override state; plugin-owned. |
| Industry page template overrides | `Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES` | Override state; plugin-owned. |
| Industry Build Plan item overrides | `Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES` | Override state; plugin-owned. |
| Disabled industry packs list | `Option_Names::DISABLED_INDUSTRY_PACKS` | Admin toggle; plugin-owned. |
| Industry-specific transients/caches | Any transient key pattern used by industry code (e.g. recommendation caches, diagnostics snapshots). | Operational cache; plugin-owned. If added in future, must use a documented key pattern so uninstall can remove without loading industry stack. |

**Implementation:** Options above are included in `Option_Names::all()` and are removed by `Uninstall_Cleanup_Service::cleanup_plugin_owned_data()` when `uninstall.php` runs. No separate industry uninstall routine is required unless industry-specific transients are introduced later; then document the key pattern and add removal in uninstall or in the cleanup service.

---

## 3. Preserved (industry-relevant)

| Data | Storage | Rationale |
|------|---------|-----------|
| Built pages and posts | Post type `page`, posts; post_content, post meta | PORTABILITY_AND_UNINSTALL: built content preserved. Industry-guided flows produce native WordPress content. |
| Post meta on built pages | Standard WP post meta | Not plugin-owned industry options; user content. |
| Pack/overlay/CTA/style preset definitions | Code/registry (PHP files) | Not stored in DB per site; nothing to remove. |

---

## 4. Not stored by industry

Pack definitions, overlay definitions, CTA patterns, style presets, SEO/LPagery rules, starter bundles, and question packs are built-in (loaded from code/registry). They are not stored in options per site. Uninstall has nothing to remove for these beyond the site-preference and override options listed in §2.

---

## 5. Operator and support guidance

- **Before uninstall:** Export site or at least profiles (including industry profile and applied preset) if the operator wants to restore industry preferences on another site. See [industry-export-restore-contract.md](../contracts/industry-export-restore-contract.md).
- **After uninstall:** Built pages remain viewable and editable. Industry profile and preset are gone; re-selecting industry requires re-running onboarding or settings if the plugin is reinstalled.
- **Diagnostics:** Support runbooks and diagnostics checklists should reference this matrix so operators know what is removed vs preserved. See [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md) and [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md).

---

## 6. Cross-references

- [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) — general policy; industry subsection.
- [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) — uninstall retention and removal list; multisite, CLI, regression guards.
- [industry-export-restore-contract.md](../contracts/industry-export-restore-contract.md) — what is exported/restored; uninstall removes the same options that restore writes.
