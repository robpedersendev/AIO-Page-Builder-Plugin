# Subtype + Goal Doc Overlay Schema (Prompt 553)

**Spec:** subtype-goal-doc-overlay-contract.md; helper-doc and page one-pager overlay schemas.

**Status:** Schema for combined subtype+goal **section-helper** and **page one-pager** overlay objects. Additive; used only when admission criteria are met and overlay is in the bounded set.

---

## 1. Section-helper (helper) overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **overlay_key** | string | Yes | Stable unique key; pattern `[a-z0-9_-]+`; max 64. |
| **subtype_key** | string | Yes | Industry subtype key; must match Industry_Subtype_Registry. Max 64. |
| **goal_key** | string | Yes | Conversion goal key (calls, bookings, estimates, consultations, valuations, lead_capture). |
| **section_key** | string | Yes | Section template internal_key. Max 64. |
| **scope** | string | Yes | Fixed: `subtype_goal_section_helper_overlay`. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` used at resolution. |
| **version_marker** | string | No | Schema version; max 32. |
| **allowed_override_regions** | list&lt;string&gt; | Yes | Allowed: tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks. |
| **tone_notes** | string | No | Max 1024. |
| **cta_usage_notes** | string | No | Max 1024. |
| **compliance_cautions** | string | No | Max 1024. |
| **media_notes** | string | No | Max 512. |
| **seo_notes** | string | No | Max 512. |
| **additive_blocks** | array | No | Array of { block_key, content }. |

---

## 2. Page one-pager overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **overlay_key** | string | Yes | Stable unique key; pattern `[a-z0-9_-]+`; max 64. |
| **subtype_key** | string | Yes | Industry subtype key; must match Industry_Subtype_Registry. Max 64. |
| **goal_key** | string | Yes | Conversion goal key (same set as above). |
| **page_key** | string | Yes | Page template internal_key or page family key. Max 64. |
| **scope** | string | Yes | Fixed: `subtype_goal_page_onepager_overlay`. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` used at resolution. |
| **version_marker** | string | No | Schema version; max 32. |
| **allowed_override_regions** | list&lt;string&gt; | Yes | Allowed: hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks (or structure_notes, funnel_notes, cta_placement_notes per page overlay schema). |
| **hierarchy_hints** | string | No | Max 1024. |
| **cta_strategy** | string | No | Max 1024. |
| **lpagery_seo_notes** | string | No | Max 1024. |
| **compliance_cautions** | string | No | Max 1024. |
| **additive_blocks** | array | No | Additive content blocks. |

---

## 3. Validation rules

- **overlay_key:** Non-empty; pattern; max length. Duplicate overlay_key in load: first wins, later skipped.
- **subtype_key:** Non-empty; must be valid subtype key. Invalid → overlay skipped.
- **goal_key:** Non-empty; must be in allowed set. Invalid → overlay skipped.
- **section_key / page_key:** Non-empty; valid key. Invalid → overlay skipped.
- **scope:** Must match `subtype_goal_section_helper_overlay` (helper) or `subtype_goal_page_onepager_overlay` (page).
- **allowed_override_regions:** Must be non-empty; each element must be in the fixed set for helper or page. Unknown regions → overlay skipped or region ignored.
- **status:** Must be one of active, draft, archived.

---

## 4. Registry and resolution

- **Registries:** Separate or unified registry per product (e.g. Subtype_Goal_Helper_Overlay_Registry, Subtype_Goal_Page_OnePager_Overlay_Registry). Load overlay definitions from a bounded set. No public mutation.
- **Resolution:** When composing helper or page doc for (industry_key, subtype_key, conversion_goal_key, section_key or page_key), include combined overlay only when overlay exists for (subtype_key, goal_key, target_ref). Invalid refs → overlay excluded; fallback to subtype + goal layers only.
- **Composition:** Combined overlay is applied **after** goal (and secondary-goal) overlays; see subtype-goal-doc-overlay-contract.md for precedence and conflict handling.
