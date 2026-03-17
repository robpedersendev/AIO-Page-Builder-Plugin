# Secondary-Goal Caution Rule Schema (Prompt 547)

**Spec:** secondary-goal-caution-rule-contract.md; conversion-goal-caution-rule-schema.md.

**Status:** Additive schema for secondary-goal caution rules. Composition: **industry → subtype → goal (primary) → secondary goal**. Rules are advisory only.

---

## 1. Purpose

- Provide **additive schema support** for secondary-goal caution rules with primary_goal_key, secondary_goal_key (distinct), scope, severity, content, status, and versioning.
- Support **composition** with industry, subtype, and primary-goal rules.
- **Safe fallback:** when no secondary goal or invalid or same as primary, only industry + subtype + primary-goal rules apply.

---

## 2. Rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **secondary_goal_rule_key** | string | Yes | Stable unique key; pattern `^[a-z0-9_-]+$`; max 64. |
| **primary_goal_key** | string | Yes | Primary conversion goal key from launch set. |
| **secondary_goal_key** | string | Yes | Secondary conversion goal key; must be distinct from primary. |
| **scope** | string | No | `global`, `section_family`, `page_family`, or empty (global). Max 32. |
| **target_section_family** | string | No | When scope section_family. Max 64. |
| **target_page_family** | string | No | When scope page_family. Max 64. |
| **severity** | string | Yes | `info`, `caution`, or `warning`. |
| **caution_summary** | string | Yes | Short summary (max 256 chars). |
| **guidance_text** | string | No | Full guidance (max 1024 chars). |
| **refinement_area** | string | No | Advisory: messaging_overload, cta_confusion, promise_ambiguity. Max 64. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` used at resolution. |
| **version_marker** | string | No | Schema/rule version; max 32. |

- **Invalid rule objects** skipped at load.
- **secondary_goal_rule_key** unique within registry (first wins on duplicate).
- **primary_goal_key** and **secondary_goal_key** must be from allowed set and **distinct**; otherwise skip.

---

## 3. Severity semantics

Same as conversion-goal and industry caution rules: info (best-practice), caution (review), warning (higher sensitivity). System does not block; surfaces guidance only.

---

## 4. Composition and fallback

- **Resolution order:** Industry → subtype → goal (primary) → **secondary goal**.
- **Fallback:** When secondary_goal_key empty, invalid, or equal to primary_goal_key, only industry + subtype + primary-goal rules returned.
