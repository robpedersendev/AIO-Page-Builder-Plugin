# Template Library Compliance QA Matrix

**Document type:** Authoritative QA and compliance matrix for section-template and page-template production (Prompt 146).  
**Governs:** Acceptance gate for all future library-expansion prompts (37+ prompts creating 250+ section and 500+ page templates). Defines rule families, severity, pass/fail criteria, evidence requirements, and batch sign-off.  
**Spec refs:** §15.9 SEO-Relevant Guidance Rules; §15.10 Accessibility Guidance Rules; §16.3 Assembly Rules; §20 Field Governance Architecture; §21.9 Validation and Fallback Rules; §31.11 Empty State Patterns (by analogy for preview/data absence); §55.8 Large Template Library Handling Rules; §56.2 Unit Test Scope; §56.3 Integration Test Scope; §56.6 Accessibility Test Scope; §60.4 Exit Criteria.

**Enhancement policy:** This matrix **gates** template-production prompts. No batch is considered complete until the applicable rows in this matrix are satisfied or explicitly waived per hardening-release-gate-matrix. Quality over quantity; compliance remains schema-first, accessible, portable, previewable, and category-aware.

---

## 1. Purpose and scope

This matrix defines **what “compliant template” means** across:

- Count compliance and category coverage
- CTA sequencing, bottom-of-page CTA, non-adjacent CTA rules
- Semantic, accessibility, and SEO structure
- Animation fallback and reduced-motion behavior
- Preview realism and smart omission
- ACF integrity and LPagery compatibility boundaries
- Exportability and registry integrity

Each rule family has **severity** (hard-fail vs warning), **pass/fail criteria**, **evidence type**, and **sample-check obligations**. The matrix is **machine- and human-reviewable**: validators and checklists can reference rule codes; batch sign-off uses the tables in §8.

**Out of scope:** No actual template creation, UI implementation, rendering code changes, or QA automation beyond contract/checklist level. This is a **governance artifact** only.

---

## 2. Rule families and severity

### 2.1 Severity codes

| Code | Meaning | Gate |
|------|---------|------|
| `hard` | Hard-fail. Template or batch fails compliance until resolved. No waiver for template acceptance. | Must pass for template to count toward library. |
| `warning` | Warning-only. Recorded for review; batch may still sign off with rationale. | May defer with documented rationale per hardening-release-gate-matrix. |

**Rule:** All `hard` checks must pass for a template (or batch) to be accepted. `warning` findings must be recorded and may be waived or deferred per project policy.

### 2.2 Rule family index

| Family code | Name | Severity default | Primary contract / spec |
|-------------|------|-------------------|--------------------------|
| `COUNT` | Count compliance | hard | template-library-coverage-matrix.md |
| `CATEGORY` | Category and variation coverage | hard | template-library-coverage-matrix.md, section-template-category-taxonomy-contract, page-template-category-taxonomy-contract |
| `CTA_COUNT` | CTA section count by page class | hard | cta-sequencing-and-placement-contract §3 |
| `CTA_BOTTOM` | Bottom-of-page CTA | hard | cta-sequencing-and-placement-contract §5 |
| `CTA_ADJACENT` | Non-adjacent CTA | hard | cta-sequencing-and-placement-contract §6 |
| `CTA_RANGE` | Non-CTA section count range (8–14) | hard (min), warning (max) | cta-sequencing-and-placement-contract §4 |
| `SEMANTIC` | Semantic / SEO / accessibility structure | hard | semantic-seo-accessibility-extension-contract.md, §15.9, §15.10 |
| `ANIMATION` | Animation fallback and reduced-motion | hard | animation-support-and-fallback-contract.md, §21.9 |
| `OMISSION` | Smart omission behavior | hard | smart-omission-rendering-contract.md |
| `PREVIEW` | Preview realism and safety | hard | template-preview-and-dummy-data-contract.md, §31.11 analogy |
| `ACF` | ACF integrity and blueprint discipline | hard | large-scale-acf-lpagery-binding-contract.md, §20 |
| `LPAGERY` | LPagery compatibility boundaries | hard | large-scale-acf-lpagery-binding-contract.md |
| `EXPORT` | Exportability and registry integrity | hard | §55.8, registry export contracts |

---

## 3. Check categories: criteria and evidence

