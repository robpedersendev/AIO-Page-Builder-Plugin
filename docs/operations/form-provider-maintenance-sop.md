# Form Provider Integration — Maintenance and Support SOP

**Spec:** §0.10.7, §0.10.11, §57.9, §58.1, §58.5, §58.9, §60.6, §61.9, §61.10.  
**Purpose:** Disciplined workflow for post-release provider-backed form maintenance: support incidents, provider API/picker changes, regression and migration checks, escalation, and release response. No silent drift; revision-controlled and evidence-backed.

**Audience:** Internal operators, support reviewers, and release owners. Complements [template-ecosystem-maintenance-runbook.md](template-ecosystem-maintenance-runbook.md) and [form-provider-upgrade-and-support-runbook.md](form-provider-upgrade-and-support-runbook.md).

---

## 1. Triage: provider-related support incidents

| Step | Action |
|------|--------|
| 1.1 | **Classify:** Is the report about (a) provider unavailable / not registered, (b) form_id invalid or stale, (c) shortcode not rendering, (d) Build Plan/execution blocked by form dependency, (e) export/restore losing form references, (f) security/permission, or (g) other? |
| 1.2 | **Gather evidence:** Form Provider Health screen (provider availability, section/page counts); support bundle including `form_provider_health_summary` and `form_provider_availability`; Queue & Logs for failures; section/page template detail for the affected form section. No secrets in bundles. |
| 1.3 | **Check known risks:** [known-risk-register.md](../release/known-risk-register.md) FPR-1 and any form-provider entries. Document if the incident matches a known risk or is new. |
| 1.4 | **Decide path:** (i) **Operator fix** (e.g. activate provider plugin, correct form_id) — close with resolution note. (ii) **Bug or regression** — open issue; run regression harness and diagnostics (§2, §3). (iii) **Provider API/picker change** — treat as compatibility/upgrade (§4). (iv) **Security/privacy** — escalate per §6. |

---

## 2. Evaluating provider API or picker changes

| Step | Action |
|------|--------|
| 2.1 | **Identify change:** Provider plugin upgrade, shortcode rename, form-list API change, or picker behavior change. |
| 2.2 | **Impact:** Does it affect (a) Form_Provider_Registry (shortcode_tag, id_attr), (b) picker adapter (get_form_list, is_item_stale), (c) availability/cache, or (d) rendering/shortcode output? |
| 2.3 | **Compatibility risk:** Run [FormProviderIntegrationRegressionHarness](../../plugin/tests/Regression/FormProviderIntegrationRegressionHarness.php) and form-provider E2E/acceptance scenarios. If adapter or registry contract changed, update adapter implementation and/or registry registration; add or adjust fixtures. |
| 2.4 | **Decision log:** If the change requires a product or technical decision (e.g. support a new shortcode format, deprecate an adapter), add an entry to [template-library-decision-log.md](../release/template-library-decision-log.md) per §61.9. Link to revision intake if the change came from post-release feedback. |
| 2.5 | **Changelog and known-risk:** Update [changelog](../release/changelog.md) and [known-risk-register.md](../release/known-risk-register.md) if the change introduces a new limitation or mitigation. |

---

## 3. Running provider regression, migration, and diagnostics

| Check | How | When |
|-------|-----|------|
| **Regression harness** | Run `FormProviderIntegrationRegressionHarness` (fixtures under `plugin/tests/fixtures/form-provider-integration/`). Unit test: `Form_Provider_Integration_Regression_Harness_Test`. All scenarios must pass. | After any change to registries, ACF blueprints, rendering, execution, migration, or diagnostics that could affect form provider flows. |
| **Migration/restore** | Run export/restore and migration tests that include form-bearing section/page templates. Confirm form_provider and form_id persist and validate on restore. | Before release; after schema or export/restore code changes. |
| **Diagnostics** | Form Provider Health screen and support bundle: confirm provider availability, section/page counts, and that no secrets appear. | After provider or availability/cache changes; before release. |
| **Security denial path** | Run permission-denied and nonce-failure paths per [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md). | After security-related code changes. |

---

## 4. Escalation: security and privacy

| Trigger | Action |
|---------|--------|
| Suspected exposure of secrets (API keys, tokens) in logs, support bundle, or UI | Escalate to Product Owner and security reviewer. Do not log or transmit raw secrets. Redact and document per [security-redaction-review](../qa/template-ecosystem-security-redaction-review.md). |
| Permission bypass or capability failure | Escalate to Technical Lead; verify capability checks and nonce on all state-changing form-provider actions. |
| Data subject request (export/erase) involving form references | Form_provider and form_id are content/structure; handle per plugin privacy and export/erase integration. Escalate if unclear. |

---

## 5. When to update known risks, changelog, decision log, backlog

| Event | Update |
|-------|--------|
| New limitation or workaround (e.g. provider X requires plugin version Y) | [known-risk-register.md](../release/known-risk-register.md): add or amend form-provider risk row; document mitigation. |
| Deprecation or behavior change (e.g. adapter contract change) | [template-library-decision-log.md](../release/template-library-decision-log.md): add entry; [changelog](../release/changelog.md): add deprecation or change note per §58.6. |
| New provider added or planned | [form-provider-extension-backlog.md](../release/form-provider-extension-backlog.md): update next-wave index; [additional-form-provider-onboarding-contract.md](../contracts/additional-form-provider-onboarding-contract.md) and [form-provider-onboarding-checklist.md](form-provider-onboarding-checklist.md) used for the add. |
| Support incident that becomes a permanent known issue | Known-risk register + decision log if a governed decision (e.g. “we do not support provider Z in export”). |

---

## 6. Rollback and release response

| Scenario | Expectation |
|----------|-------------|
| **Severe provider regression** (e.g. all form sections broken after provider plugin update) | Follow [template-ecosystem-release-sop.md](template-ecosystem-release-sop.md) and product rollback policy (§58.9). Document in decision log; consider patch or rollback release. No silent “we’ll fix later” without a recorded decision. |
| **Single-provider issue** (e.g. one adapter broken) | Fix adapter or registry registration; run regression and diagnostics. If not release-blocking, ship in next release and note in changelog/known-risk. |
| **Export/restore losing form references** | Treated as critical if it affects data integrity. Fix migration/restore logic; add tests; decision log and changelog. |

---

## 7. Procedural traceability

- **SOP → runbook:** This SOP defines *what* to do; [form-provider-upgrade-and-support-runbook.md](form-provider-upgrade-and-support-runbook.md) gives step-by-step *how* and a worked example.
- **SOP → existing docs:** Escalation (§61.10), decision log (§61.9), changelog (§58.6), known-risk register, and release sign-off (§60.8) are the same structures used for the rest of the template ecosystem; form provider is in scope for all of them.
- **Evidence:** Form Provider Health screen, support bundle `form_provider_health_summary`, regression harness results, and security checklist outcomes are the primary evidence artifacts for provider-related changes.
