# Large-Scale ACF and LPagery Binding Contract

**Spec**: §20 Field Governance Architecture; §20.6 Field Requirement Rules; §20.8 Repeater and Flexible Field Usage; §21.5 Token Naming Rules; §21.6 Token Injection Rules; §21.9 Validation and Fallback Rules; §59.5 Rendering and ACF Phase

**Upstream**: rendering-contract.md, smart-omission-rendering-contract.md, section-template-category-taxonomy-contract.md (variation_family_key), template-library-scale-extension-contract.md; Field_Blueprint_Schema / ACF field-group derivation

**Status**: Contract definition only. No mass blueprint implementation; no token runtime expansion engine changes; no admin preview UI implementation; no mass field registration refactor. This contract defines **scaling rules** so a 250-section / 500-page library remains manageable, deterministic, and previewable. Section templates still own their field logic; deterministic field naming remains mandatory; LPagery remains optional and bounded. **Preview data must be synthetic and safe**—no secret-bearing fixtures.

---

## 1. Purpose and scope

This contract extends ACF and LPagery contracts for **large-scale template library** operation. It defines:

- How field blueprints **scale** across many related section variants (reuse vs fork).
- **Dummy-data** requirements for preview and visual directory use.
- **Token-compatible** field archetypes and exclusions.
- **Preview-safe** fallback content patterns.
- **Visibility and registration** discipline at 250+ sections / 500+ page templates.
- **Blueprint-family** and **LPagery-safe** mapping rules for large library families.

**Out of scope**: Mass blueprint implementation; token runtime engine changes; admin preview UI; mass field registration refactor. Large scale must **not** justify loose field naming or undocumented blueprint cloning; reuse and preview logic remain **explicit and contract-driven**.

---

## 2. Blueprint scaling across section variants

### 2.1 Section-owned field logic

Each **section template** owns its field logic. Field groups are keyed by section: `group_aio_{section_key}` (rendering-contract §2.4). At scale:

- **Determinism**: Field names and group keys must follow a **fixed naming policy** (see ACF key-naming contract when present). No ad-hoc or duplicate key patterns.
- **One blueprint per section template**: Each section template has one field blueprint (or one primary blueprint with variant overlays per §2.3). Blueprint identity is tied to `internal_key` / `section_key`.
- **Variation families**: Sections that share a **variation_family_key** (section-template-category-taxonomy-contract §8) may share **field structure** (reuse) or extend it (variant layering); they must **not** silently clone with different keys.

### 2.2 Reuse versus fork rules for ACF blueprints

| Approach | When to use | Rule |
|----------|-------------|------|
| **Reuse** | Section variants share the **same field set** (same fields, same keys, same required/optional). | One blueprint definition; multiple section templates reference the **same** blueprint (e.g. same `field_blueprint_ref`). Field group registration key remains `group_aio_{section_key}` per section instance so each section instance has its own meta scope; blueprint **content** (field names, types, layout) is shared. |
| **Extend / variant layering** | Variants add or hide fields (e.g. “hero with CTA” vs “hero without CTA”). | Blueprint may declare **variant-specific** field subsets or conditional visibility. Extension must be **documented** and **schema-consistent**; no undocumented fork that duplicates the same logical field under a different name. |
| **Fork (new blueprint)** | Section template has a **materially different** purpose or structure (different section type, not just a variant). | New section_key → new blueprint. Fork must have distinct `internal_key` and purpose; fork is **not** used to create duplicate sections that only differ by label. |

**Invalid**: Creating 50 “hero” sections each with its own blueprint that is a copy of the same field set under 50 different keys without a shared blueprint ref or variation_family strategy. **Valid**: One hero blueprint (or one per variation_family_key) reused by many section templates that reference it; or explicit variant layering with documented conditional fields.

### 2.3 Variant layering rules

When section templates share a **variation_family_key** and differ by variant (e.g. compact, media-left):

| Rule | Requirement |
|------|-------------|
| **Shared base** | Core fields (headline, intro, CTA, etc.) share the same field names and types across variants so that token maps and assignment logic can target one family. |
| **Variant-only fields** | Variant-specific fields (e.g. “show_sidebar”) may be added in the blueprint with **conditional logic** or **variant key** in the schema; they must be documented in the blueprint manifest. |
| **No name collision** | Variant layering must not introduce two different fields with the same name and different semantics. |
| **Registration** | Field group registration remains per section_key; assignment (which groups appear on which page) is per page template / composition and section set (page-level visibility assignment). |

---

## 3. Preview dummy-data requirements

### 3.1 Purpose of preview data

Preview data is used to:

