# Conversion-Goal Conflict and Precedence Contract (Prompt 502)

**Spec**: Conversion-goal profile contract; subtype extension contracts; bundle, override, and Build Plan contracts.

**Status**: Defines precedence and conflict-resolution rules for the conversion-goal layer relative to industry, subtype, bundles, overrides, and caution rules. Explainable and deterministic.

---

## 1. Purpose

- Define **precedence** between industry, subtype, conversion goal, and override layers so planner and UI behavior are unambiguous.
- Define how **conflicts** between goal and bundles, overrides, or caution rules are **handled** and **surfaced**.
- Provide a **fallback** when goal conflicts are unresolved. Keep rules **reusable** by scoring and UI layers.

---

## 2. Layer precedence (highest to lowest)

1. **Explicit overrides** (user/operator choices that override recommendations).
2. **Caution rules** (compliance or risk rules that produce warnings; do not override data, but surface advisories).
3. **Conversion goal** (additive refinement: CTA posture, funnel intent, section/page emphasis).
4. **Subtype** (subtype-scoped bundles and recommendations).
5. **Industry** (primary industry pack and industry-scoped bundles).
6. **Base** (defaults when no industry or goal is set).

Conversion goal **never overrides** explicit overrides or caution rules. Goal **refines** within industry and subtype context.

---

## 3. Goal vs bundle

- When the **selected bundle** is not goal-aware (no overlay for the current goal): use bundle as-is; no error. Optionally surface an advisory that the bundle is not tuned for the selected goal.
- When the **selected goal** does not match any overlay for the bundle: same as above; safe fallback to non-goal bundle conversion.
- **Conflict**: If a bundle is explicitly tagged as incompatible with a goal (future extension), surface a **warning** and suggest review; do not auto-change goal or bundle.

---

## 4. Goal vs overrides

- **Overrides** always win. If the user has overridden a template or section choice, goal-based recommendations must not replace that override.
- When goal suggests a different CTA or page family than an override: surface **explanation** (e.g. "Override differs from goal posture") for review; no auto-mutation.

---

## 5. Goal vs caution rules

- **Caution rules** (e.g. compliance, risk) are independent. Goal does not suppress cautions.
- When goal and caution both apply: show both. Example: "Goal: booking-first" and "Caution: ensure booking terms are visible."

---

## 6. Fallback when unresolved

- **Invalid goal key**: Treat as no goal; proceed with industry/subtype/bundle only.
- **Missing overlay**: Proceed with base bundle conversion; optionally note "No goal overlay for this bundle."
- **Contradictory state** (e.g. goal says "calls" but bundle is booking-heavy with no overlay): Proceed with bundle as-is; surface **suggested review**: "Consider aligning bundle or goal."

---

## 7. Reuse by scoring and UI

- **Scoring services** may use this precedence to attach rationale (e.g. "goal_refined", "override_applied").
- **Explanation view models** may expose which layer drove each item (industry, subtype, goal, override).
- **Conflict detector** (Prompt 503) implements read-only checks against these rules and surfaces warnings/suggested review.

---

## 8. Example scenarios

| Scenario | Behavior |
|----------|----------|
| Goal = calls, bundle = realtor_starter, overlay exists | Apply overlay (e.g. phone-first CTA, call-focused sections); plan reflects goal. |
| Goal = calls, bundle = realtor_starter, no overlay | Use bundle as-is; optionally "No goal overlay for this bundle." |
| Goal = bookings, user override = different CTA block | Override wins; explanation: "Override differs from goal posture (bookings)." |
| Invalid goal key in profile | Treat as no goal; industry/subtype/bundle only. |