### 3.1 COUNT — Count compliance

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| COUNT-1 | Section template total ≥ 250 (complete, valid) | Count ≥ 250; all counted items pass schema and validation | Count &lt; 250 or any counted item invalid | Coverage worksheet §8 (template-library-coverage-matrix); validation report |
| COUNT-2 | Page template total ≥ 500 (complete, valid) | Count ≥ 500; all counted items pass schema and CTA/composition rules | Count &lt; 500 or any invalid | Coverage worksheet §8; validation report |
| COUNT-3 | No inflation by duplicates or stubs | Only valid variants per coverage matrix §5.1; no §5.2 disallowed | Duplicate, thin clone, placeholder, or incomplete counted | Sampling report (e.g. 5% sections, 5% pages) with checklist |

**Severity:** hard for COUNT-1, COUNT-2, COUNT-3.

---

### 3.2 CATEGORY — Category and variation coverage

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| CATEGORY-1 | Section purpose-family minimums met | Every section_purpose_family minimum (template-library-coverage-matrix §2.2) met | Any minimum not met | Coverage worksheet §8.1; distribution report |
| CATEGORY-2 | Section max share per family ≤ 25% | No section_purpose_family &gt; 25% of total | Any family &gt; 25% | Distribution report; worksheet |
| CATEGORY-3 | Section schema category max share ≤ 28% | No schema category &gt; 28% | Any category &gt; 28% | Distribution report |
| CATEGORY-4 | Section variation-family spread | ≥ 40 distinct variation_family_key or ≥ 15% in families with 2+ members | Neither condition met | Distribution report |
| CATEGORY-5 | Page template_category_class minimums met | Every template_category_class minimum (§3.2) met | Any minimum not met | Coverage worksheet §8.2 |
| CATEGORY-6 | Page template_family minimums met | Every template_family minimum (§3.3) met | Any minimum not met | Coverage worksheet §8.2 |
| CATEGORY-7 | Page max share: category class ≤ 45%, family ≤ 22% | No class &gt; 45%, no family &gt; 22% | Either exceeded | Distribution report |

**Severity:** hard for all.

---

### 3.3 CTA_COUNT, CTA_BOTTOM, CTA_ADJACENT, CTA_RANGE — CTA rules

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| CTA_COUNT-1 | Min CTA sections by template_category_class (top_level 3, hub 4, nested_hub 4, child_detail 5) | Every page template has ≥ required CTA-classified sections | Any page has fewer than minimum | Validation report (cta_count_below_minimum) |
| CTA_BOTTOM-1 | Last section in ordered_sections is CTA-classified | Final section is primary_cta, contact_cta, or navigation_cta | Final section not CTA-classified | Validation report (bottom_cta_missing) |
| CTA_ADJACENT-1 | No two CTA-classified sections adjacent | Between every pair of CTA sections there is ≥ 1 non-CTA section | Any adjacent pair both CTA-classified | Validation report (adjacent_cta_violation) |
| CTA_RANGE-1 | Non-CTA section count ≥ 8 per page | Every page has ≥ 8 non-CTA sections | Any page has &lt; 8 non-CTA | Validation report (non_cta_count_below_minimum) |
| CTA_RANGE-2 | Non-CTA section count ≤ 14 per page (warning if exceeded) | Every page has ≤ 14 non-CTA sections | Any page has &gt; 14 non-CTA | Validation report (non_cta_count_above_max); warning only |

**Severity:** hard for CTA_COUNT-1, CTA_BOTTOM-1, CTA_ADJACENT-1, CTA_RANGE-1; warning for CTA_RANGE-2.

---

