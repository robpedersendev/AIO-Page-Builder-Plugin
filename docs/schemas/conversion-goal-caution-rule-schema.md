# Conversion-Goal Caution Rule Schema (Prompt 509)

**Spec:** conversion-goal-caution-rule-contract.md; industry-compliance-rule-schema.md; subtype-compliance-rule-schema.md; conversion-goal profile contract.

**Status:** Additive schema for conversion-goal-scoped caution rules. Goal rules refine or add to industry and subtype caution rules. Rules are **advisory only**; no legal advice or enforcement.

---

## 1. Purpose

- Provide **additive schema support** for conversion-goal caution rules with goal refs, scope, severity, content, status, and versioning.
- Support **composition** with industry and subtype caution rules: industry base → subtype → goal.
- **Safe fallback**: when no conversion goal or invalid goal_key, only industry + subtype caution behavior applies.

---

## 2. Goal caution rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **goal_rule_key** | string | Yes | Stable, unique key for the goal rule (pattern `^[a-z0-9_-]+$`; max 64). |
| **goal_key** | string | Yes | Conversion goal key from launch set (pattern `^[a-z0-9_-]+$`; max 64). |
| **scope** | string | No | Target scope: `global`, `section_family`, `page_family`, or empty (global). Max 32. |
| **target_section_family** | string | No | When scope is section_family: e.g. proof, listing, contact. Max 64. |
| **target_page_family** | string | No | When scope is page_family: e.g. home, contact, services. Max 64. |
| **severity** | string | Yes | `info`, `caution`, or `warning`. Only these values allowed. |
| **caution_summary** | string | Yes | Short summary (max 256 chars) for UI or tooltip. |
| **guidance_text** | string | No | Full guidance or explanation (max 1024 chars). |
| **guidance_text_fragment_ref** | string | No | Optional fragment_key (industry-shared-fragment-schema); resolved with consumer_scope `compliance_caution` and appended to caution_summary at display (Prompt 514). Max 64. |
| **refinement_area** | string | No | Advisory label: e.g. urgency_language, conversion_pressure, claim_phrasing, form_promises, valuation_estimate_posture. Max 64. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/rule version; max 32 chars. |

- **Invalid rule objects** must fail safely at load (skipped).
- **goal_rule_key** is unique within the goal caution rule registry (first wins on duplicate).
- **goal_key** must be from the allowed conversion goal set (e.g. launch set: calls, bookings, estimates, consultations, valuations, lead_capture); invalid goal_key causes skip.

---

## 3. Severity semantics

Aligned with industry-compliance-rule-schema:

| Severity | Use |
|----------|-----|
| **info** | General best-practice or reminder; no risk implied. |
| **caution** | Editorial or compliance sensitivity; user should review. |
| **warning** | Higher sensitivity; avoid overclaiming or non-compliant language. |

The system does **not** block or enforce; it surfaces guidance only. No legal certainty is implied.

---

## 4. Composition order and fallback

- **Resolution order**: Industry caution rules (base) → subtype caution rules (when subtype valid) → goal caution rules (when conversion_goal_key valid).
- **Fallback**: When goal_key is empty, invalid, or not in allowed set, **only industry + subtype rules** are returned. No partial goal application.
- **Reuse**: Same rule shapes are reusable by docs, previews, and Build Plan review; consumers receive a merged list (source: industry | subtype | goal).

---

## 5. Registry behavior (future implementation)

- **Goal caution rule registry**: load(array), get(goal_rule_key), get_for_goal(goal_key), get_all(). Read-only after load.
- Load validates required fields, severity enum, key patterns, and goal_key against allowed set; invalid entries skipped. Duplicate goal_rule_key: first wins.
- No public mutation; registry is populated from built-in definitions (GoalCautionRules/) or optional import only.

---

## 6. Limits of the system

- **Not legal advice.** Goal rules are editorial guardrails only.
- **No jurisdiction-specific law engines.** Rules are goal/funnel oriented, not legal-domain specific.
- **No hard blocking.** Consumers may display warnings or hints; they do not prevent save or publish.
- **Exportable.** Rules are part of industry subsystem data and may be included in export/restore where applicable.
