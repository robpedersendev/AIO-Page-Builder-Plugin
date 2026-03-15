# Template Ecosystem Maintenance Runbook

**Spec:** §0.14 Change Approval Process; §0.15 Related Documents; §61.9 Decision Log Structure; §61.10 Escalation Rules; §60.6 Documentation Completion Requirements.  
**Purpose:** Internal operations runbook for maintaining the expanded template ecosystem. Ties procedures to implemented services, screens, and artifacts. No new runtime features; revision-driven, decision-logged, appendix-aware, compliance-gated.

**Audience:** Internal operators and maintainers. Do not expose secrets or unsafe support details beyond internal use.

---

## 1. Adding, Deprecating, and Versioning Template Families

### 1.1 Adding new section or page templates

- **Authority:** Template definitions live in the section/page template registries (CPT). Coverage and compliance are gated by [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md) and [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md).
- **Process:** (1) Add or update definitions via the registry services (`Section_Registry_Service`, `Page_Template_Registry_Service`) or import/seed flows. (2) Run the compliance pass (`Template_Library_Compliance_Service::run()`) and fix any hard-fail violations. (3) Regenerate inventory appendices (§2). (4) Update decision log if the change is a governed decision (§5).
- **Do not:** Add templates that violate CTA rules, category max-share, or count/category minimums without a formal waiver per [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md).

### 1.2 Deprecating a section or page template

- **Services:** `Template_Deprecation_Service` (container: `template_deprecation_service`). Deprecation blocks are built by `get_section_deprecation_block( $reason, $replacement_key )` and `get_page_template_deprecation_block( $reason, $replacement_key )`. Application to the registry is via `Section_Registry_Service` / `Page_Template_Registry_Service` (deprecation flow with reason and replacement).
- **Steps:** (1) Decide replacement key(s) and reason. (2) Apply deprecation block to the definition (status => `deprecated`; deprecation block with reason, replacement refs). (3) Generate a decision-log entry with `Template_Deprecation_Service::build_decision_log_entry()` and append to [template-library-decision-log.md](../release/template-library-decision-log.md). (4) Generate a changelog snippet with `build_changelog_snippet_for_deprecation()` and add to release changelog. (5) Regenerate appendices after registry change (§2).
- **Detail screens:** Deprecated templates remain viewable; replacement suggestions appear in metadata (Section/Page Template Detail). No automatic migration; users must select replacement explicitly.

### 1.3 Versioning

- **Authority:** Version block is part of section/page schema. `Template_Versioning_Service` and version context in definitions. After any version or deprecation change, regenerate appendices so version and deprecation columns in inventory docs stay aligned (see [section-template-inventory.md](../appendices/section-template-inventory.md), [page-template-inventory.md](../appendices/page-template-inventory.md)).

---

## 2. Regenerating and Reviewing Inventory Appendices

### 2.1 What the appendices are

- **Section Template Inventory:** `docs/appendices/section-template-inventory.md`. Generated from the **live** section registry. Content: key, name, purpose, variants, helper, deprecation, version. Do not edit by hand.
- **Page Template Inventory:** `docs/appendices/page-template-inventory.md`. Same idea for page templates. Generated from live page template registry.

**Implementing classes:** `Section_Inventory_Appendix_Generator`, `Page_Template_Inventory_Appendix_Generator`. Registered in `Registries_Provider` as `section_inventory_appendix_generator`, `page_template_inventory_appendix_generator`. Each has `generate(): string` (full markdown) and `build_result()` / `build_result_from_definitions()` for structured data.

### 2.2 When to regenerate

- After any library change (add, deprecate, version, batch import).
- Before a release that includes template changes.
- When export/restore or support needs to reference current inventory; export validators use the generators at export time (no separate “stored” appendix to migrate; see [template-library-migration-coverage-report.md](../qa/template-library-migration-coverage-report.md)).

### 2.3 How to regenerate

- **At export time:** Full export and support bundle flows already use the generators; appendix content is produced from the live registry for validation and coherence.
- **To refresh committed docs/appendices:** Obtain the generators from the container (`section_inventory_appendix_generator`, `page_template_inventory_appendix_generator`), call `generate()` on each, and write the returned markdown to `docs/appendices/section-template-inventory.md` and `docs/appendices/page-template-inventory.md`. No built-in CLI or admin button; use a maintenance script or one-off code that resolves the container and writes the files. Ensure the generator CAP (e.g. 1000 in `Section_Inventory_Appendix_Generator`) covers current library size.
- **Verification:** Run unit tests for `Section_Inventory_Appendix_Generator` and `Page_Template_Inventory_Appendix_Generator` to confirm deterministic output after changes.

---

## 3. Running Compliance, Accessibility, and Animation Reports

### 3.1 Template library compliance pass

