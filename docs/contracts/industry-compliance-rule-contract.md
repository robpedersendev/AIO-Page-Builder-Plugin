# Industry Compliance and Caution Rule Contract (Prompt 405)

**Spec:** industry-pack-extension-contract.md; industry-compliance-rule-schema (docs/schemas/industry-compliance-rule-schema.md).

**Status:** Contract for the Industry Compliance and Caution Rule subsystem. Rules are **advisory** and structured; they do not provide legal advice or enforce compliance.

---

## 1. Purpose

- Define the **contract** for industry compliance/caution rules: schema, registry, and consumer expectations.
- Support **claims language cautions**, **certification/trust wording**, **local-market sensitivity**, **testimonial/review cautions**, **pricing-disclosure cautions**, and similar **editorial guardrails** per industry.
- Ensure **no overclaiming**: the system surfaces guidance only; it does not guarantee legal or regulatory compliance.

---

## 2. Rule object (summary)

Per [industry-compliance-rule-schema.md](../schemas/industry-compliance-rule-schema.md):

- **rule_key**, **industry_key**, **severity** (info | caution | warning), **caution_summary**, **status** (active | draft | archived).
- Optional: scope, target_section_family, target_page_family, guidance_text, version_marker.
- Invalid or duplicate rules are skipped at load.

---

## 3. Registry

- **Industry_Compliance_Rule_Registry**: Read-only after load. Methods: load(array), get(rule_key), get_for_industry(industry_key), get_all().
- **No public mutation.** Rules are loaded from built-in definitions (e.g. ComplianceRules/ under Industry registry) or from an optional import path; no user-facing create/update/delete in this contract.
- **Fail-safe:** Invalid rule_key, industry_key, or severity causes the entry to be skipped; no throw.

---

## 4. Consumers (future)

- **Helper docs / one-pagers:** May reference rule_key or industry + scope to display caution text in admin or Build Plan review.
- **Build Plan review:** May surface applicable caution rules when industry is set and section/page family matches.
- **Admin previews:** May show compliance hints where rule scope matches current context.

Consumers are **not** defined in this prompt; the registry and schema enable them.

---

## 5. Relation to industry pack

- Industry pack schema and extension contract may reference **compliance_rule_refs** (list of rule_key) or rules may be **scoped by industry_key** only; resolution by industry + optional scope.
- Pack definitions do not require compliance rules; rules are additive.

---

## 6. Cross-references

- [industry-compliance-rule-schema.md](../schemas/industry-compliance-rule-schema.md) — Full schema.
- [industry-pack-extension-contract.md](industry-pack-extension-contract.md) — Subsystem boundary.
- [industry-pack-schema.md](../schemas/industry-pack-schema.md) — Optional compliance_rule_refs when added.
