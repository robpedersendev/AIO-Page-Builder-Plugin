# Industry Pack Subsystem — Lifecycle Hardening Contract

**Spec**: industry-pack-extension-contract; industry-export-restore-contract; PORTABILITY_AND_UNINSTALL. **Prompt**: 380.

This contract defines lifecycle and operational boundaries for the Industry Pack subsystem: uninstall retention/removal, multisite scoping, CLI/scripted behavior, and regression guards. Industry data remains additive; lifecycle behavior must stay explicit and non-destructive where policy requires.

---

## 1. Uninstall retention and removal

- **Policy**: Align with [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md). Preserve built content by default; remove only plugin-owned operational data.
- **Industry data on uninstall**:
  - **Removed**: Options and transients owned by the Industry Pack subsystem that are used solely for plugin operation. This includes: `Option_Names::INDUSTRY_PROFILE`, `Option_Names::APPLIED_INDUSTRY_PRESET`, `Option_Names::INDUSTRY_SECTION_OVERRIDES`, `Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES`, `Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES`, `Option_Names::DISABLED_INDUSTRY_PACKS`, and any industry-specific cache keys or transients (e.g. recommendation caches, diagnostics snapshots). Uninstall logic must be explicit in `uninstall.php` or a dedicated Industry uninstall routine called from it. See [industry-uninstall-retained-data-matrix.md](../operations/industry-uninstall-retained-data-matrix.md) for the full matrix.
  - **Preserved**: Built pages, posts, and post meta. Industry profile and applied preset are **site preference data**; removing them does not destroy user content. Content created with industry-guided flows survives; it is native WordPress content.
  - **Not stored by industry**: Pack definitions, overlay definitions, CTA patterns, style presets, SEO/LPagery rules are built-in (code/registry). They are not stored in options per site; nothing to remove beyond the options above.
- **Documentation**: What is removed vs preserved for industry must be documented in this contract, in [industry-uninstall-retained-data-matrix.md](../operations/industry-uninstall-retained-data-matrix.md), and in PORTABILITY_AND_UNINSTALL (industry subsection). Uninstall must not silently destroy user data contrary to policy.
- **Implementation**: When the plugin’s `uninstall.php` runs (`defined('WP_UNINSTALL_PLUGIN')`), industry options and industry-specific transients are deleted. No dependency on industry bootstrap for uninstall; option names and transient key patterns must be known to uninstall so they can be removed without loading full industry stack if desired.

---

## 2. Multisite scoping

- **Policy**: The plugin operates at **site level** on WordPress multisite (spec §54.9). No network-wide centralized management. Industry Pack behavior is **site-local**.
- **Industry profile and pack refs**: Stored in site options (`Option_Names::INDUSTRY_PROFILE`, `Option_Names::APPLIED_INDUSTRY_PRESET`, and other industry options). WordPress options are per-site in multisite; no change required for site locality.
- **Caches**: Any industry-related caches (e.g. recommendation result caches, overlay composition caches, starter bundle list caches, diagnostics snapshot) must use keys that include the current blog id when `is_multisite()` so there is no cross-site cache bleed. Use **`Industry_Site_Scope_Helper::scope_cache_key( $base_key )`** for any new transient or cache key; see [industry-multisite-verification.md](../qa/industry-multisite-verification.md). Cache scopes, keying, invalidation, and safe fallback are defined in [industry-cache-contract.md](industry-cache-contract.md).
- **Pack/overlay definitions**: Loaded from code/registry; same definitions on every site. No per-site pack storage; only profile, preset, overrides, and disabled list are per-site.
- **Export/restore**: Export and restore run in the context of the current site. Industry profile and applied preset in an export bundle belong to that site; restore applies to the current site only.
- **Documentation**: Multisite behavior is documented here, in [industry-multisite-verification.md](../qa/industry-multisite-verification.md), and in [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md). No network activation or network-wide industry management.

---

## 3. CLI and scripted behavior

- **Policy**: Industry subsystem may be read (e.g. profile, pack list, recommendation resolvers) in CLI or scripted contexts. Behavior must be **bounded and deterministic**. No silent bypass of validation; no unsafe side effects from read-only operations.
- **Reading industry state**: When WP-CLI or a script loads the plugin and reads industry profile, applied preset, or runs recommendation resolvers, the same validation and resolution rules apply as in admin (e.g. unknown industry_key fails safe; missing refs skip or fallback). No special CLI-only code paths that weaken validation.
- **Mutations**: Any industry mutation (e.g. set profile, apply preset) in CLI must go through the same capability and validation as admin. Do not introduce new public CLI commands in this contract; if CLI commands are added later, they must enforce permissions and nonces/context as per Admin-REST-AJAX rules.
- **Cron / queue**: If industry-related jobs run in cron or queue, they must be site-scoped in multisite (e.g. switch_to_blog when processing a specific site). No unbounded cross-site writes.
- **Documentation**: CLI/scripted expectations (read-only behavior, validation, site scope) are documented here. Manual QA in [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md) verifies no unsafe side effects from scripted use.

---

## 4. Regression guards

- **Pack validation**: Industry pack definitions must pass schema validation at load. Regression guard: adding an invalid pack (e.g. wrong version_marker, missing required field) must be rejected; existing valid packs must still load. Tests or QA steps should cover: valid pack loads, invalid pack rejected, unknown industry_key in profile fails safe.
- **Recommendation fallback**: When industry profile is missing or industry_key is unknown, section and page template recommendation resolvers must not crash; they must fall back to neutral/generic behavior. Regression guard: run resolvers with no profile and with unknown industry_key; no fatal, no uncaught exception.
- **Export/restore**: Restore with unsupported industry schema_version must skip industry restore and log; must not fail the whole profiles restore. Regression guard: restore with invalid or future schema_version; only industry restore skipped.
- **Substitute engine**: When substitute suggestion engine is used, missing definitions or empty candidate sets must return empty suggestions, not errors. Regression guard: call suggest with no recommended candidates; empty list returned.
- **Maintainability**: Regression guards must be documented and, where automated, repeatable. They must not rely on brittle implementation details; prefer contract-level behavior (e.g. “resolver returns result or safe default”) over probing internal state.

---

## 5. Cross-references

- **Degraded mode**: [industry-degraded-mode-contract.md](industry-degraded-mode-contract.md) — fail-safe and degraded-mode behavior when registries, bundles, or caches are missing or invalid.
- **PORTABILITY_AND_UNINSTALL**: [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) — general policy; this contract adds industry-specific removal list.
- **Export/restore**: [industry-export-restore-contract.md](industry-export-restore-contract.md) — what is exported/restored; uninstall removes the same options that restore writes.
- **Known risks**: [known-risk-register.md](../release/known-risk-register.md) §3 IND-1, IND-2 — industry risks and mitigations.
- **QA**: [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md) — uninstall, multisite, CLI, and regression procedures.
- **Support**: [support-triage-guide.md](../guides/support-triage-guide.md) — industry snapshot and diagnostics; lifecycle behavior for support context.
