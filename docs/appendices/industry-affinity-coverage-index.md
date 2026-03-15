# Industry Affinity Coverage Index (Prompts 363, 364)

**Spec:** section-industry-affinity-contract.md; page-template-industry-affinity-contract.md; industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md.  
**Purpose:** Single index to coverage of industry-affinity metadata across section and page-template libraries for the first launch industries (cosmetology_nail, realtor, plumber, disaster_recovery).

---

## 1. Section library

- **Coverage report:** docs/qa/section-industry-metadata-coverage-report.md.
- **Batches with industry_affinity:** SEC-01 (Hero), SEC-02 (Trust/Proof), SEC-03 (Feature/Benefit), SEC-05 (Process/Timeline/FAQ), SEC-06 (Media/Listing/Profile), SEC-07 (Legal/Policy/Utility), SEC-08 (CTA), SEC-09 (Gap-closing), Expansion pack.
- **Fields used:** `industry_affinity` (array of industry keys). Optional: `industry_discouraged`, `industry_cta_fit`, `industry_notes` (not populated in initial pass).

---

## 2. Page template library

- **Coverage report:** docs/qa/page-template-industry-metadata-coverage-report.md (created in Prompt 364).
- **Fields used:** `industry_affinity`, optional `industry_required`, `industry_discouraged`, `industry_hierarchy_fit`, `industry_lpagery_fit`, `industry_notes`.

---

## 3. Industry keys (first launch)

| industry_key | Description |
|--------------|-------------|
| cosmetology_nail | Cosmetology / Nail Technician |
| realtor | Realtor |
| plumber | Plumber |
| disaster_recovery | Disaster Recovery |

All affinity metadata uses these keys; invalid or unknown keys are rejected by schema validation.
