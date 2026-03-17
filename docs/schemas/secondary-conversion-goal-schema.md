# Secondary Conversion-Goal Schema (Prompt 528)

**Spec:** secondary-conversion-goal-contract.md; conversion-goal profile contract; industry profile schema.  
**Status:** Additive schema for optional secondary conversion-goal state and precedence semantics.

---

## 1. Purpose

- Define the **state shape** for an optional secondary conversion goal in the industry profile (or resolved context).
- Define **validation rules** and **precedence semantics** for primary + secondary combination.
- Remain **additive** to industry profile and conversion-goal profile contracts.

---

## 2. State shape (additive to industry profile)

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| **secondary_conversion_goal_key** | string | No | `""` | Optional second conversion goal from the same set as conversion_goal_key (e.g. `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture`). Empty = no secondary goal. |

- Stored in the same industry profile object as `conversion_goal_key` (primary). No separate option.
- **Resolved context:** Consumers may receive a resolved shape such as `{ primary_goal_key: string, secondary_goal_key: string }` where secondary_goal_key is empty when not set, invalid, or equal to primary.

---

## 3. Validation rules

- **Allowed values:** Same set as primary (conversion-goal-profile-contract launch set or registry). Unknown keys are invalid.
- **Distinct:** If secondary_conversion_goal_key equals conversion_goal_key (primary), treat as invalid for resolution (ignore secondary).
- **Primary required for secondary:** When conversion_goal_key (primary) is empty, secondary_conversion_goal_key is ignored at resolution; no secondary-only mode.
- **Single secondary:** Only one secondary key; no list. Schema does not define a list of secondary goals.

---

## 4. Precedence semantics (resolution)

1. **Resolve primary:** conversion_goal_key → primary_goal_key (or empty).
2. **Resolve secondary:** If secondary_conversion_goal_key is non-empty, valid, and not equal to primary_goal_key, then secondary_goal_key = secondary_conversion_goal_key; else secondary_goal_key = "".
3. **Output:** Resolved state has at most one primary and at most one secondary; both from allowed set; distinct when both present.

---

## 5. Safe failure

- **Invalid secondary key:** Strip or ignore; resolved secondary_goal_key = "". No fatal error.
- **Secondary same as primary:** Resolved secondary_goal_key = "". No fatal error.
- **Missing primary:** Resolved secondary_goal_key = "" regardless of stored secondary. No fatal error.
- **Export/restore:** Invalid or duplicate secondary may be stripped on restore; profile remains valid.

---

## 6. Implementation reference

- **Profile field:** Add optional `secondary_conversion_goal_key` to industry profile schema and repository merge/get (Prompt 529).
- **Resolver:** Secondary_Conversion_Goal_Resolver (or equivalent) returns resolved primary + secondary for consumers (Prompt 529).
- **Tests:** Valid secondary selection and fallback behavior; invalid combinations yield safe fallback (Prompt 529).
