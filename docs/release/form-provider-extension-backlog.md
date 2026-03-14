# Form Provider — Extension Backlog

**Governs:** Spec §61 (Known Risks, Open Questions, Deferred Decisions). Post–Prompt 235 next-wave work.  
**Purpose:** Index of follow-on prompts and backlog items for richer provider pickers, additional providers, provider-side form creation, and long-term maintenance. Aligned to original prompt structure and bucket sequencing.  
**Audience:** Internal planning. No commitment to scope or order until scheduled.

---

## 1. Current scope (releaseable)

- **Prompts 226–230, 233–234:** Form section and request-form page template; registry; Build Plan/execution dependency validation; security retrofit; E2E acceptance structure. Single provider (NDR) conforms; storage remains form_provider + form_id.
- **Prompt 235:** This documentation pack, release gate, and extension backlog.
- **Prompt 236:** Picker adapter contract and provider discovery layer (when implemented).

**Canonical storage:** form_provider, form_id (ACF/section field values). No change in backlog unless explicitly specified.

---

## 2. Next-wave prompt index (backlog)

| ID | Bucket | Description | Prerequisite prompts | Notes |
|----|--------|-------------|----------------------|--------|
| **237** | hardening / integrations | **WPForms provider registration.** Register WPForms as a second form provider (provider_id, shortcode_tag, id_attr). Conform to Form_Provider_Registry and picker adapter (236). | 226–230, 233, 236. | Additive; no change to canonical storage. |
| **238** | hardening / integrations | **Contact Form 7 (CF7) provider registration.** Same as 237 for CF7 shortcode/form ID semantics. | 226–230, 233, 236. | Additive. |
| **239** | features / integrations | **Provider form-list API contract.** Define optional provider API for listing available forms (id, label, status). Picker discovery (236) exposes “has form list”; adapters implement list fetch. Fallback: manual form_id entry. | 236. | Non-canonical; picker UX only. |
| **240** | features / integrations | **Provider-aware auto-provisioning.** When creating a page from request-form template, optional flow to create a form in the provider and set form_id (provider-specific; may be no-op for providers without API). | 236, 239 (optional). | Out of scope for initial release. |
| **241** | reporting / diagnostics | **Richer survivability tooling.** Extend diagnostics/support (231) with provider health checks, stale-form detection, relink guidance. | 231, 236. | Bounded; no provider internals. |
| **242** | hardening / QA | **Long-term maintenance and regression.** Automated regression for form provider flows (build/replace, picker, export/restore); add to CI when stable. | 230–234, 236. | Test coverage; no new features. |

**Rule:** Backlog items must name affected buckets and prerequisite prompt ranges. New providers (237, 238) are additive and adapter-driven; no switch-case coupling.

---

## 3. Deferred decisions (Spec §61)

| Decision | Options | Deferred until |
|----------|---------|----------------|
| Second/third provider priority | WPForms, CF7, or other; order of implementation. | Product Owner / roadmap. |
| Form-list API requirement | Optional (fallback manual entry) vs required for new providers. | Prompt 239 or equivalent. |
| Auto-provisioning scope | Per-provider; which providers support create-form API. | Prompt 240 or equivalent. |
| Picker UI surface | Dedicated picker modal vs inline select + text field. | Post-236; UI follow-up. |

---

## 4. Cross-references

- **Release gate:** [form-provider-integration-review-packet.md](form-provider-integration-review-packet.md).
- **Known risks:** [known-risk-register.md](known-risk-register.md) FPR-1.
- **Contract:** [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md).
- **Picker contract (when added):** [form-provider-picker-adapter-contract.md](../contracts/form-provider-picker-adapter-contract.md).

---

*Update this backlog when new prompts are scheduled or when deferred decisions are resolved.*
