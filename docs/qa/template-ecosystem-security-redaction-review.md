# Template Ecosystem Security, Privacy, and Redaction Review

**Spec**: §4.17 Security and Permissions Domain; §0.10.9 Security Is Mandatory; §59.14 Hardening and QA Phase; §60.8 Sign-Off Requirements. **Prompt 217.**

**Purpose:** Focused security, privacy, and redaction pass for the expanded template ecosystem. Covers preview payloads, synthetic data, exported artifacts, support bundles, reporting enrichments, detail/compare screens, and compliance reports. Complements [security-redaction-review.md](security-redaction-review.md); findings remain internal.

**Scope:** Template preview handling, synthetic preview data safety, support/export/report redaction, template-related route/action permission checks, compare/detail screen exposure, appendix generation, capability enforcement. No unrelated core security redesign.

---

## 1. Synthetic preview data safety

| Check | Finding | Evidence / fix |
|-------|---------|----------------|
| **Accidental realism** | Synthetic_Preview_Data_Generator uses placeholder copy only: PLACEHOLDER_URL `#`, FALLBACK_HEADLINE/BODY, family-specific stubs (e.g. "Welcome to Our Service", "Legal disclaimer placeholder. Terms and conditions text."). No real dates, credentials, or misleading legal text. | Docblock and legal_fields() comment (template-preview §2.4, §8); code audit. |
| **Secret-like content** | Generator does not emit keys or values that match Reporting_Payload_Schema::PROHIBITED_KEYS or common secret patterns. All URLs are `#`; no api_key, password, token (auth), or bearer in output. | Unit test: synthetic output contains no secret-like content (Prompt 217). |
| **Preview cache** | Preview_Cache_Record stores rendered HTML from renderer fed only synthetic data; cache key from context + version_hash. No production content or user data in cache. | template-preview-and-dummy-data-contract; Preview_Cache_Service uses generator output only. |

**Verdict:** No change required. Synthetic data is safe for preview; tests added to lock in no secret-like content.

---

## 2. Compare and detail screen exposure

| Surface | Check | Finding |
|---------|--------|---------|
| **Template Compare** | Capability, nonce, metadata exposure | get_capability() = MANAGE_PAGE_TEMPLATES; current_user_can() before render and in maybe_handle_add_remove(); wp_verify_nonce on add/remove; user_id > 0. Compare state exposes template_key, name, purpose_family, cta_direction, used_sections, compatibility_notes, helper_ref, preview_excerpt (from cached synthetic HTML), detail_url. No registry fields that could hold secrets; token_affinity_notes in schema are design-token metadata, not auth. |
| **Section template detail** | Capability, preview source | get_capability() = MANAGE_SECTION_TEMPLATES; current_user_can() before render. Preview from Synthetic_Preview_Data_Generator + renderer; no user content. |
| **Page template detail** | Capability, preview and one-pager | get_capability() = MANAGE_PAGE_TEMPLATES; current_user_can() before render. Preview synthetic; one-pager ref is doc reference only. |
| **Section/Page directories** | Capability | MANAGE_SECTION_TEMPLATES / MANAGE_PAGE_TEMPLATES; current_user_can() before render. |
| **Compositions** | Capability | MANAGE_COMPOSITIONS; current_user_can() before render. |

**Verdict:** No overexposed metadata identified. All template admin screens are capability-gated; Compare add/remove is nonce-protected. No fix required.

---

## 3. Support package and export redaction

| Surface | Check | Finding |
|---------|--------|---------|
| **Template_Library_Support_Summary_Builder** | Redaction of messages and errors | Uses Reporting_Redaction_Service (redact_message, redact_export_errors); cta_violations and export_viability errors redacted. Build output: health (counts, passed, sliced lists), validation_failures (redacted message), preview_issues, inventory, appendix_sync, version_summary. No raw registry dumps; template keys and counts only. | Unit test: build() output has no prohibited keys (Prompt 217). |
| **Support_Package_Generator** | Categories and redaction | REDACT_KEYS (api_key, password, secret, token, credential, auth); EXCLUDED includes raw_ai_artifacts, normalized_ai_outputs, crawl_snapshots, rollback_snapshots. Template summary included only when template_library_support_summary_builder available; summary is support-safe per above. |
| **Export (full/template)** | Registry and manifest | Registry_Export_Serializer and Export_Bundle_Schema; Registry_Export_Fragment_Builder has PROHIBITED_KEYS and strips before export. Template registries export structure only; no provider secrets or auth tokens in export categories. |
| **Appendix generators** | Content source | Section_Inventory_Appendix_Generator and Page_Template_Inventory_Appendix_Generator produce markdown from registry metadata (key, name, purpose, etc.). Schema fields (e.g. token_affinity_notes) are design-token notes; not auth. No user content or secrets in appendices. |

**Verdict:** Support and export paths apply redaction and prohibited-key stripping. No fix required; test added for support summary prohibited-keys check.

---

## 4. Reporting enrichments (template_library_report_summary)

