# Subtype + Goal Starter Bundle Overlay Schema (Prompt 551)

**Spec:** subtype-goal-starter-bundle-contract.md; industry-starter-bundle-schema.md.

**Status:** Schema for combined subtype+goal starter bundle overlay objects. Additive; used only when admission criteria are met and overlay is in the bounded allowlist/registry.

---

## 1. Object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **overlay_key** | string | Yes | Stable unique key; pattern `[a-z0-9_-]+`; max 64. |
| **subtype_key** | string | Yes | Industry subtype key; must match a valid subtype in Industry_Subtype_Registry (parent_industry_key implied by bundle context). Max 64. |
| **goal_key** | string | Yes | Conversion goal key (calls, bookings, estimates, consultations, valuations, lead_capture). |
| **target_bundle_ref** | string | No | When set, overlay applies only to this bundle key; empty = any bundle in scope. Max 64. |
| **allowed_override_regions** | list&lt;string&gt; | Yes | Only these regions may be refined. Allowed values: `section_emphasis`, `cta_posture`, `funnel_shape`, `page_family_emphasis`. |
| **section_emphasis** | list&lt;string&gt; | No | Section refs or families to add or emphasize. |
| **cta_posture** | string | No | CTA posture hint; max 128. |
| **funnel_shape** | string | No | Funnel intent hint; max 128. |
| **page_family_emphasis** | list&lt;string&gt; | No | Page families to add or emphasize. |
| **status** | string | Yes | `active`, `draft`, or `deprecated`. Only `active` used at resolution. |
| **version_marker** | string | Yes | Schema version; must match supported version (e.g. `1`). Max 32. |

---

## 2. Validation rules

- **overlay_key:** Non-empty; pattern; max length. Duplicate overlay_key in load: first wins, later skipped.
- **subtype_key:** Non-empty; must be valid subtype key; parent_industry_key may be validated at load or resolution. Invalid → overlay skipped.
- **goal_key:** Non-empty; must be in allowed set. Invalid → overlay skipped.
- **target_bundle_ref:** If present, must be non-empty and valid bundle key; otherwise overlay applies to any bundle in scope.
- **allowed_override_regions:** Must be non-empty; each element must be in the fixed set. Unknown regions → overlay skipped or region ignored.
- **status:** Must be one of active, draft, deprecated.
- **version_marker:** Must match supported schema version; otherwise overlay skipped at load.

---

## 3. Registry and resolution

- **Registry:** Load overlay definitions from a bounded set (e.g. PHP definitions or allowlist). No public mutation.
- **Resolution:** When resolving overlays for (industry_key, subtype_key, conversion_goal_key, bundle_key), include combined overlay only when overlay exists for (subtype_key, goal_key) and (optionally) target_bundle_ref matches or is empty. Invalid refs → overlay excluded; fallback to subtype + goal layers only.
- **Composition:** Combined overlay is applied **after** goal (and secondary-goal) overlays; see subtype-goal-starter-bundle-contract.md for precedence and conflict handling.
