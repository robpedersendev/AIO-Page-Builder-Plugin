# Onboarding State Machine and Step Contract

**Document type:** Authoritative contract for the onboarding workflow state machine (spec §23, §53.2, §22, §24, §25, §26, §30–34, §59.8).  
**Governs:** Onboarding steps, statuses, transitions, validation checkpoints, draft-save behavior, prefill behavior, re-entry rules, and handoff to AI planning submission.  
**Reference:** Master Specification §23 Guided Onboarding Experience, §53.2 First-Time Setup Flow, §22 Brand and Business Profile, §24 Crawl Engine, §25 Provider layer, §26 Prompt Pack and input artifacts; profile-schema.md; provider-secret-storage-contract.md; ai-provider-contract.md.

---

## 1. Scope and principles

- **Preparatory only:** Onboarding gathers and validates planning inputs. It does not execute site mutations, run AI requests, or generate Build Plans. Planner/executor separation is preserved.
- **Structured steps:** The flow is broken into explicit steps with required completion conditions. No single giant form; step outputs are structured and traceable.
- **Draft vs submitted:** Draft state and prefills are controlled and traceable. "Ready to ask AI for a plan" is distinct from "AI plan already executed." Completion states are not client-authoritative; server validates before transition to ready/submitted.
- **No secrets in drafts:** Provider secret values must never be embedded in onboarding draft state (provider-secret-storage-contract.md). Draft storage holds at most provider_id and credential_state (e.g. configured/absent), not API keys or tokens.
- **Re-entry explicit:** Partial completion, resume, and rerun behavior are defined so that first-run, draft resumption, and return-user flows do not drift.

---

## 2. Onboarding steps (canonical order)

Steps are ordered. A step is either not started, in progress, completed, skipped (where allowed), or blocked. Forward navigation beyond the current step is allowed only when required predecessors are satisfied (or the step is explicitly skippable).

| Step key | Title (reference) | Purpose | Required for "ready" | Prefill source | Blocking conditions |
|----------|-------------------|---------|----------------------|----------------|---------------------|
| `welcome` | Welcome / Orientation | Introduce plugin, reporting disclosure, next-step action (§53.2) | Acknowledgment only | — | — |
| `business_profile` | Business Profile | Collect business name, type, goals, offers, audience, geography (profile-schema §4) | Yes (required planning fields per profile-schema) | `aio_page_builder_profile_current` → business_profile | Profile validation failure |
| `brand_profile` | Brand Profile | Collect brand positioning, voice, tone, CTA style, assets (profile-schema §3) | Yes (required planning fields per profile-schema) | `aio_page_builder_profile_current` → brand_profile | Profile validation failure |
| `audience_offers` | Audience and Offers | Personas, services/offers, value prop (profile-schema §5–6) | Optional but recommended | Same profile option | — |
| `geography_competitors` | Geography and Competitors | Geography entries, competitor info (profile-schema §5, §8) | Optional | Same profile option | — |
| `asset_intake` | Asset Intake | Logo, imagery, references (§22.10) | Optional | Profile asset_references | — |
| `existing_site` | Existing Site Information | Site URL, crawl preferences, trigger for public-site analysis (§23.8, §24) | Optional for planning; required if user wants crawl context | Stored profile current_site_url; crawl session list (references only) | — |
| `crawl_preferences` | Crawl Preferences and Initiation | Configure and optionally start a crawl (§24) | No (crawl can be skipped or run later) | Last crawl run reference (id only), crawl scope preferences | Crawl in progress (optional block for "review") |
| `provider_setup` | Provider Setup | Select provider, enter credentials, confirm state (§23.9, §25) | Yes (at least one provider with credential_state = configured) | Provider config (provider_id, credential_state only; no secrets) | No configured provider → blocked at review |
| `review` | Review and Confirmation | Summarize inputs, profile completeness, crawl status, provider state; highlight gaps (§23.10) | User confirmation | — | Missing required profile fields or no configured provider → blocked |
| `submission` | Submission | Final confirmation and handoff to AI plan request creation | N/A (this step is the handoff) | — | Must not be reachable until review passed |

Step keys are stable identifiers for persistence and transitions. Exact UI labels may vary; the contract governs behavior and data.

---

## 3. Overall onboarding statuses

The **overall** onboarding state is one of the following. It is stored server-side and is not client-authoritative.

| Status | Description | Allowed next |
|--------|-------------|--------------|
| `not_started` | No onboarding session in progress; may be first run or post-submission. | `in_progress` (user starts onboarding) |
| `in_progress` | User is in the flow; current step and draft data are stored. | `draft_saved`, `blocked`, `ready_for_submission`, `in_progress` (step change) |
| `draft_saved` | User saved and left; partial state persisted. | `in_progress` (resume), `not_started` (abandon; optional) |
| `blocked` | Validation or dependency failure prevents reaching submission (e.g. missing provider, invalid profile). | `in_progress` (user fixes and continues) |
| `ready_for_submission` | All validation checkpoints passed; review step confirmed; user may submit. | `submitted` (user submits) |
| `submitted` | User confirmed submission; handoff to AI plan request creation. Onboarding session is complete. | `not_started` (for next run) or `in_progress` (rerun onboarding) |

