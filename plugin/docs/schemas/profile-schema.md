# Profile Schema (Current Editable State)

**Document type:** Authoritative contract for brand and business profile data (spec §22).  
**Governs:** Field names, types, required/optional, repeatability, validation, sensitivity, exportability.  
**Related:** profile-snapshot-schema.md (immutable run-time snapshots); storage-strategy-matrix.md (options storage).  
**Additive:** Site-level industry context (primary/secondary industry, subtype, service/geo model) is stored separately per **industry-profile-schema.md** (docs/schemas/) and Industry_Profile_Repository; it does not replace brand_profile or business_profile.

---

## 1. Design rules

- **Current state only:** This schema describes the editable, “current truth” profile. Historical AI-run inputs use the snapshot schema (§22.11).
- **Structured for AI:** Fields are defined so AI input assembly does not guess payload shapes. No freeform blob.
- **No secrets:** No API keys, passwords, or tokens in profile or asset references (§22.10).
- **Assets are references:** Asset fields hold references (e.g. attachment ID, path); validation and permission checks apply at use time.

---

## 2. Root objects

| Root object | Purpose | Storage | Exportable |
|-------------|---------|---------|------------|
| **brand_profile** | Visual, tonal, strategic identity (§22.1) | options | Yes |
| **business_profile** | Operational, commercial, audience, market context (§22.2) | options | Yes |

Both are single objects per site (not arrays). Child arrays live under these roots.

---

## 3. Brand profile

**Object:** `brand_profile`

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| brand_positioning_summary | string | Yes (planning) | No | `""` | Min length for sufficiency; warn if placeholder-like | admin-visible | Yes | Yes |
| brand_voice_summary | string | Yes (planning) | No | `""` | Min length; warn if empty | admin-visible | Yes | Yes |
| voice_tone | object | No | No | see §3.1 | — | admin-visible | Yes | Yes |
| preferred_cta_style | string | No | No | `""` | Allowed values or freeform per product | admin-visible | Yes | Yes |
| asset_references | array of asset_ref | No | Yes | `[]` | See §7 | admin-visible | Yes | Yes |
| additional_brand_rules | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| content_restrictions | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

### 3.1 Brand voice / tone structure (object `voice_tone`) — §22.9

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| core_tone_descriptors | string[] | No | Yes | `[]` | Constrained or freeform per product | admin-visible | Yes | Yes |
| prohibited_tone_descriptors | string[] | No | Yes | `[]` | — | admin-visible | Yes | Yes |
| formality_level | string | No | No | `""` | Enum: `formal` \| `neutral` \| `informal` \| `mixed` \| `not_applicable` | admin-visible | Yes | Yes |
| emotional_positioning | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| clarity_vs_sophistication | string | No | No | `""` | Enum: `clarity` \| `balanced` \| `sophistication` \| `not_applicable` | admin-visible | Yes | Yes |
| audience_style_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| copy_restrictions_or_preferences | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

**N/A handling:** `formality_level` and `clarity_vs_sophistication` may use `not_applicable` only where product logic permits; validation must not treat N/A as invalid when allowed.

---

## 4. Business profile

**Object:** `business_profile`

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| business_name | string | Yes | No | `""` | Non-empty for planning | admin-visible | Yes | Yes |
| business_type | string | Yes | No | `""` | Controlled values or freeform per product | admin-visible | Yes | Yes |
| current_site_url | string | No | No | `""` | URL format when present | admin-visible | Yes | Yes |
| preferred_contact_or_conversion_goals | string | Yes (planning) | No | `""` | Min length; warn if placeholder | admin-visible | Yes | Yes |
| primary_offers_summary | string | Yes (planning) | No | `""` | Min length | admin-visible | Yes | Yes |
| target_audience_summary | string | Yes (planning) | No | `""` | Min length | admin-visible | Yes | Yes |
| core_geographic_market | string | Yes (planning) | No | `""` | Non-empty for planning | admin-visible | Yes | Yes |
| personas | array of persona | No | Yes | `[]` | See §5 | admin-visible | Yes | Yes |
| services_offers | array of service_offer | No | Yes | `[]` | See §6 | admin-visible | Yes | Yes |
| competitors | array of competitor | No | Yes | `[]` | See §5 (competitor) | admin-visible | Yes | Yes |
| geography | array of geography_entry | No | Yes | `[]` | See §8 | admin-visible | Yes | Yes |
| value_proposition_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| strategic_priorities | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| major_differentiators | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| seasonality | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| compliance_or_legal_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| preferred_content_emphasis | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| existing_marketing_language | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| internal_sales_process_notes | string | No | No | `""` | Optional; internal | admin-visible | Yes | Yes |
| visual_inspiration_references | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

