# Industry Execution Safeguard Contract

**Spec**: Execution sections of aio-page-builder-master-spec.md; industry-build-plan-scoring-contract.md; planner/executor separation.

**Status**: Defines the policy that execution must not read live industry state; only approved Build Plan artifacts and explicit overrides may influence execution (Prompt 373).

---

## 1. Purpose

- **Harden execution** so industry preferences influence behavior only through approved Build Plan artifacts or explicit operator choices (e.g. overrides), never through live, mutable industry profile or pack state at execution time.
- **Prevent drift**: If an admin changes industry profile or packs after a plan is approved, execution of that plan must not silently change; it must follow the approved plan artifact.
- **Preserve planner/executor separation**: Planning (and scoring) may read live industry context; execution must be artifact-driven and approval-bound.

---

## 2. Policy

| Rule | Description |
|------|-------------|
| **No live industry reads in execution** | Execution entrypoints (create page, replace page, menu change, finalize plan, etc.) must not call Industry_Profile_Repository::get_profile(), Industry_Pack_Registry, or re-run Industry_*_Recommendation_Resolver or Industry_Build_Plan_Scoring_Service. |
| **Artifact-only industry influence** | Industry-related behavior during execution must be derived only from (1) the approved Build Plan definition (steps, items, payloads with industry metadata already embedded at plan generation/approval time), or (2) persisted override state (e.g. industry section/template overrides) that was recorded at review time. |
| **Safe failure** | If required approved metadata is missing (e.g. template_key or payload incomplete), execution must fail safely (e.g. skip item, log, or surface error) and must not fall back to reading live industry profile to "fix" the plan. |
| **No implicit live-state mutation** | Execution must not mutate industry profile or pack state. Override state is mutated only by dedicated admin actions (Save_Industry_*_Override_Action), not by execution jobs. |

---

## 3. Execution entrypoints (audit scope)

- **Create_Page_Handler** / **Create_Page_Job_Service**: Use plan item payload (template_key, sections, etc.); no industry resolver calls.
- **Replace_Page_Handler** / **Replace_Page_Job_Service**: Use plan item payload (target_template_key, etc.); no industry resolver calls.
- **Apply_Menu_Change_Handler** / **Menu_Change_Job_Service**: Use plan item payload; no industry reads.
- **Finalize_Plan_Handler** / **Finalization_Job_Service**: Updates plan status/artifacts; must not re-run industry scoring.
- **Bulk_Template_Page_Build_Service**, **Template_Page_Build_Service**, **Template_Page_Replacement_Service**: Use plan definition and item payloads only; no Industry_Profile_Repository or recommendation resolvers.

---

## 4. Allowed vs disallowed

- **Allowed**: Reading industry_source_refs, recommendation_reasons, industry_fit_score, industry_warning_flags, industry_conflict_results, industry_explanation_summary from the **plan item payload** (already stored in the artifact). Using override state (Industry_Section_Override_Service, etc.) for display or audit only during execution is acceptable if needed; execution logic must not branch on live overrides to change what gets built—only the approved plan item payload defines what to build.
- **Disallowed**: Calling get_profile(), get( $industry_key ) on pack registry, resolve() on section/page template recommendation resolvers, or enrich_output() on Industry_Build_Plan_Scoring_Service from any execution path.

---

## 5. Diagnostics and assertions

- Where helpful, execution services may assert or log that they are using plan-derived data (e.g. "industry influence from plan artifact") for traceability. No new audit subsystem is required; this contract documents the intended boundary.
- If future work adds execution-time industry-dependent branching, it must use only snapshot or artifact fields (e.g. industry context snapshot captured at approval, see industry-approval-snapshot-contract.md) and must not read live profile or packs.

---

## 6. Files and integration

- **Contract**: docs/contracts/industry-execution-safeguard-contract.md (this file).
- **Master spec**: docs/specs/aio-page-builder-master-spec.md should reference this contract in execution and planner/executor separation sections.
- **Audit**: Execution domain has been audited; no Industry_Profile_Repository or recommendation resolver usage in execution code paths. New execution code must comply with this contract.
