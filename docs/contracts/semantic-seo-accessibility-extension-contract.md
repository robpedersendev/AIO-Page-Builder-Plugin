# Semantic SEO and Accessibility Markup Extension Contract

**Spec**: §15.9 SEO-Relevant Guidance Rules; §15.10 Accessibility Guidance Rules; §17 Rendering Architecture; §18 Native Block Assembly; §51.5 Focus Management Rules; §51.6 Semantic Heading Rules; §51.7 Landmark and ARIA Rules; §51.8 Color Contrast Rules; §51.9 Form Accessibility Rules; §56.6 Accessibility Test Scope

**Upstream**: rendering-contract.md, css-selector-contract.md, section-template-category-taxonomy-contract.md, page-template-category-taxonomy-contract.md

**Status**: Contract definition only. No mass template creation; no schema plugin replacement. This contract translates "SEO optimized" and "ADA compliant" requests into **enforceable engineering rules** for markup. It does not guarantee search ranking outcomes or full legal compliance independent of editor-entered content.

---

## 1. Purpose and scope

This contract extends the rendering and section/page-template contracts with **stricter semantic, accessibility, and search-structure requirements** for the expanded template library (Prompts 132–136). It defines:

- Required semantic HTML patterns by section purpose family.
- Heading hierarchy and landmark rules for page templates.
- Link/button and CTA label clarity rules.
- Image/media obligations (alt-text, decorative handling).
- List, table, accordion, and form semantics.
- Search-structure readiness notes (heading clarity, internal-link opportunities, thin-content avoidance).
- Preview and QA expectations with machine-reviewable checks and invalid examples.

**Out of scope**: Mass template creation; schema plugin replacement; promise of universal search ranking; full legal ADA compliance guarantee independent of content. Semantic enhancements must **not** introduce unsafe raw markup pathways; existing sanitization, escaping, and permission boundaries remain unchanged.

---

## 2. Required semantic HTML patterns by section purpose family

Section templates **must** emit markup that satisfies the following by **section_purpose_family** (section-template-category-taxonomy-contract §2). Element roles align with css-selector-contract §3.4 where applicable.

| section_purpose_family | Required outer wrapper | Required inner structure | Required semantics |
|------------------------|------------------------|--------------------------|--------------------|
| `hero` | `<section>` with landmark or region | Inner container; headline block | One primary heading (h1 or h2 per page context); eyebrow optional; CTA when present must be link or button with visible text. |
| `proof` | `<section>` | Inner; content/cards/list | Headings (h2/h3) for section title and optional subsections; list of items as `<ul>`/`<ol>` or `<dl>` where list semantics apply. |
| `offer` | `<section>` | Inner; content/cards/cta | Section heading; pricing or feature list as list or structured content; CTA as link/button with clear label. |
| `explainer` | `<section>` | Inner; content/list | Step or process as ordered list or headings; no heading level skip within section. |
| `legal` | `<section>` or `<aside>` | Inner; content | Section/subheading as appropriate; paragraph or list for disclaimer text. |
| `utility` | `<nav>` or `<section>` | Inner; list or links | If navigation: `<nav>` with aria-label; links in list or landmark. If jump links: anchor targets with stable IDs. |
| `listing` | `<section>` | Inner; list/cards | List container as `<ul>`/`<ol>` or card group; item semantics (article/listitem) where applicable. |
| `comparison` | `<section>` | Inner; table or list | Comparison as `<table>` when tabular data, or definition list / list when not; caption or heading for context. |
| `contact` | `<section>` | Inner; form or content | If form: see §7; if contact CTA only: link/button with clear label. |
| `cta` | `<section>` | Inner; cta/cta-group | Primary CTA as link or button with **visible, descriptive text**; no image-only or icon-only CTA without accessible name. |
| `faq` | `<section>` | Inner; list/faq-item/disclosure | FAQ items as `<dl>`, or disclosure/accordion pattern with button + region; question as heading or button text; answer as associated content. |
| `profile` | `<section>` | Inner; content/media | Heading for name/role; image with alt; optional list for credentials. |
| `stats` | `<section>` | Inner; list or grid | Numbers with context; list or structured blocks; no heading skip. |
| `timeline` | `<section>` | Inner; list | Chronology as ordered list or time-ordered structure; headings per phase where used. |
| `related` | `<section>` or `<aside>` | Inner; list/cards | Heading for "Related" context; links with descriptive text. |
| `other` | `<section>` | Inner; content | Same baseline: section wrapper, logical headings, list/link/button semantics where applicable. |

