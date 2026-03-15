# Page Template Industry Affinity Contract

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md page-template metadata rules; page-template-registry-schema.md.

**Status**: Optional industry-affinity metadata on page templates for page template ranking, filtering, hierarchy planning, and AI recommendation. Page templates remain valid without any industry metadata.

---

## 1. Purpose

- Allow page templates to declare **positive affinity**, **required use**, **discouraged use**, **hierarchy fit**, **LPagery fit**, and **usage notes** per industry.
- Support industry-aware page recommendations and hierarchy planning **without forking** the page template library.
- Keep industry metadata **additive and optional**; existing templates need no changes.

---

## 2. Optional fields (page-template-registry-schema §5)

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| **industry_affinity** | array of strings or map (industry_key => string) | Each key: non-empty, pattern `^[a-z0-9_-]+$`; max 64 chars. | Industry keys where this page template is a strong/good fit. |
| **industry_required** | array of strings | Each element: industry_key pattern; max 64 chars. | Industry keys where this template is required or strongly recommended. |
| **industry_discouraged** | array of strings | Each element: industry_key pattern; max 64 chars. | Industry keys where this template is discouraged. |
| **industry_hierarchy_fit** | map (industry_key => string) or array of strings | Keys/elements: industry_key pattern; values max 512 chars. | Per-industry hierarchy fit note or classification. |
| **industry_lpagery_fit** | map (industry_key => string) or string | Keys: industry_key pattern; values max 512 chars. | Per-industry LPagery/token fit note. |
| **industry_notes** | map (industry_key => string) or string | Keys: industry_key pattern; values max 1024 chars. | Per-industry usage notes. |

- **industry_key** pattern: `^[a-z0-9_-]+$`, max 64 chars (aligned with Industry_Pack_Schema).
- Invalid or malformed values must **fail safely**: validation returns errors; template can still be stored with metadata stripped or rejected at read time per implementation.

---

## 3. Read model

- Page template registry (or related resolver) exposes page definitions that may include these optional fields.
- Consumers (e.g. template ranking, hierarchy planning, AI recommendation) read the fields when present; absence means no industry-specific guidance.

---

## 4. Validation

- **industry_affinity**: If array, all elements non-empty strings matching industry_key pattern. If map, all keys match pattern.
- **industry_required**, **industry_discouraged**: All elements non-empty strings matching industry_key pattern.
- **industry_hierarchy_fit**, **industry_lpagery_fit**: If map, all keys match industry_key pattern; values string within max length.
- **industry_notes**: If map, keys match pattern; values string. If string, single note (no per-industry).
- Page templates **without** any of these fields are valid; validation runs only when at least one field is present.

---

## 5. Extensibility

- Future industries are added as new keys in industry pack registry; page metadata can reference them without schema change.
- New optional fields can be added in later contracts.

---

## 6. Implementation reference

- **Page_Template_Schema**: Optional field constants; **validate_industry_affinity_metadata( array $page_template ): array** returns list of errors; empty when valid or when no industry fields present.
- **page-template-registry-schema.md**: §5 optional fields table and §5.2 industry affinity block.
- **data-schema-appendix.md**: Summary of page template industry-affinity metadata.
