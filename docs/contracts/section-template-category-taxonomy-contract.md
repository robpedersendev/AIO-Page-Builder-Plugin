# Section Template Category Taxonomy and Purpose Contract

**Document type:** Authoritative contract for section-template category taxonomy, purpose families, placement tendencies, and CTA classification (Prompt 134).  
**Governs:** Section-purpose families, category tags, placement_tendency, cta_classification, variation_family_key, compatibility implications, and admin directory semantics.  
**Spec refs:** §12 Section Template Registry; §12.13 Compatibility; §12.14 Versioning; §15 Helper Paragraph System; §17 Rendering Architecture; §20 Field Governance; §57.6 CSS Naming; template-library-scale-extension-contract (Prompt 132).

**Enhancement policy:** This contract **enhances** and does **not replace** Prompts 021, 027, 031, 033, 036, 042, 043, 122, or 132. Sections remain registry-driven, versioned, selector-contract-bound, ACF-owned in field logic, and helper-documented. Taxonomy metadata **enhances** the section registry; it does not weaken validation or compatibility.

---

## 1. Purpose and scope

The expanded section library (see template-library-scale-extension-contract) requires a **formal section taxonomy** so that:

- Sections are organized by **purpose family** (hero, proof, offer, explainer, legal, utility, listing, comparison, contact, CTA, etc.).
- **Placement tendencies** (opener, mid-page proof, legal footer-adjacent, CTA-ending) support composition and ordering logic.
- **CTA-classified** section families are explicit for future CTA spacing or adjacency rules.
- **Compatibility and conflict** can be reasoned about at category level as well as at section level.
- **Variation families** group section variants for browsing and coverage QA.
- Admin directory and **preview browsing** use stable registry metadata, not ad-hoc labels.

**Out of scope:** No actual section creation, admin directory screen implementation, CTA adjacency logic, or animation fallback logic. All taxonomy values are **validated and deterministic**; no change to registry permissions.

---

## 2. Section-purpose families

**section_purpose_family** is the primary purpose grouping for a section. Each section has **one** purpose family. Values are stable slugs used for directory grouping, composition hints, and coverage QA.

| Family slug | Description | Typical category (spec §12.6) | Page-template class affinity |
|-------------|-------------|-------------------------------|-------------------------------|
| `hero` | Hero, intro, above-the-fold lead. | hero_intro | top_level, hub openers. |
| `proof` | Trust, proof, social proof, testimonials. | trust_proof | All classes; mid-page. |
| `offer` | Offer, value proposition, pricing. | feature_benefit, pricing_packages | hub, child_detail. |
| `explainer` | Explain, process, steps, how-it-works. | process_steps, feature_benefit | All classes. |
| `legal` | Legal, disclaimer, terms snippet. | legal_disclaimer | top_level, child_detail; footer-adjacent. |
| `utility` | Structural, layout, navigation jump. | utility_structural, navigation_jump | All classes. |
| `listing` | Directory, list, gallery. | directory_listing, media_gallery | hub, child_detail. |
| `comparison` | Comparison, versus, decision support. | comparison | child_detail. |
| `contact` | Contact, form, request. | form_embed | top_level, child_detail. |
| `cta` | Primary CTA, conversion block. | cta_conversion | All classes; often closer to end. |
| `faq` | FAQ, Q&A. | faq | hub, child_detail. |
| `profile` | Profile, bio, person. | profile_bio | child_detail. |
| `stats` | Stats, highlights, numbers. | stats_highlights | All classes. |
| `timeline` | Timeline, chronology. | timeline | explainer, child_detail. |
| `related` | Related, recommended content. | related_recommended | Mid or end of page. |
| `other` | Other or mixed purpose. | any | Fallback. |

**Relation to existing `category`:** The section registry schema already has a required **category** field (e.g. hero_intro, trust_proof, cta_conversion). **section_purpose_family** is a coarser, purpose-oriented grouping that may map many-to-one from category (e.g. several categories can sit in `proof`). Both are first-class registry metadata. Purpose family drives **browsing and placement**; category remains the schema-required classification.

---

## 3. Section category tags (section_category_tags)

**section_category_tags** is an **optional array** of additional category slugs for filtering and compatibility. It **supplements** the single required `category` field when a section legitimately spans multiple concerns (e.g. “hero with CTA” might have category `hero_intro` and tags `['cta_conversion']`).

- **Allowed values:** Any slug from the section schema §2.1 (hero_intro, trust_proof, feature_benefit, cta_conversion, legal_disclaimer, etc.).
- **Validation:** Each tag must be from the allowed category slug set. Duplicate of the primary `category` may be omitted or allowed per policy. Order is not significant.
- **Use:** Filtering (“show sections with tag cta_conversion”), compatibility hints (“avoid stacking sections with same tag”), and coverage QA.