---

## 5. Persona (audience) — §22.6

**Object:** `persona` (array element under `business_profile.personas`)

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| persona_name_or_role | string | Yes | No | `""` | Non-empty | admin-visible | Yes | Yes |
| demographic_or_market_description | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| goals | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| pain_points | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| buying_motivations | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| objections | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| service_relevance | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| conversion_expectations | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| tone_sensitivity_or_messaging_preference | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

---

## 6. Service / offer — §22.7

**Object:** `service_offer` (array element under `business_profile.services_offers`)

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| name | string | Yes | No | `""` | Non-empty | admin-visible | Yes | Yes |
| category | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| short_description | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| strategic_importance | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| target_audience | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| geographic_applicability | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| offer_relationships | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| hierarchy_hints | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| dedicated_pages_likely | string | No | No | `""` | Enum: `yes` \| `no` \| `optional` \| `not_applicable` | admin-visible | Yes | Yes |

---

## 7. Competitor — §22.5

**Object:** `competitor` (array element under `business_profile.competitors`)

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| competitor_name | string | Yes | No | `""` | Non-empty | admin-visible | Yes | Yes |
| competitor_url | string | No | No | `""` | URL format when present | admin-visible | Yes | Yes |
| market_relevance | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| competitive_positioning_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| differentiation_observations | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| strengths_weaknesses_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

---

## 8. Geography entry — §22.8

**Object:** `geography_entry` (array element under `business_profile.geography`)

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| primary_location | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| secondary_locations | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| service_area | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| shipping_area | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| region_type | string | No | No | `""` | Optional | admin-visible | Yes | Yes |
| in_person_vs_remote | string | No | No | `""` | Enum: `in_person` \| `remote` \| `both` \| `not_applicable` | admin-visible | Yes | Yes |
| location_specific_offer_notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

---

## 9. Asset reference — §22.10

**Object:** `asset_ref` (array element under `brand_profile.asset_references` or equivalent)

| Field | Type | Required | Repeatable | Default | Validation | Sensitivity | Export | Snapshot |
|-------|------|----------|------------|---------|------------|--------------|--------|----------|
| role | string | Yes | No | `""` | Enum: `logo` \| `visual_identity` \| `color_reference` \| `typography_reference` \| `other` | admin-visible | Yes | Yes |
| attachment_id | string/int | No | No | null | Valid WP attachment ID when provided; validate at use time | admin-visible | Yes | Yes |
| path_or_url | string | No | No | `""` | Safe path/URL; no secrets | admin-visible | Yes | Yes |
| notes | string | No | No | `""` | Optional | admin-visible | Yes | Yes |

Assets are references only; file type and permission checks apply when files are used, not at schema level.

---

## 10. Validation expectations (§22.12)

- **Required-field validation:** All fields marked Required (planning) must pass before considering the profile planning-ready; empty string may be stored but triggers validation failure for planning.
- **URL validation:** `current_site_url`, `competitor_url` must be valid URL format when non-empty.
- **Min content sufficiency:** Brand/business summaries and primary-offers/target-audience/geography fields have minimum content checks; weak or placeholder-like entries produce warnings.
- **Controlled values:** Enum fields accept only listed values or `not_applicable` where specified.
- **N/A:** Use `not_applicable` only where the schema or product logic permits; treat as valid when allowed.

---

## 11. Example valid objects

### 11.1 Minimal valid brand_profile (planning-ready)

```json
{
  "brand_profile": {
    "brand_positioning_summary": "We position as the trusted local partner for small business accounting.",
    "brand_voice_summary": "Professional but approachable; clear and jargon-free.",
    "voice_tone": {
      "core_tone_descriptors": ["approachable", "clear"],
      "prohibited_tone_descriptors": ["slick", "corporate-speak"],
      "formality_level": "neutral",
      "clarity_vs_sophistication": "clarity"
    },
    "preferred_cta_style": "Soft ask with one primary button",
    "asset_references": [],
    "additional_brand_rules": "",
    "content_restrictions": ""
  }
}
```

