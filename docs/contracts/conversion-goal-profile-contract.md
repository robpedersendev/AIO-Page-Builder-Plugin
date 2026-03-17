# Conversion-Goal Profile Contract

**Spec**: Industry profile schema; conversion-goal starter bundle contract; recommendation and Build Plan contracts.

**Status**: Contract for storing and resolving the site-level conversion goal in the industry context. Additive to industry profile.

---

## 1. Purpose

- Provide a **single optional conversion goal** (e.g. calls, bookings, estimates, consultations, valuations, lead capture) for the site within industry context.
- Support **recommendation**, **bundle selection**, **Build Plan**, **what-if**, and **content-gap** flows that need goal-aware behavior.
- **Fallback**: When no goal is set or key is invalid, all consumers treat as no-goal (neutral) and preserve existing behavior.

---

## 2. Profile field

- **conversion_goal_key** (string, optional): Stable key from the launch goal set. Stored in industry profile (or equivalent site-level store). Empty or missing = no conversion goal set.
- **Launch goal set**: `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture` (exact set may be extended via schema/registry).

---

## 3. Resolution and validation

- **At read**: Normalized industry profile may include `conversion_goal_key`. Invalid or unknown keys are treated as empty (no goal).
- **At write**: Only keys from the allowed set (or registry) are persisted; invalid keys are rejected or stripped.
- **No mutation** of other profile fields when goal is set or cleared.

---

## 4. Consumers

- Bundle-to-Build Plan conversion: optional context for goal-aware overlay application.
- Build Plan explanation: show goal influence when present.
- What-if simulation: optional alternate conversion_goal_key in simulated profile.
- Content gap detector: refine severity/explanation by goal when relevant.
- Conflict detector: compare goal vs bundle/override/caution layers.

---

## 5. Security and scope

- **Admin/reviewer** only for setting and viewing. No public mutation.
- **Export/restore**: conversion_goal_key included when industry profile is exported; restore validates key.
