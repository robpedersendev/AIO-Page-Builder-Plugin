# Template Showcase Fixture Guide

**Document type:** QA guide for the template-focused demo and showcase seed pack (spec §56.4, §60.6, §60.7, §59.15; Prompt 201).  
**Purpose:** Populate a safe internal environment with representative section templates, page templates, compositions, compare sets, and Build Plan template recommendations for review, QA, and demos.

---

## 1. Scope

- **Generator:** `AIOPageBuilder\Domain\Fixtures\Template_Showcase_Fixture_Generator`
- **Output:** Deterministic synthetic data only. No real customer data, no secrets, no live AI or reporting calls.
- **Pathways:** Fixtures are designed for use with the real product pathways (Section/Page Templates directories, Template Compare, Compositions, Build Plan workspace). Apply fixture data via repositories or import so that demos exercise the same screens and schemas as production.

---

## 2. What the pack contains

| Domain | Content |
|--------|--------|
| **Sections** | 3 section templates: hero_intro, trust_proof, cta (keys: st_showcase_hero_01, st_showcase_trust_01, st_showcase_cta_01). |
| **Page templates** | 4 page templates: top_level (landing), hub, nested_hub, child_detail (keys: pt_showcase_landing_01, pt_showcase_hub_01, pt_showcase_nested_hub_01, pt_showcase_child_01). |
| **Compositions** | 2 compositions with CTA-rule-aware section order; source_template_ref set to showcase page templates. |
| **Build Plan recommendation items** | 2 new_page items with proposed_template_summary; 1 existing_page_change with existing_page_template_change_summary. All reference showcase template keys. |
| **Compare sets** | Suggested section_keys (3) and page_keys (3) for preloading the Template Compare screen. |

---

## 3. Usage

### 3.1 Generate only (no persistence)

```php
$gen   = new \AIOPageBuilder\Domain\Fixtures\Template_Showcase_Fixture_Generator();
$pack  = $gen->generate();
// $pack['manifest']   – version, generated_at, section_families, page_classes, compare_sets, counts
// $pack['sections']   – list of section definitions
// $pack['page_templates'] – list of page template definitions
// $pack['compositions']   – list of composition definitions
// $pack['build_plan_recommendation_items'] – list of Build Plan items with template context
// $pack['compare_sets']  – section_keys, page_keys
```

### 3.2 Via Demo_Fixture_Generator

Include the template showcase in the full demo fixture run:

```php
$gen    = new \AIOPageBuilder\Domain\Fixtures\Demo_Fixture_Generator();
$result = $gen->generate( array( 'include_template_showcase' => true ) );
$summary = $result->get_summary();
// $summary['template_showcase'] contains the full pack (manifest + sections, page_templates, compositions, build_plan_recommendation_items, compare_sets).
```

---

## 4. Applying fixtures to the environment

The generator does not persist data. To populate an environment:

1. **Sections / page templates / compositions:** Use the same registry and repository layer as production. Create or update section template, page template, and composition records from `pack['sections']`, `pack['page_templates']`, and `pack['compositions']` (with schema validation/normalization as usual).
2. **Compare list:** Optionally set user meta for a demo user: `_aio_compare_section_templates` = `pack['compare_sets']['section_keys']`, `_aio_compare_page_templates` = `pack['compare_sets']['page_keys']` (respecting MAX_COMPARE_ITEMS).
3. **Build Plan recommendations:** Use `pack['build_plan_recommendation_items']` as sample items in a Build Plan step (e.g. new_pages or existing_page_changes) for demo/QA plans.

---

## 5. Example template showcase fixture manifest payload

```json
{
  "version": "1.0",
  "generated_at": "2025-03-15T10:00:00Z",
  "section_families": ["hero_intro", "trust_proof", "cta"],
  "page_classes": ["top_level", "hub", "nested_hub", "child_detail"],
  "compare_sets": {
    "section_keys": ["st_showcase_hero_01", "st_showcase_trust_01", "st_showcase_cta_01"],
    "page_keys": ["pt_showcase_landing_01", "pt_showcase_hub_01", "pt_showcase_nested_hub_01"]
  },
  "counts": {
    "sections": 3,
    "page_templates": 4,
    "compositions": 2,
    "build_plan_recommendation_items": 3
  },
  "_synthetic": true
}
```

---

## 6. Schema authorities

- Section: `Domain\Registries\Section\Section_Schema`
- Page template: `Domain\Registries\PageTemplate\Page_Template_Schema`
- Composition: `Domain\Registries\Composition\Composition_Schema`
- Build Plan item: `Domain\BuildPlan\Schema\Build_Plan_Item_Schema`
- Template recommendation payloads: proposed_template_summary / existing_page_template_change_summary shapes used by Template Analytics and Build Plan workspace (see New_Page_Template_Recommendation_Builder, Existing_Page_Template_Change_Builder).

---

## 7. Security and demo mode

- All data is synthetic and tagged with `_synthetic`.
- No real provider calls, no reporting delivery, no secrets in the fixture output.
- Demo mode and fixture application must remain permission-controlled and internal-only.
