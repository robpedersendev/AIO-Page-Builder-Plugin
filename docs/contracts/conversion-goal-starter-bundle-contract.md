# Conversion-Goal Starter Bundle Contract (Prompts 496, 498)

**Spec**: Bundle-to-Build Plan conversion; Build Plan contracts; industry starter bundle schema.

**Status**: Contract for goal-aware overlays applied to starter bundles during bundle-to-plan conversion. Additive; does not replace parent or subtype bundle conversion.

---

## 1. Purpose

- Allow **conversion-goal overlays** to refine how a selected starter bundle is turned into a draft Build Plan (page families, CTA posture, section emphasis, funnel shape).
- Preserve **fallback** to non-goal bundle conversion when no goal is set or overlay is missing.
- Keep **review and approval** semantics unchanged; no auto-execution.

---

## 2. Goal overlay object (conceptual)

A conversion-goal overlay for a bundle is an additive definition keyed by goal and optionally by bundle:

| Field | Type | Description |
|-------|------|-------------|
| **goal_key** | string | Conversion goal key (e.g. `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture`). |
| **target_bundle_ref** | string (optional) | When set, overlay applies only to this bundle key; empty = applies to any bundle in scope. |
| **page_family_emphasis** | list&lt;string&gt; (optional) | Page template families to emphasize for this goal. |
| **section_emphasis** | list&lt;string&gt; (optional) | Section refs or families to emphasize. |
| **cta_posture** | string (optional) | CTA posture hint (e.g. phone-first, booking-first). |
| **funnel_shape** | string (optional) | Funnel intent (e.g. lead-nurture, direct-conversion). |
| **status** | string | `active`, `draft`, or `deprecated`. |
| **version_marker** | string | Schema/version for validation. |

Invalid or unknown goal_key or target_bundle_ref must result in **safe fallback** (no overlay applied; base bundle conversion used).

---

## 3. Conversion flow

- **Conversion service** (e.g. Conversion_Goal_Starter_Bundle_To_Build_Plan_Service) accepts optional `conversion_goal_key` in context.
- When **conversion_goal_key** is present and a matching overlay exists for the bundle (or for the goal generically), the overlay refines the normalized output (page families, CTA posture, section emphasis, funnel intent) before calling Build_Plan_Generator.
- When **no goal** or **no overlay**: use existing Industry_Starter_Bundle_To_Build_Plan_Service behavior (or equivalent) without goal refinement.
- **Rationale metadata**: Plan and item payloads may include `goal_overlay_source` or equivalent so review UI can show that goal-aware overlays shaped the draft.

---

## 4. Safety and precedence

- **Industry and subtype** remain the base layers; goal overlays are additive.
- **Invalid goal bundle refs** must not throw; fallback to non-goal conversion and optionally log.
- **Planner/executor separation** is unchanged; conversion produces a draft only; approval gating is preserved.

---

## 5. Integration

- Referenced by bundle-to-Build Plan conversion services and Build Plan explanation/view-model services.
- Overlay registry or resolution is defined by implementation (e.g. registry keyed by goal_key and optional bundle_key).
- **Secondary-goal overlays:** When a profile has both primary and secondary conversion goals, optional refinement is defined by [secondary-goal-starter-bundle-contract.md](secondary-goal-starter-bundle-contract.md) (Prompt 541). Primary-goal overlays remain authoritative; secondary overlays add low-weight nuance only.
