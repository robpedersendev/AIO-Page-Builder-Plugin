# Industry Preview Dummy Data Contract

**Spec**: template-preview-and-dummy-data-contract; industry-pack-extension-contract; §17 Rendering Architecture.

**Status**: Contract. Defines industry-aware synthetic dummy data for preview contexts only. No persistence; no execution use.

---

## 1. Purpose and scope

- **Purpose**: Allow section and page template previews to render with **industry-appropriate placeholder content** (cosmetology/nail, realtor, plumber, disaster_recovery) so previews look realistic for the vertical.
- **Scope**: Preview contexts only (directory detail, compare, composition preview). Output is **overlays** on the existing synthetic data from Synthetic_Preview_Data_Generator; same ACF field shapes; merge semantics.
- **Out of scope**: Persisting dummy data; using the generator in execution or production content; creating copyrighted or trademark-sensitive content.

---

## 2. Architecture

- **Industry_Dummy_Data_Generator**: Read-only service. Given `(purpose_family, industry_key)` returns a **partial** map of field_name => value (overrides only). Unknown industry or family returns empty array. No side effects; no storage.
- **Integration**: Consumers (e.g. state builders or a wrapper) call Synthetic_Preview_Data_Generator for base values, then merge overrides from Industry_Dummy_Data_Generator when industry profile is set. Base generator remains authoritative; industry layer is additive.
- **Compatibility**: Override keys and value shapes must match the field names and structures used by Synthetic_Preview_Data_Generator (headline, subheadline, cta_text, items, steps, etc.). Repeater/array values must be same shape as base (e.g. items as list of { name, quote, role }).

---

## 3. Supported industries and content expectations

| Industry | Content emphasis |
|----------|------------------|
| cosmetology_nail | Bookings, services, staff names, salon tone, certifications, gallery. |
| realtor | Listings, neighborhoods, valuation, open house, buyer/seller. |
| plumber | Services, emergency vs scheduled, trust, licensing, service area. |
| disaster_recovery | 24/7, emergency response, insurance/claims, certifications, urgency. |

All content is **generic placeholders**. No real business names, addresses, or phone numbers. No fake legal text or effective dates. Safe for admin preview only.

---

## 4. Security and safety

- **No persistence**: Generator output is never written to options, post meta, or live content. Used only in memory for the preview render pass.
- **No production data**: Generator does not read customer data, secrets, or real ACF values.
- **No execution path**: Build Plan execution and content generation must not call the industry dummy data generator.

---

## 5. Contract for get_overrides_for_family

- **Signature**: `get_overrides_for_family( string $purpose_family, string $industry_key ): array`
- **Returns**: Associative array of field_name => value (or nested arrays for repeaters). Only keys that should **override** base synthetic data. Empty array when industry_key is not supported or purpose_family has no industry content.
- **Deterministic**: Same inputs produce same outputs. No randomness required for cache stability.

---

## 6. Cross-references

- **template-preview-and-dummy-data-contract.md**: Base preview and dummy-data rules; realistic content by family.
- **Synthetic_Preview_Data_Generator**: Base generator; industry overrides merge on top.
- **industry-pack-extension-contract.md**: Industry pack and overlay model.
