# Combined Subtype + Goal Starter Bundle Overlay Contract (Prompt 551)

**Spec:** starter bundle contracts; subtype bundle contracts; conversion-goal bundle contracts; secondary-goal starter bundle contract; roadmap guardrail docs.

**Status:** Contract. Defines the bounded **combined subtype+goal bundle overlay** model for exceptional high-value (industry_subtype_key + conversion_goal_key) combinations where joint refinement is justified. Combined overlays remain **exceptional, not default**; overlay sprawl is explicitly constrained.

---

## 1. Purpose

- **Exceptional joint refinement:** In a small set of high-value cases, a single overlay may refine a starter bundle for a specific **subtype + conversion goal** pair (e.g. realtor_buyer_agent + calls) instead of relying only on independent subtype bundles and goal overlays.
- **Bounded:** Combined overlays are **not** the default. Independent subtype bundles and goal overlays remain the primary combined influences. Combined subtype+goal overlays are **admitted only when** they meet admission criteria and are explicitly curated.
- **No sprawl:** No large subtype×goal matrix. Strict limits and reviewable set.

---

## 2. Combined subtype+goal overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **overlay_key** | string | Stable unique key (e.g. `realtor_buyer_agent_calls`). |
| **subtype_key** | string | Industry subtype key (must match Industry_Subtype_Registry; parent_industry_key implied by bundle context). |
| **goal_key** | string | Conversion goal key (same set as conversion_goal_key). |
| **target_bundle_ref** | string (optional) | When set, overlay applies only to this bundle key; empty = applies to any bundle in scope for (subtype, goal). |
| **allowed_override_regions** | list&lt;string&gt; | Only these regions may be refined; same semantic set as goal overlays: e.g. `section_emphasis`, `cta_posture`, `funnel_shape`, `page_family_emphasis`. Schema defines the fixed set. |
| **section_emphasis** | list&lt;string&gt; (optional) | Section refs or families to add or emphasize. |
| **cta_posture** | string (optional) | CTA posture hint. |
| **funnel_shape** | string (optional) | Funnel intent hint. |
| **page_family_emphasis** | list&lt;string&gt; (optional) | Page families to add or emphasize. |
| **status** | string | `active`, `draft`, or `deprecated`. Only `active` used at resolution. |
| **version_marker** | string | Schema/version for validation. |

Invalid subtype_key, goal_key, or target_bundle_ref must result in **safe fallback** (overlay skipped at load or resolution).

---

## 3. Admission criteria for combined overlays

Combined subtype+goal overlays are **allowed only when**:

1. **Justified:** The (subtype, goal) pair has documented high value (e.g. roadmap or product decision) such that a single joint overlay is preferable to layering subtype bundle + goal overlay independently.
2. **Curated:** The overlay is part of a **bounded, reviewable set**. No automatic generation of subtype×goal matrix.
3. **Schema-valid:** Overlay passes schema validation (subtype-goal-starter-bundle-schema.md); invalid entries are skipped at load.
4. **No execution logic:** Overlay contains only advisory refinement data; no code or execution logic in overlay payload.

Product may maintain an **allowlist** of (subtype_key, goal_key) pairs for which combined overlays may exist; pairs not on the allowlist are not loaded or not resolved.

---

## 4. Precedence relative to subtype-only and goal-only overlays

- **Resolution order** (when resolving “which overlays apply to this bundle for profile with subtype + goal”):
  1. **Base bundle:** Industry or subtype-scoped bundle (per subtype-starter-bundle-contract).
  2. **Goal overlay** (conversion-goal or primary-goal overlay per conversion-goal-starter-bundle-contract): applied when conversion_goal_key is set and overlay exists for the bundle (and optionally for goal only).
  3. **Secondary-goal overlay** (when applicable): per secondary-goal-starter-bundle-contract.
  4. **Combined subtype+goal overlay** (optional): when both industry_subtype_key and conversion_goal_key are set, **and** a combined overlay exists for (subtype_key, goal_key, bundle_key), it is applied **after** goal overlays. It refines only in **allowed_override_regions**; conflicts with goal overlay are resolved in favor of **goal overlay** unless the combined overlay is explicitly defined to override (product rule).

- **Precedence rule:** Goal-only overlays (and secondary-goal overlays) remain **authoritative** for their regions. Combined subtype+goal overlay **adds or refines** only where it does not conflict with goal overlay, or product defines a narrow override rule (e.g. “combined overlay may override cta_posture for this pair only”). Default: **goal wins** on conflict.

---

## 5. Fallback to independent layer resolution

- **No combined overlay:** When no combined overlay exists for (subtype_key, goal_key, bundle_key), resolution uses **subtype bundle + goal overlay(s)** only (independent layers). No error; no combined overlay applied.
- **Invalid refs:** Invalid subtype_key (e.g. wrong parent, unknown subtype) or invalid goal_key causes the combined overlay to be **skipped**. Resolution falls back to subtype bundle + goal overlay(s) without the combined overlay.
- **Registry/planning only:** Resolution is used at bundle-to-plan conversion or planning only; no execution logic in overlay data.

---

## 6. Export and versioning

- Overlay definitions are **versioned** via **version_marker**. Unsupported versions cause overlay to be skipped at load.
- Overlays are **exportable** (e.g. in industry pack or bundle catalog export); no secrets or execution logic in overlay data.
- **Strict limits:** Document the maximum number or allowlist of combined overlays per product policy to avoid sprawl.

---

## 7. Cross-references

- [subtype-starter-bundle-contract.md](subtype-starter-bundle-contract.md) — Subtype-scoped bundles; base layer.
- [conversion-goal-starter-bundle-contract.md](conversion-goal-starter-bundle-contract.md) — Goal overlays; precedence.
- [secondary-goal-starter-bundle-contract.md](secondary-goal-starter-bundle-contract.md) — Primary + secondary goal overlays.
- [subtype-goal-starter-bundle-schema.md](../schemas/subtype-goal-starter-bundle-schema.md) — Schema for combined overlay objects.
- [industry-starter-bundle-catalog.md](../appendices/industry-starter-bundle-catalog.md) — Bundle catalog; comparison screen.
