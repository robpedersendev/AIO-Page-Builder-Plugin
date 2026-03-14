# Form Provider Integration — Release Review Packet

**Governs:** Spec §59.15, §60.4–60.8, §61. Form provider extension pack closure within the 190+ prompt system.  
**Purpose:** Release gate, evidence traceability, and sign-off readiness for provider-backed form sections and request-form page template.  
**Audience:** Product Owner, Technical Lead, QA, Security. Internal only.

---

## 1. Release gate checklist (form provider)

| # | Gate | Criterion | Evidence |
|---|------|-----------|----------|
| 1 | UI | Form section and request-form template visible in directories and detail; form binding state (provider, form_id, validation) on section detail. | [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md); [request-form-page-lifecycle-verification.md](../qa/request-form-page-lifecycle-verification.md) §1. |
| 2 | Functionality | Build/replace blocked when provider missing; finalization form_dependency; provider refs in execution/closure. | [request-form-page-lifecycle-verification.md](../qa/request-form-page-lifecycle-verification.md) §2–4; Prompts 230, 233. |
| 3 | Security | Provider ID and form ID validated/sanitized; no arbitrary shortcode; seed nonce+capability; validation helpers. | [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md); [form-provider-security-review.md](../qa/form-provider-security-review.md); [Form_Provider_Registry_Security_Test.php](../../plugin/tests/Unit/Form_Provider_Registry_Security_Test.php). |
| 4 | Diagnostics | Provider dependency classified; survivability messaging (per Prompt 231 when complete). | [form-provider-survivability-diagnostics-report.md](../qa/form-provider-survivability-diagnostics-report.md) (231); [form-provider-security-review.md](../qa/form-provider-security-review.md) §2. |
| 5 | Export/restore | Provider refs in export; restore validation (per Prompt 232 when complete). | [form-provider-export-restore-verification.md](../qa/form-provider-export-restore-verification.md) (232); [form-provider-extension-backlog.md](form-provider-extension-backlog.md). |
| 6 | Documentation | Operator guide, support references, changelog addendum, known-risk, extension backlog. | [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md); [form-provider-extension-backlog.md](form-provider-extension-backlog.md); [changelog.md](changelog.md); [known-risk-register.md](known-risk-register.md). |
| 7 | Acceptance | E2E scenario manifest and acceptance report; unit tests for registry and dependency validator. | [form-provider-end-to-end-acceptance-report.md](../qa/form-provider-end-to-end-acceptance-report.md); [SCENARIO_MANIFEST.md](../../tests/e2e/form-provider-integration/SCENARIO_MANIFEST.md); Form_Provider_Registry_Security_Test; Form_Provider_Dependency_Validator_Test. |

**Rule:** All gates must be satisfied or explicitly deferred with rationale. Prompts 231–232 (diagnostics, export/restore) are dependencies for gates 4 and 5; when complete, evidence links must be verified.

---

## 2. Evidence summary and traceability

| Area | Summary | Artifact |
|------|---------|----------|
| Build Plan / execution | Form_Provider_Dependency_Validator blocks build/replace when provider missing; New_Page_Template_Recommendation_Builder dependency_warnings; Template_Finalization_Service form_dependency in closure. | [request-form-page-lifecycle-verification.md](../qa/request-form-page-lifecycle-verification.md); Prompt 230. |
| Security | Registry validates provider_id and form_id; build_shortcode returns null for invalid; seed nonce+capability; is_valid_provider_id, validate_provider_and_form. | [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md); [form-provider-security-review.md](../qa/form-provider-security-review.md). |
| E2E acceptance | Scenario manifest FPE2E-*; acceptance report; unit tests for registry and dependency validator. | [form-provider-end-to-end-acceptance-report.md](../qa/form-provider-end-to-end-acceptance-report.md); [tests/e2e/form-provider-integration/SCENARIO_MANIFEST.md](../../tests/e2e/form-provider-integration/SCENARIO_MANIFEST.md). |
| Known risk | FPR-1 in known-risk-register; mitigation and doc refs. | [known-risk-register.md](known-risk-register.md) §3. |
| Extension backlog | Next-wave prompts (WPForms/CF7, form-list API, auto-provisioning, survivability, maintenance). | [form-provider-extension-backlog.md](form-provider-extension-backlog.md). |

---

## 3. Release readiness statement

**Current implementation (Prompts 226–230, 233–234):** UI (form section, request-form template, form binding state), functionality (build/replace with dependency validation, finalization form_dependency), security (validation, sanitization, nonce/capability), and acceptance structure (scenarios, unit tests) are complete and evidence-backed.

**Dependencies:** Diagnostics/survivability (Prompt 231) and export/restore validation (Prompt 232) are in scope for full closure; when complete, gates 4 and 5 are satisfied and links in this packet must be updated.

**Blockers:** None identified at packet creation. Any open critical/high in hardening register must be closed or waived before release.

**Sign-off readiness:** This extension pack is **release-ready** for the current scope (single provider, registry-driven, security-hardened, lifecycle-integrated) provided: (1) final evidence pass confirms all linked artifacts exist and are correct, (2) Prompts 231–232 are completed or explicitly deferred with waiver, (3) sign-off checklist records approval per §60.8.

---

## 4. Cross-references

- **Contract:** [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md).
- **Operator:** [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md).
- **Support:** [template-library-support-guide.md](../guides/template-library-support-guide.md) §2 (form provider row when 231 complete).
- **Main release packet:** [release-review-packet.md](release-review-packet.md) §2.7 (template library); add form-provider row when this packet is approved.

---

*Update this packet when 231–232 deliver or when evidence links change.*
