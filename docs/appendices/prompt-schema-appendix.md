# Prompt Schema Appendix

**Spec refs:** §62.4 Prompt Schema Appendix; §26 Prompt Packs; §59.8 AI Provider and Prompt Phase.

This appendix documents the prompt-pack object structure, system prompt sections, injection manifest shape, and prompt version fields. Implementation constants: `Prompt_Pack_Schema`.

---

## 1. Prompt-pack object structure

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| internal_key | string | yes | Pack identifier (e.g. aio/build-plan-draft). |
| name | string | yes | Human-readable name. |
| version | string | yes | Semantic version (e.g. 1.0.0). |
| pack_type | string | yes | One of: planning, repair, summary, other. |
| status | string | yes | One of: active, inactive, deprecated. |
| segments | object | yes | Segment key → body or { body } (see §2). |
| schema_target_ref | string | no | Output schema ref (e.g. aio/build-plan-draft-v1). |
| repair_prompt_ref | string | no | Repair pack ref when applicable. |
| placeholder_rules | object | no | Placeholder name → { source, required?, max_length? }. |
| provider_compatibility | object | no | { supported_providers?: string[] }. |
| artifact_refs | array | no | Referenced artifact categories. |
| redaction | object | no | Redaction policy. |
| changelog | array | no | { version, note } entries. |
| deprecation | object | no | Deprecation metadata. |

---

## 2. System prompt sections (segment keys)

Segments are assembled in order. Only `system_base` is required.

| Segment key | Purpose |
|-------------|---------|
| system_base | Core system instruction (required). |
| role_framing | Role and scope. |
| safety_instructions | Safety and boundaries. |
| schema_requirements | Output schema reference. |
| normalization_expectations | Normalization rules. |
| template_family_guidance | (Prompt 210) Template taxonomy and family examples; may use `{{template_family_guidance}}`. |
| cta_law_guidance | (Prompt 210) CTA rules; may use `{{cta_law_rules}}`. |
| hierarchy_role_guidance | (Prompt 210) Hierarchy roles; may use `{{hierarchy_role_guidance}}`. |
| provider_notes | Provider-specific notes. |

User-message segments: `planning_instructions`, `site_analysis_instructions`.

---

## 3. Injection manifest shape (placeholder_rules)

Each placeholder rule may specify:

- **source**: One of profile, registry, crawl, goal, custom, planning_guidance (Prompt 210).
- **required**: boolean; if true, empty value causes assembly failure.
- **max_length**: optional character cap.

Placeholders are substituted in segment bodies. Standard placeholders: `{{profile_summary}}`, `{{crawl_summary}}`, `{{registry_summary}}`, `{{goal}}`, `{{goal_or_intent}}`. Planning-guidance placeholders (Prompt 210): `{{template_family_guidance}}`, `{{cta_law_rules}}`, `{{hierarchy_role_guidance}}`; values come from `input_artifact.planning_guidance` when present. See **template-family-planning-prompt-pack-addendum.md**.

---

## 4. Prompt version fields

- **version**: Pack semantic version; used for fixture naming and changelog.
- **schema_target_ref**: Target AI output schema; validated after provider response.
- **changelog**: Version notes for traceability (e.g. "Prompt 210: Add template-family and CTA-law guidance segments.").

---

## 5. Planning guidance content source

For planning packs, guidance text is supplied by `Prompt_Pack_Registry_Service::get_planning_guidance_content()`. Callers should set `input_artifact.planning_guidance` from that result so placeholders resolve. Schema version in guidance payload: `Prompt_Pack_Registry_Service::PLANNING_GUIDANCE_SCHEMA_VERSION`.
