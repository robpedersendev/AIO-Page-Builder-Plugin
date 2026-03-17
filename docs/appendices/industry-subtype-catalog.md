# Industry Subtype Catalog (Prompt 415)

**Spec**: industry-subtype-schema.md; industry-subtype-extension-contract.md.

This appendix lists built-in industry subtype definitions loaded by the Industry Subtype Registry. Subtypes are overlays on parent industry packs and provide structured nuance (e.g. buyer vs seller realtor, residential vs commercial plumber) without forking whole packs.

---

## 1. Cosmetology / Nail (`cosmetology_nail`)

| Subtype key | Label | Intended use case |
|-------------|--------|-------------------|
| `cosmetology_nail_luxury_salon` | Luxury Nail Salon | High-end salon or spa nail services; experience, premium products, appointment-based booking. Full-service nail salons, day spas with nail departments. |
| `cosmetology_nail_mobile_tech` | Mobile Nail Technician | Nail tech who travels to client locations (home, office, events). Booking, service area, convenience. Mobile nail artists, event stylists. |

**Source**: `plugin/src/Domain/Industry/Registry/Subtypes/cosmetology-nail-subtypes.php`.

---

## 2. Realtor (`realtor`)

| Subtype key | Label | Intended use case |
|-------------|--------|-------------------|
| `realtor_buyer_agent` | Buyer-Focused Realtor | Agent primarily serving home buyers: search support, buyer guides, pre-approval and closing. Buyer’s agents, buyer specialist teams. |
| `realtor_listing_agent` | Seller-Focused Realtor | Agent primarily serving sellers: listing presentation, staging, marketing, sale process. Listing agents, seller specialist teams. |

**Source**: `plugin/src/Domain/Industry/Registry/Subtypes/realtor-subtypes.php`.

---

## 3. Plumber (`plumber`)

| Subtype key | Label | Intended use case |
|-------------|--------|-------------------|
| `plumber_residential` | Residential Plumber | Plumbing for homes and small properties: repairs, installations, emergency. Local residential plumbers, home-service focus. |
| `plumber_commercial` | Commercial Plumber | Plumbing for commercial and industrial properties: maintenance contracts, large installations, compliance. Commercial plumbing contractors. |

**Source**: `plugin/src/Domain/Industry/Registry/Subtypes/plumber-subtypes.php`.

---

## 4. Disaster Recovery (`disaster_recovery`)

| Subtype key | Label | Intended use case |
|-------------|--------|-------------------|
| `disaster_recovery_residential` | Residential Restoration | Restoration and mitigation for homes: water, fire, storm; insurance and homeowner focus. Residential restoration companies. |
| `disaster_recovery_commercial` | Commercial Restoration | Restoration for commercial and industrial: business continuity, larger-scale mitigation, commercial insurance. Commercial restoration contractors. |

**Source**: `plugin/src/Domain/Industry/Registry/Subtypes/disaster-recovery-subtypes.php`.

---

## 5. Loading and validation

Subtypes are loaded by **Industry_Packs_Module** via `Industry_Subtype_Registry::get_builtin_definitions()` (sourced from **Subtypes/Builtin_Subtypes.php**, which aggregates the four per-industry files). Invalid or duplicate subtype keys are skipped at load. Profile field `industry_subtype_key` must reference a subtype whose `parent_industry_key` matches the profile’s `primary_industry_key`; otherwise resolution falls back to parent industry only. See industry-subtype-extension-contract.md and Industry_Subtype_Resolver.

---

## 6. Subtype caution rules

Subtype-specific compliance/caution rules (subtype-compliance-rule-schema.md, subtype-compliance-rule-contract.md) are seeded in **SubtypeComplianceRules/subtype-compliance-rule-definitions.php** and loaded via **Subtype_Compliance_Rule_Registry**. They refine or add to parent-industry rules for the launch subtype set (e.g. mobile service area, buyer/listing agent phrasing, residential/commercial plumber and disaster recovery). Resolution: parent rules base layer, then subtype rules when subtype is valid; fallback to parent only when no or invalid subtype.
