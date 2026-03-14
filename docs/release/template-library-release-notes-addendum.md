# Template Library Expansion — Release Notes Addendum

**Spec:** §58.6 Release Notes Standards; §58.7 Breaking Change Policy; §58.8 Deprecation Policy; §59.15, §60.6, §60.8.  
**Purpose:** Release-notes addendum for the expanded template-library initiative. Summarizes counts, screens, preview/compare, compositions, CTA enforcement, compatibility, migration, and limitations so the expansion can be shipped responsibly. Complements [release-notes-rc1.md](release-notes-rc1.md); does not replace it.

**Audience:** Operators, administrators, and support. Truthful and operationally useful; no marketing softening of limitations.

---

## 1. What the expansion is

The expanded template library is a **structural and operational** change: a large, governed registry of section and page templates with dedicated admin surfaces, preview and compare workflows, composition builder, CTA-rule enforcement, versioning/deprecation, and export/restore alignment. It is not only “more templates added”; it introduces new screens, caps, validation, and documentation that operators and support must understand.

---

## 2. Counts and families

| Metric | Count | Authority |
|--------|--------|-----------|
| **Section templates** | **254** | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §1; [template-library-inventory-manifest.md](../contracts/template-library-inventory-manifest.md). |
| **Page templates** | **580** | Same. |

**Category coverage:** Section purpose families and CTA classification, and page template_category_class and template_family, meet the coverage matrix minimums. Max share per section family ≤ 25%; section schema category ≤ 28%. Evidence: [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md), [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md).

---

## 3. New screens and surfaces

| Screen | Slug | Purpose |
|--------|------|---------|
| **Page Templates** | `aio-page-builder-page-templates` | Hierarchical browse by category and family; list with View, Add/Remove compare; pagination and filters. |
| **Section Templates** | `aio-page-builder-section-templates` | Hierarchical browse by purpose family and CTA/variant; list with View, Add/Remove compare, helper link; pagination and filters. |
| **Section Template Detail** | `aio-page-builder-section-template-detail` | Single section: metadata, field summary, helper doc, version/deprecation, **rendered preview** (synthetic data). No menu entry; reached via View from directory. |
| **Page Template Detail** | `aio-page-builder-page-template-detail` | Single page template: metadata, composition/section summary, version/deprecation, **rendered preview**. No menu entry; reached via View from directory. |
| **Template Compare** | `aio-page-builder-template-compare` | Side-by-side comparison of section or page templates. **Observational only**; compare list stored in user meta; **max 10 items** per type. Add/remove from directory or detail. |
| **Compositions** | `aio-page-builder-compositions` | List of governed compositions; **Build composition** opens category- and CTA-aware builder. Section set from registry only; validation reflects CTA rules. |

**Analytics and support:** Build Plan Analytics and Template Analytics screens provide aggregate views. Support triage and diagnostics remain as documented in [support-triage-guide.md](../guides/support-triage-guide.md) and [template-library-support-guide.md](../guides/template-library-support-guide.md). In-product help references (Operator Guide) appear on directory, compare, and compositions screens; link is clickable when `aio_page_builder_docs_base_url` filter is set.

---

## 4. Preview and rendering behavior

- **Detail previews:** Use **synthetic** (dummy) data and the **real** section/page renderer. No live site content; no secrets. Preview reflects the same pipeline as built pages (GenerateBlocks when available, native block assembly otherwise). Optional reduced-motion support.
- **Compare:** Compact preview excerpts in the compare workspace; same preview pipeline.
- **Preview cache:** Capped (e.g. 800 entries) for performance; see [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md).
- **Authority:** [template-preview-and-dummy-data-contract.md](../contracts/template-preview-and-dummy-data-contract.md); Preview_Cache_Service.

---

## 5. CTA-rule enforcement

- **Section CTA classification:** Sections are classified (e.g. CTA vs non-CTA). Used for composition and page validation.
- **Page and composition rules:** Minimum CTA sections per page class; **bottom-of-page CTA** (last section in ordered list must be CTA-classified); **non-adjacent CTAs** (no two CTA sections back-to-back); non-CTA section count in range (e.g. 8–14). Enforced by validators and composition builder; validation status shown on compositions.
- **No bypass:** Operators and editors cannot disable CTA rules; they are part of the product contract. See [cta-sequencing-and-placement-contract.md](../contracts/cta-sequencing-and-placement-contract.md) and [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md) §3.

---

## 6. Compositions and one-pagers

- **Compositions:** Governed custom page structures built from **registered section templates only**. No freeform HTML or arbitrary blocks. Builder is category- and CTA-aware; validation reflects CTA and composition schema rules.
- **One-pager:** A page layout built from an ordered list of sections (a composition or a page template that references one). One-pager metadata is exposed in planner-facing payloads and inventory appendices.
- **List cap:** Compositions list view is capped (e.g. 100) for performance.

