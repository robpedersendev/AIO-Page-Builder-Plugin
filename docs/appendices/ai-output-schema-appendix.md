# AI Output Schema Appendix

**Spec refs:** §62.5 AI Output Schema Appendix; §28 (AI output); §29 (validation); §59.8 AI Provider and Prompt Phase.

This appendix documents the machine schema for validated AI planning output. Output validation is mandatory; only normalized output may be used to generate a Build Plan. AI remains planner-only; no autonomous execution.

---

## 1. Top-level object definitions

AI planning output (e.g. build-plan-draft) is schema-target-ref specific. The plugin expects:

- **Schema version**: Declared in the response; must match a supported schema version.
- **Top-level envelope**: Contains version, steps or recommendations, and any schema-specific required fields.
- **Nullable rules**: Optional fields may be null or omitted per schema definition; required fields must be present and non-null after normalization.

---

## 2. Enums and controlled values

- **Step or action types**: Defined per schema (e.g. new_page, existing_page_update, menu_change, token_set).
- **template_category_class**: top_level | hub | nested_hub | child_detail (page-template-category-taxonomy-contract).
- **template_family**: Family slug from governed library (e.g. home, services, products); no ad-hoc values.
- **Status / outcome enums**: Per schema (e.g. success, validation_failure, repair_required).

---

## 3. Required fields (representative)

For build-plan-draft-style output:

- Schema version identifier.
- Steps or recommendations array (may be empty).
- Per-step: type, and type-specific required fields (e.g. template_key for new_page when applicable).

Recommendations must reference **template_key** and **template_family** from the governed template library; internal naming and CSS remain system-owned. CTA and composition rules (cta-sequencing-and-placement-contract) apply to recommended templates; the planner is informed via prompt-pack guidance (Prompt 210) so proposals fit the governed system.

---

## 4. Example valid payload (minimal)

```json
{
  "schema_version": "aio/build-plan-draft-v1",
  "steps": [],
  "rationale": null
}
```

---

## 5. Example invalid payload notes

- **Missing schema_version**: Validation failure.
- **template_key not in registry**: Recommendation rejected or dropped during normalization.
- **template_family or template_category_class inconsistent with library**: Flagged or normalized per product policy.
- **Prohibited or unknown top-level keys**: May be stripped or cause validation failure per schema.

---

## 6. Relationship to prompt-pack guidance

Prompt 210 adds template-family and CTA-law guidance to planning prompt packs. The AI is instructed to recommend templates that satisfy hierarchy and CTA rules; output validation still enforces schema and registry consistency. This appendix remains the authority for output shape; guidance improves input quality, not output schema relaxation.