---

## 4. Per-step statuses

Each step has a status used for navigation and validation.

| Step status | Description |
|-------------|-------------|
| `not_started` | Step not yet entered or not applicable. |
| `in_progress` | User is on this step or has started it but not completed validation. |
| `completed` | Step validation passed (and any required data saved). |
| `skipped` | Step was explicitly skipped (only where product allows). |
| `blocked` | Step cannot be completed due to dependency (e.g. provider_setup blocked because no credential). |

Completed steps are not re-validated on re-entry unless the underlying data (e.g. profile) is changed; then the step may revert to in_progress or blocked until re-validated.

---

## 5. State transition table (overall and key transitions)

| From status | Event / condition | To status | Notes |
|-------------|-------------------|-----------|-------|
| `not_started` | User opens onboarding (first time or rerun) | `in_progress` | Current step set from prefill or welcome. |
| `in_progress` | User clicks "Save draft" / equivalent | `draft_saved` | Draft payload persisted; no secrets. |
| `draft_saved` | User resumes onboarding | `in_progress` | Prefill from draft + profile/crawl refs. |
| `in_progress` | User completes review; validation passes | `ready_for_submission` | All checkpoints passed. |
| `in_progress` | Validation fails (missing provider, invalid profile) | `blocked` | UI shows what is missing; user fixes and continues. |
| `blocked` | User addresses failure (e.g. configures provider) | `in_progress` | Re-validate and proceed. |
| `ready_for_submission` | User confirms submission | `submitted` | Handoff to AI plan request creation. |
| `submitted` | — | Terminal for this run | Next planning run may start a new onboarding session or reuse profile/crawl. |

Step-level transitions:

- **Advance:** User completes current step (validation passes) → current_step advances; previous step marked completed.
- **Save draft:** Current step and all prior step data saved; overall status → draft_saved.
- **Re-entry:** Load draft + prefill; current_step and per-step statuses restored; overall → in_progress.
- **Block:** If at review and provider missing or profile invalid → overall blocked; review step shows blockers.

---

## 6. Validation checkpoints

Validation is run at defined points; it is server-side. Client may do best-effort validation for UX; authoritative result is server.

| Checkpoint | When | Condition for pass | On fail |
|------------|------|--------------------|--------|
| **Profile completeness (planning)** | After business_profile, brand_profile (and optionally audience_offers, etc.) | Required fields per profile-schema.md present and valid (non-empty, min length where required, no placeholder-only values where prohibited) | Step remains in_progress or blocked; errors surfaced. |
| **Provider readiness** | Before or at review | At least one provider in provider config with credential_state = `configured` (provider-secret-storage-contract). | overall → blocked; user directed to provider_setup. |
| **Crawl/site context (optional)** | Review step | If user chose to use crawl context: a crawl run exists and is available by reference. Not required for "ready" if product allows planning without crawl. | Review may show "no crawl yet" as informational; only blocks if product requires crawl for submission. |
| **User intent / goal** | Review step | User has confirmed readiness (and optionally entered goal text if product requires). | Review not complete. |
| **Final confirmation** | Submission step | User explicitly confirms; nonce and capability checked. | No transition to submitted. |

No checkpoint may depend on raw secret values. Provider readiness uses credential_state only.

---

## 7. Draft state shape (stored)

Draft state is persisted so the user can leave and resume. It must be secret-free and export-safe.

| Field | Type | Description |
|-------|------|-------------|
| `version` | int/string | Schema version for draft payload (for future migration). |
| `overall_status` | string | One of overall statuses (§3). |
| `current_step_key` | string | Step key (§2) the user was on when saving. |
| `step_statuses` | object | Map step_key → step status (§4). |
| `profile_snapshot_ref` | string/null | Optional reference to saved profile (e.g. option key or "current"); draft must not duplicate full profile blob if it can be loaded from profile storage. |
| `crawl_run_id_ref` | string/null | Optional crawl run id reference; no crawl payload in draft. |
| `provider_refs` | array | Optional list of { provider_id, credential_state }; no secrets. |
| `goal_or_intent_text` | string | Optional user-entered goal; redacted if ever logged. |
| `updated_at` | string (ISO 8601) | Last draft save time. |

**Excluded from draft:** API keys, tokens, passwords, raw profile blobs (if profile is stored separately and loadable by ref), full crawl payloads. Draft storage location (e.g. option, user meta) is implementation-defined; the shape above is the contract.

---

## 8. Prefill source mapping

When entering or resuming onboarding, data is prefilled from the following. No secret values are ever prefilled from the secret store.

