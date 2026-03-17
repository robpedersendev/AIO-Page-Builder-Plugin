# Build Plan UI Contract

**Spec**: Build Plan UI and review flows in aio-page-builder-master-spec.md; industry-build-plan-scoring-contract.md.  
**Status**: Defines Build Plan review UI behavior and industry explanation integration (Prompt 365).

---

## 1. Review flow

- Build Plan **review** is approval-gated. Users see proposed steps (existing page changes, new pages, navigation, etc.) and approve/deny per item or in bulk.
- **Build_Plan_Workspace_Screen** renders the three-zone shell; **Build_Plan_UI_State_Builder** supplies context rail and stepper; step-specific UI services supply **step_list_rows**, **detail_panel**, **step_messages**, **bulk_action_states**.
- **Detail panel**: When a row is selected, the detail panel shows sections for that item. Sections are built by step-specific detail builders (e.g. New_Page_Creation_Detail_Builder, Existing_Page_Update_Detail_Builder).

---

## 2. Create draft from starter bundle (Prompt 409)

- **Entry**: From the Industry starter bundle assistant, when a bundle is selected, a “Create draft Build Plan from this bundle” action is available. It links to `admin-post.php?action=aio_create_plan_from_bundle` with `bundle_key` and nonce.
- **Flow**: **Create_Plan_From_Starter_Bundle_Action** handles the request (capability `APPROVE_BUILD_PLANS`, nonce `aio_create_plan_from_bundle`). **Industry_Starter_Bundle_To_Build_Plan_Service** converts the selected bundle into a normalized draft (run_summary, site_purpose, new_pages_to_create from bundle template/section refs) and calls **Build_Plan_Generator::generate()**. The resulting plan is stored as a normal Build Plan and remains **pending review**; approval gating is unchanged.
- **Result**: On success, the user is redirected to the Build Plans screen with the new plan; on failure (invalid/missing/inactive bundle), redirect to Industry Profile with `aio_bundle_plan_result=error`. The plan is editable and reviewable like any other draft; it carries `source_starter_bundle_key` in its definition when created from a bundle.

---

## 3. Industry explanation integration (Prompt 365)

- **Item-level**: Each plan item payload may contain additive industry metadata (`industry_source_refs`, `recommendation_reasons`, `industry_fit_score`, `industry_warning_flags`). The **Industry_Build_Plan_Explanation_View_Model** turns this into a UI-facing view (summary lines, warning badges, fit classification, source refs).
- **Detail panel**: When an item has industry data, the detail builders add an **"Industry context"** section. The section is rendered via **plugin/src/Admin/Views/build-plan/industry-plan-explanations.php**, which shows fit badge, rationale lines, warning badges, and industry sources. When the item has no industry metadata, the section is omitted (generic fallback).
- **Plan-level warnings**: Plan definition `warnings` (Build_Plan_Schema::KEY_WARNINGS) are shown in the **context rail** (warnings_summary). Industry hierarchy, CTA, or LPagery plan-level warnings from the scoring/family-rule/LPagery advisor should be merged into this array so they appear in the rail.
- **Safe fallback**: Malformed or missing industry explanation metadata must not break the panel; the view model returns `has_industry_data: false` and the section is not rendered.

---

## 4. Constraints

- No auto-approval of plans; explanations are informational only.
- Planner/executor separation unchanged; review UI does not mutate plan decisions by itself.
- Reviewer/admin-only; capability checks remain at screen and action level.