### 3.4 SEMANTIC — Semantic, accessibility, SEO structure

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| SEMANTIC-1 | Section outer wrapper is semantic (&lt;section&gt;, &lt;aside&gt;, &lt;nav&gt; as appropriate) | Every section emits required wrapper per semantic-seo-accessibility-extension-contract §2 | Bare &lt;div&gt; only or wrong wrapper | Markup review; checklist per purpose family |
| SEMANTIC-2 | Single h1 per page; no heading level skip | Exactly one h1 in main content; no h2→h4 skip | Multiple h1 or skip | Markup review; §56.6 accessibility test scope |
| SEMANTIC-3 | Landmarks: main content in &lt;main&gt; or role=main; nav in &lt;nav&gt; with aria-label | Page assembly and sections satisfy landmark rules | Missing or incorrect landmarks | Markup review |
| SEMANTIC-4 | CTA and interactive: visible text, no image-only CTA without accessible name | All CTAs and links have visible, descriptive text or accessible name | Image-only or icon-only CTA without name | Accessibility checklist; §15.10 |
| SEMANTIC-5 | Images: alt when meaningful; decorative handled | Alt text or decorative pattern per contract | Missing alt where required; misleading alt | Checklist; §15.10 |
| SEMANTIC-6 | Lists, tables, FAQ: correct semantics (&lt;ul&gt;/&lt;ol&gt;/&lt;dl&gt;, &lt;table&gt;, disclosure pattern) | Markup matches content type per contract | Wrong or missing list/table/FAQ semantics | Markup review |

**Severity:** hard for all. Evidence aligns with §56.6 Accessibility Test Scope and §15.9, §15.10.

---

### 3.5 ANIMATION — Animation fallback and reduced-motion

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| ANIMATION-1 | Tier `none` fallback: content and layout correct without animation | Every section/page that declares animation has defined fallback; no broken layout when animation disabled | Content invisible or layout broken without animation | Fallback test; animation-support-and-fallback-contract §2, §4 |
| ANIMATION-2 | Reduced-motion respected | When user prefers reduced motion, tier downgrade or no motion applied | Motion forced when preference set | Reduced-motion test; contract §5 |
| ANIMATION-3 | No undefined or “best effort” fallback | Fallback behavior is deterministic per family/tier | Undefined or silent failure state | Contract §4.3; checklist |

**Severity:** hard for all. Aligns with §21.9 Validation and Fallback Rules.

---

### 3.6 OMISSION — Smart omission behavior

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| OMISSION-1 | Optional empty fields omitted per blueprint; required nodes never omitted for emptiness | Omission only where contract allows; headline/required structure preserved | Required node omitted or omission where disallowed | Renderer check; smart-omission-rendering-contract §2, §3 |
| OMISSION-2 | CTA structure preserved when CTA required; label may be fallback text | No silent removal of structural CTA; label fallback when empty | CTA structure removed or broken | Contract §2.2 (cta row); checklist |
| OMISSION-3 | No omission of content that participates in outline (e.g. headings) | Headings and outline-critical nodes not omitted based on emptiness alone | Heading omitted incorrectly | Markup review |

**Severity:** hard for all.

---

### 3.7 PREVIEW — Preview realism and safety

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| PREVIEW-1 | Preview uses real renderer; no mock HTML or separate preview path | Same section/page renderer as production; synthetic data only | Mock HTML or different code path | Implementation review; template-preview-and-dummy-data-contract §2 |
| PREVIEW-2 | No production data or secrets in preview input | Preview input is synthetic or preview-only store only | Real user content or secrets in preview | Security/preview audit; §31.11 analogy (no blank lists without explanation) |
| PREVIEW-3 | Realistic dummy data by family where applicable | Content patterns match section/page family (template-preview-and-dummy-data-contract §3.2) | Generic lorem for everything or misleading legal/fake dates | Sampling; checklist |
| PREVIEW-4 | No hidden publish or mutation path | Preview is read-only, admin-only | Preview triggers save/publish or mutates live content | Implementation review |

**Severity:** hard for all.

---

### 3.8 ACF — ACF integrity and blueprint discipline

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| ACF-1 | One blueprint per section template (or shared ref); deterministic keys | No ad-hoc or duplicate key patterns; blueprint identity tied to section_key | 50 clones with 50 different blueprint keys and same structure | Blueprint inventory; large-scale-acf-lpagery-binding-contract §2 |
| ACF-2 | Reuse vs fork documented; variant layering when shared variation_family_key | Reuse or variant layering per contract §2.2, §2.3 | Undocumented fork or silent clone | Blueprint manifest; checklist |
| ACF-3 | Required vs optional fields per blueprint; no optional promoted to required without schema change | Blueprint and schema aligned; omission respects required | Mismatch or required field omitted in renderer | Schema/blueprint review |

**Severity:** hard for all. Aligns with §20 Field Governance Architecture.

---