**Baseline for all families**: Outer wrapper must be a semantic element (`section`, `aside`, `nav` as appropriate). No bare `<div>` as the only section wrapper. Classes and IDs follow css-selector-contract; semantics are additive to that contract.

---

## 3. Heading hierarchy rules across page templates

### 3.1 Page-level rules

| Rule | Requirement |
|------|-------------|
| Single h1 | Each page template must result in **exactly one** `<h1>` in the main content (typically hero or first content section). No multiple h1 per page from template output. |
| No skip | Heading levels must not skip (e.g. h2 → h4 is invalid). Allowed: h1 → h2 → h3 → h2 → h3. |
| Section ownership | Each section template declares its **heading role** (e.g. "this section provides the h1" or "this section provides h2"). Page assembly must resolve so that the concatenated order yields a single logical outline. |
| Outline consistency | The document outline (h1–h6 sequence) must be logical and reflect content hierarchy. |

### 3.2 Section contribution to outline

| Section position / role | Allowed heading levels | Notes |
|-------------------------|-------------------------|--------|
| First content section (opener) | h1, then optional h2/h3 | Hero or lead section typically supplies h1. |
| Subsequent sections | h2 for section title, then h3/h4 as needed | No section may emit h1 unless it is the designated opener. |
| Subsections within section | h3 under h2, h4 under h3 | No skip. |
| FAQ section | h2 for "FAQ" or section title; each question may be h3 or button (see §7). | If question is heading, it participates in outline. |
| Legal / footer-adjacent | h2 or no heading if brief | Avoid redundant "Legal" h2 if page already has one. |

### 3.3 Invalid heading examples (QA fail)

- Two or more `<h1>` in main content.
- Heading level skip: e.g. `<h2>` followed by `<h4>` with no `<h3>` in between.
- Headings used purely for visual styling (e.g. small text) when a `<p>` or `<span>` with class would be correct.
- Empty headings (no text content).
- Section that should provide h2 for its title but emits no heading.

---

## 4. Landmark and region rules

### 4.1 Required landmarks

| Context | Requirement |
|---------|-------------|
| Main content | The main page content (all section instances in order) must be wrapped in a single `<main>` or have role="main" on the wrapper. Per rendering-contract, this is part of page assembly. |
| Sections | Each section uses `<section>`; optional aria-label or aria-labelledby when the section title is not the first heading inside it. |
| Navigation | Any block that is navigation (e.g. utility section with jump links) must use `<nav>` and aria-label describing the navigation purpose. |
| Complementary | Optional `<aside>` for related or secondary content when it is not the main flow. |

### 4.2 ARIA rules (spec §51.7)

| Rule | Requirement |
|------|-------------|
| Prefer semantic HTML | Use `<main>`, `<nav>`, `<section>`, `<aside>`, `<article>` before adding ARIA roles. |
| ARIA when needed | Use ARIA for relationships (aria-labelledby, aria-describedby) or interaction patterns (expandable, live regions) only when semantic HTML is insufficient. |
| No redundant ARIA | Do not add role="section" on `<section>`, or redundant aria-label that duplicates visible text. |
| Stateful controls | Buttons/links that toggle (e.g. accordion) must expose state (aria-expanded, aria-controls) and have accessible name. |

