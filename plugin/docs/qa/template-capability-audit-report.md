# Template Capability Audit Report

**Document type:** QA audit summary for template-related screens and actions (spec §44, §49.6, §49.7, §62.2; Prompt 200).  
**Purpose:** Ensure role-specific capability enforcement and UI restriction across the expanded template ecosystem. Observational; no automatic planner/executor changes.

---

## 1. Scope

- Section Templates directory and detail
- Page Templates directory and detail
- Template Compare workspace (add/remove compare list)
- Compositions list and builder
- Template Analytics dashboard
- Template/composition seed actions (admin_post handlers)
- Dashboard quick actions (no template-specific links in current definitions; menu visibility is capability-driven)

---

## 2. Authority

- Capability model: `Infrastructure\Config\Capabilities`, spec §44.2, §44.3, §44.5.
- Screen capability: each screen’s `get_capability()`; menu uses it for visibility and access.
- Server-side: every screen `render()` checks `current_user_can( $this->get_capability() )` before content; mutating actions use nonce + capability.

---

## 3. Template capability audit summary

| Screen / action | Route / slug | Capability required | Server check | Notes |
|-----------------|--------------|---------------------|--------------|-------|
| Section Templates directory | `aio-page-builder-section-templates` | `aio_manage_section_templates` | `render()` + menu | §44.5; browsing and metadata gated. |
| Section Template detail | `aio-page-builder-section-template-detail` | `aio_manage_section_templates` | `render()` + menu | Hidden from menu; link from directory. |
| Page Templates directory | `aio-page-builder-page-templates` | `aio_manage_page_templates` | `render()` + menu | §44.5. |
| Page Template detail | `aio-page-builder-page-template-detail` | `aio_manage_page_templates` | `render()` + menu | Hidden from menu; link from directory. |
| Template Compare | `aio-page-builder-template-compare` | `aio_manage_page_templates` | `render()` + `maybe_handle_add_remove()` + menu | Add/remove nonce; capability re-checked in handler. |
| Compositions | `aio-page-builder-compositions` | `aio_manage_compositions` | `render()` + menu | List and build view gated. |
| Template Analytics | `aio-page-builder-template-analytics` | `aio_view_logs` | `render()` + menu | Stricter than Build Plans; support-oriented. |
| Build Plan Analytics | `aio-page-builder-build-plan-analytics` | `aio_view_build_plans` | `render()` + menu | Unchanged; plan-focused. |

Example template capability audit summary row (single row from the table above):

| Screen / action | Route / slug | Capability required | Server check | Notes |
|-----------------|--------------|---------------------|--------------|-------|
| Template Compare | `aio-page-builder-template-compare` | `aio_manage_page_templates` | `render()` + `maybe_handle_add_remove()` + menu | Add/remove nonce; capability re-checked in handler. |

---

## 4. Seed and mutation actions

- Template/composition seed handlers (admin_post) in `Admin_Menu` are gated by the corresponding plugin capability: section seeds require `aio_manage_section_templates`; page template seeds require `aio_manage_page_templates`; page+composition expansion pack requires both `aio_manage_page_templates` and `aio_manage_compositions`; form template seed requires both section and page manage caps.
- CPT create/edit/deprecate/archive: gated by `Post_Type_Registrar` map_cap per post type (`MANAGE_SECTION_TEMPLATES`, `MANAGE_PAGE_TEMPLATES`, `MANAGE_COMPOSITIONS`).

---

## 5. Privilege and direct URL

- No screen relies on UI hiding alone; each checks capability in `render()`.
- Direct URL to a template screen without the required capability returns 403 (wp_die).
- Compare add/remove: same capability check and nonce verification; no privilege escalation via GET.

---

## 6. What was not changed

- Planner or executor logic.
- Registry schemas or authority.
- New roles or new capability names (only assignment to screens/handlers aligned with §44.5).
