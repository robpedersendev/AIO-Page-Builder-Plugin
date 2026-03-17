# Industry Override Contract

**Spec**: industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md; industry-build-plan-scoring-contract.md.  
**Status**: Defines the operator override model for industry recommendations (Prompt 366). Recommendations remain advisory; overrides are explicit and auditable.

---

## 1. Purpose

- Allow **admins/reviewers** to intentionally override an industry recommendation (e.g. accept a discouraged section, reject a recommended template, or choose a different option than the resolver suggested).
- **Preserve** default recommendation behavior when no override exists.
- **Keep overrides auditable**: reason capture, optional actor/created markers, and persistent warning visibility so overridden items remain clearly labeled in later views.

---

## 2. Override scopes

| Target type | Description | Target key meaning |
|-------------|-------------|---------------------|
| **section** | Override applies to a section template choice (e.g. "use this section despite discouraged"). | Section template internal_key. |
| **page_template** | Override applies to a page template choice (e.g. "use this template despite weak fit"). | Page template internal_key. |
| **build_plan_item** | Override applies to a single Build Plan item (new_page or existing_page_change). | Plan item_id (within a plan). |

Override identity is scoped by (target_type, target_key) and optionally by plan_id for build_plan_item so the same section/template can have different overrides in different plans.

---

## 3. Override state and reason

- **Override state**: `accepted` (operator accepts the choice despite warning), `rejected` (operator rejects the recommendation), or product-defined equivalents. State determines how the item is labeled (e.g. "Accepted (override)" vs "Rejected").
- **Reason**: Required or strongly encouraged short text (sanitized, bounded length) explaining why the override was applied. Stored for audit and for display in review/compare views.
- **Warnings remain visible**: After override, the original industry warning (e.g. "Discouraged for this industry") remains visible in UI so operators do not lose context. Override does not remove or rewrite industry metadata on the underlying registry definition.

---

## 4. Storage and versioning

- Override objects are stored per product design (e.g. per Build Plan in plan definition, or in a dedicated override store keyed by scope). Schema: Industry_Override_Schema.
- **Versioning**: Override records may include a schema_version or version_marker for future evolution. Created/updated timestamps (or markers) support audit trails where allowed by existing audit conventions.
- **Actor metadata**: Where the plugin already captures actor (e.g. user id) for audit, override may reference it; otherwise override is anonymous or "current user" at write time. No new audit subsystem required in this contract.

---

## 5. Safe defaults

- **Missing override**: When no override exists for (target_type, target_key [and plan_id if applicable]), behavior is **normal recommendation**: show resolver result, no override badge, no reason.
- **Invalid override**: Malformed or invalid override records (e.g. missing target_key, invalid state) must not break recommendation or UI; treat as "no override" and optionally log.

---

## 6. Constraints

- Overrides must be **admin/reviewer-only** (capability check at write).
- Reason must be **sanitized** (e.g. text only, max length per Industry_Override_Schema).
- Overrides **do not** silently rewrite industry_affinity or pack rules on section/page template definitions.
- Planner/executor separation remains; override state may influence display and future "apply" behavior but does not auto-execute.

---

## 7. UI and persistence (Prompts 367–369)

### 7.1 Section library

- **Screen**: Section Templates Directory. For each section row with industry fit `FIT_DISCOURAGED` or `FIT_ALLOWED_WEAK`, an override control is shown.
- **Override state**: If an override exists for that section key, the actions column shows "Overridden" and the stored reason (e.g. in a title attribute). Otherwise a "Use anyway" inline form is shown (POST to `admin-post.php` with `action=aio_save_industry_section_override`, nonce `aio_section_override_nonce`, `section_key`, `state=accepted`, optional `reason`, `_wp_http_referer`).
- **Persistence**: Option `Option_Names::INDUSTRY_SECTION_OVERRIDES` (array keyed by section_key). Write path: `Save_Industry_Section_Override_Action` (admin-post); capability `Capabilities::MANAGE_SECTION_TEMPLATES`. Read: `Industry_Section_Override_Service::list_overrides()` merged into screen state as `industry_section_overrides_by_key`.
- **Recommendation metadata**: Industry badges and warnings remain visible after override; override does not remove or rewrite section registry data.