### 3.9 LPAGERY — LPagery compatibility boundaries

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| LPAGERY-1 | Token-compatible fields only where contract allows; no structural injection | Token archetypes and exclusions per large-scale-acf-lpagery-binding-contract §4 | Token in unsupported field or markup injection | Token map review; contract §4 |
| LPAGERY-2 | Preview does not require live LPagery resolution for directory display | Preview uses literal placeholder or preview token map only | Production token resolution required for preview | Implementation review |
| LPAGERY-3 | Token map and fallback documented; no silent broken content when token missing | Fallback behavior per §21.9; documented | Silent broken or misleading output | Validation and fallback checklist |

**Severity:** hard for all.

---

### 3.10 EXPORT — Exportability and registry integrity

| Item | Criterion | Pass | Fail | Evidence |
|------|-----------|------|------|----------|
| EXPORT-1 | All templates exportable without loss; schema fidelity preserved | Export/import round-trip preserves definition and references | Data loss or schema drift on export | Export test; registry export contract |
| EXPORT-2 | No scale-only code paths that skip validation | Validation runs for all templates regardless of count | Bypass or skip when library is large | Code review; template-library-coverage-matrix §6.1 (12) |
| EXPORT-3 | Registry remains single source of truth; no duplicate truth store | Query/index layers are additive; registry authoritative | Second data model or duplicated truth | Architecture review; §55.8 |

**Severity:** hard for all.

---

## 4. Evidence requirements summary

| Evidence type | When required | Artifact |
|---------------|----------------|----------|
| Coverage worksheet | Every batch / library milestone | template-library-coverage-matrix §8 (section and page worksheets) |
| Distribution report | Category and variation checks | Counts by family/class/category; max-share flags |
| Validation report | CTA and composition | All page templates pass; error codes per cta-sequencing-and-placement-contract §7 |
| Sampling report | Count and quality | 5% (or agreed) sections and pages reviewed for valid variation and no duplicates |
| Markup review | Semantic, accessibility, SEO | Checklist per semantic-seo-accessibility-extension-contract; §56.6 |
| Fallback test | Animation | Tier none and reduced-motion scenarios |
| Implementation review | Preview, ACF, LPagery, export | No mock preview path; no production data; blueprint discipline; export round-trip |

---

## 5. Hard-fail vs warning summary

| Category | Hard-fail items | Warning-only items |
|----------|------------------|---------------------|
| COUNT | COUNT-1, COUNT-2, COUNT-3 | — |
| CATEGORY | All CATEGORY-1–7 | — |
| CTA | CTA_COUNT-1, CTA_BOTTOM-1, CTA_ADJACENT-1, CTA_RANGE-1 | CTA_RANGE-2 (non_cta_above_max) |
| SEMANTIC | All SEMANTIC-1–6 | — |
| ANIMATION | All ANIMATION-1–3 | — |
| OMISSION | All OMISSION-1–3 | — |
| PREVIEW | All PREVIEW-1–4 | — |
| ACF | All ACF-1–3 | — |
| LPAGERY | All LPAGERY-1–3 | — |
| EXPORT | All EXPORT-1–3 | — |

**Rule:** Any unresolved hard-fail in scope fails the batch for exit (§60.4). Warnings may be waived or deferred with rationale per hardening-release-gate-matrix.

---

## 6. Sample-check obligations

For each batch (or full library sign-off):

1. **Count and category:** Run coverage worksheet; fill Actual column; verify all Meets checked and no max-share violation.
2. **CTA:** Run composition/CTA validator on every page template; zero errors (bottom_cta_missing, adjacent_cta_violation, cta_count_below_minimum, non_cta_count_below_minimum).
3. **Semantic/accessibility:** Sample at least 5% of sections and 5% of pages for markup checklist (wrapper, heading, landmarks, CTA labels, alt, list/table/FAQ semantics).
4. **Animation:** For sections/pages that declare animation, verify tier-none and reduced-motion behavior on sample.
5. **Preview:** Verify preview path uses real renderer and synthetic data only; no production data or publish path.
6. **ACF/LPagery:** Blueprint inventory and token map spot-check; no undocumented fork or unsupported token usage.
7. **Export:** One full export/import round-trip; verify no loss and validation still passes.

---

## 7. Future batch sign-off table