| Check | Finding |
|-------|---------|
| **Payload shape** | Template_Library_Report_Summary_Builder builds only: section_template_count, page_template_count, composition_count, library_version_marker, plugin_version_marker, appendices_available, compliance_summary. No free text from user or system beyond version strings and enum (ok/warning/critical/unknown). |
| **Prohibited keys** | Existing unit test (Template_Library_Report_Summary_Builder_Test) asserts has_no_prohibited_keys on build() output. Reporting_Payload_Schema::PROHIBITED_KEYS enforced. |
| **Transport** | Install, heartbeat, and error reports use same transport and redaction rules as baseline; template_library_report_summary is additive only. |

**Verdict:** No leakage risk. No fix required.

---

## 5. Permission checks on template-related routes and actions

| Route / action | Capability | Nonce | Evidence |
|-----------------|------------|--------|----------|
| Section Templates directory | MANAGE_SECTION_TEMPLATES | N/A (read-only list) | Section_Templates_Directory_Screen::render() |
| Page Templates directory | MANAGE_PAGE_TEMPLATES | N/A | Page_Templates_Directory_Screen::render() |
| Section template detail | MANAGE_SECTION_TEMPLATES | N/A | Section_Template_Detail_Screen::render() |
| Page template detail | MANAGE_PAGE_TEMPLATES | N/A | Page_Template_Detail_Screen::render() |
| Template Compare (view) | MANAGE_PAGE_TEMPLATES | N/A | Template_Compare_Screen::render() |
| Template Compare add/remove | MANAGE_PAGE_TEMPLATES | NONCE_ACTION, _wpnonce | maybe_handle_add_remove() |
| Compositions | MANAGE_COMPOSITIONS | N/A (or per action) | Compositions_Screen::render() |

**Verdict:** All template admin screens and state-changing Compare actions are capability-gated; Compare add/remove has nonce. No capability drift found. No fix required.

---

## 6. Appendix generation safety

| Check | Finding |
|-------|---------|
| **Input** | Appendix generators read from section/page template repositories (definitions). No request parameters or user input control content beyond which templates exist. |
| **Output** | Markdown files (section-template-inventory, page-template-inventory); key, name, purpose, variants, helper, deprecation, version. No export of raw ACF field values or user content. |
| **Path** | Generated under plugin-controlled paths; no arbitrary path injection. |

**Verdict:** Safe. No fix required.

---

## 7. Template-specific capability and denial tests

| Test | Purpose |
|------|---------|
| **Permission denial** | E2E scenarios E2E-DIR-05, E2E-CMP-04, E2E-COM-04, E2E-CAP-01/02/03 in [template-ecosystem SCENARIO_MANIFEST](../../tests/e2e/template-ecosystem/SCENARIO_MANIFEST.md) require access denied when user lacks MANAGE_SECTION_TEMPLATES, MANAGE_PAGE_TEMPLATES, or MANAGE_COMPOSITIONS. Execute and record in [template-ecosystem-end-to-end-acceptance-report.md](template-ecosystem-end-to-end-acceptance-report.md). |
| **Unit** | Synthetic preview: test added that generated output contains no secret-like content. Support summary: test added that build() output has no prohibited keys. |

**Verdict:** Tests added per §18; E2E denial scenarios already in manifest.

---

## 8. Issue register and fixes

| id | Category | Severity | Title | Affected surface | Status | Fix / evidence |
|----|----------|----------|-------|------------------|--------|----------------|
| *(none)* | — | — | — | — | — | No high-severity template-ecosystem security or redaction issues found. |

No material high-severity findings. No code changes required beyond adding tests. No waiver and no known-risk-register entry required for this pass.

---

## 9. Residual caveats

- **Export/Import capability:** As in baseline [security-redaction-review.md](security-redaction-review.md), Export_Generator and Restore_Pipeline do not check capability; callers (e.g. uninstall UI or future admin export/import UI) must enforce EXPORT_DATA / IMPORT_DATA. Template export is a mode of the same pipeline; no additional template-specific capability.
- **Registry definition fields:** Section_Schema and Page_Template_Schema include `token_affinity_notes` / `default_token_affinity_notes`. These denote design-token affinity for templates, not authentication tokens. No change; documented here to avoid confusion in future audits.
- **Preview excerpt in compare:** Compare state may include preview_excerpt (trimmed words from cached preview HTML). That HTML is rendered from synthetic data only; no user or production content.

---

## 10. Alignment with hardening matrix

- **Security gate (gate 1):** Template admin screens and Compare add/remove have capability and (where state-changing) nonce. No REST/AJAX in template screens; no secrets in template payloads.
- **Redaction gate (gate 6):** Support summary and report summary use redaction or bounded payloads; export uses prohibited-key stripping. Template preview data is synthetic only.
- **Evidence:** This review plus new unit tests (synthetic no-secret-like content, support summary no prohibited keys) and E2E scenario execution (permission denial) satisfy Prompt 217 acceptance criteria.

---

## 11. Sign-off

This document completes the dedicated security, privacy, and redaction review for the expanded template ecosystem. All material surfaces (preview, compare, detail, support, export, report) were audited. No high-severity issues found; tests added to preserve synthetic data safety and support/report redaction. Findings are internal; release readiness for template expansion from a security/redaction perspective is supported by this review and the baseline [security-redaction-review.md](security-redaction-review.md).