### 4.3 Invalid landmark/ARIA examples (QA fail)

- Multiple `<main>` on the page.
- `<section>` with no accessible name when it has a visual title that is not a heading (add aria-labelledby or aria-label).
- Navigation block implemented as `<div>` with links and no `<nav>` or role="navigation" and no aria-label.
- Redundant role on native element that already implies the role.

---

## 5. Link and button label clarity rules (including CTA)

### 5.1 General rules

| Rule | Requirement |
|------|-------------|
| Visible text | Every link and button that is a CTA or primary action must have **visible, descriptive text** (or icon + visible text). |
| Accessible name | Links and buttons must have an accessible name (visible text, aria-label, or aria-labelledby) that describes the action or destination. |
| No generic only | Avoid "Click here", "Read more" as the only text unless context is provided (e.g. aria-label or preceding heading). Prefer "Read more about [topic]" or "Get [offer name]". |
| CTA clarity | CTA sections (section_purpose_family `cta`, or sections with cta_classification) must have at least one link or button with a **clear, action-oriented or destination-oriented** label. |

### 5.2 CTA-specific rules

| Rule | Requirement |
|------|-------------|
| Primary CTA | The primary CTA in a section (e.g. "Sign up", "Get quote", "Contact us") must not be image-only or icon-only without an accessible name. |
| Group CTAs | When multiple CTAs exist (cta-group), each must be distinguishable (different text or aria-label). |
| Decorative links | Links that are purely decorative (e.g. wrap an image for layout) must have aria-hidden="true" on the link and the image must have alt="" if decorative, or the link must have an accessible name that describes the destination. |

### 5.3 Invalid link/button examples (QA fail)

- CTA that is an image with no alt and no visible text or aria-label.
- Button or link with only "Submit" or "Click here" and no surrounding context or aria-label.
- Multiple "Read more" links with no way to distinguish destination (e.g. same text, no aria-label).

---

## 6. Image and media obligations

### 6.1 Alt-text rules

| Case | Requirement |
|------|-------------|
| Informative image | Must have meaningful alt text that conveys the same information or function as the image. |
| Decorative image | Must have alt="" (empty) and must not convey critical information. |
| Complex image | Alt summarizes; long description via link or aria-describedby if needed. |
| Image as link/button | Alt describes destination or action, not the image content (or image is decorative and link has accessible name). |

### 6.2 Decorative and functional media

| Case | Requirement |
|------|-------------|
| Decorative | alt=""; no critical info in image. Optionally aria-hidden="true" on wrapper if entire block is decorative. |
| Caption | Use figcaption or visible caption element; do not put essential info only in alt if caption is present—alt and caption can complement. |
| Video/audio | Captions or transcripts when required by policy; controls must be keyboard accessible. |

### 6.3 Invalid image/media examples (QA fail)

- Informative image with missing alt or alt that duplicates surrounding text verbatim without adding value.
- Decorative image with long descriptive alt (should be alt="").
- Image that is the only content of a CTA link with no alt and no visible text.

---

## 7. List, table, accordion, and form semantics

### 7.1 Lists

| Content type | Required markup |
|--------------|-----------------|
| Unordered list | `<ul>` and `<li>`. |
| Ordered list (steps, ranking) | `<ol>` and `<li>`. |
| Definition list (terms + definitions) | `<dl>`, `<dt>`, `<dd>`. FAQ may use `<dl>` or disclosure pattern. |
| List container | Use css-selector-contract element `list` / `list-item`; semantic element must match (ul/ol/dl + li or dt/dd). |

### 7.2 Tables

| Rule | Requirement |
|------|-------------|
| Tabular data | Use `<table>`, `<thead>`, `<tbody>`, `<th>`, `<td>`. Scope or headers/id for association. |
| Caption | Use `<caption>` or aria-describedby for table title/context. |
| Not for layout | Do not use table for non-tabular layout. |