**Conflict implication:** Sections that share a tag may be subject to **duplicate_purpose_of** or **avoid_adjacent** in compatibility when stacking would be redundant or harmful. Taxonomy does not define the exact rules; it supplies the tags that composition logic can use.

---

## 4. Placement tendency

**placement_tendency** indicates where a section **tends** to appear in a page flow. One value per section. Used for composition suggestions, ordering validation, and preview grouping.

| Tendency slug | Description | Typical purpose families | Notes |
|---------------|-------------|--------------------------|--------|
| `opener` | Typically first or early in page. | hero, offer (lead) | Often one opener per page. |
| `mid_page` | Middle of page; proof, explainer, listing. | proof, explainer, listing, stats | Flexible order within mid. |
| `comparison` | Comparison or decision block. | comparison | Often one per page; mid or late. |
| `legal_footer_adjacent` | Legal/disclaimer; often near footer. | legal | Avoid multiple; placement near end. |
| `cta_ending` | CTA or conversion; often late or last. | cta, contact | Strong candidate for ending. |
| `utility_any` | Utility or structural; position flexible. | utility | Jump links, layout; any position. |
| `related_any` | Related/recommended; mid or end. | related | After main content. |
| `other` | No strong tendency. | other | Default when none of the above. |

**Validation:** placement_tendency must be one of the slugs above. **Invalid combinations:** A section with purpose_family `hero` and placement_tendency `cta_ending` is inconsistent (hero is opener). A section with purpose_family `legal` and placement_tendency `opener` is inconsistent. See §8 for invalid-assignment matrix.

---

## 5. CTA classification (cta_classification)

Sections that are **CTA-oriented** must be explicitly classified so future CTA spacing or adjacency logic can treat them consistently.

| Classification slug | Description | Typical purpose family |
|---------------------|-------------|------------------------|
| `primary_cta` | Primary conversion CTA (signup, purchase, quote). | cta |
| `contact_cta` | Contact or request CTA. | contact, cta |
| `navigation_cta` | Navigation or “read more” CTA. | cta, utility |
| `none` | Not a CTA section. | hero, proof, legal, listing, etc. |

**Rule:** Sections with purpose_family `cta` or `contact` **should** have cta_classification set to a non-`none` value. Sections with purpose_family `hero` or `proof` may have an embedded CTA but are not necessarily classified as CTA sections; classification is for **sections whose primary role is CTA**. Validation may **warn** if purpose_family is `cta` and cta_classification is `none`.

**Invalid:** cta_classification may not be a value outside the allowed set. No new slug may be introduced without contract update.

---

## 6. Relation to page-template category classes

Sections do **not** have a required page-template class; pages are built from sections. The taxonomy supports **affinity** between section purpose and page class:

- **top_level / hub openers:** Sections with placement_tendency `opener` and purpose_family `hero` or `offer` are natural openers for top_level and hub page templates.
- **child_detail:** Sections with purpose_family `listing`, `comparison`, `profile`, `faq` are commonly used in child/detail pages.
- **Legal and CTA:** Sections with purpose_family `legal` or `cta` and placement_tendency `legal_footer_adjacent` or `cta_ending` support page-level composition rules (e.g. “one primary CTA block”, “legal near footer”).

This contract does **not** define CTA adjacency or sequencing logic; it defines the **metadata** (cta_classification, placement_tendency, purpose_family) that such logic can use.

---

## 7. Compatibility and conflict at category level

Existing **compatibility** (§12.13) remains at section level (may_precede, may_follow, avoid_adjacent, duplicate_purpose_of). Taxonomy adds **category-level implications**:

- **Same purpose_family stacking:** Multiple sections with the same section_purpose_family (e.g. two `cta` sections) may trigger a **warning** or validation hint unless explicitly allowed (e.g. “hero CTA” + “footer CTA”). Composition logic may use purpose_family to suggest avoid_adjacent or duplicate_purpose_of.
- **Placement conflict:** Two sections both with placement_tendency `opener` on the same page may be flagged (typically one opener). Two sections with placement_tendency `cta_ending` may be allowed but noted for “multiple CTAs”.
- **Legal and CTA:** Sections with placement_tendency `legal_footer_adjacent` and `cta_ending` are not inherently incompatible; ordering rules (e.g. CTA before legal) are out of scope here but can reference these tendencies.

**Compatibility metadata** (may_precede, may_follow, avoid_adjacent) remains the **authoritative** source for section-to-section rules. Taxonomy supplies **default or suggested** implications that tools can use when section-level compatibility is unspecified.

---

## 8. Variation family (variation_family_key)

