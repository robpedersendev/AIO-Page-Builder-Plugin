# Conversion-Goal Caution Rule Contract (Prompt 509)

**Spec:** Caution-rule contracts (industry-compliance-rule-contract.md, subtype-compliance-rule-contract.md); conversion-goal profile contract.

**Status:** Extension contract for optional conversion-goal-aware caution rules. Goal rules are **advisory and additive**; they refine editorial warnings when funnel intent increases risk (overclaiming, over-urgency, poor-fit conversion framing). No legal automation or blocking.

---

## 1. Purpose

- Define the **extension layer** for conversion-goal caution rules layered on top of parent-industry and subtype caution rules.
- Support **refinement areas**: urgency language, conversion pressure, claim phrasing, low-friction form promises, valuation/estimate promise posture.
- Keep the model **advisory, bounded, and reusable** by docs, previews, and Build Plan review. No legal/compliance guarantees.

---

## 2. Goal caution rule object (summary)

Per [conversion-goal-caution-rule-schema.md](../schemas/conversion-goal-caution-rule-schema.md):

- **goal_rule_key**, **goal_key**, **scope** (global | section_family | page_family), **severity** (info | caution | warning), **caution_summary**, **guidance_text** (optional), **status** (active | draft | archived), **version_marker** (optional).
- Optional: target_section_family, target_page_family, refinement_area (e.g. urgency_language, conversion_pressure, claim_phrasing, form_promises, valuation_estimate_posture).
- Invalid or duplicate goal rules are skipped at load.

---

## 3. Allowed refinement areas

Goal caution rules may target (for documentation and scoping only; not enforced by schema):

- **urgency_language** — Strong time/urgency claims when goal is calls or bookings.
- **conversion_pressure** — CTA or conversion framing that may overstate commitment.
- **claim_phrasing** — Outcome or guarantee claims that may overclaim.
- **form_promises** — Low-friction form promises (e.g. “no obligation”) that must stay accurate.
- **valuation_estimate_posture** — Valuation or estimate language that must not mislead.

Refinement areas are **advisory labels** for authoring and cataloging; resolution uses scope and target_* only.

---

## 4. Composition order

1. **Parent-industry rules** (Industry_Compliance_Rule_Registry) for profile primary_industry_key.
2. **Subtype rules** (Subtype_Compliance_Rule_Registry) for (parent_industry_key, subtype_key) when valid.
3. **Goal rules** (Goal Caution Rule registry) for profile conversion_goal_key when valid. Goal rules are additive; they do not remove or replace industry/subtype rules.

Deduplication/display is consumer-defined (e.g. merge by scope and target; show goal-specific note when goal is set).

---

## 5. Safe fallback

- When **no conversion goal** is set or **goal_key is invalid**: only industry + subtype caution layers apply; goal rules are not applied.
- **Invalid goal_rule_key or goal_key** at load: entry skipped; no throw.
- No partial application: if goal is not in the launch set or unknown, the goal layer is skipped entirely.

---

## 6. Registry and resolution (future)

- **Goal caution rule registry**: Read-only after load. Methods: load(array), get(goal_rule_key), get_for_goal(goal_key), get_all().
- **No public mutation.** Rules are loaded from built-in definitions (e.g. GoalCautionRules/) or optional import path.
- **Fail-safe:** Invalid goal_rule_key, goal_key, or severity causes the entry to be skipped; no throw.
- **Resolution:** A resolver that has industry, subtype, and goal registries (and profile conversion_goal_key) returns merged list for (industry, subtype, goal). When goal is empty or invalid, only industry + subtype rules are returned.

---

## 7. Consumers

- **Helper docs / one-pagers:** May reference goal rules when conversion goal is set.
- **Build Plan review:** May surface goal-aware cautions when goal is set and scope matches.
- **Admin previews:** May show goal-related warnings where scope matches and goal is in context.

Consumers use the same advisory, non-blocking behavior as industry and subtype rules. No legal or regulatory certainty is implied.

---

## 8. Limits

- **Not legal advice.** Goal caution rules are editorial guardrails only.
- **No legal automation.** Rules do not block content publication or planning automatically.
- **No jurisdiction-specific rule systems.** Rules are goal/funnel oriented.
- **Exportable and versioned.** Rules are part of industry subsystem data; schema supports version_marker and status.

---

## 9. Secondary-goal layer (Prompt 547)

When a profile has both primary and secondary conversion goals, an optional **secondary-goal caution rule** layer may apply after the primary-goal rules. Composition order: industry → subtype → goal (primary) → **secondary goal**. See [secondary-goal-caution-rule-contract.md](secondary-goal-caution-rule-contract.md).

---

## 10. Cross-references

- [conversion-goal-caution-rule-schema.md](../schemas/conversion-goal-caution-rule-schema.md) — Full schema.
- [industry-compliance-rule-contract.md](industry-compliance-rule-contract.md) — Parent industry caution rules.
- [subtype-compliance-rule-contract.md](subtype-compliance-rule-contract.md) — Subtype caution rules.
- [conversion-goal-profile-contract.md](conversion-goal-profile-contract.md) — Profile conversion_goal_key.
- [secondary-goal-caution-rule-contract.md](secondary-goal-caution-rule-contract.md) — Secondary-goal caution rules (Prompt 547).
