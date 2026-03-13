# Template Library Scale Extension Contract

**Document type:** Authoritative extension contract for large-scale template library targets and governance (Prompt 132).  
**Governs:** Minimum library counts, variation philosophy, category coverage, scale-governance rules, and enhancement boundaries.  
**Spec refs:** §1.9.1 Template Registry Pillar, §1.9.2 Field and Content Architecture Pillar, §1.9.3 Rendering and Portability Pillar; §12 Section Template Registry; §13 Page Template Registry; §14 Custom Page Template Composition; §16 Page Template One-Pager System; §59.4 Registry and Content Model Phase; §60.4 Exit Criteria.

**Enhancement policy:** This contract **enhances** and does **not replace** the architecture and contracts established in Prompts 021–032, 033–040, 041–048, 070–076, 109–111, and 122–123. All prior registry, ACF, rendering, portability, documentation, validation, and export/restore rules remain in force.

---

## 1. Purpose and scope

The plugin’s template-registry architecture is extended to support a **governed large-scale template library** with defined minimum counts and quality rules. This contract:

- Sets **minimum scale targets** for section and page templates.
- Defines **variation philosophy** (valid variation vs duplicate or thin clone).
- Defines **template-family logic** and **allowed variation axes**.
- Sets **category-coverage expectations** across page families.
- States that **future template-expansion prompts enhance prior prompts** rather than replace them.
- Defines **scale-governance rules** for documentation, previews, ACF, rendering, and QA.
- Defines **failure conditions** when counts are achieved but coverage or quality rules are not.
- Provides a **coverage checklist** for later verification of counts, category spread, variation quality, and downstream requirements.

**Out of scope for this contract:** Actual creation of new section or page templates, UI implementation, rendering or ACF code changes, or mass data seeding. This document formalizes targets and rules only.

---

## 2. Minimum scale targets

| Asset type           | Minimum count | Unit of count |
|----------------------|---------------|----------------|
| Section templates    | 250           | Distinct, complete section template definitions (per section-registry-schema). |
| Page templates      | 500           | Distinct, complete page template definitions (per page-template-registry-schema). |

- **Section templates:** Each must satisfy the full required field set and validation rules in **section-registry-schema.md** and spec §12. Incomplete or deprecated-only entries do not count toward the minimum unless they remain valid for use in compositions and builds per registry policy.
- **Page templates:** Each must satisfy the full required field set and validation rules in **page-template-registry-schema.md** and spec §13. Each must reference only registered section templates and comply with ordered section composition rules (§13.4, §14). Incomplete or deprecated-only entries do not count toward the minimum unless policy allows.

**Scale does not relax schema.** Reaching 250 sections or 500 pages with definitions that omit required fields, skip validation, or bypass compatibility rules does **not** satisfy this contract.

---

## 3. Variation philosophy

### 3.1 Valid variation

A **valid variation** is a distinct template that:

- Serves a **different purpose or use case** (documented in `purpose_summary` and, where applicable, archetype/category).
- Differs in **structure, section mix, or ordering** in a way that materially affects page outcome or planning (for page templates), or in **structure, fields, or variant set** in a way that materially affects section behavior (for section templates).
- Has **distinct identity** in the registry (unique internal key, non-duplicate definition).
- Meets **all required fields and validation** for its type (section or page).
- Contributes to **category or family coverage** as defined in §5.

### 3.2 What does not count as valid variation

- **Duplicate:** Same purpose, same section order (page) or same structure/fields (section), with only cosmetic label or copy changes.
- **Thin clone:** Minimal change (e.g. one optional section swapped, or one variant label changed) such that the template does not represent a meaningfully different page type or section type.
- **Placeholder or stub:** Template created only to inflate count, with generic or empty purpose, no real differentiation from existing templates.
- **Unvalidated or incomplete:** Fails required-field or validation rules in the applicable schema.

**Governance rule:** The library must be **curated**. Quantity must not be achieved by mass duplication or thin clones. Variation is **curated**, not random.

---

## 4. Template-family logic and allowed variation axes

### 4.1 Page template families

Page templates are grouped by **family** for coverage and variation:

| Family            | Description | Variation axes (examples) |
|-------------------|-------------|----------------------------|
| **Top-level**     | Home, main landing, primary entry pages. | Purpose, section mix, opening/closing expectations. |
| **Hub**           | Category or topic hub pages. | Depth, section order, CTA placement, listing density. |
| **Nested hub**    | Sub-hub or sub-category pages. | Hierarchy role, section subset, linking strategy. |
| **Child / detail**| Service, offer, location, event, profile, FAQ, etc. | Archetype, section count, one-pager assembly, required sections. |

