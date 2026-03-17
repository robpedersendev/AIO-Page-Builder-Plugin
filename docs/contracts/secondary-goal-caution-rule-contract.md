# Secondary-Goal Caution Rule Contract (Prompt 547)

**Spec:** caution-rule contracts (conversion-goal-caution-rule-contract.md); secondary-conversion-goal-contract.md; goal caution-rule contracts.

**Status:** Extension contract for optional secondary-goal-aware caution rules. Secondary-goal rules are **advisory and additive**; they refine editorial warnings for mixed-funnel messaging risks (CTA confusion, messaging overload, promise ambiguity). No legal automation or blocking.

---

## 1. Purpose

- Define the **extension layer** for secondary-goal caution rules layered below primary-goal caution rules and above neutral/no-goal warnings.
- Support **mixed-funnel refinement areas**: messaging overload, CTA confusion, promise ambiguity when both primary and secondary conversion goals are set.
- Keep the model **advisory, bounded, and deterministic**. No legal/compliance guarantees.

---

## 2. Secondary-goal caution rule object (summary)

Per [secondary-goal-caution-rule-schema.md](../schemas/secondary-goal-caution-rule-schema.md):

- **secondary_goal_rule_key**, **primary_goal_key**, **secondary_goal_key** (distinct), **scope** (global | section_family | page_family), **severity** (info | caution | warning), **caution_summary**, **guidance_text** (optional), **status** (active | draft | archived), **version_marker** (optional).
- Optional: target_section_family, target_page_family, refinement_area (e.g. messaging_overload, cta_confusion, promise_ambiguity).
- Invalid or duplicate rules are skipped at load.

---

## 3. Allowed refinement areas

Secondary-goal caution rules may target (advisory labels only):

- **messaging_overload** — Too many conversion paths (primary + secondary) diluting focus.
- **cta_confusion** — Primary vs secondary CTA competing or unclear hierarchy.
- **promise_ambiguity** — Mixed-funnel promises that could overcommit or confuse.

Refinement areas are **advisory**; resolution uses scope and target_* only.

---

## 4. Composition order

1. **Parent-industry rules** (Industry_Compliance_Rule_Registry).
2. **Subtype rules** (Subtype_Compliance_Rule_Registry) when valid.
3. **Goal (primary) rules** (Goal_Caution_Rule_Registry) for conversion_goal_key when valid.
4. **Secondary-goal rules** (Secondary_Goal_Caution_Rule_Registry) when secondary_conversion_goal_key valid and distinct from primary.

When no secondary goal or invalid: only prior layers apply. Safe fallback.

---

## 5. Safe fallback

- **No secondary goal** or **secondary same as primary** or **invalid secondary**: secondary-goal rules not applied; primary-goal and industry/subtype layers unchanged.
- **Invalid rule at load:** entry skipped; no throw.
- No partial application: if secondary is not in allowed set or equals primary, secondary layer is skipped entirely.

---

## 6. Registry and resolution

- **Secondary_Goal_Caution_Rule_Registry:** Read-only after load. load(array), get(secondary_goal_rule_key), get_for_primary_secondary(primary_goal_key, secondary_goal_key), get_all().
- **No public mutation.** Rules loaded from built-in definitions (e.g. SecondaryGoalCautionRules/) or optional import.
- **Fail-safe:** Invalid keys or primary equals secondary → skip; no throw.
- **Resolution:** Resolver that has industry, subtype, goal (primary), and secondary-goal registries returns merged list. When secondary is empty or invalid, only industry + subtype + primary-goal rules are returned.

---

## 7. Limits

- **Not legal advice.** Secondary-goal rules are editorial guardrails only.
- **No legal automation.** Rules do not block publication or planning.
- **Exportable and versioned.** Schema supports version_marker and status.

---

## 8. Cross-references

- [conversion-goal-caution-rule-contract.md](conversion-goal-caution-rule-contract.md) — Primary goal caution rules; composition order.
- [secondary-goal-caution-rule-schema.md](../schemas/secondary-goal-caution-rule-schema.md) — Schema for secondary-goal caution rules (Prompt 547).
- [secondary-conversion-goal-contract.md](secondary-conversion-goal-contract.md) — Secondary goal state; allowed combinations.