### 7.3 Accordion / disclosure

| Rule | Requirement |
|------|-------------|
| Trigger | Use `<button>` (preferred) or link with role="button"; must have aria-expanded and aria-controls. |
| Panel | Associated region with id referenced by aria-controls; optionally aria-labelledby to heading or button. |
| Keyboard | Expand/collapse via Enter/Space; focus management per §51.5. |

### 7.4 Forms (spec §51.9)

| Rule | Requirement |
|------|-------------|
| Labels | Every form control must have a visible label or aria-label/aria-labelledby. |
| Association | Use `<label for="id">` and id on control, or aria-labelledby. |
| Required | Required fields must have aria-required="true" and visible indication (e.g. text "required"). |
| Errors | Error messages associated (aria-describedby or aria-errormessage); not color alone. |
| No placeholder-only | Placeholder must not be the only label. |

### 7.5 Invalid list/table/accordion/form examples (QA fail)

- List content marked up as divs only (no ul/ol/dl).
- Table used for layout with no th/scope or caption.
- Accordion trigger that is not a button or properly exposed; missing aria-expanded/aria-controls.
- Form control with no associated label or only placeholder as label.

---

## 8. Search-structure readiness notes

These are **guidance rules** for template and content structure to support search and clarity. They are not a guarantee of ranking.

| Area | Rule / note |
|------|-------------|
| Heading clarity | Section titles and headings should be descriptive and reflect content (supports §15.9 heading clarity). |
| Internal-link opportunities | Templates that include "related" or "navigation" sections should support internal links with descriptive anchor text. |
| Thin-content avoidance | Avoid templates that produce only a single short paragraph and no substantive structure; helper/one-pager should note when a section needs minimum content to be meaningful. |
| Duplicate-content risk | Helper system should note when section reuse across many pages could create duplicate content; no contract mandate to change content. |
| Keyword stuffing | No requirement to insert keywords; guidance is to avoid stuffing. Template structure should not encourage repeated identical phrases. |

---

## 9. Rule tables by section family and page-template class

### 9.1 Section family → semantic obligations (summary)

| section_purpose_family | Heading | Landmark/region | Lists/tables | CTA/link | Media |
|------------------------|---------|-----------------|-------------|----------|--------|
| hero | h1 or h2 (per page) | section | — | Required if CTA present; clear label | Alt required or decorative |
| proof | h2 (section title) | section | Optional list | Optional; if present, labeled | Alt or decorative |
| offer | h2 | section | Optional list | CTA labeled | Alt or decorative |
| explainer | h2, h3… no skip | section | ol/dl where steps | Optional | Alt or decorative |
| legal | h2 or none | section/aside | Optional | Optional | — |
| utility | — | nav/section | Links in list/nav | Links labeled | — |
| listing | h2 | section | ul/ol or cards | Optional | Alt or decorative |
| comparison | h2 | section | table or dl/list | Optional | — |
| contact | h2 | section | — | Form or CTA labeled | — |
| cta | h2 (optional) | section | — | **Primary CTA labeled** | No image-only CTA |
| faq | h2; q as h3 or button | section | dl or disclosure | — | — |
| profile | h2 (name) | section | Optional | Optional | Alt required |
| stats | h2 | section | list/grid | Optional | — |
| timeline | h2 | section | ol | Optional | — |
| related | h2 | section/aside | list/cards | Links labeled | Alt or decorative |
| other | h2 | section | As content | Labeled | Per content |

### 9.2 Page-template class → expectations

| template_category_class (page) | Expectation |
|--------------------------------|-------------|
| top_level | One h1 in opener; logical outline; main landmark wraps sections. |
| hub | One h1 in opener; sub-sections with h2; main landmark. |
| child_detail | One h1 or h2 (if parent supplies context); no skip; main landmark. |
| other | Same baseline: one primary heading, outline order, main landmark. |

---

## 10. Preview and QA expectations