**Allowed variation axes** for page templates include (non-exhaustive):

- Archetype and purpose (e.g. service vs offer vs location).
- Ordered section list and required vs optional section designations.
- One-pager rules and page-purpose summary.
- Hierarchy hints and endpoint/usage notes.
- Compatibility and default structural assumptions.

**Not allowed:** Arbitrary or untracked duplication; templates that differ only by internal key or non-user-facing metadata with no impact on structure or behavior.

### 4.2 Section template variation axes

**Allowed variation axes** for section templates include (non-exhaustive):

- Category (e.g. hero_intro, cta_conversion, faq).
- Purpose summary and structural/field blueprint refs.
- Variant set (default variant and additional variants).
- Compatibility, render mode, asset declaration.
- Helper and CSS contract refs.

**Not allowed:** Sections that are identical in structure, fields, and behavior with only key or name changed; sections that omit required fields to speed creation.

### 4.3 Composition and one-pager

User-created **compositions** (spec §14) and **one-pager** generation (spec §16) continue to use only **registered** section and page templates. Scale does not introduce unregistered or ad-hoc templates into compositions. All composition and one-pager rules from spec §14 and §16 and from the existing registry contracts remain in force.

---

## 5. Category-coverage expectations

### 5.1 Section template categories

The section registry schema defines allowed category slugs (e.g. hero_intro, trust_proof, feature_benefit, cta_conversion, faq, etc.). The **large library** must achieve:

- **Spread across categories:** No single category shall dominate. A minimum spread (e.g. no category &gt; 25% of section count unless justified by product scope) should be defined at library planning time.
- **Coverage of high-value categories:** Hero/intro, CTA/conversion, trust/proof, feature/benefit, FAQ, and form/embed (where applicable) must have sufficient section options to support diverse page templates without over-reuse of the same section in every template.

### 5.2 Page template archetypes and families

The page template registry schema defines allowed archetypes (e.g. service_page, hub_page, landing_page, etc.). The **large library** must achieve:

- **Top-level / hub / nested hub / child balance:** Page templates must cover top-level, hub, nested hub, and child (detail) families so that real sites can be planned and built across hierarchy levels.
- **Archetype spread:** No single archetype shall dominate. Templates should support service, offer, hub, landing, location, event, profile, FAQ, directory, comparison, and informational-detail use cases where in scope.

**Failure condition:** Reaching 500 page templates by adding only slight variants of one archetype (e.g. 400 “landing” clones) or 250 sections in one category fails the **coverage** requirement even if count is met.

---

## 6. Enhancement-not-replacement policy

All of the following prompt groups remain **in force**. This contract and any future “template library expansion” or “category taxonomy” or “mass library” prompts **enhance** them and do **not replace** them:

- **Prompts 021–032:** Registry and object model, section/page template objects, storage, validation.
- **Prompts 033–040:** ACF, field blueprints, rendering, portability.
- **Prompts 041–048:** Composition, one-pager, compatibility, documentation.
- **Prompts 070–076:** Admin screens, registry UI, versioning, export/import.
- **Prompts 109–111:** Build Plan, planning integration, template selection in plans.
- **Prompts 122–123:** Diagnostics, reporting, and any registry-related diagnostics or reporting.

**Downstream contract dependencies:** Implementations that add templates at scale must:

- Conform to **section-registry-schema.md** and **page-template-registry-schema.md**.
- Conform to **build-plan-admin-ia-contract.md** where Build Plan UI references templates.
- Conform to ACF, rendering, and portability contracts established in the pillars (§1.9.1, §1.9.2, §1.9.3) and in the referenced prompts.

---

## 7. Scale-governance rules

### 7.1 Documentation

- Every section and page template that counts toward the minimum must have **required fields populated**, including purpose summary, category/archetype, and version/status.
- Optional fields (e.g. preview description, hierarchy hints, AI planning notes) should be used to support discoverability and planning, not left empty for the majority of the library.
- **Documentation expectations:** Helper refs, one-pager metadata, and endpoint/usage notes must remain valid and auditable. Scale must not result in templates with placeholder or copy-paste documentation only.

### 7.2 Previews