- Render **section and page-template previews** in admin or directory (e.g. visual directory, section picker).
- Provide **realistic placeholder** content so layout and structure are visible without real page data.
- Support **QA and regression** of rendering and omission logic with known inputs.

Preview data must **not** become live content, must not contain secrets, and must be **synthetic and safe**.

### 3.2 Dummy-data source rules

| Rule | Requirement |
|------|-------------|
| **Source** | Dummy data is supplied by **blueprint or section manifest** (default/preview values) or by a **curated preview-data store** (e.g. per section_key or per variation_family_key). It is **not** sourced from production user content or secrets. |
| **Schema** | Preview values conform to **field type** (text → string, image → attachment ID or URL placeholder, repeater → array of N items with subfield shapes). Schema is documented so preview renderers know the shape. |
| **Token placeholders** | For token-compatible fields, preview data may use **literal placeholder text** (e.g. “Location Name”) or **token syntax** (e.g. `{{location_name}}`) that is either resolved from a preview token map or displayed as placeholder. Preview must **not** require live LPagery token resolution for directory display unless explicitly designed. |
| **Fallback** | If no preview data is defined for a section, the renderer uses **preview-safe fallback** per §4 (generic labels, no real URLs/secrets). |

### 3.3 Preview-safe fallback content patterns

When a field has no preview value and no default:

| Field type | Preview-safe fallback | Must not use |
|------------|------------------------|--------------|
| Text / headline | “Heading”, “Section title”, or blueprint placeholder label | Real business names, real user data |
| Text / body | “Body copy for this section.” or short lorem | Secrets, API keys, real PII |
| Image | Placeholder image ref (system or blueprint-defined) or omit | Real user uploads, external URLs that could leak |
| Link/URL | “#” or “” or “https://example.com” (clearly placeholder) | Real site URLs that could be followed, credentials |
| Repeater | Empty array `[]` or one row with fallback subfield values | Production data |
| Tokenized field | Placeholder label (e.g. “{{token}}”) or safe literal | Live token expansion with production data |

**Rule**: Preview data and fallbacks must be **synthetic and safe**. No secret-bearing defaults; no real user content in preview fixtures.

### 3.4 Preview dummy-data examples (valid)

- **Hero section**: headline = “Hero Headline”, subheadline = “Supporting copy here.”, cta_text = “Get started”, cta_url = “#”.
- **Proof section**: repeater with 2 rows: name = “Client A”, quote = “Testimonial text.”; name = “Client B”, quote = “Another quote.”
- **Token-compatible location field**: preview value = “Main Street” or “{{location_name}}” (displayed as placeholder if no token map).

### 3.5 Invalid preview patterns (disallowed)

- Using production database content (real post titles, real user meta) as preview source without explicit sanitization and preview-only scope.
- Storing or shipping secrets, API keys, or real credentials in preview defaults.
- Preview URLs that point to real internal or external sensitive endpoints.
- Duplicate or inconsistent preview shapes for the same section_key across environments without documentation.

---

## 4. Token-compatible field archetypes and exclusions

### 4.1 Token compatibility (Spec §21.5, §21.6)

Tokenized values enter through **supported fields** only; field-level token handling preserves structural integrity; token values are content inputs, not markup instructions; tokenized values must not break class, ID, attribute, or wrapper contracts (rendering-contract, css-selector-contract). Injection is **governed and server-authoritative**; token mappings are **validated**.

### 4.2 Token-compatible field archetypes

Fields that **may** support LPagery token injection (per Field_Blueprint_Schema / token map) must be of types that accept **string or simple value** replacement:

| Field archetype | Token compatible? | Notes |
|-----------------|--------------------|--------|
| text | Yes | Primary candidate; token replaces string. |
| textarea | Yes | Same. |
| url / link (URL part) | Yes | Token may supply URL. |
| WYSIWYG | Conditional | Only if token inserts **content**, not markup; must be escaped. Risk of markup injection; document if allowed. |
| image | Conditional | Token may supply attachment ID or URL in supported workflows; document expected token shape. |
| select / relationship | Conditional | Token may supply selected value/key where map is defined. |
| repeater | No (at container level) | Token does not replace repeater structure; subfields may be token-compatible per row. |
| group | No (at container level) | Subfields may be token-compatible. |
| true_false / checkbox | No | Boolean; not a string token target. |
| file / gallery | Conditional | Per implementation; token may supply ID(s). |

### 4.3 Token-field archetype matrix (summary)

