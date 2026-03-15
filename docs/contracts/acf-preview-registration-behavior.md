# ACF Registration — Preview and Iframe Behavior

**Prompt**: 298  
**Upstream**: acf-conditional-registration-contract.md, acf-page-visibility-contract.md

---

## 1. Purpose

Define how ACF scoped registration behaves for internal preview contexts (preview iframes, editor previews, internal page-preview routes) so they remain functional without falling back to full registration or being treated as generic public requests when they require admin-scoped behavior.

---

## 2. Contexts and behavior

| Context | Request type | Registration mode |
|---------|--------------|-------------------|
| **Front-end public view** | `! is_admin()` | No registration (existing). |
| **Front-end preview URL** (e.g. `?preview=true`, theme preview) | `! is_admin()` | No registration. Previews that load as front-end do not need ACF groups registered; saved field values are read from post meta. |
| **Admin template/section detail preview** | Admin, not `post.php`/`post-new.php` | Non-page admin → 0 groups (existing). |
| **Admin page edit screen** | `post.php` with post type `page` | Existing-page scoped registration (existing). |
| **Admin new page screen** | `post-new.php` with post type `page` | New-page scoped registration (existing). |
| **Preview iframe inside admin** | If URL is admin and matches page edit, treated as that context; otherwise non-page admin. | No separate “preview” registration path; use existing context resolution. |

---

## 3. Rules

- Preview or iframe requests that reach the server as **front-end** (`! is_admin()`) always get **no** ACF registration. No exception.
- Preview or iframe requests that reach the server as **admin** are resolved by **Admin_Post_Edit_Context_Resolver**: only `post.php` (page) and `post-new.php` (page) get scoped registration; all other admin (including preview query params on non-edit screens) get **non-page admin** → 0 groups.
- No full registration fallback for any preview context. If a preview route needs ACF groups, it must be designed to run in an existing-page or new-page edit context, or accept zero groups.

---

## 4. Implementation

- **Registration_Request_Context::should_skip_registration()**: Returns true when `is_front_end()`. Preview URLs that load as front-end therefore skip registration.
- **Admin_Post_Edit_Context_Resolver**: No special “preview” branch required for typical behavior. If a future preview route uses `post.php` with a page ID, it will receive existing-page scoped registration; if it uses a different admin URL, it receives 0 groups.
- Optional: If the plugin introduces a dedicated preview route that must have zero registration, the resolver can detect a specific query parameter (e.g. `aio_preview=1`) and force NON_PAGE_ADMIN. Document any such parameter here.

---

## 5. Cross-references

- acf-conditional-registration-contract.md §4.1 (front-end), §4.2–4.4 (admin)
- acf-registration-exception-matrix.md (no preview exception for full registration)
