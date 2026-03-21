# AI Providers — credentials, connection tests, and spend caps

**Audience:** Site operators who configure AI integrations.  
**Screens:** **AIO Page Builder → AI Providers** (`aio-page-builder-ai-providers`).  
**Related:** [ai-runs-and-run-details.md](ai-runs-and-run-details.md); [onboarding-and-profile.md](onboarding-and-profile.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [admin-operator-guide.md §3](../../guides/admin-operator-guide.md); [FILE_MAP.md](../FILE_MAP.md) §4.

---

## 1. What this screen is for

The **AI Providers** screen lists **built-in provider integrations** (see §2), shows **credential status** (never full keys), shows **default planning model** and **last connection test** summary, and hosts **monthly spend cap** settings per provider. In-product disclosure blocks explain **external data transfer** and **cost** (same ideas as summarized below).

---

## 2. Supported providers (as implemented)

- The plugin’s **registered drivers** today correspond to provider IDs **`openai`** and **`anthropic`** (labels: OpenAI, Anthropic).
- The provider table can also include **additional provider IDs** merged from stored **provider configuration** (`providers[].provider_id`). A row may appear for an ID that has **no driver** in this install: the **Default model** column shows an em dash, and **Test connection** responds with **Provider not found** because only registered drivers can be tested from this screen.
- **Default model (planning)** is resolved from the driver and planning schema support. If no default can be resolved, the UI shows **No default**.

---

## 3. Permissions

| Action | Capability |
|--------|------------|
| Open **AI Providers**, update credentials, run connection tests, save spend caps | `aio_manage_ai_providers` |
| View **AI Runs** (list and detail) | `aio_view_ai_runs` |

Default role mapping is described in [concepts-and-glossary.md](../concepts-and-glossary.md). Users without `aio_manage_ai_providers` cannot change provider settings; users without `aio_view_ai_runs` cannot review run history from **AI Runs**.

---

## 4. Credentials — where they go and what is stored or shown

- **Entry point:** Per row, use the **password** field (placeholder “New key”) and **Update credential**. Submitting sends a **POST** with a **nonce** scoped to that provider; empty input is rejected with **Credential is required.**
- **Storage:** Values are written through the **provider secret store** (local to the site). The UI **does not display raw API keys** after save; only **credential status** labels appear (e.g. Not configured, Configured, Pending validation, Invalid, Rotated — depending on store state).
- **After update:** Provider UI state is updated to **pending validation** for masked status and default model is cleared until revalidated; run a **connection test** or a successful planning flow to confirm.

---

## 5. Connection testing — behavior

- **Action:** **Test connection** (per provider, POST + nonce).
- **What runs:** A **small, bounded** provider request (minimal completion budget, short timeout) with a fixed test prompt — not a full plan. If **no model** can be chosen for the test, the test **fails** with a **no model available** style outcome (no outbound completion call in that case).
- **Outcomes:**
  - **Success:** Redirect back with a **success** admin notice (**Connection test succeeded.**). Last test state is stored (success + timestamp); **last successful use** timestamp is updated on success.
  - **Failure:** Redirect with an **error** notice. The message is the provider-normalized **user message** when available; otherwise a generic failure message. An uncaught exception yields **Connection test failed.**
- **Table column “Last connection test”:** Shows the **last stored** result (checkmark or cross, short message, local formatted time). If no test has been recorded yet, the cell shows an em dash.

---

## 6. Monthly spend caps and warnings

**UI copy on the screen:** Caps help limit **new AI runs** when spend exceeds the configured monthly amount, **unless override is enabled**. Tracking uses **approximate** rates; operators should **verify billing in the provider’s dashboard**.

### 6.1 Configuration

- **Monthly cap (USD):** Per provider. **`0` disables** the cap (no enforcement from this mechanism).
- **Maximum** cap value accepted by settings is **9999.99** (sanity bound).
- **Allow override when cap exceeded:** When checked, **new runs are still allowed** after the cap is reached or exceeded. When unchecked, enforcement applies (see §6.3).
- Settings persist under per-provider options (`aio_pb_spend_cap_{provider_id}`).

### 6.2 Month-to-date total and warnings (AI Providers UI)

- **Month-to-date spend** is read from a **per-provider, per-calendar-month** accumulator option (`aio_pb_monthly_spend_{provider_id}_{YYYY_MM}`), using **UTC month** boundaries in the option key.
- The **AI Providers** spend section shows:
  - **No spend recorded this month** when total is zero and there is no cap.
  - Otherwise **spent / cap** text, or:
  - **Approaching** warning when usage is **≥ 80%** and **&lt; 100%** of cap.
  - **Exceeded** error notice when **spent ≥ cap**; if override is on, copy states that **runs are still allowed**.

### 6.3 When the cap blocks work

- **Preflight enforcement** runs in the **onboarding planning request** path (`Onboarding_Planning_Request_Orchestrator`): if a **non-zero cap** is set, month-to-date is **at or over** the cap, and **override is disabled**, the planning request returns **blocked** with a message directing the operator to enable the override on **AI Providers**.
- **Scope note:** In the current codebase, this preflight is wired to that **onboarding AI planning** orchestrator. Treat other potential AI entry points separately unless a future release documents broader enforcement.

### 6.4 How month-to-date totals increase

- After a **successful** planning response, the orchestrator may call **record run cost** using the **`cost_usd`** field on the **usage** structure returned from the provider path.
- **No increment** when `cost_usd` is **missing**, not numeric, or **≤ 0**. That means **uncosted runs do not move the accumulator**, so the UI total can **lag behind real provider billing**.

---

## 7. Privacy, external transfer, and reporting

- **External transfer:** Disclosure on the screen states that **profile and site context** are sent to the provider when using AI; responses return over the network; **do not put secrets in prompts**; keys stay **local** and are **not shown in full** after save.
- **Cost:** Disclosure states that requests consume tokens and may incur provider charges; the plugin **does not manage** provider billing or quotas.
- **Operational reporting** (install/heartbeat/diagnostics) for private distribution is separate; see [admin-operator-guide.md §10](../../guides/admin-operator-guide.md), [REPORTING_EXCEPTION.md](../../standards/REPORTING_EXCEPTION.md), and **Privacy, Reporting & Settings** in the admin menu.

---

## 8. Step-by-step — normal workflows

### 8.1 Configure a provider (first time)

1. Ensure your WordPress user has **`aio_manage_ai_providers`**.
2. Open **AIO Page Builder → AI Providers**.
3. Find the provider row (e.g. OpenAI or Anthropic).
4. Paste the API key into the **New key** field → **Update credential**.
5. Click **Test connection** and confirm the **success** notice after redirect.

### 8.2 Rotate or update credentials

1. Open **AI Providers**.
2. Enter the **new** key in the row’s password field → **Update credential**.
3. Run **Test connection** again. Expect **Pending validation** until the test or a real planning run succeeds.

### 8.3 Test connectivity

1. Open **AI Providers**.
2. Click **Test connection** for the target provider.
3. Read the top-of-screen notice (success vs error). Optionally confirm the **Last connection test** cell updated.

### 8.4 Set or change a spend cap

1. Open **AI Providers** and scroll to **Monthly Spend Caps**.
2. Enter **Monthly cap (USD)** (`0` to disable) and set **override** per policy.
3. **Save cap settings**. Revisit after runs to compare **month-to-date** to the cap.

### 8.5 Review spend alongside runs

- Open **AI Runs** for the **month-to-date by provider** table and run list — see [ai-runs-and-run-details.md](ai-runs-and-run-details.md).

---

## 9. Edge cases

| Situation | What happens |
|-----------|----------------|
| **Connection test fails** | Error notice; last test stored as failure; no **last successful use** update from that test. |
| **Missing / invalid credential** | Empty submit rejected; provider may return auth errors on test or planning. Status labels may show **Not configured**, **Invalid**, etc. |
| **Provider listed but no driver** | **Test connection** → **Provider not found.** Model column **—**. |
| **No model for connection test** | Test fails without a normal completion call; message indicates **no model available** for the test. |
| **Default model shows “No default”** | Planning may still be constrained elsewhere; resolve provider config / capability resolution before relying on planning. |
| **Cap reached mid-month** | Once **month_total ≥ cap** and override is **off**, **onboarding planning** requests are **blocked** with the spend-cap message. |
| **Override enabled while over cap** | UI shows exceeded notice but copy says **runs are still allowed**; preflight allows the run. |
| **Runs complete with no `cost_usd`** | **Month-to-date** may **not** increase; cap logic may **undercount** vs provider invoices. |
| **Approaching cap (80%)** | Warning notice on **AI Providers**; **AI Runs** summary shows **Approaching cap (N%)**. |

---

## 10. FAQ and troubleshooting

**Why did the connection test fail?**  
Check key validity, network egress, provider outage, model resolution, and account permissions. Read the **redirect notice** text — it may include the normalized provider/user message. Confirm **Default model** is not **—** / **No default** where relevant.

**Why does month-to-date not match my provider bill?**  
Totals use **plugin-side** `cost_usd` when present, **approximate** rates, and **UTC** month buckets. Connection tests and uncosted runs may **not** add to the accumulator.

**Why can’t I open AI Providers?**  
The menu/screen requires **`aio_manage_ai_providers`**.

**Where is reporting disclosed?**  
[admin-operator-guide.md §10](../../guides/admin-operator-guide.md); in-product **Privacy, Reporting & Settings**; [REPORTING_EXCEPTION.md](../../standards/REPORTING_EXCEPTION.md).

---

## 11. Implementation pointers (for maintainers)

- Screen: `AI_Providers_Screen.php`
- UI state: `AI_Providers_UI_State_Builder.php`
- Connection test: `Provider_Connection_Test_Service.php`
- Caps: `Provider_Spend_Cap_Settings.php`, `Provider_Monthly_Spend_Service.php`
- Spend preflight + cost recording: `Onboarding_Planning_Request_Orchestrator.php`