---

## 7. Versioning, deprecation, and decision log

- **Version:** Section and page templates carry version metadata (e.g. version, stable_key_retained, changelog_ref, breaking). Shown in directory and detail. Template_Versioning_Service; continuity across upgrade per [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md).
- **Deprecation:** Templates can be marked deprecated with reason and replacement key(s). Still viewable and usable; for **new** content, replacement is recommended. No automatic migration of existing plans or compositions. Template_Deprecation_Service; decision log and changelog snippets per [template-library-decision-log.md](template-library-decision-log.md) and changelog deprecation sync.
- **Registry authority:** Definitions are registry/CPT-backed; version and deprecation are stored in the definition. No edit-in-place from detail screen; governed flows only.

---

## 8. ACF and LPagery behavior (expansion-relevant)

- **ACF:** Required (ACF Pro 6.2+). Field groups and assignment are derived from template/composition section set; assignment is bounded per page, not all 250+ groups on every load. Preview uses synthetic field data. ACF diagnostics and blueprint discipline per [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md) ACF family.
- **LPagery:** Optional. Token workflows when present; **warning only** when absent. Core template and planning remain usable without LPagery. LPagery compatibility summaries (supported/unsupported mappings) per Library_LPagery_Compatibility_Service; unsupported combinations produce clear reason, not silent acceptance. See [compatibility-matrix.md](../qa/compatibility-matrix.md) and [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md).

---

## 9. Compatibility and migration notes (expansion)

- **Compatibility:** Supported environment unchanged: WP 6.6+, PHP 8.1+, ACF Pro 6.2+, GenerateBlocks 2.0+, preferred GeneratePress. Expanded library compatibility pass (directory, previews, builds, ACF at scale, GenerateBlocks/native, LPagery, themes) is documented in [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md). Run the checklist and record results; do not claim a template family compatible without testing representative previews and builds.
- **Migration / upgrade:** Template-library upgrade compatibility is handled by Template_Library_Upgrade_Helper (registry_schema in version_markers). Idempotent and retry-safe. Section/page definitions, version/deprecation metadata, compare lists (user meta), and compositions survive upgrade; appendices are **generated** from the live registry (no stored appendix to migrate). See [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md) and [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md).
- **Export/restore:** Template library included in full and template-only exports. Appendix coherence validated by Template_Library_Export_Validator and Template_Library_Restore_Validator. Same-major import and schema rules apply.

---

## 10. Known limitations (expansion-specific)

- **Compare:** Observational only; max 10 items per type. No “apply to page” or “use in plan” from compare screen. Selection for execution is via Build Plans.
- **Detail:** No edit-in-place of template definition from detail screen.
- **Compositions:** Governed builder only; section set from registry; CTA rules enforced. List view capped (e.g. 100).
- **Preview:** Synthetic data only; not live site content. Full preview pipeline requires ACF and GenerateBlocks.
- **Deprecated templates:** No automatic replacement; user must select replacement explicitly in plans or compositions.
- **Appendix:** Generated from live registry at export or on demand; no persisted appendix store. Regeneration is implicit after upgrade.
- **Large library on constrained hosting:** Directory, compare, composition list, and appendix generation may be slower on constrained hosting. Pagination (e.g. 50 per page), compare cap (10), compositions list cap (100), and preview cache cap (800) mitigate; see [known-risk-register.md](known-risk-register.md) and [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md).

---

## 11. Documentation and sign-off

| Need | Document |
|------|----------|
| Operator guidance (directories, compare, compositions, detail) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) |
| Editor guidance (choosing templates, one-pagers, helper docs) | [template-library-editor-guide.md](../guides/template-library-editor-guide.md) |
| Support (diagnostics, appendices, compliance, support bundles) | [template-library-support-guide.md](../guides/template-library-support-guide.md) |
| Expansion evidence and sign-off | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md), [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) |
| Compliance and QA | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md), [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md), [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md) |
| Performance hardening | [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) |

---

## 12. Breaking changes and deprecations (expansion)

- **Breaking changes:** None introduced by the expansion. Registry schema and export schema remain compatible; Template_Library_Upgrade_Helper only ensures registry_schema is set in version_markers when missing or "0". Per §58.7.
- **Deprecations:** Individual section or page templates may be marked deprecated with reason and replacement. Such deprecations are communicated in template metadata and, when recorded, in [template-library-decision-log.md](template-library-decision-log.md) and changelog deprecation lines. No product-wide deprecation of a screen or API in this addendum. Per §58.8.

---

*This addendum is part of the product release documentation. Update when expansion scope or evidence changes. Do not expose secrets or internal-only risk detail.*
