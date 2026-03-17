# Combined Subtype + Goal Doc Overlay Contract (Prompt 553)

**Spec:** helper-doc contracts; page one-pager contracts; subtype and goal overlay contracts; roadmap guardrail docs.

**Status:** Defines the bounded contract for **exceptional** combined subtype+goal overlays in **helper** (section-helper) and **page** (page one-pager) guidance. Only truly high-value joint scenarios may introduce combined editorial nuance; combinatorial bloat is explicitly constrained.

---

## 1. Purpose

- **Exceptional joint documentation:** In a small set of high-value cases, a single overlay may refine **section-helper** or **page one-pager** guidance for a specific **subtype + conversion goal** pair (e.g. realtor_buyer_agent + consultations for buyer-consultation flows) where layering subtype-only and goal-only overlays is not expressive enough.
- **Bounded:** Combined doc overlays are **not** the default. Base, industry, subtype, and goal overlays remain the normal layering model. Combined subtype+goal doc overlays are **admitted only when** they meet strict admission criteria.
- **Documentation sprawl constrained:** No broad subtype×goal matrix for helper or page docs. Strict limits and reviewable set.

---

## 2. Combined helper (section-helper) overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **overlay_key** | string | Stable unique key (e.g. `realtor_buyer_agent_consultations_hero`). |
| **subtype_key** | string | Industry subtype key (must match Industry_Subtype_Registry). |
| **goal_key** | string | Conversion goal key (same set as conversion_goal_key). |
| **section_key** | string | Section template internal_key (same as base/industry/subtype/goal). |
| **scope** | string | Fixed: `subtype_goal_section_helper_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **allowed_override_regions** | list&lt;string&gt; | Only these regions may be refined: tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks. |
| **tone_notes** | string (optional) | Joint subtype+goal tone; max 1024. |
| **cta_usage_notes** | string (optional) | CTA/conversion notes; max 1024. |
| **compliance_cautions** | string (optional) | Cautions; max 1024. |
| **media_notes** | string (optional) | Media/asset guidance; max 512. |
| **seo_notes** | string (optional) | SEO notes; max 512. |
| **additive_blocks** | array (optional) | Array of { block_key, content } for additional blocks. |

Invalid subtype_key, goal_key, or section_key must **fail safely** at load (skip overlay).

---

## 3. Combined page one-pager overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **overlay_key** | string | Stable unique key (e.g. `plumber_commercial_estimates_contact`). |
| **subtype_key** | string | Industry subtype key (must match Industry_Subtype_Registry). |
| **goal_key** | string | Conversion goal key (same set as conversion_goal_key). |
| **page_key** | string | Page template internal_key or page family key (same as base/industry/subtype/goal). |
| **scope** | string | Fixed: `subtype_goal_page_onepager_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **allowed_override_regions** | list&lt;string&gt; | Only these regions may be refined: hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks (or structure_notes, funnel_notes, cta_placement_notes per page overlay schema). |
| **hierarchy_hints** | string (optional) | Page hierarchy hints; max 1024. |
| **cta_strategy** | string (optional) | CTA strategy; max 1024. |
| **lpagery_seo_notes** | string (optional) | LPagery/SEO notes; max 1024. |
| **compliance_cautions** | string (optional) | Cautions; max 1024. |
| **additive_blocks** | array (optional) | Additive content blocks. |

Invalid subtype_key, goal_key, or page_key must **fail safely** at load (skip overlay).

---

## 4. Admission criteria for combined doc overlays

Combined subtype+goal helper/page overlays are **allowed only when**:

1. **Justified:** The (subtype, goal) pair has documented high value for **documentation** (e.g. buyer-consultation flows, mobile-booking flows, commercial-emergency flows) such that a single joint overlay is preferable to layering subtype + goal overlays independently.
2. **Curated:** The overlay is part of a **bounded, reviewable set**. No automatic generation of subtype×goal×section or subtype×goal×page matrix.
3. **Schema-valid:** Overlay passes schema validation (subtype-goal-doc-overlay-schema.md); invalid entries are skipped at load.
4. **No arbitrary override regions:** Only approved regions (per helper or page schema) may be overridden. No freeform replacement of base content_body or full doc bodies.

Product may maintain an **allowlist** of (subtype_key, goal_key) pairs (and optionally target refs) for which combined doc overlays may exist.

---

## 5. Precedence relative to subtype-only and goal-only overlays

- **Composition order** (helper): Base → industry → subtype → **conversion goal** → secondary goal (when applicable) → **combined subtype+goal** (when present and valid).
- **Composition order** (page): Base → industry → subtype → **primary-goal** → secondary goal (when applicable) → **combined subtype+goal** (when present and valid).
- **Precedence rule:** Goal-only (and secondary-goal) overlays remain **authoritative** for their regions. Combined subtype+goal overlay **adds or refines** only where it does not conflict with goal overlay, or product defines a narrow override. Default: **goal wins** on conflict.
- **Deterministic:** Resolution is deterministic; same (subtype, goal, section_key or page_key) always yields the same layer sequence.

---

## 6. Fallback to independent layer composition

- **No combined overlay:** When no combined overlay exists for (subtype_key, goal_key, section_key or page_key), composition uses **subtype + goal** layers only (independent). No error; no combined overlay applied.
- **Invalid refs:** Invalid subtype_key, goal_key, or target ref causes the combined overlay to be **skipped**. Composition falls back to subtype + goal layers without the combined overlay.
- **Exportable and versioned:** Overlays are exportable and versioned; invalid refs must not throw.

---

## 7. Strict limits

- No broad combinatorial authoring. Seed set (Prompt 554) is intentionally narrow.
- Documentation corpus sprawl must be constrained. Each combined overlay must have a documented admission rationale.
- Cross-refs: [conversion-goal-helper-overlay-contract.md](conversion-goal-helper-overlay-contract.md); [conversion-goal-page-onepager-overlay-contract.md](conversion-goal-page-onepager-overlay-contract.md); [secondary-goal-helper-overlay-contract.md](secondary-goal-helper-overlay-contract.md); [secondary-goal-page-onepager-overlay-contract.md](secondary-goal-page-onepager-overlay-contract.md); subtype section-helper and page one-pager overlay contracts; [subtype-goal-doc-overlay-schema.md](../schemas/subtype-goal-doc-overlay-schema.md).
