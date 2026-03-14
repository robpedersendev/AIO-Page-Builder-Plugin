# Template Family and CTA Law Planning Prompt Pack Addendum

**Document type:** Addendum to prompt-pack schema and injection manifest (Prompt 210).  
**Governs:** Template-family taxonomy, hierarchy-role guidance, and CTA-law rules injection into planning prompt packs.  
**Spec refs:** §1.9.7 AI Planning Pillar; §59.8 AI Provider and Prompt Phase; §62.4 Prompt Schema Appendix; §62.5 AI Output Schema Appendix; page-template-directory-ia-extension; cta-sequencing-and-placement-contract.

**Enhancement policy:** This addendum **extends** prompt-pack assembly and input artifact packaging. AI remains planner-only; output validation remains mandatory. No autonomous execution or direct page mutation.

---

## 1. Purpose and scope

Planning prompt packs shall be able to inject:

- **Page-template class and family guidance** — taxonomy (top_level, hub, nested_hub, child_detail) and template_family examples so the AI proposes recommendations that fit the governed library.
- **CTA rule constraints** — minimum CTA counts by page class, mandatory bottom-of-page CTA, and non-adjacency of CTA sections (cta-sequencing-and-placement-contract).
- **Hierarchy-role expectations** — when to recommend top-level vs hub vs nested hub vs child/detail pages.

Content is **advisory** and **schema-bound**. Internal naming, CSS, and template contracts remain system-owned and non-renamable.

---

## 2. Planning guidance content source

The **Prompt_Pack_Registry_Service** exposes:

- **get_planning_guidance_content()** — returns a structured array suitable for inclusion in the input artifact under `planning_guidance`:
  - `template_family_guidance` (string): Summary of page-template category classes and family examples.
  - `cta_law_rules` (string): CTA minimums by class, bottom-CTA requirement, non-adjacency rule.
  - `hierarchy_role_guidance` (string): When to use each hierarchy class.
  - `schema_version` (string): Version of the guidance payload for traceability.

Artifact builders (e.g. onboarding orchestrator) should call this method and set `artifact.planning_guidance` so that placeholder resolution can substitute into pack segments.

---

## 3. Placeholder and segment extensions

### 3.1 Placeholders

Prompt packs may reference the following placeholders. Values are taken from `input_artifact.planning_guidance` when present.

| Placeholder | Source key | Description |
|-------------|------------|-------------|
| `{{template_family_guidance}}` | `template_family_guidance` | Page-template taxonomy and family examples. |
| `{{cta_law_rules}}` | `cta_law_rules` | CTA count rules, bottom-CTA, non-adjacency. |
| `{{hierarchy_role_guidance}}` | `hierarchy_role_guidance` | Hierarchy class usage expectations. |

Placeholder source is **planning_guidance**. If `planning_guidance` is missing or a key is absent, the placeholder is replaced with an empty string (no validation error).

### 3.2 Optional segment keys

Packs may include optional segments that are assembled into the system prompt when present:

- **template_family_guidance** — Segment body may contain `{{template_family_guidance}}` or static taxonomy text.
- **cta_law_guidance** — Segment body may contain `{{cta_law_rules}}` or static CTA rules.
- **hierarchy_role_guidance** — Segment body may contain `{{hierarchy_role_guidance}}`.

These segments are **optional**. Omission does not affect pack eligibility.

---

## 4. Injection manifest shape

When building the input artifact for planning, callers may pass:

```json
{
  "planning_guidance": {
    "template_family_guidance": "<text from get_planning_guidance_content>",
    "cta_law_rules": "<text from get_planning_guidance_content>",
    "hierarchy_role_guidance": "<text from get_planning_guidance_content>",
    "schema_version": "1"
  }
}
```

Placeholder rules in the pack may declare `source: "planning_guidance"` and reference the placeholder name (e.g. `template_family_guidance`). Resolution is provider-agnostic.

---

## 5. CTA law summary (authoritative: cta-sequencing-and-placement-contract)

- **Minimum CTA sections by template_category_class:** top_level 3, hub 4, nested_hub 4, child_detail 5.
- **Bottom-of-page CTA:** The last section in ordered_sections must be CTA-classified.
- **Non-adjacency:** No two CTA-classified sections may be adjacent; at least one non-CTA section between any two CTAs.
- **Non-CTA range:** Target 8–14 non-CTA sections per page (validation: below 8 = error, above 14 = warning).

---

## 6. Hierarchy role expectations

- **top_level:** Home, about, contact, key landing pages; shallow path; often in main nav.
- **hub:** Category/listing pages (e.g. services, products, locations); path depth 1–2; may be in nav.
- **nested_hub:** Sub-category or regional hub; path depth 2; may or may not be in nav.
- **child_detail:** Individual item or detail page (product, location, profile); path depth 3+ or slug suggests detail.

---

## 7. Schema versioning and traceability

- Guidance payload includes `schema_version` so prompt-pack regression and logs can record which guidance version was used.
- Prompt pack changelog entries should reference this addendum when adding template-family or CTA-law segments/placeholders.

---

## 8. Example template-family-aware prompt-pack metadata payload

```json
{
  "internal_key": "aio/build-plan-draft",
  "name": "Build Plan Draft",
  "version": "1.1.0",
  "pack_type": "planning",
  "status": "active",
  "schema_target_ref": "aio/build-plan-draft-v1",
  "segments": {
    "system_base": "You are a site planning assistant. Output valid JSON per schema.",
    "template_family_guidance": "Page template taxonomy and hierarchy:\n{{template_family_guidance}}",
    "cta_law_guidance": "CTA rules (must be satisfied by recommended templates):\n{{cta_law_rules}}",
    "hierarchy_role_guidance": "Hierarchy roles:\n{{hierarchy_role_guidance}}",
    "planning_instructions": "Use the context. Recommend templates that match hierarchy and satisfy CTA rules."
  },
  "placeholder_rules": {
    "template_family_guidance": { "source": "planning_guidance", "required": false },
    "cta_law_rules": { "source": "planning_guidance", "required": false },
    "hierarchy_role_guidance": { "source": "planning_guidance", "required": false }
  },
  "changelog": [
    { "version": "1.1.0", "note": "Prompt 210: Add template-family and CTA-law guidance segments and placeholders." }
  ]
}
```
