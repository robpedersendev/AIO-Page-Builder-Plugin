# AIO Page Builder — v2 Scope Backlog

Features explicitly deferred from v1 for implementation in v2. These are NOT permanently de-scoped;
each item has a defined implementation plan documented here. All v1 de-scope decisions are reversed by
this document and superseded by the v2 targets below.

---

## 1. Execution Engine — `ASSIGN_PAGE_HIERARCHY` handler

**Constant:** `Execution_Action_Types::ASSIGN_PAGE_HIERARCHY`

**v1 state:** Hierarchy assignment is embedded inline in `CREATE_PAGE` via `Template_Page_Build_Service`
(resolves `post_parent` from plan item payload during page creation). No standalone executable handler exists.
The Build Plan hierarchy step generates advisory `ITEM_TYPE_HIERARCHY_NOTE` items.

**v2 implementation plan:**
- Create `Assign_Page_Hierarchy_Handler` implementing `Execution_Handler_Interface`.
- Register in `Execution_Provider` / handler registry.
- Add `ASSIGN_PAGE_HIERARCHY` to `Execution_Action_Types::ALL`.
- Build Plan hierarchy step to emit executable envelopes where appropriate.
- Row action in Build Plan workspace: execute / retry for hierarchy items.
- Capability: existing `aio_pb_execute` or a fine-grained `aio_pb_execute_hierarchy`.
- Nonce: `aio_pb_execute_hierarchy_item_{item_id}` / `aio_pb_execute_hierarchy_bulk`.
- Audit log: actor, plan_id, item_id, page_id, old_parent, new_parent, result.
- Tests: handler unit, executor integration, UI truthfulness.

---

## 2. Execution Engine — `CREATE_MENU` handler

**Constant:** `Execution_Action_Types::CREATE_MENU`

**v1 state:** New-menu creation is subsumed by `UPDATE_MENU` via `Apply_Menu_Change_Handler::do_create()`.
No separate `create_menu` plan item type or UI affordance emits `create_menu` envelopes.

**v2 implementation plan:**
- Create `Create_Menu_Handler` implementing `Execution_Handler_Interface`.
- Register in `Execution_Provider` / handler registry.
- Add `CREATE_MENU` to `Execution_Action_Types::ALL`.
- Build Plan navigation step to emit `create_menu` envelopes for net-new menus (separate from replace/update flows).
- Distinct UI affordance in Build Plan workspace for menu creation vs. menu update.
- Capability: existing `aio_pb_execute` or fine-grained `aio_pb_execute_menu`.
- Nonce: `aio_pb_execute_menu_item_{item_id}` / `aio_pb_execute_menu_bulk`.
- Audit log: actor, plan_id, item_id, menu_name, location, result.
- Tests: handler unit, executor integration, UI truthfulness, separation from UPDATE_MENU path.

---

## 3. Profile Snapshot Persistence

**File:** `plugin/src/Domain/Storage/Profile/Profile_Snapshot_Data.php`  
**Spec:** §22.11, SPR-010; `docs/schema/profile-snapshot-schema.md`

**v1 state:** Schema/type definition only. No persistence, no repository, no UI, no export/restore inclusion.
`Operational_Snapshot_Repository` handles crawl snapshots; profile snapshots are a separate concern.

**v2 implementation plan:**
- Create `Profile_Snapshot_Repository` for CRUD of profile snapshots (WordPress options or custom table).
- Capture snapshot on onboarding completion (`Onboarding_Planning_Request_Orchestrator::run()`).
- Capture snapshot on profile save via `Profile_Store::merge_brand_profile()` / `merge_business_profile()`.
- Expose history UI: list of snapshots with timestamps, diff view, restore action.
- Include snapshots in export bundle (`Export_Bundle_Schema`).
- Import/restore flow: validate snapshot schema version, restore profile fields.
- Tests: repository CRUD, capture on save/onboarding, export inclusion, restore flow.

---

## 4. AI Provider Cost Tracking (`cost_usd`)

**Files:**
- `plugin/src/Domain/AI/Providers/Drivers/Concrete_AI_Provider_Driver.php`
- `plugin/src/Domain/AI/Providers/Drivers/Additional_AI_Provider_Driver.php`
- `plugin/src/Domain/AI/Providers/Provider_Response_Normalizer.php`

**v1 state:** `cost_usd` field is present in the normalized usage struct but always `null`.
Token counts (`prompt_tokens`, `completion_tokens`, `total_tokens`) are authoritative provider-reported values
and are stored. Neither OpenAI nor Anthropic return cost directly in their API responses.

**v2 implementation plan:**
- Build a `Provider_Pricing_Registry` keyed by provider slug + model slug → per-token USD rates.
- Seed initial rates from provider public pricing pages; document update cadence.
- `Concrete_AI_Provider_Driver` and `Additional_AI_Provider_Driver` compute `cost_usd` as
  `(prompt_tokens * input_rate) + (completion_tokens * output_rate)` using the registry.
- Store `cost_usd` in the AI run artifact metadata alongside token counts.
- Surface total-cost and per-run-cost in the AI Run History admin screen.
- Alert/cap: configurable monthly spend limit per provider with admin notice when approaching threshold.
- Tests: pricing registry unit, cost computation in both drivers, storage integration, UI display, cap/alert logic.

---

## 5. Release Gate for v2

Before any v2 feature ships, each item above requires:
1. Decision memo confirming final implementation approach.
2. Full handler/service/repository implementation.
3. Capability, nonce, sanitization, and audit log for any state-changing path.
4. Unit + integration tests passing.
5. Gap report updated to "Fully Implemented."
6. Changelog entry.
