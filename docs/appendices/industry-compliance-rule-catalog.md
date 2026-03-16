# Industry Compliance Rule Catalog (Prompt 406)

**Spec:** industry-compliance-rule-schema.md; industry-compliance-rule-contract.md.

**Status:** Appendix. Lists built-in compliance/caution rules for launch industries. Rules are **advisory only**; no legal advice or enforcement.

---

## 1. Cosmetology / Nail

| rule_key | severity | caution_summary |
|----------|----------|-----------------|
| cosmetology_license_claims | caution | License and certification claims must be accurate and current. |
| cosmetology_testimonial_disclosure | info | Testimonials and reviews should be genuine; avoid misleading before/after. |
| cosmetology_product_claims | caution | Product and result claims must be accurate; avoid overclaiming outcomes. |
| cosmetology_pricing_disclosure | info | Pricing and package descriptions should be clear; avoid hidden fees. |

---

## 2. Realtor

| rule_key | severity | caution_summary |
|----------|----------|-----------------|
| realtor_mls_board | warning | MLS and board rules may govern listings, valuation language, and claims. |
| realtor_local_market_sensitivity | caution | Local and market-area claims should be accurate and not misleading. |
| realtor_pricing_valuation | caution | Valuation and pricing language must be accurate; avoid misleading estimates. |
| realtor_testimonial_review | info | Client testimonials and reviews should be genuine; avoid misleading success claims. |

---

## 3. Plumber

| rule_key | severity | caution_summary |
|----------|----------|-----------------|
| plumber_license_insurance | caution | License and insurance claims must be accurate; jurisdiction rules may apply. |
| plumber_pricing_disclosure | info | Pricing and financing messaging should be clear and accurate. |
| plumber_emergency_claims | warning | Emergency and response-time claims must be accurate; avoid guaranteed response. |
| plumber_testimonial_disclosure | info | Testimonials should be genuine; avoid misleading before/after or outcome claims. |

---

## 4. Disaster Recovery

| rule_key | severity | caution_summary |
|----------|----------|-----------------|
| disaster_recovery_certification | warning | Certification claims (e.g. IICRC) must be accurate; do not imply endorsement. |
| disaster_recovery_insurance_assistance | caution | Insurance assistance messaging must be accurate; do not provide legal advice. |
| disaster_recovery_emergency_response | warning | Emergency and response-time claims must be accurate; avoid guaranteed availability. |

---

## 5. Resolution

- **Registry:** Industry_Compliance_Rule_Registry; definitions in `ComplianceRules/compliance-rule-definitions.php`.
- **Subtype rules:** Subtype_Compliance_Rule_Registry; definitions in `SubtypeComplianceRules/subtype-compliance-rule-definitions.php`. When profile has a valid industry_subtype_key, Industry_Compliance_Warning_Resolver merges parent + subtype rules for display. See subtype-compliance-rule-contract.md.
- **Consumers:** Helper docs, one-pagers, section/page previews, Build Plan review (advisory surfacing only).
- **Industry pack refs:** Optional `compliance_rule_refs` in industry pack schema resolve to this registry.
