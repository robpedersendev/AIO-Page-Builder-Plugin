# Rendering Contract

**Spec**: §7.5 Native Blocks Strategy; §7.6 Render Callback Usage Strategy; §17 Rendering Architecture; §17.1–17.11; §18 CSS Contract; §19 Design Token Engine; §59.5 Rendering and ACF Phase

**Upstream Authorities**: Section_Schema, Page_Template_Schema, Composition_Schema (see plugin src/Domain/Registries/), registry-export-basics-contract.md

**Extensions**: semantic-seo-accessibility-extension-contract.md (semantic HTML, heading hierarchy, landmarks, CTA/link/image rules, preview/QA); animation-support-and-fallback-contract.md (animation tiers, families, reduced-motion, fallbacks; animation is optional progressive enhancement).

**Status**: Contract definition only; no renderer implementation.

---

## 1. Purpose

This contract defines how section templates, page templates, and compositions become durable page content. It formalizes rendering inputs, outputs, normalization steps, durable-versus-runtime output boundaries, render-callback restrictions, and survivability expectations. Implementation must follow this contract without reinterpretation.

---

## 2. Rendering Inputs

Rendering consumes these upstream objects. Each is authoritative per its schema.

### 2.1 Input Object: Page Template

| Key | Authority | Description |
|-----|------------|-------------|
| `internal_key` | Page_Template_Schema | Stable template identifier |
| `ordered_sections` | Page_Template_Schema | Ordered list of section references |
| `section_requirements` | Page_Template_Schema | Required vs optional per section |
| `archetype` | Page_Template_Schema | Template category/type |
| `default_structural_assumptions` | Page_Template_Schema | Structural defaults |
| `status` | Page_Template_Schema | draft \| active \| inactive \| deprecated |

**Section item shape** (per `ordered_sections`):

- `section_key` (required): Section template `internal_key`
- `position` (optional): Zero-based order
- `required` (optional): Boolean

### 2.2 Input Object: Composition

| Key | Authority | Description |
|-----|------------|-------------|
| `composition_id` | Composition_Schema | Unique composition identifier |
| `ordered_section_list` | Composition_Schema | Ordered list of section items |
| `status` | Composition_Schema | draft \| active \| archived |
| `validation_status` | Composition_Schema | Validation result |
| `source_template_ref` | Composition_Schema | Optional page template derivation |

**Section item shape** (per `ordered_section_list`):

- `section_key` (required): Section template `internal_key`
- `position` (optional): Zero-based order
- `variant` (optional): Section variant key

### 2.3 Input Object: Section Template Definition

| Key | Authority | Description |
|-----|------------|-------------|
| `internal_key` | Section_Schema | Stable section identifier |
| `field_blueprint` | Section_Field_Blueprint_Service | ACF field definitions (embedded) |
| `variants` | Section_Schema | Variant map (default, etc.) |
| `default_variant` | Section_Schema | Default variant key |
| `status` | Section_Schema | draft \| active \| inactive \| deprecated |
| `structural_blueprint_ref` | Section_Schema | Structural layout reference |
| `field_blueprint_ref` | Section_Schema | Field blueprint reference |
| `css_contract_ref` | Section_Schema | CSS contract manifest reference |
| `render_mode` | Section_Schema | Render mode classification |

### 2.4 Input Object: Field Data

Field data comes from ACF post meta for the page, keyed by section and field. It is the runtime value for each field on the page instance.

| Aspect | Description |
|--------|-------------|
| Source | ACF field groups assigned to the page via assignment map |
| Keying | `group_aio_{section_key}` → field values by field name |
| Scope | Per-page; one value set per section instance |

### 2.5 Input Object: Tokens

| Token Type | Description | Rendering Role |
|------------|-------------|----------------|
| LPagery tokens | `{{token_name}}` placeholders for location/landing workflows | Replace with value before or during block serialization |
| Design tokens | Color, typography, spacing values from brand profile | Injected via CSS or inline styles per CSS contract |
| Token compatibility | Per Field_Blueprint_Schema LPagery-supported types | Only token-compatible field types may receive tokenized values |

