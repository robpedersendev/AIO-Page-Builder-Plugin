# ACF Conditional Registration — Third-Party Admin Compatibility Matrix

**Prompt**: 305  
**Contract**: acf-conditional-registration-contract.md

---

## 1. Purpose

Documents how the conditional-registration system behaves when third-party plugins or editor integrations alter admin screens, URLs, or ACF initialization. Safe failure must favor no-registration or minimal registration, not full registration.

---

## 2. Resolver assumptions (canonical)

| Assumption | Source | Risk from third-party |
|------------|--------|------------------------|
| `$pagenow` is set and is `post.php` or `post-new.php` for page edit | WordPress core | Plugins can unset or change `$pagenow`; custom admin screens may not set it. |
| `$_GET['post']` / `$_GET['post_type']` reflect the current edit | Core | Redirects, overrides, or custom UIs might change or omit these. |
| `get_post_type( $post_id )` returns `'page'` for a valid page | Core | Filters or broken post can return false or other type. |
| `acf/init` runs at priority 5 before ACF builds field list | ACF + provider | Other plugins could change hook order or load order. |

---

## 3. Guardrails added (Prompt 305)

| Guard | Location | Behavior |
|-------|----------|----------|
| **$pagenow invalid** | Admin_Post_Edit_Context_Resolver::resolve() | If `$pagenow` is not set, not a string, or empty, return NON_PAGE_ADMIN (zero groups). No full registration. |
| **get_post_type !== 'page'** | Same | Already treated as UNSUPPORTED_ADMIN; documented as covering false or non-page. |
| **Secondary edit (AJAX, revision)** | is_secondary_edit_request() | Autosave, heartbeat, quick-edit, revision → UNSUPPORTED_ADMIN. |

Under uncertainty (e.g. unexpected $pagenow), the resolver falls back to NON_PAGE_ADMIN or UNSUPPORTED_ADMIN so that **no** full registration is triggered.

---

## 4. Compatibility states

| Scenario | Support state | Notes |
|----------|----------------|--------|
| **Standard WordPress admin** (post.php, post-new.php, page) | Supported | Primary target; resolver matches as designed. |
| **Custom admin URL that still sets $pagenow and get params** | Likely supported | If $pagenow and post/post_type are correct, behavior unchanged. |
| **Admin UI that unsets or overrides $pagenow** | Unknown / fail safe | Resolver returns NON_PAGE_ADMIN; zero groups. No heavy registration. |
| **REST or headless admin** | Not page-edit context | Treated as non-admin or non-page; skip or zero groups. |
| **Block editor (Gutenberg) on page** | Supported | post.php with post_type=page; standard flow. |
| **Classic editor** | Supported | Same. |
| **ACF init timing changed by another plugin** | Unknown | If acf/init runs late, our hook still runs at priority 5 relative to ACF; risk is ACF or other code ordering. No change to our logic. |

---

## 5. What we do not do

- No broad compatibility promise for every third-party plugin.
- No full registration fallback when context is ambiguous.
- No reliance on request parameters from third parties to switch into full registration.
- Compatibility handling is additive (guards); resolver logic is not rewritten.

---

## 6. QA suggestions

- Manual: On a clean install, verify existing-page and new-page edit show correct scoped groups.
- With a plugin that alters admin (e.g. custom dashboard, redirect): confirm resolver fails safe (zero groups or non-page) and no full registration.
- Do not rely on heavy registration when $pagenow or post type is unexpected.

---

## 7. Conflict verification (Prompt 311)

A **plugin/theme conflict verification** pack exercises realistic interference scenarios (admin menu/redirect plugins, page builders, themes that alter admin, REST/headless, caching). Results are recorded in [acf-plugin-theme-conflict-verification.md](acf-plugin-theme-conflict-verification.md). Summary:

- **Compatible**: Standard admin, themes that do not change $pagenow/post context, caching plugins (per-request decision unchanged).
- **Fail-safe**: When $pagenow or post type is wrong or missing, resolver returns NON_PAGE_ADMIN or UNSUPPORTED_ADMIN; zero groups; no full registration.
- **Unsupported**: Custom admin UIs that do not set core edit context; zero groups is the safe outcome; no bespoke integration.

---

## 8. Cross-references

- acf-conditional-registration-contract.md
- [acf-plugin-theme-conflict-verification.md](acf-plugin-theme-conflict-verification.md)
- Admin_Post_Edit_Context_Resolver.php
- acf-secondary-admin-request-matrix.md (autosave, heartbeat, etc.)
