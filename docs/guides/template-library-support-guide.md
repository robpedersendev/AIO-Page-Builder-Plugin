# Template Library — Support Guide

**Audience:** Support reviewers and operators performing template-library diagnostics and issue triage.  
**Spec:** §0.10.7, §1.9.4, §54.8, §55.8, §57.9, §59.14, §60.6.  
**Purpose:** Template-library diagnostics, appendices, compliance reports, support bundles, and bounded limitations. Product-accurate; no exposure of secrets or unsafe shortcuts.

---

## 1. Scope

This guide covers **support and diagnostics** specific to the expanded template library:

- What to check when a user reports template directory, preview, composition, or build issues.
- Where to find template-library evidence (appendices, compliance reports, compatibility pass).
- What is included in support bundles for template/registry data.
- Known limitations and safe troubleshooting steps.

It **complements** the general [support-triage-guide.md](support-triage-guide.md) (logs, reporting, redaction, export modes). It does not replace security or redaction rules.

---

## 2. Template-library diagnostics (what to check)

| Symptom | Check | Screen / doc |
|---------|--------|---------------|
| Directory slow or timeout | Pagination and per_page cap; large-library query. Filter by purpose_family/category/status to reduce result set. | Section/Page Templates directory; [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) |
| Compare list full | Compare list is capped at **10** items per type. User must remove items before adding more. | Template Compare screen; Template_Compare_State_Builder::MAX_COMPARE_ITEMS |
| Preview blank or error | GenerateBlocks and ACF required for full preview pipeline. Check required plugins and version. | [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md); Environment_Validator |
| ACF groups missing or wrong on page edit | Registration is conditional: only sections assigned to the page (or chosen template/composition on new page) are registered. If assignments are wrong, reassign or check assignment map. Section-key cache invalidates when assignments, template, or composition definitions change. | Assignment map; [acf-conditional-registration-diagnostics-checklist.md](../qa/acf-conditional-registration-diagnostics-checklist.md); [acf-conditional-registration-support-runbook.md](../operations/acf-conditional-registration-support-runbook.md); [acf-conditional-registration-rollback-playbook.md](../operations/acf-conditional-registration-rollback-playbook.md). |
| Composition validation errors | CTA rules (sequencing, bottom CTA, non-adjacent). Show user validation status and composition rules. | Compositions screen; [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md) (CTA rules) |
| Template not found (detail 404) | Invalid or removed internal_key; or capability. Confirm key from directory list and user capability. | Section/Page Template Detail; Capabilities::MANAGE_SECTION_TEMPLATES / MANAGE_PAGE_TEMPLATES |
| Deprecated template behavior | Deprecated templates are still viewable; replacement is suggested in metadata. No auto-migration. | Detail screen metadata; [template-library-operator-guide.md](template-library-operator-guide.md) §5, §11 |
| Export/restore template mismatch | Appendix is generated from **live** registry at export. Restore validates against manifest and appendix. | [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md); Template_Library_Export_Validator / Restore_Validator |
| Form provider not registered / build blocked | New-page or replace using request-form (or form_embed template) fails with "Form provider X is not registered." | User must activate the provider plugin (e.g. NDR Form Manager) or choose a template without form sections. [form-provider-operator-guide.md](form-provider-operator-guide.md) §4; [form-provider-security-review.md](../qa/form-provider-security-review.md). |
| Form section shortcode not rendering | form_id invalid or empty; or provider deactivated after page was built. | Validate form_id (letters, numbers, hyphens, underscores only); confirm provider plugin active. [form-provider-operator-guide.md](form-provider-operator-guide.md) §4. |
| Styling not applying or missing after deinstall | Plugin CSS stops on deactivation; styling options removed on uninstall. Built page content/structure preserved. | Set expectation: plugin-owned styling is not retained after uninstall unless exported and restored. Theme can target same selectors (`.aio-page`, `.aio-s-*`, `--aio-*`) per [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md). Do not promise exact look; document selector/token continuity only. |
| Styling save fails or validation errors | Invalid token/component key or prohibited value (e.g. url(, expression(, &lt;&gt;). | User must use only allowed tokens and safe values. Refer to [styling-security-checklist.md](../security/styling-security-checklist.md) for support-safe guidance; do not expose internal validation detail. |

**Security:** Diagnostics must remain admin-internal. Do not expose secrets, raw API keys, or unredacted payloads. Template definitions in exports/support bundles are structure and content only; no credentials.

---

## 3. Appendices and generated docs

- **Section Template Inventory Appendix:** Generated from the live section registry (Section_Inventory_Appendix_Generator). Markdown list of section templates (key, name, purpose, category, variants, helper status, deprecation, version). Used for export/restore coherence and support reference. **Not** a persisted store; regenerated at export or on demand.
- **Page Template Inventory Appendix:** Same idea for page templates (Page_Template_Inventory_Appendix_Generator). Generated from live registry.
- **Where they appear:** In full operational or template-only exports, appendix content can be included for validation. Support can refer to these to verify what templates exist and their status without browsing the full UI.
- **After upgrade:** No separate appendix migration. Next export or generation produces appendices from the current registry. See [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md).

---

## 4. Compliance and compatibility reports

| Report | Purpose | Location |
|--------|---------|----------|
| Template library compliance matrix | Rule families (count, category, CTA, semantic, ACF, LPAGERY, preview, export). Pass/fail and evidence for template batches. | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md) |
| Template library compatibility report | Compatibility pass for directory, previews, builds, ACF, GenerateBlocks, LPagery, themes. Checklist and bounded degradations. | [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md) |
| Template library migration coverage | Upgrade paths, appendix regen, version/deprecation continuity, retry-safety. | [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md) |
| Migration coverage matrix | Version keys, upgrade flow, template-library rows. | [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md) |

Use these for **evidence-based** support: e.g. "per our compatibility report, preview requires GenerateBlocks 2.0+; please confirm it is active." Do not claim compatibility for environments not in the matrix or report.

---

## 5. Support bundles and template data

- **Support bundle** (Import / Export → Create export → **Support bundle**): Includes settings (redacted), profile (redacted), **registries** (section/page templates, compositions as defined in export-bundle-structure), plans, token sets; optional logs and reporting_history (redacted). When available, **template_library_support_summary** includes **form_provider_health_summary** (provider availability, section/page counts using forms, built_at). No raw AI artifacts; no secrets.
- **Form Provider Health screen** (AIO Page Builder → Form Provider Health): Internal diagnostics for provider-backed forms—provider availability, section templates (form_embed) count, page templates using form sections count, and links to Section/Page Template directories. Capability: `aio_view_logs`. See [form-provider-health-dashboard-verification.md](../qa/form-provider-health-dashboard-verification.md).
- **Template-only export:** Export mode **Template only** (if implemented) or full backup includes template/registry data for restore or diagnostics. Appendix content may be included for validation.
- **Redaction:** Settings and profile in support bundle are redacted. Template definitions are structure and content (keys, names, field blueprints, composition order); no credentials. Do not request or ship full backups containing secrets.

When triaging template issues, a **Support bundle** is usually sufficient to see registry state, composition definitions, and validation status. For export/restore issues, a **validated package** (ZIP) and its validation summary are more useful than raw DB dumps.

---

## 6. Known limitations (support wording)

- **Compare:** Observational only; max 10 items per type. No "apply to page" from compare.
- **Preview:** Synthetic data only; not live site content. Requires ACF Pro and GenerateBlocks for full pipeline.
- **Compositions:** Governed builder only; section set from registry; CTA rules enforced. No freeform HTML.
- **Deprecated templates:** No automatic replacement. User must select replacement explicitly.
- **Appendix:** Generated from live registry; no stored appendix to "migrate." Regeneration is implicit on next export or on-demand run.
- **Theme/dependencies:** Supported environment per [compatibility-matrix.md](../qa/compatibility-matrix.md). LPagery optional (warning only). Do not promise behavior in unsupported environments.
- **Styling (deactivation/uninstall):** Plugin CSS stops on deactivation; styling options are removed on uninstall. Built page content and structure are preserved. Theme override continuity and export/restore behavior: [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md).

Use this list to set expectations and avoid over-promising.

---

## 7. Cross-references

| Need | Doc |
|------|-----|
| General support triage, logs, redaction | [support-triage-guide.md](support-triage-guide.md) |
| Operating the template library | [template-library-operator-guide.md](template-library-operator-guide.md) |
| Editor template choice and one-pagers | [template-library-editor-guide.md](template-library-editor-guide.md) |
| Export modes and bundle structure | [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md) |
| Compatibility and environment claims | [compatibility-matrix.md](../qa/compatibility-matrix.md) |
| Styling lifecycle, uninstall, portability | [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md) |
| Security and redaction | [security-redaction-review.md](../qa/security-redaction-review.md) |

---

## 8. Safe troubleshooting checklist

1. **Reproduce:** Note WP/PHP, plugin version, and required plugins (ACF Pro 6.2+, GenerateBlocks 2.0+). Check [compatibility-matrix.md](../qa/compatibility-matrix.md).
2. **Capability:** Confirm user has `aio_manage_section_templates` / `aio_manage_page_templates` / `aio_manage_compositions` as needed.
3. **Directory/compare:** If slow or "list full," refer to §2 (pagination, compare cap).
4. **Preview:** If blank or error, confirm ACF and GenerateBlocks active and versions. Refer to [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md).
5. **Composition validation:** Explain CTA rules and point to validation status; no bypass.
6. **Support bundle:** Request Support bundle (not full backup) for registry/composition state. Do not ask for or store secrets.
7. **Document:** Record findings and any doc mismatch in QA closure or internal notes; suggest doc updates if the product has changed.

Do not expose hidden support-only internals beyond what is approved in this guide and the support-triage guide.
