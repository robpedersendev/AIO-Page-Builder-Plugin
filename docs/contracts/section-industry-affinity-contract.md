# Section Template Industry Affinity Contract

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md section-template metadata rules; section-registry-schema.md.

**Status**: Optional industry-affinity metadata on section templates for section selection, filtering, and AI planning. Sections remain valid without any industry metadata.

---

## 1. Purpose

- Allow section templates to declare **positive affinity**, **discouraged use**, **CTA fit**, and **usage notes** per industry (e.g. cosmetology, realtor, plumber, disaster recovery).
- Support template ranking and AI planning **without forking** the section library or creating industry-specific duplicate sections.
- Keep industry affinity **optional**; existing sections need no changes.

---

## 2. Optional fields (section-registry-schema §10)

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| **industry_affinity** | array of strings or map (industry_key => string) | Each key: non-empty, pattern `^[a-z0-9_-]+$`; values: short label (e.g. strong, good). | Industry keys where this section is a strong or good fit. |
| **industry_discouraged** | array of strings | Each element: industry_key pattern; max 64 chars. | Industry keys where this section is discouraged. |
| **industry_cta_fit** | array of strings or map (industry_key => string) | Each value max 512 chars. | Per-industry CTA fit note or classification. |
| **industry_notes** | map (industry_key => string) or string | Keys: industry_key pattern; values max 1024 chars. | Per-industry usage notes. |

- **industry_key** pattern: `^[a-z0-9_-]+$`, max 64 chars (aligned with Industry_Pack_Schema).
- Invalid or malformed values must **fail safely**: validation returns errors; section can still be stored with metadata stripped or rejected at read time per implementation.

---

## 3. Read model

- Section registry (or related resolver) exposes section definitions that may include these optional fields.
- Consumers (e.g. template ranking, AI planning) read industry_affinity, industry_discouraged, industry_cta_fit, industry_notes when present; absence means no industry-specific guidance.

---

## 4. Validation

- **industry_affinity**: If array, all elements must be non-empty strings matching industry_key pattern. If map, all keys must match pattern; values string.
- **industry_discouraged**: All elements non-empty strings matching industry_key pattern.
- **industry_cta_fit**: If array or map, keys/elements must be valid industry_key pattern when used as key; values string, max length.
- **industry_notes**: If map, keys industry_key pattern; values string. If string, single note (no per-industry).
- Sections **without** any of these fields are valid; validation runs only when at least one field is present.

---

## 5. Extensibility

- Future industries are added as new keys in industry pack registry; section metadata can reference them without schema change.
- New optional fields (e.g. industry_hierarchy_fit) can be added in later contracts.

---

## 6. Implementation reference

- **Section_Schema**: Optional field constants (industry_affinity, industry_discouraged, industry_cta_fit, industry_notes); **validate_industry_affinity_metadata( array $section ): array** returns list of errors; empty array when valid or when no industry fields present.
- **section-registry-schema.md**: §10 optional fields table and §10.1 industry affinity block.
- **data-schema-appendix.md**: Summary of section industry-affinity metadata.
- **industry-section-recommendation-contract.md**: Resolver that consumes this metadata (and industry profile/pack) to produce scored, ranked section recommendations; section registry remains authoritative.