- **Service:** `Template_Library_Compliance_Service` (container: `template_library_compliance_service`). Registered in `Registries_Provider`.
- **Run:** Resolve from container and call `run()`. Returns `Template_Library_Compliance_Result` with `to_array()` (machine-readable) and `to_summary_lines()` (human-readable).
- **Authority:** [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md), [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md), [cta-sequencing-and-placement-contract.md](../contracts/cta-sequencing-and-placement-contract.md).
- **Detail:** [template-library-automated-compliance-report.md](../qa/template-library-automated-compliance-report.md). Hard-fail violations must be resolved before accepting a batch or release.

### 3.2 Accessibility audit

- **Service:** `Template_Accessibility_Audit_Service` (container: `template_accessibility_audit_service`). Call `run()`; returns `Template_Accessibility_Audit_Result`.
- **Authority:** [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md). Covers semantic, CTA, and machine-checkable accessibility rules. Does not replace human accessibility review (§56.6).

### 3.3 Animation fallback QA

- **Service:** `Animation_QA_Service` (container: `animation_qa_service`). Call `run()`; returns `Animation_QA_Result`.
- **Authority:** [template-library-animation-fallback-report.md](../qa/template-library-animation-fallback-report.md). Animation tier, reduced-motion, cross-browser fallback.

### 3.4 Using results

- Compliance result drives sign-off: [template-library-expansion-sign-off-checklist.md](../release/template-library-expansion-sign-off-checklist.md). No unwaived hard-fail for release.
- Findings must be recorded in the relevant QA report or hardening matrix; waivers require waiver_id and rationale per [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md).

---

## 4. Escalation Paths (Spec §61.10)

| Issue type | Escalate to | Note |
|------------|-------------|------|
| Implementation issue (bugs, technical debt) | Technical Lead | No silent carry into release. |
| Product / scope issue | Product Owner | Scope and requirement clarity. |
| Security / privacy issue | Product Owner + security reviewer | E.g. template payloads, support bundle content, reporting. |
| Release-blocking risk | Formal milestone review | Per §60.8 sign-off requirements. |

**Rule:** No critical unresolved issue may be silently carried into release. Document blockers in the decision log or known-risk register and resolve or waive with approval.

---

## 5. Decision Log and Changelog Expectations

### 5.1 Decision log (template-library-decision-log.md)

- **Structure:** Per spec §61.9. Each entry: Decision ID, Date, Owner, Status (proposed | approved | superseded | rejected), Summary, Rationale, Alternatives considered, Impacted section keys, Impacted template keys, Effective version.
- **Creating entries:** Use `Template_Deprecation_Service::build_decision_log_entry( $decision_id, $summary, $rationale, $owner, $status, $effective_version, $impacted_section_keys, $impacted_template_keys, $alternatives_considered )` to produce a consistent payload; paste or sync into [template-library-decision-log.md](../release/template-library-decision-log.md).
- **When:** For deprecations, family-cap decisions, category policy changes, and any change that affects “what is in the library” or governed rules.

### 5.2 Changelog (deprecations)

- Use `Template_Deprecation_Service::build_changelog_snippet_for_deprecation( $template_key, $type, $reason, $replacement_keys )` to generate a line for the Deprecations section of the release changelog. Insert into the release changelog per §58.6.

### 5.3 Revision log and spec

- Per §0.14, no implementation shortcut may silently override the approved specification. If a maintenance change implies a spec or contract change, follow the change approval process (classify change, get approval, update spec and revision log).

---

## 6. Support Triage for Template-Specific Issues

- **Primary doc:** [template-library-support-guide.md](../guides/template-library-support-guide.md). Use it for: directory slowness, compare list full, preview blank/error, composition validation errors, template not found, deprecated template behavior, export/restore template mismatch.
- **Diagnostics table:** Support guide §2 maps symptom → check → screen/doc. Use [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md), [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md), [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md), capabilities, and Environment_Validator as needed.
- **Support bundle:** Request Support bundle (Import/Export → Create export → Support bundle) for registry and composition state. Template_Library_Support_Summary_Builder adds `template_library_support_summary` (redacted; counts, health, validation_failures redacted). Do not request or ship full backups with secrets.
- **Safe troubleshooting checklist:** Support guide §8. Follow reproduce → capability → directory/compare → preview → composition → support bundle → document. Do not expose hidden internals beyond approved guides.

---

## 7. Procedural Consistency (Mapping to Implemented Artifacts)

