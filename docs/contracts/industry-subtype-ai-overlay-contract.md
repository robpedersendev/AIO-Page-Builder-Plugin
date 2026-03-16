# Industry Subtype AI Overlay Contract (Prompt 430)

**Spec**: industry-planner-input-contract.md; industry-prompt-pack-overlay-contract.md; industry-subtype-extension-contract.md.

**Status**: Contract. Defines how resolved subtype context is carried in planner input artifacts and applied in prompt-pack overlays so subtype nuance refines AI planning without bypassing validation or planner/executor separation.

---

## 1. Purpose

- Add **subtype context** to the planner input artifact (industry_context block) so downstream prompt-pack overlay and Build Plan scoring can use it.
- Add **subtype-aware prompt-pack overlay** behavior: when a valid subtype is resolved, subtype guidance (e.g. label, summary, CTA posture) refines the industry overlay. Fallback to parent-industry overlay when subtype is missing or invalid.
- Keep subtype influence **explicit** in explanation metadata and **auditable**; no secrets; structured output validation remains mandatory.

---

## 2. Planner input artifact extension

The **industry_context** block (industry-planner-input-contract.md §2) gains **optional** additive fields:

| Field | Type | Description |
|-------|------|-------------|
| **industry_subtype_key** | string | Resolved subtype key when valid (e.g. realtor_buyer_agent). Empty when no subtype or invalid. |
| **resolved_subtype_snapshot** | object | Sanitized snapshot for planning: label, summary only. Omitted when no valid subtype. |
| **subtype_bundle_refs** | array | Optional list of starter bundle keys recommended for this subtype (from subtype def or get_for_industry(primary, subtype_key)). |
| **subtype_cta_posture_ref** | string | Optional CTA posture ref from subtype definition. |
| **subtype_rule_refs** | array | Optional caution/rule refs from subtype definition. |

- All fields optional. When subtype is missing or invalid, these are omitted or empty; parent-industry context remains the base.
- **Safe failure**: Assemblers must not invent subtype data; use only resolver output. Invalid refs are omitted.

---

## 3. Subtype prompt-pack overlay

- **Industry_Subtype_Prompt_Pack_Overlay_Service**: Builds a **subtype overlay fragment** from the input artifact. Method: `get_overlay_for_artifact( array $input_artifact ): array`. Returns overlay array (schema_version, optional subtype_guidance_text, subtype_cta_priorities, etc.) or minimal empty overlay when industry_context has no valid subtype.
- **Integration**: Callers (e.g. Onboarding_Planning_Request_Orchestrator) obtain industry overlay from Industry_Prompt_Pack_Overlay_Service and subtype overlay from Industry_Subtype_Prompt_Pack_Overlay_Service; both are passed to Normalized_Prompt_Package_Builder. The builder **merges** subtype overlay after industry overlay (subtype guidance appended or overrides where defined). When subtype overlay is empty, behavior is industry-only.
- **Fallback**: When industry_subtype_key is empty or resolved_subtype_snapshot is absent, subtype overlay is empty; parent-industry overlay remains in effect.

---

## 4. Overlay merge order

1. Base prompt pack segments.
2. **Industry overlay** (Industry_Prompt_Pack_Overlay_Service).
3. **Subtype overlay** (Industry_Subtype_Prompt_Pack_Overlay_Service) when present.

Subtype overlay may append **subtype_guidance_text** (e.g. resolved_subtype_snapshot.summary) and refine **cta_priorities** when subtype_cta_posture_ref or subtype-specific priorities apply. No new placeholder is required; merge is additive.

---

## 5. Security and constraints

- No secrets or unsafe provider behavior. Subtype snapshot contains only label/summary.
- Validation failures continue to block malformed outputs. Subtype overlay does not relax structured-output validation.
- No public AI mutation surfaces. Subtype context is artifact-backed and exportable.

---

## 6. Implementation reference

- **Input artifact assembly**: Onboarding_Planning_Request_Orchestrator (or equivalent) uses Industry_Subtype_Resolver to get resolved context; adds industry_subtype_key, resolved_subtype_snapshot, and optional subtype_bundle_refs / subtype_cta_posture_ref / subtype_rule_refs to industry_context when has_valid_subtype.
- **Industry_Subtype_Prompt_Pack_Overlay_Service**: get_overlay_for_artifact( input_artifact ); reads industry_context; when industry_subtype_key and resolved_subtype_snapshot present, returns overlay fragment.
- **Normalized_Prompt_Package_Builder**: Accepts optional subtype_overlay in build options; merges after industry_overlay into system prompt or placeholder values.