### 10.1 Section preview obligations

When a section template is previewed (e.g. in admin or preview context):

| Check | Requirement |
|-------|-------------|
| Semantic wrapper | Preview output uses `<section>` (or nav/aside) with contract classes. |
| Heading level | Preview uses a heading level consistent with its declared role (e.g. hero h1, others h2). |
| No invalid outline | Preview in isolation may show h2; when composed, page assembler ensures single h1 and no skip. |
| CTA if present | Any CTA in preview has visible or accessible name. |
| Images | Any image has alt (or alt="" for decorative). |

### 10.2 Page-template preview obligations

When a page template is previewed (full page or composed preview):

| Check | Requirement |
|-------|-------------|
| Single h1 | Exactly one h1 in main content. |
| Heading sequence | No level skip in the outline. |
| Landmarks | One main; sections present; nav if applicable. |
| CTAs | Every CTA link/button has clear label. |
| Lists/tables | List and table markup used where content is list/table. |
| Forms | If form present, labels and required indicators. |

### 10.3 Semantic/accessibility checklist (machine-reviewable)

Use this checklist for section and page-template previews and for QA (aligns with §56.6).

**Heading and outline**

- [ ] Page has exactly one `<h1>` in main content.
- [ ] No heading level skip (h2 → h4 without h3).
- [ ] No empty headings.
- [ ] Headings are not used for visual styling only.

**Landmarks and ARIA**

- [ ] One `<main>` (or role="main") wrapping main content.
- [ ] Sections use `<section>` (or appropriate landmark).
- [ ] Navigation uses `<nav>` with aria-label.
- [ ] No redundant ARIA on semantic elements.

**Links and buttons (CTA)**

- [ ] Every CTA link/button has visible or accessible name.
- [ ] No image-only or icon-only CTA without accessible name.
- [ ] No generic-only "Click here" / "Read more" without context or aria-label.

**Images and media**

- [ ] Informative images have meaningful alt.
- [ ] Decorative images have alt="" (or equivalent).
- [ ] Image-as-link has alt describing destination or link has accessible name.

**Lists, tables, forms**

- [ ] List content uses ul/ol/dl + li or dt/dd.
- [ ] Tabular data uses table with th/scope/caption as needed.
- [ ] Accordion/disclosure uses button with aria-expanded/aria-controls.
- [ ] Form controls have associated labels; no placeholder-only labeling.

**Invalid examples (must fail QA)**

- Broken heading flow: two h1; or h2 then h4.
- Unlabeled CTA: button or link with no text and no aria-label; or image-only CTA with no alt/name.
- Improper interactive nesting: button inside link; or link wrapping interactive control.
- List content as divs only.
- Table for layout with no semantics.
- Form control with only placeholder.

---

## 11. Security and sanitization

Semantic and accessibility markup **must not** introduce unsafe pathways:

| Constraint | Requirement |
|------------|-------------|
| No raw HTML injection | All user/ACF content remains escaped per WordPress standards; semantic structure uses allowed elements and attributes only. |
| ARIA and attributes | Only allowed ARIA and data-aio-* attributes per contract; no user-supplied attribute names on structural elements. |
| IDs | IDs follow css-selector-contract patterns; no user-supplied or arbitrary IDs on section/page structure. |

---

## 12. Cross-references

- **rendering-contract.md**: Section rendering responsibilities (§6.1); accessibility handling; wrapper output.
- **css-selector-contract.md**: Element roles (§3.4); class/ID patterns; data-aio-* attributes.
- **section-template-category-taxonomy-contract.md**: section_purpose_family, placement_tendency, cta_classification.
- **page-template-category-taxonomy-contract.md**: template_category_class, template_family.
- **accessibility-remediation-checklist.md**: Admin and front-end scope; §56.6; checklist alignment for generated pages.

---

## 13. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 137 | Initial semantic SEO and accessibility extension contract. |
