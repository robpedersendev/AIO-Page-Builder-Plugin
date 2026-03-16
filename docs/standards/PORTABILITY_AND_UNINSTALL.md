# Portability and Uninstall

This document defines policy for content survivability and plugin uninstall behavior. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

## Survivability

Generated content must not depend on plugin activation for survival unless explicitly approved.

- Do not couple generated content (pages, blocks, layouts, user-created data) to plugin activation. Content created by the plugin for users should survive plugin deactivation and, where appropriate, uninstall.
- Exceptions require explicit approval and must be documented in [DECISION_LOG.md](../decisions/DECISION_LOG.md).

## Storage

- Prefer native WordPress storage for user-created content: posts, post meta, options, taxonomies.
- Content stored in standard WP structures is portable and survives plugin lifecycle changes. Custom tables or proprietary formats may reduce portability.

## Uninstall Policy

- **Preserve built content by default.** User-created pages, posts, blocks, and similar content must remain intact after uninstall unless the user explicitly requests removal.
- **Remove only plugin-owned operational data.** Options, transients, cron jobs, and other data used solely for plugin operation may be removed in `uninstall.php`.
- Document what is removed vs preserved. Include rationale in feature design and in this document when policies change.

### ACF integration

ACF field values (post meta) and assignment map data are preserved by default; see [ACF Uninstall Retention Contract](../contracts/acf-uninstall-retention-contract.md) and [ACF Uninstall Preservation Policy](../operations/acf-uninstall-preservation-policy.md). Field group definitions registered at runtime do not survive uninstall unless an explicit preservation step (handoff or export) is used. Handed-off native ACF field groups (created by the handoff generator before uninstall) remain in ACF storage and are not deleted. Exact retained vs removed data: [ACF Uninstall Retained Data Matrix](../operations/acf-uninstall-retained-data-matrix.md). **Operator workflow and verification:** [ACF Uninstall Preservation Operator Guide](../guides/acf-uninstall-preservation-operator-guide.md) and [ACF Uninstall Preservation Verification](../qa/acf-uninstall-preservation-verification.md).

### Industry Pack subsystem

Industry Pack data is **additive** and site-preference only. **Removed on uninstall:** Industry profile option (`Option_Names::INDUSTRY_PROFILE`), applied industry preset option (`Option_Names::APPLIED_INDUSTRY_PRESET`), industry section/page/build-plan overrides (`INDUSTRY_SECTION_OVERRIDES`, `INDUSTRY_PAGE_TEMPLATE_OVERRIDES`, `INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES`), disabled packs list (`DISABLED_INDUSTRY_PACKS`), and any industry-specific cache keys/transients. **Preserved:** Built pages and content (industry does not own content; it guides selection). Pack and overlay definitions are built-in (code); they are not stored per site. Full retained vs removed matrix: [Industry Uninstall Retained Data Matrix](../operations/industry-uninstall-retained-data-matrix.md). See [Industry Lifecycle Hardening Contract](../contracts/industry-lifecycle-hardening-contract.md) and [Industry Lifecycle Regression Guard](../qa/industry-lifecycle-regression-guard.md) for full policy and QA.

## Implementation

- `uninstall.php` runs only when the plugin is deleted via the WordPress admin (not on deactivation).
- Check `defined( 'WP_UNINSTALL_PLUGIN' )` before any uninstall logic.
- Do not remove content that users may have invested in. When in doubt, preserve.
