# ACF Page-Level Visibility Assignment Contract

**Spec**: §20 Field Governance; §59.5 Rendering and ACF Phase (page assignment logic)

**Status**: Active. Defines which ACF field groups are assigned to which page and how assignment relates to registration.

**Large-scale extension**: **large-scale-acf-lpagery-binding-contract.md** §6.2–6.3 defines page-level visibility assignment at scale: assignment **derived** from template/composition section list; only groups for sections on that page are assigned; registration must not load all 250+ groups on every page; deterministic assignment.

**Conditional registration**: **acf-conditional-registration-contract.md** formalizes when and how groups are registered by request context. Visibility (which groups are *assigned* to a page) is separate from registration (which groups are *registered* with ACF on a given request). The assignment map remains the source of truth for visibility; conditional registration uses it to register only visible groups in admin page-edit context.

---

## 1. Assignment derivation

- Assignment is **derived** from page template or composition (ordered section list), not stored per-page in an unbounded way.
- **Page_Field_Group_Assignment_Service** persists assignments via **Assignment_Map_Service**; `get_visible_groups_for_page( $post_id )` returns the list of ACF group keys (e.g. `group_aio_st01_hero`) for that page.
- Group keys follow `group_aio_{section_key}` (acf-key-naming-contract). Section key is recovered via **Group_Key_Section_Key_Resolver** (reverse mapping); invalid or non-plugin group keys are rejected.

---

## 2. Visibility vs registration

| Concept | Meaning |
|--------|----------|
| **Visibility / assignment** | Which groups are *assigned* to a page (stored in assignment map; used by build/execution and by conditional registration to know which groups to register on that page’s edit screen). |
| **Registration** | Which groups are actually *registered* with ACF on the current request (PHP call to `acf_add_local_field_group`). After the retrofit, registration is conditional: front-end registers none; admin page edit registers only the visible groups for that page. |

---

## 3. Admin post-edit context (Prompt 293)

**Admin_Post_Edit_Context_Resolver** is the canonical way the plugin decides whether scoped ACF registration runs and which downstream resolver to use. It resolves the current admin request into a typed result:

| Context | Condition | Result |
|---------|-----------|--------|
| Existing-page edit | `post.php`, valid `post` ID, post type `page` | `EXISTING_PAGE_EDIT` (with page_id). |
| New-page edit | `post-new.php`, `post_type=page` (or default page) | `NEW_PAGE_EDIT`. |
| Unsupported admin | `post.php` but invalid/missing post or non-page post type | `UNSUPPORTED_ADMIN` (fail safe; no full registration). |
| Non-page admin | `post-new.php` with other post type; or any other admin screen | `NON_PAGE_ADMIN`. |

**Admin_Post_Edit_Context_Result** carries the context type and, for existing-page edit, the page ID. The ACF bootstrap controller uses this result to branch to the existing-page or new-page section-key resolver, or to register zero groups. No request-context logic is duplicated elsewhere after this resolver.

---

## 4. Cross-references

- **large-scale-acf-lpagery-binding-contract.md**: Registration scaling, derivation from section list, performance (§6.2–6.3).
- **acf-conditional-registration-contract.md**: Conditional registration by context; use of assignment map for resolving section keys on admin existing-page edit.
- **rendering-contract.md**: Field data keying `group_aio_{section_key}`.
