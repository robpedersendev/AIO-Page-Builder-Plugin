# Secondary-Goal Starter Bundle Overlay Contract (Prompt 541)

**Spec:** secondary-conversion-goal-contract.md; conversion-goal-starter-bundle-contract.md; starter bundle contracts; Build Plan contracts in the master spec.

**Status:** Contract. Defines the bounded overlay model that allows an optional secondary conversion goal to refine starter bundles without undermining primary-goal precedence, parent industry/subtype bundle authority, or Build Plan review safeguards.

---

## 1. Purpose

- **Bounded secondary-goal bundle refinement:** When a profile has both a primary and a valid secondary conversion goal, optional secondary-goal starter-bundle overlays may add low-weight refinement to bundle-to-plan conversion (e.g. section emphasis, CTA nuance, funnel shape).
- **Primary remains authoritative:** Primary-goal bundle overlays (when present) remain the main funnel context; secondary-goal overlays refine only where allowed and never override primary.
- **No sprawl:** No arbitrary multi-goal bundle layering beyond the documented primary/secondary model. Industry and subtype bundle layers remain the foundation.

---

## 2. Scope and constraints

- **In scope:** Secondary-goal starter-bundle overlay object shape; allowed refinement areas; precedence relative to primary-goal overlays; fallback when no secondary overlay exists; exportability and versioning; mixed-goal limits.
- **Out of scope:** Seeding overlays (Prompt 542); redesigning the bundle registry; allowing arbitrary multi-goal layering; execution logic in bundle data.

---

## 3. Secondary-goal bundle overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **overlay_key** | string | Stable unique key for the overlay (e.g. `calls_lead_capture`, `bookings_consultation`). |
| **primary_goal_key** | string | Primary conversion goal key (same set as conversion_goal_key). |
| **secondary_goal_key** | string | Secondary conversion goal key; must be distinct from primary. |
| **target_bundle_ref** | string (optional) | When set, overlay applies only to this bundle key; empty = applies to any bundle in scope. |
| **allowed_overlay_regions** | list&lt;string&gt; | Only these regions may be refined: e.g. `section_emphasis`, `cta_posture`, `funnel_shape`, `page_family_emphasis`. Schema defines the fixed set. |
| **section_emphasis** | list&lt;string&gt; (optional) | Section refs or families to add or emphasize for mixed-funnel (low-weight). |
| **cta_posture** | string (optional) | CTA posture hint for secondary nuance (e.g. lead-nurture alongside primary conversion). |
| **funnel_shape** | string (optional) | Funnel intent hint (e.g. lead-nurture, direct-conversion). |
| **precedence_marker** | string | Fixed: `secondary` (always below primary-goal overlays). |
| **status** | string | `active`, `draft`, or `deprecated`. Only `active` used at resolution. |
| **version_marker** | string | Schema/version for validation. |

Invalid primary_goal_key, secondary_goal_key (e.g. same as primary, or not in allowed set), or target_bundle_ref must result in **safe fallback** (overlay skipped at load or resolution).

---

## 4. Allowed refinement areas

Secondary-goal overlays may **only** refine the following regions when listed in **allowed_overlay_regions**:

- **section_emphasis:** Add or emphasize section refs for secondary-funnel nuance. Merged additively with primary-goal emphasis; never replaces primary.
- **cta_posture:** Optional hint for mixed CTA (e.g. primary call + secondary lead capture). Low-weight; primary CTA posture remains dominant.
- **funnel_shape:** Optional funnel intent (e.g. lead-nurture). Refines explanation metadata only.
- **page_family_emphasis:** Optional page families to add for secondary intent. Additive; primary page families remain authoritative.

No execution logic in overlay data. Refinement is advisory until converted into a Build Plan and reviewed.

---

## 5. Precedence and fallback

- **Order:** Industry/subtype bundle → primary-goal overlay (when present and valid) → **secondary-goal overlay** (when present, valid, and distinct from primary).
- **Primary authoritative:** Any conflict or overlap is resolved in favor of primary-goal overlay. Secondary adds only where primary does not define or where merge is defined (e.g. additive section_emphasis).
- **Fallback when no secondary overlay exists:** Use primary-goal-only bundle behavior (or base bundle conversion when no primary overlay). No error; safe degradation.
- **Invalid secondary refs:** Strip or skip overlay; fall back to primary-only. No public mutation surfaces.

---

## 6. Mixed-goal limits

- At most **one** secondary goal per profile; same limit as secondary-conversion-goal-contract.
- Secondary overlay applies only when **primary_goal_key** and **secondary_goal_key** are both valid and **distinct**.
- No tertiary or list of goals. No cross-product matrix of all goal pairs; seed set is bounded (Prompt 542).

---

## 7. Export and versioning

- Overlay definitions are versioned via **version_marker**. Unsupported versions cause overlay to be skipped at load.
- Export/restore of industry pack or bundle catalog may reference overlay keys; no secrets or execution logic in overlay data.

---

## 8. Cross-references

- [secondary-conversion-goal-contract.md](secondary-conversion-goal-contract.md) — Secondary goal state shape; allowed combinations; precedence.
- [conversion-goal-starter-bundle-contract.md](conversion-goal-starter-bundle-contract.md) — Primary conversion-goal overlay model; conversion flow.
- [industry-starter-bundle-schema.md](../schemas/industry-starter-bundle-schema.md) — Base starter bundle schema.
- [secondary-goal-starter-bundle-schema.md](../schemas/secondary-goal-starter-bundle-schema.md) — Schema for secondary-goal bundle overlay objects (Prompt 541).
