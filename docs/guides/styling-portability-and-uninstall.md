# Styling: Portability and Uninstall Behavior

**Spec:** §17.10 Rendered Content Independence; §53.5–53.9 Deactivation, Uninstall, Reinstall, Restore.  
**Contracts:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md) §8, [css-selector-contract.md](../contracts/css-selector-contract.md), [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).  
**Purpose:** Document what happens to plugin CSS, global styling settings, per-entity style payloads, and token values on deactivation, uninstall, and portability so behavior aligns with survivability requirements.

---

## 1. Summary

| Event | Plugin CSS | Global styling settings | Per-entity style payloads | Token values in content | Built page content/structure |
|-------|------------|--------------------------|----------------------------|--------------------------|------------------------------|
| **Deactivation** | No longer loaded | Remain in DB (options) | Remain in DB | Not stored in post_content | Preserved |
| **Uninstall** | N/A (plugin removed) | **Removed** | **Removed** | N/A | **Preserved** |
| **Export** | N/A | Included when present | Included when present | N/A | As per export scope |
| **Restore** | Re-applied if plugin active | Re-applied within sanitization | Re-applied within sanitization | N/A | As per restore |

Built pages remain **meaningful** without the plugin: content and structure survive; styling is an **enhancement**, not a structural dependency.

---

## 2. Deactivation

- **Plugin CSS:** Plugin stylesheets and inline token/override output **stop loading**. No plugin-owned CSS is enqueued.
- **Content and structure:** Post content, section markup, and structural classes (e.g. `.aio-page`, `.aio-s-*`) **remain** in the database and in rendered HTML. Built pages are still viewable and readable (spec §17.10).
- **Stored styling data:** Options `aio_global_style_settings` and `aio_entity_style_payloads` (and `aio_style_cache_version`) **remain** in the database. They are not used while the plugin is deactivated.
- **Token values:** Token **names** (e.g. `--aio-color-primary`) are fixed in the selector/token contract; **values** were supplied by the plugin at runtime. After deactivation, no plugin code sets those variables; any theme or child-theme CSS that targets the same selectors or variables can still apply its own values.

**Takeaway:** Pages look unstyled by the plugin but remain structurally intact. Re-activation restores plugin styling behavior using the same options.

---

## 3. Uninstall / Plugin Removal

Uninstall runs only when the plugin is **deleted** (e.g. from Plugins → Delete), per WordPress lifecycle and PORTABILITY_AND_UNINSTALL.

- **Plugin-owned options removed:** The following options are deleted by uninstall cleanup (they are registered in `Option_Names::all()` and cleared by `Uninstall_Cleanup_Service`):
  - `aio_global_style_settings`
  - `aio_entity_style_payloads`
  - `aio_style_cache_version`
- **Built pages:** **Not** deleted. Post content, post meta, and native WordPress page structure are preserved. Uninstall does **not** mutate or corrupt saved content.
- **Exported styling:** If the user exported a package that includes styling data before uninstall, that package retains global and entity styling for potential restore elsewhere. Uninstall does not alter export files already created.
- **No guarantee of plugin-owned styling after uninstall:** Plugin-owned styling (options above) is **removed**. The plugin does not promise that token-driven styles or override rules will remain after uninstall unless they were exported and later restored on a site where the plugin is active.

---

## 4. Export and Restore

- **Export:** Full operational (and support) exports can include styling data: global style settings and entity style payloads are read from options and written into the export package as documented in the export/restore contracts. Styling is included in the export schema and validated on restore.
- **Restore:** On restore, styling data from the package is written back only when the package contains it and the restore pipeline processes the styling step. All restored styling is re-validated through the normalizer and sanitizer; only whitelist-approved keys and values are persisted. No raw CSS or arbitrary selectors are restored.
- **Survivability:** Export/restore does not change the rule that **built page content** survives uninstall; styling in an export is for **re-applying** styling on a target site (e.g. after reinstall or migration), not for preserving it in the database after plugin removal.

---

## 5. Theme Override and Selector Continuity After Plugin Removal

- **Fixed selectors:** Structural class and ID names are defined by [css-selector-contract.md](../contracts/css-selector-contract.md) and are **stable**. Examples: `.aio-page`, `.aio-s-{section_key}`, `.aio-s-{section_key}__inner`, element roles such as `__headline`, `__cta`. Page wrapper: `aio-page`, `aio-page--{template_key}`.
- **Fixed token names:** CSS custom property **names** use the `--aio-*` prefix (e.g. `--aio-color-primary`, `--aio-space-md`). Names are fixed; only **values** were supplied by the plugin at runtime.
- **Theme CSS:** A theme or child theme can target these same selectors and variable names **after** the plugin is removed. For example:
  - Target `.aio-page` or `.aio-s-st01_hero` for layout or typography.
  - Set or override `--aio-color-primary` (and other `--aio-*` variables) on `:root` or `.aio-page` to keep a consistent look without the plugin.
- **Honest documentation:** We do **not** guarantee that theme overrides will match the exact look the plugin produced; we document that the **selector and token-name contract** remains the bridge so themes can continue to style the same markup.

---

## 6. What Is Exported, Retained, or Removed

| Data | On export | On uninstall | Retained after uninstall |
|------|-----------|--------------|--------------------------|
| Global style settings (tokens, component overrides) | Included in full/support export when present | **Removed** (option deleted) | No |
| Per-entity style payloads | Included in full/support export when present | **Removed** (option deleted) | No |
| Style cache version | Internal; may be in export context | **Removed** | No |
| Built page post_content | Per export scope | **Preserved** | Yes |
| Structural classes/IDs in content | In post_content | **Preserved** | Yes |

---

## 7. Manual QA Checklist: Deactivation and Uninstall

Use this checklist to verify styling lifecycle behavior and survivability.

**Deactivation**

- [ ] Deactivate the plugin. Confirm plugin stylesheets and inline token/override CSS no longer load on the front end.
- [ ] Confirm at least one built page still renders: content and structural markup (e.g. `.aio-page`, `.aio-s-*`) are present in the output.
- [ ] Re-activate the plugin. Confirm global and per-entity styling apply again without re-saving.

**Uninstall**

- [ ] Create a full export that includes styling data. Note one built page URL and its key structural classes.
- [ ] Uninstall (delete) the plugin. Confirm options `aio_global_style_settings`, `aio_entity_style_payloads`, and `aio_style_cache_version` are no longer present in the database.
- [ ] Confirm the same built page still exists and post_content has not been mutated (no removal or corruption of sections or markup).
- [ ] Confirm the page is still viewable on the front end; layout may lack plugin styling but structure remains.
- [ ] (Optional) Reinstall the plugin and restore from the export; confirm styling data is re-applied within sanitization and pages render with styling again.

**Theme continuity**

- [ ] After uninstall, add theme CSS that targets `.aio-page` and/or `--aio-color-primary`. Confirm the theme rules apply to the same markup and that no plugin CSS is required for the selectors to exist.

---

## 8. Cross-References

- **Spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §17.10, §53.5–53.9.
- **Contracts:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md) §8, [css-selector-contract.md](../contracts/css-selector-contract.md).
- **Standards:** [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).
- **Uninstall implementation:** `Uninstall_Cleanup_Service`, `Option_Names::all()`.