Each batch (e.g. SEC-01–SEC-09, PT-01–PT-10 per template-library-inventory-manifest) **shall** complete a sign-off row. “Batch” column identifies the batch; “Date” is sign-off date; “Evidence” references the artifact (e.g. “Coverage worksheet v1.2”, “Validation report 2025-07-20”).

| Batch ID | COUNT | CATEGORY | CTA_* | SEMANTIC | ANIMATION | OMISSION | PREVIEW | ACF | LPAGERY | EXPORT | Date | Evidence |
|----------|-------|----------|-------|----------|-----------|----------|---------|-----|---------|--------|------|----------|
| SEC-01 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | section-library-batch-validation-report.md; Hero_Intro_Library_Batch_Test |
| SEC-02 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | Trust_Proof_Library_Batch_Test |
| SEC-03 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | Feature_Benefit_Value_Library_Batch_Test |
| SEC-05 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | Process_Timeline_FAQ_Library_Batch_Test |
| SEC-06 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | Media_Listing_Profile_Detail_Library_Batch_Test |
| SEC-07 | ☑ | ☑ | N/A | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | Legal_Policy_Utility_Library_Batch_Test |
| SEC-08 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | 2025-03 | CTA_Super_Library_Batch_Test; CTA metadata checks |
| SEC-09 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | _____ | _____ |
| PT-01 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Top_Level_Marketing_Page_Template_Test; page-template-batch-validation-report.md |
| PT-02 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Top_Level_Legal_Utility_Page_Template_Test |
| PT-03 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Hub_Page_Template_Test |
| PT-04 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Geographic_Hub_Page_Template_Test |
| PT-06 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Nested_Hub_Page_Template_Test |
| PT-07 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Child_Detail_Page_Template_Test |
| PT-08 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Child_Detail_Product_Page_Template_Test |
| PT-09 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Child_Detail_Profile_Entity_Page_Template_Test |
| PT-10 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Top_Level_Educational_Resource_Authority_Page_Template_Test |
| PT-11 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Top_Level_Variant_Expansion_Page_Template_Test |
| PT-12 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Hub_Nested_Hub_Variant_Expansion_Page_Template_Test |
| PT-13 | ☑ | ☑ | ☑ | ☑ | ☑ | ☑ | ☐ | ☐ | ☐ | ☑ | 2025-03 | Child_Detail_Variant_Expansion_Page_Template_Test |

**Rule:** All checkboxes must be checked (or N/A with rationale) for the batch to be considered compliant. Sign-off is recorded per hardening-release-gate-matrix §6. Section batches SEC-01–SEC-08 filled after Prompt 154. Page batches PT-01–PT-13 filled after Prompt 167; COUNT/CATEGORY/CTA_*/EXPORT evidenced by unit tests and page-template-batch-validation-report.md; SEMANTIC/ANIMATION/OMISSION/ACF/LPAGERY remain implementation-level (markup, renderer, blueprint) and are ☐ until markup/renderer review.

---

## 8. Sample filled-in compliance rows (one section family, one page family)

The following are **example** filled rows showing how a future prompt will prove compliance for one section family and one page family. Values are illustrative.

### 8.1 Sample: section family `hero` (hypothetical batch SEC-01 subset)

| Family code | Item | Criterion | Pass/Fail | Evidence note |
|-------------|------|-----------|-----------|----------------|
| COUNT | COUNT-1 (section total) | Section total ≥ 250 (library-wide) | Pass (library) | Worksheet: 250 sections; SEC-01 contributes 28 hero. |
| CATEGORY | CATEGORY-1 | hero minimum 12 | Pass | Actual: 28 hero sections in SEC-01 + other batches. |
| CATEGORY | CATEGORY-2 | No family &gt; 25% | Pass | hero = 28/250 = 11.2%. |
| SEMANTIC | SEMANTIC-1 | Section wrapper &lt;section&gt; or appropriate | Pass | All hero sections emit &lt;section&gt;; hero contract §2. |
| SEMANTIC | SEMANTIC-2 | Single h1; no skip | Pass | Hero opener supplies h1; sample checked. |
| SEMANTIC | SEMANTIC-4 | CTA visible text / accessible name | Pass | All hero CTAs have visible label or aria-label. |
| ANIMATION | ANIMATION-1 | Tier none fallback | Pass | Hero entrance/hover fallback: static layout verified. |
| OMISSION | OMISSION-1 | Optional omitted; required kept | Pass | Headline required; eyebrow optional omitted when empty. |
| PREVIEW | PREVIEW-1, PREVIEW-2 | Real renderer; synthetic data | Pass | Preview uses section renderer + curated hero placeholders. |
| ACF | ACF-1 | One blueprint per section or shared ref | Pass | hero_compact, hero_media_left share blueprint ref; 3 distinct blueprints for 28 sections. |
| EXPORT | EXPORT-1 | Export round-trip | Pass | Export/import run; hero definitions intact. |