### 11.2 Minimal valid business_profile (planning-ready)

```json
{
  "business_profile": {
    "business_name": "Acme Accounting LLC",
    "business_type": "Professional services",
    "current_site_url": "https://example.com",
    "preferred_contact_or_conversion_goals": "Contact form and phone; goal is consultation booking.",
    "primary_offers_summary": "Tax preparation, bookkeeping, and payroll for small businesses.",
    "target_audience_summary": "Small business owners and sole proprietors in the metro area.",
    "core_geographic_market": "Metro Denver",
    "personas": [
      {
        "persona_name_or_role": "Small business owner",
        "demographic_or_market_description": "Owner-operator, 5–20 employees",
        "goals": "Stay compliant and save time",
        "pain_points": "Complexity of tax rules"
      }
    ],
    "services_offers": [
      {
        "name": "Tax preparation",
        "category": "Tax",
        "short_description": "Full-year and quarterly tax prep",
        "dedicated_pages_likely": "yes"
      }
    ],
    "competitors": [],
    "geography": [
      {
        "primary_location": "Denver, CO",
        "service_area": "Metro Denver and Front Range",
        "in_person_vs_remote": "both"
      }
    ]
  }
}
```

---

## 12. Example invalid or discouraged

### 12.1 Invalid: missing required planning fields

```json
{
  "business_profile": {
    "business_name": "",
    "business_type": "Services",
    "primary_offers_summary": "",
    "target_audience_summary": "",
    "core_geographic_market": ""
  }
}
```

**Reason:** `business_name`, `primary_offers_summary`, `target_audience_summary`, `core_geographic_market` are required for planning; empty values must fail planning-ready validation.

### 12.2 Invalid: enum value not allowed

```json
{
  "brand_profile": {
    "voice_tone": {
      "formality_level": "super_casual"
    }
  }
}
```

**Reason:** `formality_level` allows only `formal` | `neutral` | `informal` | `mixed` | `not_applicable`.

### 12.3 Invalid: URL format

```json
{
  "business_profile": {
    "current_site_url": "not-a-valid-url"
  }
}
```

**Reason:** URL validation must fail when field is non-empty and not a valid URL.

---

## 13. Section 22 coverage checklist

- [ ] §22.1 Brand profile purpose — brand_profile root and voice_tone
- [ ] §22.2 Business profile purpose — business_profile root
- [ ] §22.3 Required data categories — business_name, business_type, primary offers, target audience, core geographic market, brand positioning/voice summaries, current site URL, contact/conversion goals, basic brand asset references (asset_references)
- [ ] §22.4 Optional/advanced — secondary offers (services_offers), seasonality, strategic_priorities, differentiators, compliance_notes, content_emphasis, marketing_language, value_proposition, additional_brand_rules, content_restrictions, internal_sales_notes, visual_inspiration
- [ ] §22.5 Competitor structure — competitor object (name, url, market_relevance, positioning, differentiation, strengths/weaknesses)
- [ ] §22.6 Audience/persona — persona object (name/role, demographic, goals, pain points, motivations, objections, service relevance, conversion expectations, tone sensitivity)
- [ ] §22.7 Service/product — service_offer object (name, category, description, importance, audience, geography, relationships, hierarchy, dedicated_pages_likely)
- [ ] §22.8 Geographic market — geography_entry (primary/secondary location, service/shipping area, region_type, in_person_vs_remote, location-specific notes)
- [ ] §22.9 Brand voice/tone — voice_tone (core/prohibited tone, formality, emotional positioning, clarity vs sophistication, style notes, copy restrictions, CTA style)
- [ ] §22.10 Asset intake — asset_ref (role, attachment_id, path_or_url, notes); validation and safe handling noted
- [ ] §22.11 Edit/reuse/snapshot — snapshot schema in profile-snapshot-schema.md; current schema is editable state only
- [ ] §22.12 Validation — required-field, URL, min sufficiency, enum, N/A handling documented

All §22 data categories are covered in this schema. No planning or AI payload field should be invented without aligning to this document.