- **Preview expectations:** Where preview assets or preview metadata are defined in the schema, templates in the large library should have preview expectations documented (e.g. preview image or description) so that QA and content authors can verify correct use. Absence of preview metadata is acceptable only where explicitly allowed by schema; it must not be a blanket omission for speed.

### 7.3 ACF and rendering

- **No weakening for scale:** ACF field-group generation, field naming, and render boundaries (spec §1.9.2, §1.9.3) apply to every template. No “bulk template” path that skips validation or ACF alignment.
- **Portability and survivability:** Rendered output and built-page survivability rules apply to all templates. Quantity must not degrade accessibility, markup consistency, or export/restore behavior.

### 7.4 QA thresholds

- **Validation:** Every template that counts toward the minimum must pass the same validation and completeness rules as today (required fields, compatibility, section resolution).
- **QA checklist:** Before milestone exit (§60.4), a coverage checklist (see §9) must be used to verify that counts, category spread, variation quality, and downstream preview/documentation requirements are met. Failure to meet coverage or quality rules is a **failure condition** even if raw counts are achieved.

### 7.5 Security and permissions

- **No weakening of permissions or template-validation rules** because of scale. Registry expansion remains **admin-governed**. No unvalidated bulk template injection. Capability and validation checks apply to every template add/update.

---

## 8. Failure conditions

The following conditions **fail** the large-library extension even if the numeric minimums (250 sections, 500 pages) are reached:

1. **Coverage failure:** Category or archetype spread is not met (e.g. one category or archetype dominates; top-level/hub/child balance is missing).
2. **Variation failure:** A significant share of templates are duplicates or thin clones under §3.2.
3. **Schema or validation failure:** Templates used toward the count are incomplete, invalid, or bypass required-field or compatibility rules.
4. **Documentation failure:** Required or expected documentation (purpose, helper refs, one-pager) is placeholder or missing for a large share of templates.
5. **Rendering or ACF failure:** Templates do not conform to ACF and rendering contracts; or portability/survivability is degraded for templates in the library.
6. **Governance failure:** Bulk or unvalidated injection is used; permission or validation is bypassed for scale.

---

## 9. Coverage checklist (for later verification)

Use this checklist when verifying that the large-library target is met and that enhancement boundaries are respected. It does not replace acceptance tests; it supplements them.

### 9.1 Counts

- [ ] Section template count ≥ 250 (each complete and valid per section-registry-schema).
- [ ] Page template count ≥ 500 (each complete and valid per page-template-registry-schema).

### 9.2 Category and archetype spread

- [ ] Section templates: no single category &gt; agreed maximum share (e.g. 25%) unless justified.
- [ ] Page templates: top-level, hub, nested hub, and child families represented; no single archetype dominates without justification.

### 9.3 Variation quality

- [ ] Sampled templates are not duplicates or thin clones (per §3.2).
- [ ] Purpose and structure differ meaningfully across sampled templates.

### 9.4 Documentation and preview

- [ ] Required fields (including purpose summary, category/archetype) populated for templates in scope.
- [ ] Preview expectations (where applicable) documented or implemented per schema.

### 9.5 Downstream requirements

- [ ] All templates pass existing validation and compatibility rules.
- [ ] ACF and rendering behavior unchanged for existing and new templates; no scale-only code paths that skip validation.
- [ ] Export/restore and composition rules apply to the extended library; no regression in portability or Build Plan integration.

### 9.6 Enhancement not replacement

- [ ] No removal or replacement of contracts or behavior from Prompts 021–032, 033–040, 041–048, 070–076, 109–111, 122–123.
- [ ] section-registry-schema.md, page-template-registry-schema.md, and build-plan-admin-ia-contract.md remain authoritative; scale contract adds targets and governance only.

---

## 10. Links to prompts enhanced by this contract

Future work that produces or curates the large library must **enhance** (and not replace) the architecture and behavior established in:

| Prompt range   | Area |
|----------------|------|
| 021–032        | Registry and object model, section/page template objects, storage, validation. |
| 033–040        | ACF, field blueprints, rendering, portability. |
| 041–048        | Composition, one-pager, compatibility, documentation. |
| 070–076        | Admin screens, registry UI, versioning, export/import. |
| 109–111        | Build Plan, planning integration, template selection. |
| 122–123        | Diagnostics, reporting, registry-related diagnostics. |

Category taxonomies, CTA rules, animation rules, or mass library production prompts must align with this contract and with the existing registry, ACF, rendering, and portability pillars (§1.9.1–1.9.3) and specs §12, §13, §14, §16, §59.4, §60.4.