---

## 3. Normalized Render-Ready Structures

Before block serialization, inputs are normalized into intermediate structures.

### 3.1 Render-Ready Section Instance

| Key | Type | Description |
|-----|------|-------------|
| `section_key` | string | Section internal_key |
| `variant` | string | Resolved variant key (default or per-position) |
| `position` | int | Zero-based order on page |
| `field_values` | object | Map of field_name → value (resolved, tokens applied) |
| `wrapper_attrs` | object | class, id, data-* per CSS contract |
| `structural_hint` | string | Optional structural blueprint ref |

### 3.2 Render-Ready Page Structure

| Key | Type | Description |
|-----|------|-------------|
| `source_type` | enum | `page_template` \| `composition` |
| `source_ref` | string | Template internal_key or composition_id |
| `ordered_instances` | array | Ordered list of Render-Ready Section Instances |
| `page_wrapper_attrs` | object | Page-level class, id per contract |
| `structural_metadata` | object | Template/plan provenance for traceability |

### 3.3 Normalization Steps

1. Resolve structural source: page template or composition.
2. Resolve ordered section list from source.
3. For each section ref: load section definition, resolve variant, load field values from page ACF meta.
4. Apply token replacement to field values where LPagery tokens exist.
5. Resolve wrapper attributes (class, id) per section and page CSS contract.
6. Produce ordered list of Render-Ready Section Instances.
7. Produce Render-Ready Page Structure.

---

## 4. Durable Output Rules vs Justified Runtime-Only Output

### 4.1 Durable Output (Saved to post_content)

Content that must be saved and survive plugin deactivation.

| Output Class | Storage | Survivability | Rationale |
|--------------|---------|---------------|-----------|
| Block markup | post_content | Full | Native WordPress storage; editable in block editor |
| Section wrappers | post_content | Full | Structural HTML; no plugin dependency |
| Injected field values | post_content | Full | User-authored content; must persist |
| Classes and IDs | post_content | Full | Part of structural contract; theme-compatible |
| Design token values (CSS) | post_content or theme | Full | Inline styles or external CSS; no runtime eval |

### 4.2 Justified Runtime-Only Output (Render Callbacks)

Output that may use render callbacks only when explicitly justified.

| Output Class | Allowed | Condition |
|--------------|---------|-----------|
| System state display | Yes | Utility blocks (e.g., plan status, diagnostics) |
| Dynamic lists/references | Yes | Intentionally plugin-driven; narrow scope |
| Admin-supportive UI | Yes | Plan/editor context only; not public page |
| Core page content | No | Must be durable; never callback for primary content |
| Section instance markup | No | Must be serialized into post_content |
| Field-display markup | No | Must be saved as blocks |

### 4.3 Plugin-Dependent vs Durable Boundary

| Boundary | Durable Side | Plugin-Dependent Side |
|----------|--------------|------------------------|
| Page structure | Block serialization in post_content | Assembly orchestration (build-time only) |
| Section markup | Saved HTML/block output | Section definition resolution (build-time only) |
| Field display | Saved content from ACF values | ACF meta read (build-time only) |
| Enhancements | None required for survival | Optional plugin assets, hooks |
| Diagnostics/plan UI | N/A | Admin-only; never on public page |

---

## 5. Render Callback Decision Matrix

### 5.1 Primary Matrix: Use Render Callback?

| Scenario | Use Render Callback? | Reason |
|----------|---------------------|--------|
| Section instance on public page | No | Must be durable block output |
| Field value display on public page | No | Must be saved in post_content |
| Plan status in admin UI | Yes | Utility; admin context |
| Build progress indicator | Yes | Operational; admin context |
| Block that shows "last built" meta | Yes | Narrow runtime behavior; optional enhancement |
| Hero headline from ACF | No | Core content; save as block |
| FAQ repeater content | No | Core content; save as block |
| Navigation recommendation block | Maybe | Only if intentionally plugin-dependent and documented |
| Design token CSS variables | No | Output to stylesheet or inline; not callback |
| LPagery token replacement | No | Done at build time before save |

