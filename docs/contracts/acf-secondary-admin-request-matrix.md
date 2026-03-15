# ACF Registration — Secondary Admin Request Matrix

**Prompt**: 299  
**Upstream**: acf-conditional-registration-contract.md

---

## 1. Purpose

Ensure autosave, revision, quick-edit, inline edit, and bulk-edit requests do not trigger full or unnecessary ACF registration. These secondary admin request types often use different PHP/HTTP entry points and must be explicitly guarded.

---

## 2. Contexts and behavior

| Context | Detection | Registration mode |
|---------|-----------|-------------------|
| **Autosave** | `wp_doing_ajax()` and `action=autosave` (or WordPress autosave heartbeat) | No registration. Treat as unsupported for scoped registration. |
| **Heartbeat** | `wp_doing_ajax()` and `action=heartbeat` | No registration. |
| **Quick-edit / inline-save** | `wp_doing_ajax()` and `action=inline-save` (or list table inline edit) | No registration. |
| **Revision view** | `post_type=revision` or loading a revision (e.g. `revision` in request) | No registration; do not treat as existing-page edit. |
| **Bulk-edit** | List table bulk action (often AJAX or form post to edit.php) | No registration. |
| **Normal page edit** | `post.php`, valid `post` ID, post type `page`, not one of the above | Existing-page scoped registration. |

---

## 3. Rules

- When the resolver detects a secondary request type (autosave, revision, quick-edit, heartbeat, bulk-edit), it must **not** return EXISTING_PAGE_EDIT. Return UNSUPPORTED_ADMIN or equivalent so that **zero** groups are registered.
- No full-registration fallback for these contexts.
- Normal save (publish/update) from the main edit screen is still `post.php` with a page and is **not** autosave/inline-save; it continues to receive existing-page scoped registration.

---

## 4. Implementation

- **Admin_Post_Edit_Context_Resolver::resolve()**: Before returning EXISTING_PAGE_EDIT for `post.php`, check:
  - If `wp_doing_ajax()` and action is `autosave`, `heartbeat`, `inline-save`, or other known secondary action → return UNSUPPORTED_ADMIN.
  - If the post type of the requested post is `revision` → return UNSUPPORTED_ADMIN.
- This ensures autosave, revision, quick-edit, and similar paths never receive scoped registration and never trigger assignment-map or section resolution for ACF.

---

## 5. Cross-references

- acf-conditional-registration-contract.md §4.2 (existing-page), §7 (safe-failure)
- Admin_Post_Edit_Context_Resolver (Prompt 293)