### 7.2 Page template directory

- **Screen**: Page Templates Directory. For each template row with fit `FIT_DISCOURAGED` or `FIT_ALLOWED_WEAK`, an override control is shown.
- **Override state**: "Overridden" (with reason) or "Use anyway" form (POST `action=aio_save_industry_page_template_override`, nonce `aio_page_template_override_nonce`, `template_key`, `state`, `reason`, `_wp_http_referer`).
- **Persistence**: Option `Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES`. Write: `Save_Industry_Page_Template_Override_Action`; capability `Capabilities::MANAGE_PAGE_TEMPLATES`. Read: `Industry_Page_Template_Override_Service::list_overrides()` as `industry_page_template_overrides_by_key`.

### 7.3 Build Plan item (review step)

- **Context**: Build Plan workspace, step 2 (New pages) or step 2 (Existing page updates). Detail panel for a selected plan item.
- **Override section**: When the item has industry warning flags (`industry_warning_flags` in payload) and a plan_id is available, the detail builder adds an "Industry override" section. If an override exists for (plan_id, item_id): shows "Overridden" and the reason. Otherwise shows an "Accept anyway" form with optional review note (textarea), POST `action=aio_save_industry_build_plan_override`, nonce `aio_save_industry_build_plan_override`, hidden `plan_id`, `item_id`, `state=accepted`, `_wp_http_referer`.
- **Persistence**: Option `Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES` (nested: plan_id => item_id => override record). Write: `Save_Industry_Build_Plan_Override_Action`; capability `Capabilities::APPROVE_BUILD_PLANS`. Read: `Industry_Build_Plan_Item_Override_Service::get_override(plan_id, item_id)` / `list_for_plan(plan_id)`.
- **Detail builders**: `New_Page_Creation_Detail_Builder::build_sections($item, $plan_id)` and `Existing_Page_Update_Detail_Builder::build_sections($item, $plan_id)` accept an optional `$plan_id` and render the override section when the item has industry warnings.

---

## 8. Bulk override management (Prompt 436)

- **Screen**: Industry Overrides (slug `aio-page-builder-industry-overrides`), under main AIO Page Builder menu. Lists all overrides across section, page template, and Build Plan item. Filter by type, state, reason presence, and optional industry_context_ref.
- **Read model**: Industry_Override_Read_Model_Builder aggregates from the three override services and supports the same filters. Used by the management screen.
- **Remove**: Bounded single-override removal via admin_post action `aio_remove_industry_override`; nonce and capability per scope (MANAGE_SECTION_TEMPLATES, MANAGE_PAGE_TEMPLATES, APPROVE_BUILD_PLANS). Redirects back to the management screen.
- **Files**: plugin/src/Admin/Screens/Industry/Industry_Override_Management_Screen.php, plugin/src/Domain/Industry/Overrides/Industry_Override_Read_Model_Builder.php, plugin/src/Admin/Actions/Remove_Industry_Override_Action.php.

---

## 9. Override audit report (Prompt 437)

- **Purpose**: Bounded, exportable summary for support/diagnostics. See docs/contracts/industry-override-audit-report-contract.md.
- **Service**: Industry_Override_Audit_Report_Service builds a report grouped by target_type and optional industry_context_ref. Included in support packages (industry_override_audit_summary.json) and in industry diagnostics snapshot (override_summary) when the service is available.

---

## 10. Files

- **Schema**: plugin/src/Domain/Industry/Overrides/Industry_Override_Schema.php
- **Services**: plugin/src/Domain/Industry/Overrides/Industry_Section_Override_Service.php, Industry_Page_Template_Override_Service.php, Industry_Build_Plan_Item_Override_Service.php, Industry_Override_Read_Model_Builder.php
- **Actions**: plugin/src/Admin/Actions/Save_Industry_Section_Override_Action.php, Save_Industry_Page_Template_Override_Action.php, Save_Industry_Build_Plan_Override_Action.php, Remove_Industry_Override_Action.php
- **Reporting**: plugin/src/Domain/Industry/Reporting/Industry_Override_Audit_Report_Service.php
- **Contracts**: docs/contracts/industry-override-contract.md, docs/contracts/industry-override-audit-report-contract.md