### 5.2 Secondary Matrix: When Static Output Is Required

| Content Type | Static Required? | Fallback if Callback Used |
|--------------|-----------------|----------------------------|
| Page body structure | Yes | Page becomes empty or broken |
| Section wrappers | Yes | Structure lost |
| Headlines, copy, media | Yes | Content lost |
| CTA links | Yes | Links lost |
| Repeater/group fields | Yes | Repeater content lost |
| Admin diagnostics | N/A | Callback acceptable |
| Optional "powered by" footer | No | Can be plugin-dependent if documented |

---

## 6. Section Rendering Responsibilities vs Page Assembly Responsibilities

### 6.1 Section Rendering Responsibilities

The section renderer is responsible for:

| Responsibility | Description |
|----------------|-------------|
| Wrapper output | Emit outer element (e.g., section) with contract classes/IDs |
| Variant handling | Apply variant-specific markup or attributes |
| Field-to-markup mapping | Map each field value to appropriate block or HTML |
| Token application | Replace LPagery tokens in values before output |
| Class and ID assignment | Per section CSS contract |
| Omission logic | Skip optional empty fields per section rules |
| Accessibility | Apply ARIA, semantics per structural blueprint and **semantic-seo-accessibility-extension-contract.md** (heading hierarchy, landmarks, CTA/link/image/list/form rules) |

The section renderer must **not**:

- Depend on runtime plugin execution for front-end display
- Use render callbacks for section instance output
- Emit content that cannot be stored in post_content

### 6.2 Page Assembly Responsibilities

The page assembler is responsible for:

| Responsibility | Description |
|-----------------|-------------|
| Source resolution | Determine page template or composition |
| Section sequence | Enforce ordered_sections / ordered_section_list |
| Instance creation | Produce Render-Ready Section Instance per position |
| Block concatenation | Serialize section outputs into single block stream |
| Page-level wrapper | Add page wrapper where applicable |
| Metadata recording | Record template ref, plan ref, orchestration metadata |
| ACF assignment derivation | Ensure field groups assigned per section set |

The page assembler must **not**:

- Modify section definitions during assembly
- Introduce content that bypasses section rendering
- Store assembly logic as runtime dependency for display

### 6.3 Section-to-Block Translation

| Step | Owner | Output |
|------|-------|--------|
| 1. Section definition + variant + field values | Assembler | Render-Ready Section Instance |
| 2. Instance → block(s) | Section renderer | One or more native blocks (e.g., core/paragraph, GenerateBlocks) |
| 3. Block serialization | Section renderer | Block comment delimiters + inner HTML |
| 4. Concatenate blocks | Assembler | Full post_content block stream |
| 5. Save to post | Assembler | Persisted post_content |

---

## 7. Page Instantiation Expectations

### 7.1 Instantiation Process

When a page is created or rebuilt from a template or composition:

| Phase | Actions |
|-------|---------|
| Target determination | Create new page or replace existing per workflow |
| Template selection | Record source_ref (template key or composition_id) |
| Section sequence realization | Resolve ordered sections; validate against registry |
| Field initialization | Apply default or AI-generated values to ACF fields |
| ACF assignment | Derive and assign field groups per Field_Group_Derivation_Service |
| Rendering | Produce Render-Ready Page Structure → block serialization → post_content |
| Metadata recording | one_pager ref, plan ref, AI provenance where relevant |
| Orchestration record | Traceable execution record |

### 7.2 Instantiation Output

| Output | Storage | Purpose |
|--------|---------|---------|
| post_content | wp_posts | Block markup; durable |
| ACF meta | postmeta | Field values; durable |
| Assignment rows | aio_assignment_maps | Template/composition/group refs; plugin-owned |
| Orchestration meta | postmeta (plugin-scoped) | Traceability; optional for survivability |

---

## 8. Survivability Expectations

### 8.1 After Plugin Deactivation

