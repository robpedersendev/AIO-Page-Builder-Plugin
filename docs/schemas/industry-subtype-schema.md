# Industry Subtype Schema (Prompt 413)

**Spec**: industry-pack-extension-contract.md; industry-subtype-extension-contract.md; industry-profile-schema.md.

**Status**: Canonical schema for industry subtype objects. Subtypes are structured sub-variants of a parent industry pack (e.g. luxury nail salon, mobile nail tech, residential plumber, buyer-agent realtor) without forking whole packs.

---

## 1. Purpose

- Define the **canonical shape** of an industry subtype object.
- Support **validation**, **versioning**, and **registry loading** consistent with industry pack patterns.
- Ensure subtypes remain **overlays** on a parent industry; they do not replace the parent pack.

---

## 2. Required fields

| Field | Type | Description |
|-------|------|-------------|
| **subtype_key** | string | Stable, unique key for the subtype within the subsystem (e.g. `realtor_buyer_agent`, `plumber_residential`). Pattern: `^[a-z0-9_-]+$`; max length 64. |
| **parent_industry_key** | string | Industry pack key this subtype belongs to. Must match an industry_key in Industry_Pack_Registry. |
| **label** | string | Human-readable subtype name. |
| **summary** | string | Short description of the subtype and how it differs from the parent. |
| **status** | string | Lifecycle: `active`, `draft`, or `deprecated`. Only `active` subtypes are used for resolution and overlays. |
| **version_marker** | string | Schema version (e.g. `1`). Used to reject unsupported versions at load. |

---

## 3. Optional fields (allowed override scope)

| Field | Type | Description |
|-------|------|-------------|
| **cta_posture_ref** | string | Optional override or refinement of CTA posture for this subtype (reference to CTA pattern or posture key). |
| **page_family_emphasis** | list&lt;string&gt; | Page template families to emphasize for this subtype (overlay on parent pack supported_page_families). |
| **starter_bundle_ref** | string | Optional starter bundle key recommended for this subtype (must belong to parent industry or be subtype-specific). |
| **helper_overlay_refs** | list&lt;string&gt; | Section-helper overlay refs to add or prioritize for this subtype. |
| **one_pager_overlay_refs** | list&lt;string&gt; | Page one-pager overlay refs to add or prioritize for this subtype. |
| **caution_rule_refs** | list&lt;string&gt; | Compliance/caution rule keys (advisory) for this subtype. |
| **metadata** | map | Optional metadata (sort order, internal notes). No secrets. |

---

## 4. Validation rules

- **subtype_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max length 64. Uniqueness is global within the subtype registry (or scoped by parent_industry_key per implementation).
- **parent_industry_key**: Non-empty; must exist in Industry_Pack_Registry when validated in full context.
- **label**, **summary**: Non-empty when present; bounded length (e.g. 256 / 1024).
- **status**: One of `active`, `draft`, `deprecated`.
- **version_marker**: Must match supported schema version (e.g. `1`). Unsupported → reject at load.
- **Refs**: All override refs (cta_posture_ref, starter_bundle_ref, helper_overlay_refs, one_pager_overlay_refs, caution_rule_refs) must be non-empty strings or arrays of non-empty strings; resolution is defined by respective registries. Invalid refs fail safely at resolution (fallback to parent).

---

## 5. Relationship to Industry Profile

- The site Industry Profile may store an optional **industry_subtype_key** (or equivalent) that references a subtype_key.
- When present, **industry_subtype_key** must reference a subtype whose **parent_industry_key** matches the profile’s **primary_industry_key**. Otherwise the subtype ref is invalid and resolution falls back to parent industry only.
- When no subtype is selected or the ref is invalid, behavior is determined by the parent industry pack only (see industry-subtype-extension-contract.md).

---

## 6. Versioning and export

- **version_marker** on the subtype object identifies the schema version. Plugin supports a fixed set (e.g. `1`). Unsupported versions cause validation failure.
- Subtype definitions are **exportable** and **portable**; no secrets. Export/restore may include subtype definitions when the industry export includes pack-related data; profile stores only the selected subtype_key ref.

---

## 7. Implementation reference

- **Industry_Subtype_Resolver** (Prompt 414): Resolves effective context (parent + subtype) from profile; fallback to parent when subtype missing or invalid.
- **Industry_Profile_Schema**: Optional field for subtype ref (industry_subtype_key); validation in Industry_Profile_Validator.
- Registry: Future **Industry_Subtype_Registry** (or equivalent) loads and exposes subtypes by subtype_key and by parent_industry_key. Not required in Prompt 413; schema and contract define the model for implementation.
