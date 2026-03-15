# Industry Prompt-Pack Overlay Contract

**Spec**: AI planning sections of aio-page-builder-master-spec.md; industry-planner-input-contract.md; prompt-pack and provider abstraction contracts.

**Status**: Industry-aware prompt-pack overlay layer. Active industry packs contribute planning constraints and guidance; base packs remain authoritative; structured output validation unchanged.

---

## 1. Purpose

- Let **active industry packs** contribute **planning constraints** and **guidance** to prompt-pack assembly in a controlled, versioned overlay layer.
- Preserve **base prompt packs** as authoritative; overlays are additive.
- Preserve **structured output validation** and **planner recommendation-only** behavior; no bypass.

---

## 2. Overlay object/model

The **industry prompt-pack overlay** is a structured object (schema version 1) produced by **Industry_Prompt_Pack_Overlay_Service** from input artifact industry_context and industry pack refs. It is **optional** and **versioned**.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **schema_version** | string | Yes | `1`. |
| **active_industry_key** | string | No | Primary industry key when overlay applies. |
| **required_page_families** | array | No | Page family keys the planner should favor or require when applicable. |
| **discouraged_weak_fit** | array | No | Page or section keys to discourage for this industry. |
| **cta_priorities** | array | No | CTA pattern or priority hints (e.g. book_now, contact). |
| **proof_expectations** | string | No | Short guidance on proof/social proof for this vertical. |
| **local_seo_posture** | string | No | Local-SEO stance (e.g. emphasize_local, neutral). |
| **lpagery_stance** | string | No | LPagery stance (e.g. prefer_local, neutral, defer). |
| **industry_guidance_text** | string | No | Flattened text block for injection into planning guidance or placeholder (e.g. {{industry_constraints}}). |

- All fields optional except schema_version. When no industry context or pack, overlay is empty or minimal (schema_version only).
- **Auditable**: Overlay is internal/config-driven; no raw provider behavior change. Validation failures still block malformed planning outputs.

---

## 3. Integration with prompt-pack assembly

- **Normalized_Prompt_Package_Builder** (or equivalent) may accept an **optional** industry overlay (e.g. from Industry_Prompt_Pack_Overlay_Service). When present, overlay content is merged into planning guidance or into placeholder values so that pack segments can reference e.g. `{{industry_constraints}}` or `{{industry_guidance}}`.
- **Placeholder resolution**: If the pack defines a placeholder source for industry guidance, the overlay service output (e.g. industry_guidance_text) supplies the value. When overlay is absent or empty, placeholder resolves to empty string.
- **Downstream validation**: Structured-output validation and schema requirements remain mandatory; industry overlay does not relax them.

---

## 4. Service contract

- **Industry_Prompt_Pack_Overlay_Service**: Builds overlay from input artifact (and optionally industry pack registry). Method: `get_overlay_for_artifact( array $input_artifact ): array`. Returns overlay array (schema version 1) or minimal empty overlay when industry_context missing or not ready. Safe: no throw.
- **Input**: Reads `input_artifact[Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT]` when present; uses readiness and active_industry_pack_refs to resolve pack definition and build constraints.
- **Output**: Overlay array suitable for merge into planning_guidance or placeholder values. Bounded; no secrets.

---

## 5. Required vs optional constraints

- **Required** (for overlay to be non-empty): industry_context present and readiness state at least partial; active industry pack ref resolvable. Otherwise return empty/minimal overlay.
- **Optional**: required_page_families, discouraged_weak_fit, cta_priorities, proof_expectations, local_seo_posture, lpagery_stance, industry_guidance_text—all depend on pack definition and product rules.

---

## 6. Implementation reference

- **Industry_Prompt_Pack_Overlay_Service**: get_overlay_for_artifact( input_artifact ); optional dependency on Industry_Pack_Registry.
- **Normalized_Prompt_Package_Builder**: Accept optional industry_overlay; merge into placeholder values or planning_guidance when building package.
- **industry-planner-input-contract.md**: industry_context shape and readiness.
- **industry-pack-service-map.md**: AI category and overlay service registration.