| Data needed | Source | Notes |
|-------------|--------|-------|
| Business profile | Option `aio_page_builder_profile_current` → `business_profile` | profile-schema §4. |
| Brand profile | Option `aio_page_builder_profile_current` → `brand_profile` | profile-schema §3. |
| Current site URL | Same profile → `business_profile.current_site_url` | §23.8. |
| Crawl context | Crawl session list / latest run id (reference only) | §24; no full crawl payload in onboarding draft. |
| Provider readiness | Option `aio_page_builder_provider_config` (provider_id, credential_state only) | provider-secret-storage-contract; never prefill secret values. |
| Draft state | Stored draft payload (§7) | Restore current_step_key, step_statuses, and any draft-specific fields. |

Prefill order: load draft first (if any), then overlay current profile and provider config. Where draft and profile both have data, product may prefer draft for in-progress edits or profile for "latest saved"; contract recommends draft for step position and step_statuses, profile for actual field values unless draft holds explicit overrides.

---

## 9. Re-entry and partial completion behavior

- **Resume draft:** User returns after "Save draft." Load draft payload; restore overall_status to in_progress, current_step_key, and step_statuses. Prefill profile and provider refs. User continues from current step.
- **Rerun after submission:** User opens onboarding again. Treat as new run: prefill from current profile and crawl/provider refs; overall_status = in_progress; current_step_key = welcome (or first required step). No silent overwrite of prior run history (§23.11).
- **Partial completion:** If user leaves without submitting, only completed steps and draft payload are persisted. On resume, validation is re-run for the current step so that expired state (e.g. provider became invalid) can set blocked.
- **Blocked state re-entry:** User fixes blocker (e.g. configures provider). On next "Continue" or "Review," re-run validation; if pass, overall_status → in_progress and user can proceed to ready_for_submission.

---

## 10. Handoff to AI plan request creation

- **Handoff condition:** Overall status = `ready_for_submission` and user has confirmed submission (capability + nonce verified). After handoff, status becomes `submitted`.
- **What handoff provides (conceptually):** Validated profile reference (or snapshot), crawl run reference (if used), provider selection, prompt pack reference, and user goal/intent. No secret values; the AI submission layer retrieves credentials via the secret store when making the request.
- **Boundary:** This contract does not implement the AI submission logic. It only defines that (1) onboarding is complete when status = submitted, and (2) the downstream "create AI plan request" step receives structured, non-secret inputs and is responsible for calling the provider layer and storing artifacts. Planner/executor separation: onboarding produces planning-ready inputs; execution of the plan is a later phase.

---

## 11. Scenario coverage

| Scenario | Initial state | Actions | Expected outcome |
|---------|---------------|---------|------------------|
| **Fresh first-run onboarding** | not_started; no profile, no provider, no draft | User opens onboarding → welcome → business_profile → … → provider_setup → review → submit | Steps advance; validation at each checkpoint; on submit → submitted. |
| **Partial draft resumption** | draft_saved; current_step_key = brand_profile; step_statuses: welcome completed, business_profile completed | User resumes onboarding | in_progress; prefill from draft + profile; user continues from brand_profile. |
| **Missing-provider blocked state** | in_progress; current_step = review; no provider with credential_state = configured | User reaches review | overall → blocked; UI shows "Configure provider"; user goes to provider_setup, configures, returns → re-validate → in_progress → ready_for_submission when pass. |
| **Profile-prefilled resume state** | draft_saved; profile already has business_profile and brand_profile filled | User resumes | Prefill from profile; draft restores step position; user sees existing data and can edit or continue. |
| **Ready-for-submission state** | ready_for_submission; review confirmed | User clicks final submit (nonce + capability ok) | overall → submitted; handoff to AI plan request creation with profile ref, crawl ref, provider id, no secrets. |

---

## 12. Security and permissions

- **Draft state:** Admin-owned; stored under capability-gated write. Future step mutations (save draft, advance step, submit) must be capability-checked and nonced (intent verification).
- **No client-authoritative completion:** Ready and submitted states are set only after server-side validation and (for submit) nonce/capability verification.
- **No secrets in draft:** Enforced by draft shape (§7) and prefill rules (§8). Provider setup step stores only provider_id and credential_state in draft/provider_refs.

---

## 13. Cross-references

- **Profile:** profile-schema.md (required fields, validation); global-options-schema.md (`aio_page_builder_profile_current`).
- **Provider:** provider-secret-storage-contract.md (credential states, no secrets in config); ai-provider-contract.md (request/response shapes; credentials via separate path).
- **Crawl:** §24; crawler-admin-screen-contract.md (sessions, run id).
- **Admin screen:** admin-screen-inventory.md (`aio-page-builder-onboarding` — Onboarding & Profile; not implemented).
- **Build Plan / AI run:** §10.4, §10.5; Build Plan prerequisites are satisfied when onboarding delivers validated profile, crawl ref, and provider readiness; actual plan creation is out of scope for this contract.

---

## 14. Out of scope for this contract

- Onboarding UI implementation.
- Provider driver calls or connection tests.
- AI submission or Build Plan generation.
- Export/restore of onboarding draft (draft may be excluded or included under export rules elsewhere).
- Definition of admin routes beyond the referenced screen slug.
