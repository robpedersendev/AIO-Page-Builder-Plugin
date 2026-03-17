# Subtype Compliance and Caution Rule Contract (Prompt 446)

**Spec:** industry-compliance-rule-contract.md; industry-subtype-extension-contract.md; subtype-compliance-rule-schema.md (docs/schemas/subtype-compliance-rule-schema.md).

**Status:** Contract for subtype-scoped compliance/caution rules. Subtype rules are **additive and bounded** refinements to parent-industry caution rules. Advisory only; no legal advice or enforcement.

---

## 1. Purpose

- Define the **contract** for subtype-specific caution rules: schema, registry, composition with parent rules, and consumer expectations.
- Support **subtype nuance** (e.g. mobile service claims, commercial capability claims, buyer/seller phrasing, location/service-area phrasing) without fragmenting the core caution system or overclaiming legal/compliance authority.
- Ensure **safe fallback** to parent-industry caution behavior when subtype is missing or invalid.

---

## 2. Subtype rule object (summary)

Per [subtype-compliance-rule-schema.md](../schemas/subtype-compliance-rule-schema.md):

- **subtype_rule_key**, **subtype_key**, **parent_industry_key**, **severity** (info | caution | warning), **caution_summary**, **status** (active | draft | archived).
- Optional: scope, target_section_family, target_page_family, guidance_text, **refinement_of_rule_key** (when set, refines that parent rule), **additive_note**, version_marker.
- Invalid or duplicate subtype rules are skipped at load.

---

## 3. Allowed override/refinement behavior

- **Refinement**: When **refinement_of_rule_key** is set, the subtype rule refines the parent rule with that rule_key for the given subtype. Consumers may show the subtype caution_summary (and optional guidance_text) in place of or in addition to the parent rule for that context; exact display is consumer-defined.
- **Additive**: When **refinement_of_rule_key** is empty, the subtype rule is additive only (additional caution item for the subtype).
- **Bounded**: Subtype rules do not remove or disable parent rules; they layer on top. Parent-industry rules remain the base layer.

---

## 4. Composition order

1. **Parent-industry rules** for the profile’s primary_industry_key (from Industry_Compliance_Rule_Registry).
2. **Subtype rules** for (parent_industry_key, subtype_key) when profile has a valid industry_subtype_key matching that parent (from Subtype_Compliance_Rule_Registry).
3. **Deduplication/refinement**: When a subtype rule has refinement_of_rule_key, consumers may merge display (e.g. show subtype summary for that key when subtype is in context); otherwise both parent and subtype entries appear.

---

## 5. Registry and resolution

- **Subtype_Compliance_Rule_Registry**: Read-only after load. Methods: load(array), get(subtype_rule_key), get_for_subtype(parent_industry_key, subtype_key), get_all().
- **No public mutation.** Subtype rules are loaded from built-in definitions (SubtypeComplianceRules/) or optional import path.
- **Fail-safe:** Invalid subtype_rule_key, subtype_key, parent_industry_key, or severity causes the entry to be skipped; no throw.
- **Resolution:** A resolver or consumer that has both Industry_Compliance_Rule_Registry and Subtype_Compliance_Rule_Registry (and optional subtype context) returns merged, display-safe list for (industry, subtype). When subtype is empty or invalid, only parent rules are returned.

---

## 6. Consumers

- **Helper docs / one-pagers:** May reference subtype rules when subtype context is present.
- **Build Plan review:** May surface parent + subtype caution rules when industry and subtype are set.
- **Admin previews:** May show compliance hints where scope matches and subtype is in context.

Consumers use the same advisory, non-blocking behavior as parent rules.

---

## 7. Relation to parent and subtype extension

- **Parent rules** remain in Industry_Compliance_Rule_Registry; unchanged.
- **Subtype extension contract** (industry-subtype-extension-contract.md): subtype object may reference **caution_rule_refs**; subtype caution rules in this contract are the implementation of that overlay. Resolution is by (parent_industry_key, subtype_key).
- **Fallback:** When no subtype is selected or subtype ref is invalid, only parent-industry caution behavior applies.

---

## 8. Cross-references

- [subtype-compliance-rule-schema.md](../schemas/subtype-compliance-rule-schema.md) — Full schema.
- [industry-compliance-rule-contract.md](industry-compliance-rule-contract.md) — Parent rule contract.
- [industry-subtype-extension-contract.md](industry-subtype-extension-contract.md) — Subtype overlay scope.