| Content | Must Survive? | Notes |
|---------|---------------|-------|
| post_content (blocks) | Yes | Core WordPress; fully editable |
| ACF field values | Yes | ACF meta; survives if ACF plugin remains |
| Page structure | Yes | Blocks are standard; no plugin dependency |
| Section markup | Yes | Saved as HTML/blocks |
| Classes, IDs | Yes | Part of saved markup |
| Plugin admin UI | N/A | Not applicable; admin only |
| Assignment map rows | No | Plugin operational data |
| Orchestration metadata | Optional | Traceability; may be plugin-scoped |

### 8.2 After Plugin Uninstall

| Content | Preserved? | Per PORTABILITY_AND_UNINSTALL |
|---------|------------|-------------------------------|
| Built pages (post_content) | Yes | User content preserved |
| ACF meta (if ACF present) | Yes | Third-party plugin data |
| Custom tables (assignment map) | Removed | Plugin-owned operational data |
| Options, transients | Removed | Plugin operational data |

### 8.3 Survivability Criteria

A built page is **meaningful** after plugin deactivation when:

1. post_content contains valid block markup.
2. Visible structure (sections, headings, copy, media, links) is present and readable.
3. No critical content requires plugin execution to display.
4. A human can edit the page in the block editor without errors.
5. Front-end HTML renders without plugin PHP (assets may degrade gracefully).

### 8.4 Survivability Test Scenarios

| Scenario | Expected Result | Acceptance |
|----------|-----------------|------------|
| Deactivate plugin, view built page | Page displays; structure intact | Pass |
| Deactivate plugin, edit page in block editor | Editable; no fatal errors | Pass |
| Uninstall plugin, view built page | Page displays; content preserved | Pass |
| Built page uses only core blocks | Full compatibility | Pass |
| Built page uses GenerateBlocks | Survives if GenerateBlocks active; otherwise blocks may show as unrecognized but content present | Acceptable |
| Section with render callback for content | Fails survivability | Must not occur |

---

## 9. Static vs Dynamic Boundary Summary

| Aspect | Static (Preferred) | Dynamic (Justified Only) |
|--------|-------------------|--------------------------|
| Section output | Saved in post_content | Never for section content |
| Field display | Saved in blocks | Never for core content |
| Page structure | Saved block stream | Never |
| Utility blocks | N/A | Admin/plan display only |
| Token replacement | At build time | Not at render time for content |
| Design tokens | Output to CSS/stylesheet | Not via callback |

**Default**: Static. **Dynamic**: Must be justified and documented.

---

## 10. Security and Permission Constraints

| Constraint | Description |
|------------|-------------|
| No privileged data | Rendering must not expose secrets, tokens, API keys, or internal IDs |
| Server-side only | Rendering execution is server-side; authorized context |
| No callback backdoors | Render callbacks must not execute privileged logic or bypass capability checks |
| Sanitize on output | All user/field content escaped per WordPress standards |
| Redact in logs | No sensitive values in rendering logs |

---

## 11. Cross-References

- **Section_Schema**: `plugin/src/Domain/Registries/Section/Section_Schema.php`
- **Page_Template_Schema**: `plugin/src/Domain/Registries/PageTemplate/Page_Template_Schema.php`
- **Composition_Schema**: `plugin/src/Domain/Registries/Composition/Composition_Schema.php`
- **Field_Blueprint_Schema**: `plugin/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php`
- **registry-export-basics-contract.md**: Export fragment shapes; composition ordered_section_list
- **PORTABILITY_AND_UNINSTALL.md**: Survivability and uninstall policy
- **semantic-seo-accessibility-extension-contract.md**: Semantic HTML, heading hierarchy, landmarks, link/button/image/list/form rules, and preview/QA expectations for generated templates
- **animation-support-and-fallback-contract.md**: Animation tiers, families, reduced-motion, and fallbacks; templates must render correctly without animation (progressive enhancement)

---

## 12. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 041 | Initial contract definition |
| 2 | Prompt 137 | Cross-reference to semantic-seo-accessibility-extension-contract; section renderer accessibility responsibility extended. |
| 3 | Prompt 138 | Cross-reference to animation-support-and-fallback-contract; animation as optional progressive enhancement. |
