# Template Preview and Realistic Dummy-Data Contract

**Spec**: §16.2 Source Inputs for One-Pager Generation; §16.3 Assembly Rules; §17 Rendering Architecture; §18 Native Block Assembly; §49.3 Screen Hierarchy; §60.6 Documentation Completion Requirements

**Upstream**: rendering-contract.md, smart-omission-rendering-contract.md, animation-support-and-fallback-contract.md, large-scale-acf-lpagery-binding-contract.md, semantic-seo-accessibility-extension-contract.md; page-template-directory-ia-extension.md, section-template-directory-ia-extension.md

**Status**: Contract definition only. No actual preview UI implementation; no full fixture-seeding system; no page-template generation. Previews must **reflect real renderer behavior**, not separate mock HTML. Dummy data must be **synthetic and safe**. Previews must **not** create hidden execution or publishing paths. Smart omission and animation fallback rules apply to preview output.

---

## 1. Purpose and scope

This contract formalizes the **preview system** for both **page templates** and **section templates** so directory detail screens can show **realistic rendered previews** with **safe dummy data** that reflects what a site visitor would see. It defines:

- Preview **fidelity levels** (what “realistic” means).
- **Synthetic ACF data generation** rules by template family.
- **Realistic dummy-data** expectations (headings, descriptions, lists, testimonials, pricing/location/contact placeholders).
- **Required metadata** shown alongside the preview on detail screens.
- **Animation** preview posture and reduced-motion handling.
- **Preview-safe omission** behavior for empty optional fields.
- **Invalid** preview and dummy-data behaviors.

**Out of scope**: Actual preview screen implementation; full fixture-seeding system; page-template generation. **Architecture constraint**: Previews must **not** drift from the actual rendering pipeline; the preview system is grounded in **real section/page rendering** with synthetic data inputs, not bespoke showcase HTML.

---

## 2. Preview fidelity expectations

### 2.1 Fidelity levels

| Level | Description | Use |
|-------|-------------|-----|
| **Structural** | Layout, wrappers, and element roles match the real renderer output. Content may be minimal placeholders. | Fallback when no dummy data; still uses real renderer. |
| **Realistic** | Same as structural, plus **realistic synthetic content** (headings, copy, list items, testimonials, pricing hints, etc.) so the preview looks like a real page/section a visitor would see. | Default for directory detail previews. |
| **Pixel-perfect** | Not required. Preview may differ in viewport, theme CSS, or asset loading from production; **structure and content realism** are required, not pixel identity. | — |

**Rule**: Directory previews target **realistic** fidelity. They are produced by the **same rendering pipeline** (section renderer, page assembler) that produces durable post_content, with **synthetic ACF field values** (and optional preview token map) as input. No separate “preview HTML” generator.

### 2.2 Section template preview

- **Input**: Section template definition + **synthetic field values** (per §4) for that section’s blueprint.
- **Process**: Same section renderer as for real page build; output is block markup (or equivalent) that would be saved to post_content.
- **Output**: Rendered section fragment suitable for display in an iframe or preview container in admin. Smart omission applies: optional empty fields are omitted per smart-omission-rendering-contract. Animation applies per animation-support-and-fallback-contract (including reduced-motion).

### 2.3 Page template preview

- **Input**: Page template definition (ordered sections) + **synthetic field values** for each section instance on the page.
- **Process**: Same page assembly and section rendering as for real page build; concatenated block stream.
- **Output**: Full page structure (main landmark, section sequence) suitable for preview in admin. Same omission and animation rules as production.

### 2.4 Invalid preview behaviors (must not occur)

- **Mock HTML**: Generating preview with a different code path or mock HTML instead of the real renderer. Preview must use the **real** section/page renderer.
- **Hidden publish path**: Preview must not trigger save, publish, or any mutation of live content. Preview is **read-only** and **admin-only**.
- **Production data**: Using real customer data, real secrets, or live ACF values from production posts as preview input. Preview input is **synthetic only** (or explicitly scoped preview store).
- **Misleading legal/privacy**: Dummy content must not include fake but realistic-looking legal or privacy claims that could be mistaken for real policy (e.g. avoid “Privacy Policy effective 2025” with fake dates). Use clearly generic placeholders (e.g. “Legal placeholder”, “Privacy policy text here”).

---

## 3. Synthetic ACF data generation rules by template family

### 3.1 Source of synthetic data

