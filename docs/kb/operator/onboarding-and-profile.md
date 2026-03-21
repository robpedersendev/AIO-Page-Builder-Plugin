# Onboarding and profile (operator guide)

**Screen:** **AIO Page Builder → Onboarding & Profile** (`aio-page-builder-onboarding`).  
**Capability:** `aio_run_onboarding` (see [concepts-and-glossary.md](../concepts-and-glossary.md) for default roles).  
**Planning submission additionally requires:** `aio_run_ai_plans` (enforced when you click **Request AI plan**).  
**Related:** [admin-operator-guide.md](../../guides/admin-operator-guide.md) §2; [end-user-workflow-guide.md](../../guides/end-user-workflow-guide.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [FILE_MAP.md](../FILE_MAP.md).

This page describes **implemented** behavior: step order, draft storage, prefill, blocking rules, and planning submission. It does **not** create Build Plans or execute site changes—that happens later from **AI Runs** / **Build Plans**.

---

## 1. What onboarding is for

Onboarding collects **brand profile**, **business profile**, optional **template preference signals**, and context about your **existing site** and **crawls** so the plugin can run an **AI planning request** (an **AI Run** with validated output). The in-screen copy states that the flow collects context “so you can request an AI-generated plan,” and that the plan appears in **AI Runs**, then you can create a **Build Plan** from it.

**Separate from Industry Profile:** Primary industry, starter bundles, and industry question-pack answers are managed mainly on **Industry Profile** (`aio-page-builder-industry-profile`). The onboarding handler can **persist** industry fields when they are posted with the form, but the **visible onboarding steps** do not render industry pickers—use **Industry Profile** for that configuration. See [industry-admin-workflows.md](../industry/industry-admin-workflows.md).

---

## 2. What to gather before starting

Have ready (as applicable):

- Business name, type, goals, value proposition, differentiators.
- Brand positioning, voice, formality/clarity preferences, CTA style, restrictions.
- Audience, offers, strategic priorities, compliance notes.
- Geographic market, existing marketing language, seasonality.
- Visual inspiration references and sales-process notes (free text).
- **Current site URL** if you have a live site (used for planning/crawl context).
- **AI provider** credentials (configured on **AI Providers**—not entered on this screen).
- Optional: run a **crawl** first (see **Crawl Preferences** step) if you want richer structure context—requires `aio_view_sensitive_diagnostics` for **Crawl Sessions**.

---

## 3. Step order (as implemented)

Steps are fixed in this order (`Onboarding_Step_Keys::ordered()`):

| Order | Step key (internal) | UI label |
|------:|---------------------|----------|
| 1 | `welcome` | Welcome |
| 2 | `business_profile` | Business Profile |
| 3 | `brand_profile` | Brand Profile |
| 4 | `audience_offers` | Audience & Offers |
| 5 | `geography_competitors` | Geography & Competitors |
| 6 | `asset_intake` | Asset Intake |
| 7 | `existing_site` | Existing Site |
| 8 | `crawl_preferences` | Crawl Preferences |
| 9 | `provider_setup` | AI Provider Setup |
| 10 | `template_preferences` | Page & template preferences |
| 11 | `review` | Review |
| 12 | `submission` | Submission |

**Note:** The **Geography & Competitors** label is used in the UI; the current form fields cover **core geographic market**, **existing marketing language**, and **seasonality** only (no separate “competitors” field on this screen).

---

## 4. What each step does (behavior)

- **Welcome:** Intro text only (no fields).
- **Business Profile:** Core business fields (name, type, contact/conversion goals, value prop, differentiators). Saved into the **current profile** store (`Profile_Store` / `brand_profile` + `business_profile` roots).
- **Brand Profile:** Positioning, voice summary, voice/tone enums, CTA style, extra rules, content restrictions.
- **Audience & Offers:** Target audience, primary offers, strategic priorities, compliance/legal notes.
- **Geography & Competitors:** Geographic market, marketing language, seasonality.
- **Asset Intake:** Visual inspiration references, sales process notes.
- **Existing Site:** **Current site URL** (optional). Includes a link/button to open **Crawl Sessions** (same target as **Crawl Preferences**).
- **Crawl Preferences:** Shows **latest crawl run id** and count when available; button to **Crawl Sessions**. Does not start a crawl from this screen.
- **AI Provider Setup:** Readiness message only—**configure providers on AI Providers**. “Ready” means at least one provider has `credential_state === configured` in stored provider config (no secrets shown).
- **Page & template preferences:** Advisory dropdowns/checkboxes; stored in `template_preference_profile` on save/advance when this step is current. Copy states they do not override structural or CTA rules.
- **Review:** Read-only summary (business name, type, audience, positioning) plus provider list (`provider_id: credential_state`). If **no** provider is configured, you see **Not set** / not-ready messaging and a pointer to **AI Providers**.
- **Submission:** Explains requesting a plan; **Request AI plan** submits the orchestrator. Link to last run appears when `last_planning_run_id` / post id exist on the draft after a prior attempt.

---

## 5. Drafts: how they work

- **Persisted draft** (plugin option via `Onboarding_Draft_Service`) stores: `version`, `overall_status`, `current_step_key`, per-step statuses, optional `crawl_run_id_ref`, `provider_refs` snapshot shape, `goal_or_intent_text` (schema field; **not** exposed in the current onboarding form), `last_planning_run_id`, `last_planning_run_post_id`, `updated_at`.
- **Save draft** (`save_draft` action): Writes profile fields from POST (brand/business merge rules below), merges **industry profile** from POST when industry services exist, sets `overall_status` to **`draft_saved`**, redirects with a success notice (“Draft saved. You can return later to continue.”).
- **Resume:** When `overall_status` is `draft_saved`, the UI shows an info notice: “You have saved draft progress. You can continue below.” For display, `draft_saved` is treated like **in progress** in the stepper.
- **Advance** (`advance_step`): Marks the current step **completed**, moves to the next step (**in progress**), sets `overall_status` to **`in_progress`**. If the **next** step is **Review** and no provider is ready, `overall_status` becomes **`blocked`** (you can still land on Review and see blockers).
- **Back** (`go_back`): Moves to the previous step and marks it **in progress**; sets `overall_status` to **`in_progress`**.

---

## 6. How saved data is reused (prefill)

`Onboarding_Prefill_Service` builds:

- **`profile`:** Full current profile from `Profile_Store` (brand, business, template preferences).
- **`current_site_url`:** From business profile when set.
- **Crawl listing:** Up to 20 sessions; **latest** run id; timestamp for stale-crawl warnings. If the draft contains `crawl_run_id_ref`, that value **overrides** which run is treated as “latest” for prefill.
- **`provider_refs`:** Provider id + `credential_state` only (from settings)—**never** API keys.

Forms **prefill** from this payload on each render. Saving merges **partial** POST into profile: brand and business persist **any posted fields** on **Save draft** and **Next** (not step-gated). **Template preferences** are written only when the current step is **`template_preferences`**.

---

## 7. “Ready” vs “not ready” (truthful meanings)

| Meaning | Implementation |
|--------|------------------|
| **Provider ready** | At least one provider has credentials in state **`configured`** (`Onboarding_Prefill_Service::is_provider_ready()`). |
| **Blocked on Review** | You are on the **Review** step **and** provider is not ready → warning “Cannot proceed until: Configure an AI provider to continue.” **Next** is hidden until unblocked. |
| **Can request AI plan** | You are on **Submission**, the UI is not in the blocked-review pattern, submit handler allows **`aio_run_onboarding`** + **`aio_run_ai_plans`**, orchestrator passes provider/pack/cap checks. |
| **Submission warnings (non-blocking)** | On **Submission**, optional notices: **profile updated since last successful planning run** (when profile merge snapshots exist and are newer than the last run’s post modified time), and **stale crawl** (default threshold **30 days**, overridable via main settings key `onboarding_stale_crawl_warning_days` when &gt; 0). These **do not** block the button. |

Constants such as `ready_for_submission` / `submitted` exist in the status enum for the data model; the **live screen** primarily uses **`not_started`**, **`in_progress`**, **`draft_saved`**, and **`blocked`** in the save/advance paths you interact with.

---

## 8. Planning request (rerun / outcomes)

- Button label: **Request AI plan**.
- **Does not** run in the background of other steps; you must be on **Submission** (`Onboarding_Planning_Request_Orchestrator` blocks otherwise).
- **Creates an AI Run** (success, failed validation, or provider failure). On every completed handoff path that creates a run, the draft is updated with **`last_planning_run_id`** and **`last_planning_run_post_id`** (`link_run_to_draft`).
- **Success:** User message states you can open the run from **AI Runs** or create a **Build Plan**. **Does not** auto-create a Build Plan.
- **Other failures:** Blocked (no provider, spend cap, no prompt pack, etc.), validation failed, or provider error—messages point you to **AI Runs** where applicable.
- **Spend cap:** If a monthly cap is configured and exceeded without override, submission is blocked with a message referencing **AI Providers** settings.
- **Rerun:** You can submit again from **Submission**; the latest run link updates when a new run is recorded. If profile changed after a successful run, the **submission warning** suggests submitting again so planning reflects the latest profile.

---

## 9. Step-by-step guides

### 9.1 First-time onboarding

1. Open **Onboarding & Profile** (must have `aio_run_onboarding`).
2. Read **Welcome** → **Next**.
3. Complete **Business Profile** → **Save draft** or **Next** as needed.
4. Continue through **Brand**, **Audience**, **Geography**, **Assets**, **Existing Site** (URL optional).
5. On **Crawl Preferences**, optionally open **Crawl Sessions**, run a crawl, return and continue.
6. **AI Provider Setup:** Open **AI Providers**, add/configure at least one provider, return.
7. Set **Page & template preferences** if desired → **Next**.
8. **Review:** Confirm summary and provider readiness → **Next**.
9. **Submission:** Click **Request AI plan** (requires `aio_run_ai_plans`). Follow the admin notice, then open **AI Runs** / create a **Build Plan** as appropriate.

### 9.2 Revisiting onboarding

Open the same screen. The stepper shows saved **current step** and per-step statuses. If you previously **saved draft**, you’ll see the resume notice. Use **Back** / **Next** to move; use **Save draft** to persist without advancing.

### 9.3 Updating brand/business information later

You can return to any step and change fields. **Save draft** or **Next** merges posted brand/business fields into the **current profile** store. Template preferences only update when you are on **Page & template preferences**. For **industry** fields, use **Industry Profile** for the full UI.

### 9.4 Incomplete inputs

Many fields are optional. **Review** shows **Not set** for empty summary fields—submission is still allowed if provider and capability checks pass. Prefer completing high-impact fields (business name/type, audience, positioning) for better planning quality.

---

## 10. Edge cases

| Situation | What happens |
|-----------|----------------|
| **User leaves mid-way** | Draft stores `current_step_key` and `draft_saved` or `in_progress`. Profile data already merged via **Save draft** / **Next** remains in `Profile_Store`. |
| **No AI provider yet** | **Review** blocks **Next** until a provider is **configured**. **Provider setup** step is informational only. |
| **Provider configured after block** | Configure on **AI Providers**, return to onboarding, move from **Review** to **Submission** (you may need **Back** then **Next** if the UI left you on Review with blockers cleared). |
| **Profile changed after earlier runs** | **Submission** may show a warning to submit again if profile merge snapshots indicate a save **after** the last planning run’s modified time (when snapshot repository is available). |
| **Imported site / profile** | Restored or imported **current profile** data appears in prefill like any other stored profile. Onboarding does not auto-start; open the screen to confirm or adjust. |
| **Profile Snapshot History (menu: Profile History)** | Separate from onboarding drafts. Restore (`aio_manage_settings`) replaces the **current** profile via `Profile_Store`; it does not reset onboarding step state by itself. Re-check onboarding if you need a clean stepper. See [concepts-and-glossary.md](../concepts-and-glossary.md) (snapshot vs profile). |
| **goal_or_intent_text** | Present in draft schema and passed into planning when set; **no** field on the current onboarding UI—defaults empty. |
| **Crawler links from onboarding** | **Existing Site** and **Crawl Preferences** open **Crawl Sessions** (`aio-page-builder-crawler-sessions`). |

---

## 11. FAQ and troubleshooting

**Why doesn’t my data show in Review?**  
Review shows a **short summary** (business name, type, audience, positioning). Other fields still exist in the profile but are not all listed on Review.

**Why don’t template preferences stick when I save on another step?**  
They are only persisted when **Page & template preferences** is the **current** step.

**When should I rerun onboarding?**  
When business/brand context materially changes, after **import/restore**, or when submission warnings suggest resubmitting. You do not have to clear the draft to edit fields.

**What if provider setup is incomplete?**  
You cannot advance past **Review** until at least one provider is **configured**. Submission is also blocked inside the orchestrator if readiness fails.

**I clicked Request AI plan and saw an error**  
Open **AI Runs** for the recorded run (id may be in the notice). Failures include validation, provider errors, missing prompt pack, spend cap, or input artifact build issues—messages are user-safe, not raw provider dumps.

**Where did my crawl go?**  
**Crawl Preferences** lists the latest id when the crawl service is available. Full detail is on **Crawl Sessions** / **Crawl Comparison** ([admin-operator-guide.md](../../guides/admin-operator-guide.md) §4).

---

## 12. Cross-links

| Topic | Doc |
|--------|-----|
| Capabilities and roles | [concepts-and-glossary.md](../concepts-and-glossary.md) |
| AI Providers (credentials, test connection, spend cap) | [ai-providers-credentials-budget.md](ai-providers-credentials-budget.md); [admin-operator-guide.md](../../guides/admin-operator-guide.md) §3 |
| Crawl Sessions / Comparison | [admin-operator-guide.md](../../guides/admin-operator-guide.md) §4 |
| Build Plans (after planning) | [admin-operator-guide.md](../../guides/admin-operator-guide.md) §6–§7; [end-user-workflow-guide.md](../../guides/end-user-workflow-guide.md) |
| Profile snapshot history / restore | [advanced-ai-labs.md](advanced-ai-labs.md) (stub); screen **Profile History** / `aio-page-builder-profile-snapshots`; [FILE_MAP.md](../FILE_MAP.md) §4 |
| Industry profile | [industry-admin-workflows.md](../industry/industry-admin-workflows.md) |
| Import / export affecting profile | [admin-operator-guide.md](../../guides/admin-operator-guide.md) §11; [FILE_MAP.md](../FILE_MAP.md) §11 |

---

## 13. Technical references (implementers)

- `Onboarding_Screen`, `Onboarding_Draft_Service`, `Onboarding_Prefill_Service`, `Onboarding_UI_State_Builder`, `Onboarding_Planning_Request_Orchestrator`, `Planning_Request_Result`, `Profile_Store`, `Profile_Schema`.