**variation_family_key** groups section templates that are **variants** of the same conceptual section (e.g. “hero_compact”, “hero_media_left” share a variation_family_key such as `hero_primary`). One key per section; format: stable slug, max 64 chars.

- **Use:** Browsing (“show all variants of hero_primary”), coverage QA (“ensure no thin clones within same variation_family”), and replacement suggestions (deprecate one variant, suggest another in same family).
- **Validation:** Optional. When present, must be non-empty and pattern `^[a-z0-9_]+$`. Sections that are not variants of another share no key or use a key equal to internal_key.
- **Uniqueness:** variation_family_key is **not** unique across sections; multiple sections may share the same key (they are the “variants” in that family).

---

## 9. Representation in registry and admin directory

### 9.1 Additive registry fields

The following are **additive optional** fields on the section template definition. See section-registry-schema.md for the optional-fields table extension.

| Field | Type | Required | Allowed values | Notes |
|-------|------|----------|----------------|-------|
| `section_purpose_family` | string | For taxonomy participation | Slugs from §2 | Primary purpose family. |
| `section_category_tags` | array of strings | No | Category slugs from schema §2.1 | Additional category tags. |
| `placement_tendency` | string | No | Slugs from §4 | Opener, mid_page, cta_ending, etc. |
| `cta_classification` | string | No | Slugs from §5 | primary_cta, contact_cta, navigation_cta, none. |
| `variation_family_key` | string | No | Pattern `^[a-z0-9_]+$`; max 64 chars | Groups variants. |

All values are **validated and deterministic**. They are first-class registry metadata for browsing, preview grouping, and placement-aware composition.

### 9.2 Admin directory and preview

When admin screens list or browse **section templates**, grouping and filtering **should** use:

- **section_purpose_family** (primary grouping: Hero, Proof, CTA, etc.).
- **category** (existing required field) for compatibility with current schema.
- **placement_tendency** for “Openers”, “CTA / Ending”, “Legal”, etc.
- **cta_classification** for “CTA sections” filter.

Preview browsing and coverage QA use the same taxonomy so that section counts and category spread (template-library-scale-extension-contract §9) can be measured by purpose_family and placement_tendency.

---

## 10. Taxonomy examples and invalid assignments

### 10.1 Valid examples

| purpose_family | category (existing) | placement_tendency | cta_classification | Notes |
|----------------|----------------------|--------------------|--------------------|-------|
| hero | hero_intro | opener | none | Standard hero. |
| proof | trust_proof | mid_page | none | Trust block. |
| cta | cta_conversion | cta_ending | primary_cta | Primary CTA section. |
| contact | form_embed | cta_ending | contact_cta | Contact form. |
| legal | legal_disclaimer | legal_footer_adjacent | none | Legal snippet. |
| comparison | comparison | comparison | none | Comparison block. |
| utility | navigation_jump | utility_any | none | Jump links. |
| offer | feature_benefit | opener | none | Value prop opener. |

### 10.2 Invalid or inconsistent assignments (must be rejected or warned)

| Condition | Reason |
|-----------|--------|
| placement_tendency = `opener` and purpose_family = `legal` | Legal is not an opener. |
| placement_tendency = `opener` and purpose_family = `cta` (with no exception) | CTA sections tend to ending; hero-with-CTA may be opener but purpose_family could be hero. |
| placement_tendency = `cta_ending` and purpose_family = `hero` | Hero is opener; inconsistent. |
| cta_classification = `primary_cta` and purpose_family = `legal` | Legal is not a CTA section. |
| section_category_tags contains a slug not in schema §2.1 allowlist | Invalid tag. |
| placement_tendency not in defined set (§4) | Unknown tendency. |
| cta_classification not in defined set (§5) | Unknown classification. |
| purpose_family not in defined set (§2) | Unknown family. |

**Warn (do not reject):** purpose_family = `cta` and cta_classification = `none` (recommend setting classification).

---

## 11. Links to enhanced prompts

This contract **enhances** (and does not replace) the following:

| Prompt | Area |
|--------|------|
| 021 | Section template object and registry model. |
| 027, 031 | Section storage, validation. |
| 033, 036 | ACF, field governance; taxonomy does not change field ownership. |
| 042, 043 | Composition, helper system; taxonomy supports placement and compatibility. |
| 122 | Diagnostics; taxonomy supports coverage reporting. |
| 132 | Scale extension; taxonomy implements section category coverage and variation structure. |

Sections remain **registry-driven**, **versioned** (§12.14), **selector-contract-bound** (§17, §57.6), **ACF-owned** in field logic (§20), and **helper-documented** (§15). This contract adds **purpose family, placement tendency, CTA classification, and variation family** metadata on top of that foundation.