| Source | Description |
|--------|-------------|
| **Blueprint/manifest defaults** | Section or page blueprint may define **preview defaults** per field (large-scale-acf-lpagery-binding-contract §3). |
| **Curated preview store** | Per section_key, variation_family_key, or template_family: a map of field_name → synthetic value. |
| **Category-aware generator** | When no blueprint default or store value exists, a **generator** produces values by **template family** (purpose family for sections; template_family/category for pages) so content is realistic for that family. |
| **Fallback** | If none of the above: use preview-safe fallback per large-scale-acf-lpagery-binding-contract §3.3 (e.g. “Heading”, “Body copy for this section.”, “#” for URL). |

### 3.2 Realistic content patterns by category (section purpose family / page family)

Synthetic data should **match the intent** of the section or page type so the preview looks plausible.

| Family / category | Realistic dummy-data expectations |
|-------------------|-----------------------------------|
| **Hero** | Headline (short, 3–8 words), subheadline (1–2 sentences), optional eyebrow, CTA text + URL (# or placeholder). No lorem for hero; use coherent placeholder headline (e.g. “Welcome to Our Service”). |
| **Proof / testimonials** | 2–3 repeater rows: name (e.g. “Client A”, “Jane D.”), quote (1–2 sentences), optional role or company (generic). |
| **Offer / pricing** | Heading, short intro, 2–3 “plans” or items: title, price placeholder (“$XX”), short feature list or bullet points. |
| **Explainer / process** | Heading, 3–4 steps: title + short description per step. Ordered list or numbered. |
| **Legal** | Short generic placeholder (“Legal disclaimer placeholder.” or “Terms and conditions text.”). **No** fake effective dates or realistic-looking policy text. |
| **Listing / directory** | Heading, 3–5 list items: title + optional short description or link (#). |
| **Comparison** | Heading, 2–3 comparison rows or table: option name + short pros/cons or specs (generic). |
| **Contact** | Heading, form placeholder or “Contact form” + optional address/phone placeholders (“123 Example St”, “(555) 000-0000”). No real addresses or numbers. |
| **CTA** | Headline (action-oriented), 1–2 CTA buttons/links: text (e.g. “Get started”, “Contact us”) + URL (#). |
| **FAQ** | Heading, 3–4 FAQ items: question (generic, e.g. “What is this service?”) + answer (1–2 sentences). |
| **Profile** | Name (e.g. “Team Member”), role, short bio (1–2 sentences), optional image placeholder. |
| **Stats** | Heading, 3–4 stat items: label (e.g. “Projects”) + number (e.g. “100+”) or placeholder. |
| **Locations** | Heading, 2–3 location placeholders: name (“Main Office”), address placeholder (“123 Example St”), optional link (#). No real business names or addresses. |
| **Other** | Generic heading + 1–2 sentences or list. |

**Rule**: All text is **synthetic**. No real customer names, real company names, real addresses, real phone numbers, or real legal text. Placeholders must be **clearly placeholder** where ambiguity could cause confusion (e.g. legal).

### 3.3 Token and value treatment in preview

| Case | Requirement |
|------|-------------|
| **Tokenized field** | Preview may supply **literal placeholder** (e.g. “Location Name”) or **resolved value** from a **preview-only token map** (e.g. `{{location_name}}` → “Sample Location”). Preview must **not** use production LPagery token resolution or production data. |
| **Missing token** | If a token is not in the preview token map, use preview-safe fallback (large-scale-acf-lpagery-binding-contract §3.2, §7). |
| **Design tokens** | Colors, typography, spacing in preview may use default or preview theme; no requirement to load full brand profile for preview. |

---

## 4. Required metadata shown alongside preview (detail screens)

Detail screens (page template detail L5, section template detail L5) must display the following **in addition to** the rendered preview. Preview is one part of the detail view; metadata is required for usability and differentiation.

### 4.1 Page template detail – required side-panel (or equivalent) metadata

| Field | Source | Purpose |
|-------|--------|---------|
| **Name / label** | Template name or purpose_summary (short) | Identity. |
| **Description** | purpose_summary or one-pager excerpt | What the template is for. |
| **Used sections** | ordered_sections (list of section names or internal_keys) | Which sections appear and in what order. |
| **Differentiation notes** | Optional short text or tags | How this template differs from others (e.g. “Full-width hero”, “With pricing table”). |
| **Purpose / CTA direction** | template_family, page_purpose_family, or CTA intent | Planning context (e.g. “Conversion”, “Services hub”). |
| **Category / hierarchy** | template_category_class, hierarchy_role | Top-level, hub, child/detail. |
| **Rendered preview** | Output of real renderer with synthetic data | Visual representation. |
| **One-pager link** | Link to one-pager (Spec §16) | Documentation. |
| **Composition provenance** | “Used in N compositions” if applicable | Reuse context. |

### 4.2 Section template detail – required side-panel (or equivalent) metadata

| Field | Source | Purpose |
|-------|--------|---------|
| **Name / label** | Section name or purpose_summary (short) | Identity. |
| **Description** | purpose_summary or helper excerpt | What the section is for. |
| **Purpose family / CTA** | section_purpose_family, cta_classification | Hero, Proof, CTA, etc. |
| **Placement tendency** | placement_tendency | Opener, mid_page, cta_ending, etc. |
| **Variants** | variants (schema) or variation_family_key | Variant list or family. |
| **Field blueprint summary** | Field list, required/optional | What fields the section needs. |
| **Helper doc link** | Helper paragraph (Spec §15) | Documentation. |
| **Rendered preview** | Output of real section renderer with synthetic data | Visual representation. |

### 4.3 Preview placement

- **Rendered preview** may be inline on the detail screen (e.g. iframe or preview container) or behind a “Preview” tab/link. It must be **visible** from the detail screen without leaving the directory (no requirement to open a new window unless design chooses that).
- **Metadata** (name, description, used sections, etc.) must be **visible** alongside or above/below the preview so the user can read context and preview together.

---

## 5. Animation preview posture and reduced-motion handling

### 5.1 Animation in preview

| Rule | Requirement |
|------|-------------|
| **Same contract** | Preview rendering uses the **same** animation-support-and-fallback-contract as production. Animation tier and families are those of the section/page template; no “preview-only” animation that does not exist in production. |
| **Reduced motion** | If the admin or user has **prefers-reduced-motion: reduce** (or a preview setting to simulate it), preview must **honor** it: no decorative animation in preview when reduced motion is on (animation-support-and-fallback-contract §5). |
| **Fallback** | When animation is disabled or unsupported in preview context, layout and content must remain correct (tier `none` behavior). |

### 5.2 Preview context

- Preview may be rendered in an **iframe** or isolated container. Animation (e.g. entrance, hover) may behave differently in a small viewport or without full page scroll; that is acceptable. **Structure and content** must still match real renderer output.
- **No promise** of identical animation timing or effect in preview vs production; **promise** of same markup and omission/fallback rules.

---

## 6. Preview-safe omission behavior

### 6.1 Application of smart omission in preview

- **Optional empty fields** in the **synthetic** data set: the renderer applies **smart-omission-rendering-contract** as in production. So if the preview data intentionally leaves an optional field empty (or supplies empty string), that element is **omitted** in the preview output.
- **Required fields** and **required nodes** (e.g. section headline when it supplies h1): must **not** be omitted. Preview data generator must supply **fallback values** for required fields so the preview never omits a required node (smart-omission-rendering-contract §3, §8).

### 6.2 Preview data completeness for required nodes

- Synthetic data generation (or preview store) must ensure that for every section in the preview:
  - **Required** blueprint fields have a non-empty synthetic value (or fallback).
  - **Headline** (when section supplies h1 or section h2) has a value so outline is valid.
  - **Primary CTA** (when section is CTA-classified) has label and URL so CTA structure is preserved.

If a required value is missing, the **preview renderer** uses the same **fallback** rules as production (smart-omission-rendering-contract §8) so preview never shows broken structure (e.g. empty h1, empty CTA button).

---

## 7. Preview payload and integration notes

### 7.1 Conceptual preview payload (section)

A preview request for a **section** can be thought of as:

| Input | Description |
|-------|-------------|
| section_key | Section template internal_key. |
| variant | Optional variant key (default if omitted). |
| field_values | Map of field_name → synthetic value (from blueprint defaults, preview store, or category-aware generator). |
| options | Optional: reduced_motion, animation_tier override. |

**Output**: Rendered HTML/block markup (same as section renderer output for real build).

### 7.2 Conceptual preview payload (page template)

| Input | Description |
|-------|-------------|
| template_key | Page template internal_key. |
| section_field_values | Per section instance: section_key + position + field_values map (synthetic data per section). |
| options | Optional: reduced_motion, animation_tier override. |

**Output**: Rendered full page markup (same as page assembler + section renderers for real build).

Implementation details (API shape, caching, iframe embedding) are out of scope; the contract defines **inputs** (synthetic data), **process** (real renderer), and **output** (realistic, safe, omission- and animation-compliant).

---

## 8. Invalid dummy-data and preview patterns

The following are **invalid** and must be disallowed:

| Invalid pattern | Reason |
|-----------------|--------|
| **Real customer data** | Names, emails, addresses, phone numbers from production or real people. |
| **Real secrets** | API keys, passwords, tokens (even if placeholder-looking). |
| **Misleading fake legal** | Realistic-looking “Privacy Policy effective …” or “Terms and Conditions” with fake dates or clauses that could be copied. Use “Legal placeholder” or clearly generic text. |
| **Real business names or URLs** | External links to real sites (except explicitly allowed placeholder like example.com). |
| **Production ACF values** | Using get_field() or live post meta for preview without explicit “preview with this post” mode that is documented and permission-gated. |
| **Mock HTML** | Any preview markup not produced by the real section/page renderer. |
| **Omission bypass** | Forcing visibility of optional empty elements in preview that would be omitted in production; preview must apply same omission rules. |
| **Animation bypass** | Running full animation in preview when user has reduced-motion preference. |

---

## 9. Checklist: realism, safety, omission, animation

Use this checklist for preview implementation and QA:

**Realism**

- [ ] Preview uses **real** section/page renderer (no mock HTML).
- [ ] Synthetic data is **category-appropriate** (hero copy for hero, testimonials for proof, etc.).
- [ ] Headings, lists, and CTAs look like plausible placeholders, not lorem-only or empty.
- [ ] When an industry profile is set, section/page previews use **industry-appropriate** dummy overrides where available (see industry-preview-dummy-data-contract.md).

**Safety**

- [ ] No real customer data, secrets, or production ACF in preview input.
- [ ] No misleading fake legal/privacy text; use clearly generic placeholders.
- [ ] Preview access is **permission-gated** (same as directory).

**Omission**

- [ ] Optional empty fields in synthetic data result in **omitted** elements (same as smart-omission-rendering-contract).
- [ ] Required fields/nodes (headline when h1, primary CTA when CTA section) have values or fallbacks so they are **not** omitted.

**Animation**

- [ ] Preview respects **prefers-reduced-motion: reduce** (no decorative animation when set).
- [ ] When animation is disabled or unsupported, layout and content remain correct (tier none).

---

## 10. Example preview payloads (section)

**Hero section (minimal):**

```json
{
  "section_key": "st01_hero",
  "variant": "default",
  "field_values": {
    "headline": "Welcome to Our Service",
    "subheadline": "Supporting copy that explains the value in one or two sentences.",
    "cta_text": "Get started",
    "cta_url": "#"
  }
}
```

**Proof section (repeater):**

```json
{
  "section_key": "st02_testimonial",
  "variant": "default",
  "field_values": {
    "headline": "What Our Clients Say",
    "items": [
      { "name": "Client A", "quote": "This service made a real difference.", "role": "Customer" },
      { "name": "Jane D.", "quote": "Professional and responsive.", "role": "Client" }
    ]
  }
}
```

**CTA section:**

```json
{
  "section_key": "st_cta_signup",
  "variant": "default",
  "field_values": {
    "headline": "Ready to get started?",
    "cta_text": "Sign up now",
    "cta_url": "#"
  }
}
```

---

## 11. Cross-references

- **rendering-contract.md**: Same renderer for preview and production; durable output rules; no render callback for content.
- **smart-omission-rendering-contract.md**: Omission rules apply to preview; required nodes must have fallbacks.
- **animation-support-and-fallback-contract.md**: Animation tier and reduced-motion in preview.
- **large-scale-acf-lpagery-binding-contract.md**: Preview dummy-data source, fallbacks, preview-safe patterns (§3); token treatment.
- **semantic-seo-accessibility-extension-contract.md**: Preview output must still satisfy semantic/accessibility rules (headings, landmarks, CTA labels).
- **page-template-directory-ia-extension.md**: Preview link placement (§7.3); required metadata aligns with §4.1.
- **section-template-directory-ia-extension.md**: Preview link placement (§7.3); required metadata aligns with §4.2.
- **demo-fixture-guide.md**: Fixture data is synthetic; preview dummy data and fixture data share “no production, no secrets” policy; preview contract defines **preview-specific** fidelity and detail-screen metadata.

---

## 12. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 143 | Initial template preview and realistic dummy-data contract. |