| Field type | Token-compatible | LPagery-safe mapping |
|------------|------------------|----------------------|
| text, textarea | Yes | Token name documented; value injected at build time; escaped on output. |
| url | Yes | Token → href or link URL; validated and escaped. |
| WYSIWYG | With care | Token → content only; no raw HTML from token; sanitized. |
| image | Per token map | Token → attachment ID or URL; fallback when missing. |
| repeater (subfields) | Per subfield | Subfield text/url may be tokenized; repeater structure fixed. |
| group (subfields) | Per subfield | Same. |
| select/relationship | Per map | Token value must match allowed option; validated. |

### 4.4 Exclusions (must not tokenize)

- **Structural or system fields**: Class names, IDs, data attributes that are part of css-selector-contract or rendering-contract must **not** be driven by LPagery tokens (Spec §21.8; tokens are content inputs, not markup/structural contracts).
- **Secret or credential fields**: No token mapping for passwords, API keys, or secrets.
- **Unvalidated freeform**: Fields that would accept arbitrary markup or script must not receive token injection without strict sanitization and documentation.

---

## 5. LPagery-safe mappings for large library families

### 5.1 Token naming (Spec §21.5)

Token names and references must be **deterministic**, **human-understandable**, and **documented** per token-compatible field. At scale:

- **Stable names**: Token reference style must be consistent with LPagery’s expected style (e.g. `{{location_name}}`, `{{service_title}}`). Avoid ambiguous aliases.
- **Per-field documentation**: Each token-compatible field (or field archetype) documents the **expected token name(s)** and fallback behavior when token is missing (§21.9).
- **Family-wide consistency**: Within a **variation_family_key** or **section_purpose_family**, token mappings should be **consistent** so bulk generation and token maps can target families without per-section guesswork.

### 5.2 Large-library family mapping rules

| Rule | Requirement |
|------|-------------|
| **Token map per section or family** | Token maps may be defined per section_key or per variation_family_key. When many sections share a family, one **family-level** token map can define default mappings; section-level overrides allowed but must be documented. |
| **Validation at scale** | Required token availability and fallback behavior (§21.9) must be validated at **build/generation time**. Missing required token with no fallback → clear failure state; no silent broken output. |
| **Bulk generation** | §21.7: page-template suitability, field-level token availability, consistency of output, prevention of structurally invalid pages when token data is incomplete. Large-scale contract does not change these; it ensures **registration and visibility** (which groups are on which pages) remain deterministic so token-fed fields are known. |

### 5.3 Invalid scaling patterns (disallowed)

- **Loose field naming**: Different sections using different key names for the same logical field (e.g. `headline` vs `title` vs `heading`) without a shared blueprint or documented alias. Breaks token map reuse and determinism.
- **Undocumented blueprint cloning**: Copying a blueprint to a new section_key with no reuse ref or variation_family_key and no documentation. Creates maintenance and token-map drift.
- **Preview data from production**: Using live user or post data as preview source without explicit preview-only path and safety rules.
- **Token injection into structural attributes**: Using tokens to set class, id, or data-* that are part of the structural contract. Forbidden by Spec §21.8.
- **Unbounded token maps**: Adding token mappings without validation or fallback rules; or allowing arbitrary token names per field without documentation.

---

## 6. Field-inventory and registration-scaling discipline

### 6.1 Field-inventory discipline

At 250+ sections and 500+ page templates:

| Discipline | Requirement |
|------------|-------------|
| **Blueprint inventory** | Blueprints (or blueprint refs) are **enumerable** and tied to section_key. No orphan or duplicate blueprint identity for the same logical section. |
| **Field naming registry** | Field names within a blueprint follow **fixed naming policy** (ACF key-naming contract). Cross-section reuse (same field name, same type) is preferred for shared purposes (e.g. `headline`, `cta_text`, `cta_url`) so token maps and helpers can reference by name. |
| **Required vs optional** | Per §20.6 and smart-omission-rendering-contract: required and optional fields are **explicit** in the blueprint. At scale, omission and validation depend on this. |
| **Documentation** | Each blueprint or blueprint family documents: field list, token-compatible fields (if any), preview default source, variant layering (if any). |

### 6.2 Page-level visibility assignment (registration at scale)

Which ACF field groups are **assigned** to which page is derived from the **page template or composition** (ordered sections). Rendering-contract §2.4: keying `group_aio_{section_key}` per section on the page.

| Rule | Requirement |
|------|-------------|
| **Derivation** | Assignment is **derived** from template/composition section list, not stored per-page in an unbounded way. So 500 page templates imply a **bounded** set of section_keys; each page instance gets only the groups for **its** sections. |
| **Performance** | Registration and assignment logic must **not** load or register all 250 section groups on every page. Only groups for sections **on that page** are assigned. Lazy or on-demand assignment per page build is acceptable and recommended. |
| **Determinism** | Given the same template and section list, assignment is **deterministic**. No random or environment-dependent assignment. |

