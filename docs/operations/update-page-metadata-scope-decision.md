# UPDATE_PAGE_METADATA — Scope Decision

**Date:** 2025-03-18  
**Status:** Accepted  
**Sources:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §40.1; [execution-action-contract.md](../contracts/execution-action-contract.md); Execution_Action_Types, Bulk_Executor, Execution_Provider, Queue_Health_Summary_Builder, Queue_Recovery_Service, Logs_Monitoring_State_Builder; SEO_Media_Step_UI_Service.

---

## 1. Objective

Resolve the product/spec status of **UPDATE_PAGE_METADATA** (update_page_metadata): in scope as an executable action or out of scope with cleanup so the system does not advertise or track it as available work.

---

## 2. Current state

- **Spec §40.1:** Execution engine “shall support” job types including “update page metadata.” Snapshot scope (§41.1) includes “page metadata.”
- **Contract:** execution-action-contract defines `update_page_metadata`: “Update title, slug, meta only”; target `page_ref`, `plan_item_id`; snapshot optional.
- **Action type:** `Execution_Action_Types::UPDATE_PAGE_METADATA` exists and is in `ALL`.
- **Bulk_Executor:** Maps `Build_Plan_Item_Schema::ITEM_TYPE_SEO` → `UPDATE_PAGE_METADATA`. Approved SEO items are turned into update_page_metadata envelopes.
- **Execution_Provider:** Does **not** register a handler for `UPDATE_PAGE_METADATA`. Registered handlers: CREATE_PAGE, REPLACE_PAGE, UPDATE_MENU, APPLY_TOKEN_SET, FINALIZE_PLAN.
- **Result:** Envelopes for update_page_metadata are built when SEO items are approved, but `Single_Action_Executor` has no handler, so jobs are refused (e.g. ERROR_ACTION_NOT_AVAILABLE).
- **SEO step:** `SEO_Media_Step_UI_Service` is explicitly “Shell-only UI for seo step. No SEO or media execution.” Same pattern as the design-tokens step (recommendation-only).

---

## 3. Spec vs product stance

- The **spec** defines “update page metadata” as a supported job type. The **contract** defines the envelope and target reference.
- The **product** has not implemented a handler; the SEO step is shell-only and does not offer execution. So the system currently advertises the type (it appears in action-type lists and in the item-type→action-type mapping) but cannot execute it, leading to refused jobs when users approve SEO items and run the queue.
- **Conclusion:** Align product with the explicit “no SEO or media execution” stance: treat UPDATE_PAGE_METADATA as **out of scope** for this version and remove it from places that imply it is available work.

---

## 4. Decision

**Outcome: B — UPDATE_PAGE_METADATA is out of scope; de-scope so the system no longer advertises or tracks it as available.**

- **Rationale:** (1) SEO step is documented as shell-only with no SEO or media execution. (2) No handler is registered; jobs are refused. (3) Spec defines the job type for future use, but the product has not committed to implementing it in this version. (4) Removing the mapping and type from “known” lists avoids building envelopes that will always be refused and stops the system from implying the action is supported.
- **Intended product truth:** The SEO/meta step shows recommendations for review only. There is no executable “update page metadata” action in this version. The action type remains in the contract and enum for spec/contract stability, but the plugin does not map plan items to it and does not list it among handled action types in queue health, recovery, or logs.

---

## 5. If outcome had been A (in scope)

If UPDATE_PAGE_METADATA had been approved as in scope, the minimum would be:

- **Execution_Provider:** Register a handler for `UPDATE_PAGE_METADATA` (e.g. `Update_Page_Metadata_Handler` delegating to a job service).
- **Job service:** Accept envelope with `page_ref` and `plan_item_id`; resolve page; update title, slug, and/or meta (e.g. SEO meta description, OG tags) from plan item payload; optional pre-change snapshot; record outcome. Validation: page exists, meta keys allowed, no raw HTML/script in values.
- **Contract:** execution-action-contract already defines target reference and snapshot optional; payload shape for “new meta” would need to be specified (e.g. title, slug, meta_description, meta_keywords or schema).
- **Rollback:** Optional; snapshot/restore for page_metadata object family if required by spec.

This outcome was not chosen; it is recorded here for traceability only.