**Sign-off:** SEC-01 hero subset — all applicable checks passed; evidence: coverage worksheet, markup sample (10 hero sections), preview path review, export test. Date: *(example)* 2025-07-22.

### 8.2 Sample: page family `services` / template_category_class `child_detail` (hypothetical batch PT-03 subset)

| Family code | Item | Criterion | Pass/Fail | Evidence note |
|-------------|------|-----------|-----------|----------------|
| COUNT | COUNT-2 | Page total ≥ 500 (library-wide) | Pass (library) | Worksheet: 500 pages; PT-03 contributes 45 services. |
| CATEGORY | CATEGORY-5 | child_detail minimum 200 | Pass | Actual: 200+ child_detail across library. |
| CATEGORY | CATEGORY-6 | services family minimum 45 | Pass | Actual: 45 services templates. |
| CTA_COUNT | CTA_COUNT-1 | child_detail min 5 CTA sections | Pass | Every services page has ≥ 5 CTA-classified sections. |
| CTA_BOTTOM | CTA_BOTTOM-1 | Last section CTA-classified | Pass | Validator: zero bottom_cta_missing. |
| CTA_ADJACENT | CTA_ADJACENT-1 | No adjacent CTA | Pass | Validator: zero adjacent_cta_violation. |
| CTA_RANGE | CTA_RANGE-1 | Non-CTA ≥ 8 | Pass | All services pages have 8–14 non-CTA sections. |
| SEMANTIC | SEMANTIC-2 | Single h1; no skip | Pass | First section (hero) supplies h1; outline sample checked. |
| PREVIEW | PREVIEW-1, PREVIEW-2 | Real renderer; synthetic data | Pass | Page preview uses page assembler + section renderers; synthetic ACF per section. |
| EXPORT | EXPORT-1, EXPORT-2 | Export round-trip; no validation skip | Pass | Export/import run; all 45 services pages validate after import. |

**Sign-off:** PT-03 services subset — all applicable checks passed; evidence: coverage worksheet, CTA validation report (45/45 pass), outline sample (5 pages), export test. Date: *(example)* 2025-07-22.

---

## 9. Cross-references

- **Spec:** §15.9, §15.10, §16.3, §20, §21.9, §31.11, §55.8, §56.2, §56.3, §56.6, §60.4.
- **Contracts:** template-library-coverage-matrix.md, template-library-inventory-manifest.md, cta-sequencing-and-placement-contract.md, semantic-seo-accessibility-extension-contract.md, animation-support-and-fallback-contract.md, smart-omission-rendering-contract.md, template-preview-and-dummy-data-contract.md, large-scale-acf-lpagery-binding-contract.md.
- **Release gate:** [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) — waivers and sign-off process; [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md) — count and category targets.
- **Automated pass (Prompt 176):** [template-library-automated-compliance-report.md](template-library-automated-compliance-report.md) — executable validation and result payload; `Template_Library_Compliance_Service` implements this matrix as the library-wide enforcement gate.
- **Semantic/accessibility audit (Prompt 186):** [template-library-accessibility-audit-report.md](template-library-accessibility-audit-report.md) — machine-checkable semantic, accessibility, and CTA rules over section and page registries; `Template_Accessibility_Audit_Service`; evidence for SEMANTIC/CTA families and §56.6.
- **Animation/fallback QA (Prompt 187):** [template-library-animation-fallback-report.md](template-library-animation-fallback-report.md) — animation tier/fallback metadata and reduced-motion resolution at library scale; `Animation_QA_Service`; evidence for ANIMATION family and §59.14.

---

*This matrix is the acceptance gate for the 37 library-expansion prompts. No batch is complete until the applicable compliance rows are satisfied and evidence is recorded.*