| Maintenance step | Implemented artifact |
|------------------|----------------------|
| Add/deprecate/version templates | Section_Registry_Service, Page_Template_Registry_Service, Template_Deprecation_Service |
| Build deprecation block | Template_Deprecation_Service::get_section_deprecation_block, get_page_template_deprecation_block |
| Decision log entry | Template_Deprecation_Service::build_decision_log_entry; docs/release/template-library-decision-log.md |
| Changelog snippet (deprecation) | Template_Deprecation_Service::build_changelog_snippet_for_deprecation |
| Regenerate section appendix | Section_Inventory_Appendix_Generator::generate(); docs/appendices/section-template-inventory.md |
| Regenerate page appendix | Page_Template_Inventory_Appendix_Generator::generate(); docs/appendices/page-template-inventory.md |
| Run compliance | Template_Library_Compliance_Service::run(); template-library-compliance-matrix.md, template-library-automated-compliance-report.md |
| Run accessibility audit | Template_Accessibility_Audit_Service::run(); template-library-accessibility-audit-report.md |
| Run animation QA | Animation_QA_Service::run(); template-library-animation-fallback-report.md |
| Support triage | template-library-support-guide.md; Support_Package_Generator, Template_Library_Support_Summary_Builder |
| Escalation | Spec §61.10 (implementation → Technical Lead; product → PO; security/privacy → PO + security; release-blocking → milestone review) |
| Post-release revision intake | template-ecosystem-revision-intake-template.md; template-ecosystem-post-release-review-cadence.md; decision log + revision history (§0.13) |
| Post-release review cadence | post-release-health-review-template.md; template-ecosystem-post-release-review-cadence.md; support guide, analytics, compatibility |

No step in this runbook references a screen, service, or doc that does not exist in the codebase or docs. If a new tool is added, update this runbook and the procedural consistency table.

---

## 8. Post-release review and revision intake

Post-release findings (support pain, analytics, compatibility issues, recommendation quality) must become **governed revisions**, not ad hoc changes. Spec §0.14, §61.9, §61.10; §59.15 Production Readiness Phase.

### 8.1 Revision intake

- **Template:** [template-ecosystem-revision-intake-template.md](template-ecosystem-revision-intake-template.md). Use it for every proposed change that affects template families, registry rules, compliance, or documented template behavior.
- **Sources:** Analytics (Build Plan Analytics, Template Analytics, exported summaries); support findings (support guide §2, support bundle, template_library_support_summary); QA reports (compliance, accessibility, compatibility); recommendation feedback.
- **Evidence:** Each intake must reference at least one concrete evidence (support ref, analytics date range, QA report path, compatibility run). For template-family changes, evidence requirements in the intake template §7 apply (compliance run, coverage alignment, appendix regen, decision log entry).

### 8.2 Escalation categories

| Category | Escalate to | Do not |
|----------|-------------|--------|
| Security / privacy | Product Owner + security reviewer | Mix into generic backlog. |
| Compatibility | Technical Lead (PO if scope) | Ignore theme/plugin/env evidence. |
| UX | Product Owner | Bypass intake for “quick” UX tweaks. |
| Release-blocking | Formal milestone review | Carry silently into release. |
| Other | Technical Lead or PO | Skip decision log when approved. |

### 8.3 Flow: finding → intake → decision log / revision history

1. Capture finding with evidence (support, analytics, QA, compatibility).
2. Triage by escalation category; security/privacy escalated explicitly.
3. Fill revision intake template; propose change and impacted keys.
4. Approval per §0.14 (change type determines authority).
5. When approved: add decision log entry to [template-library-decision-log.md](../release/template-library-decision-log.md); link Decision ID to revision intake ID.
6. If spec or contract changes: update revision history per §0.13.
7. Implement per runbook §1–§3 (add/deprecate/version, appendices, compliance); no shortcut may silently override the approved specification.

**Cadence:** [template-ecosystem-post-release-review-cadence.md](template-ecosystem-post-release-review-cadence.md) defines review timing, what to collect, and a full procedural traceability example (support finding → intake → decision log and revision history).

---

## 6. Form provider maintenance (Prompt 241)

Provider-backed form sections and request-form page templates are maintained through a dedicated SOP and runbook so that provider API changes, picker drift, stale bindings, and support incidents are handled in a disciplined way.

- **SOP:** [form-provider-maintenance-sop.md](form-provider-maintenance-sop.md) — Triage, provider API/picker evaluation, regression and diagnostics checks, escalation (security/privacy), when to update known-risk register/changelog/decision log, and rollback/release response.
- **Runbook:** [form-provider-upgrade-and-support-runbook.md](form-provider-upgrade-and-support-runbook.md) — Step-by-step procedures for support incidents, provider plugin upgrades, and regression verification; includes a worked example (provider API regression → decision log → release).
- **Evidence:** Form Provider Health screen, support bundle `form_provider_health_summary`, [FormProviderIntegrationRegressionHarness](../../plugin/tests/Regression/FormProviderIntegrationRegressionHarness.php), and [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md). Provider-related decisions may be recorded in [template-library-decision-log.md](../release/template-library-decision-log.md) with a Decision ID prefix (e.g. DL-FP-001).
- **Adding new providers:** [additional-form-provider-onboarding-contract.md](../contracts/additional-form-provider-onboarding-contract.md) and [form-provider-onboarding-checklist.md](form-provider-onboarding-checklist.md). No ad hoc provider integration.