### 6.3 Registration-scaling notes

- **Group registration**: ACF group registration (PHP) may be **lazy**: register a group when first needed for a page that uses that section, or register all known groups at load from a registry. Either way, **naming** is deterministic and **key** is `group_aio_{section_key}`.
- **Preview context**: Preview (admin/directory) may need a **subset** of groups or **synthetic** field values without full registration of all 250 sections. Preview dummy-data and fallbacks (§3, §4) support this.
- **Export/import**: At scale, export/import of templates and blueprints must remain **documented and versioned**; large-scale contract does not change export format but recommends that blueprint refs and variation_family_key are included so re-use and family mapping are restorable.

---

## 7. Blueprint-family examples (valid)

- **Hero family**: One blueprint “hero_primary” (or ref) with fields `eyebrow`, `headline`, `subheadline`, `media`, `cta_text`, `cta_url`. Sections `st01_hero`, `st01_hero_compact`, `st01_hero_media_left` share this blueprint (same field_blueprint_ref) and use variant layering for layout; `variation_family_key` = `hero_primary`. Token map for family: `headline` → `{{page_title}}` or `{{hero_headline}}`, `cta_url` → `{{cta_url}}`.
- **Proof family**: One blueprint “proof_cards” with repeater `items` (name, quote, image). Sections `st02_testimonial`, `st02_testimonial_compact` share blueprint; token map optional for `items[].quote` if LPagery supports repeater token.
- **CTA family**: Blueprint “cta_primary” with `headline`, `cta_text`, `cta_url`. All CTA-classified sections in the family reference it; token map: `cta_url` → `{{primary_cta_url}}`, fallback `#`.

---

## 8. Token-compatible field examples (valid)

- **headline** (text): token `{{location_name}}` or `{{service_title}}`; fallback “Heading”.
- **cta_url** (url): token `{{booking_url}}`; fallback `#` or site home.
- **intro** (textarea): token `{{location_intro}}`; fallback “Copy for this section.” (preview-safe).

---

## 9. Preview dummy-data examples (valid)

- **Section st01_hero**: `headline` = “Hero Headline”, `subheadline` = “Supporting text.”, `cta_text` = “Get started”, `cta_url` = “#”, `media` = placeholder image ID or omit.
- **Section st05_faq**: repeater `faq_items` = [ { question: “Example question?”, answer: “Example answer.” }, { question: “Another?”, answer: “Yes.” } ].
- **Tokenized field in preview**: `headline` = “{{location_name}}” shown as literal or resolved from preview token map with value “Sample Location”.

---

## 10. Invalid scaling patterns (must be disallowed)

- **50 hero blueprints with identical structure** under 50 different section_keys with no shared ref or variation_family_key.
- **Field name `title` in one section and `headline` in another** for the same logical role without shared blueprint or documented alias.
- **Preview data from `get_posts()` or user meta** without explicit preview-only scope and no secrets.
- **Token mapping for `class` or `id`** on section wrapper.
- **Registering all 250 groups on every admin page load** when only a subset is needed for current view.
- **Optional fallback for required token-fed field** that silently leaves content empty without failure state (§21.9).

---

## 11. Security and permission

| Requirement | Rule |
|-------------|------|
| Preview data | Synthetic and safe; no secret-bearing defaults; no real credentials in fixtures. |
| Token mappings | Validated and server-authoritative; no client-side or user-supplied token key injection that could bypass validation. |
| Escaping | Token-injected values are escaped on output per field type; no raw markup from tokens unless explicitly allowed and sanitized. |

---

## 12. Cross-references

- **rendering-contract.md**: Field data keying `group_aio_{section_key}`; token replacement at build time; Field_Blueprint_Schema.
- **smart-omission-rendering-contract.md**: Required vs optional fields; omission eligibility; blueprint requiredness.
- **section-template-category-taxonomy-contract.md**: variation_family_key; section_purpose_family; taxonomy for grouping.
- **template-library-scale-extension-contract.md**: 250 section / 500 page targets; variation philosophy; scale-governance.
- **acf-key-naming-contract.md**: Fixed naming policy for field keys and group keys; large-scale contract §6.1 and §5.3 require deterministic naming.
- **acf-page-visibility-contract.md**: Page-level assignment derivation and visibility rules; large-scale contract §6.2–6.3.
- **acf-field-blueprint-schema.md** (docs/schemas): Blueprint schema; required/optional; token-compatible field metadata; stub references this contract.

---

## 13. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 140 | Initial large-scale ACF and LPagery binding contract. |
