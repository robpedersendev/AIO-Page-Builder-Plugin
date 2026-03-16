# Subtype Compliance and Caution Rule Schema (Prompt 446)

**Spec:** industry-compliance-rule-schema.md; industry-compliance-rule-contract.md; industry-subtype-extension-contract.md.

**Status:** Additive schema for subtype-scoped caution rules. Subtype rules refine or add to parent-industry caution rules. Rules are **advisory only**; no legal advice or enforcement.

---

## 1. Purpose

- Provide a **bounded, structured** way for optional industry subtypes to refine parent-industry editorial guardrails (e.g. mobile vs fixed-location, residential vs commercial, buyer vs seller).
- Support **composition** with parent caution rules: parent base layer + subtype refinements/additions.
- **Safe fallback**: when no subtype or invalid subtype, only parent-industry caution behavior applies.

---

## 2. Subtype rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **subtype_rule_key** | string | Yes | Stable, unique key for the subtype rule (pattern `^[a-z0-9_-]+$`; max 64). |
| **subtype_key** | string | Yes | Subtype key (pattern `^[a-z0-9_-]+$`; max 64). Must match a registered subtype. |
| **parent_industry_key** | string | Yes | Parent industry pack key (pattern `^[a-z0-9_-]+$`; max 64). |
| **scope** | string | No | Target scope: `global`, `section_family`, `page_family`, or empty (global). Max 32. |
| **target_section_family** | string | No | When scope is section_family: e.g. proof, listing, contact. Max 64. |
| **target_page_family** | string | No | When scope is page_family: e.g. home, contact, services. Max 64. |
| **severity** | string | Yes | `info`, `caution`, or `warning`. Only these values allowed. |
| **caution_summary** | string | Yes | Short summary (max 256 chars) for UI or tooltip. |
| **guidance_text** | string | No | Full guidance or explanation (max 1024 chars). |
| **refinement_of_rule_key** | string | No | When set, this subtype rule refines the parent rule with this rule_key. Empty = additive rule. Max 64. |
| **additive_note** | string | No | Optional note that this rule adds to parent context (max 256 chars). |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/rule version; max 32 chars. |

- **Invalid subtype rule objects** must fail safely at load (skipped).
- **subtype_rule_key** is unique within the subtype rule registry (first wins on duplicate).
- **refinement_of_rule_key**: when present, resolution may replace or layer the parent rule’s display for this subtype; when empty, the subtype rule is additive only.

---

## 3. Composition order and fallback

- **Resolution order**: Parent-industry caution rules (base) → subtype caution rules (additive/refinement) when subtype is valid and matches parent.
- **Fallback**: When subtype_key is empty, invalid, or parent_industry_key does not match profile primary industry, **only parent-industry rules** are returned. No partial subtype application.
- **Reuse**: Same rule shapes are reusable by docs, previews, and Build Plan review surfaces; consumers receive a merged list of display items (rule_key or subtype_rule_key, severity, caution_summary, optional source: parent | subtype).

---

## 4. Registry behavior

- **Subtype_Compliance_Rule_Registry**: load(array), get(subtype_rule_key), get_for_subtype(parent_industry_key, subtype_key), get_all(). Read-only after load.
- Load validates required fields, severity enum, and key patterns; invalid entries skipped. Duplicate subtype_rule_key: first wins.
- No public mutation; registry is populated from built-in definitions (SubtypeComplianceRules/) or optional import only.

---

## 5. Limits of the system

- **Not legal advice.** Subtype rules are editorial guardrails only.
- **No jurisdiction-specific law engines.** Subtype rules are industry/subtype oriented, not legal-domain specific.
- **No hard blocking.** Consumers may display warnings or hints; they do not prevent save or publish.
- **Parent remains base.** Subtype rules do not replace parent rules; they refine or add.

---

## 6. Implementation reference

- **Subtype_Compliance_Rule_Registry**: Domain\Industry\Registry\Subtype_Compliance_Rule_Registry.
- **Contract**: subtype-compliance-rule-contract.md.
- **Parent rules**: Industry_Compliance_Rule_Registry; industry-compliance-rule-schema.md.
