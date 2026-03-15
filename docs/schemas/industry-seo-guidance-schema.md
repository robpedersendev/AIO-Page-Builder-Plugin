# Industry SEO and Entity-Guidance Rule Schema

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md; helper-doc and one-pager contracts.

**Status**: Schema for industry SEO and entity-page guidance rules. Rules are advisory/overlay-driven; no direct mutation of external SEO plugins. Structured and exportable.

---

## 1. Purpose

- Provide **structured, registry-backed** guidance for page titles, H1 patterns, internal linking, local SEO posture, FAQ/review emphasis, and entity-specific cautions per industry (and optionally per page family).
- Support docs, planner, and future UI consumption without changing live SEO plugin settings or injecting frontend metadata in this prompt.
- Rules remain **advisory**; existing helper-doc and one-pager systems remain authoritative for final surfaced docs.

---

## 2. Rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **guidance_rule_key** | string | Yes | Stable unique key (e.g. `legal_entity_01`, `realtor_local_01`). Pattern `^[a-z0-9_-]+$`; max 64. |
| **industry_key** | string | Yes | Industry pack key (same pattern; max 64). |
| **version_marker** | string | Yes | Schema version (e.g. `1`). Unsupported versions rejected at load. |
| **status** | string | Yes | `active`, `draft`, or `deprecated`. Only `active` rules are used. |
| **page_family** | string | No | Optional scope: page template family (e.g. `landing_legal`, `hub_services`). Empty = industry-wide. |
| **title_patterns** | string or list&lt;string&gt; | No | Page title patterns or recommendations; max 1024 per item. |
| **h1_patterns** | string or list&lt;string&gt; | No | H1 patterns or recommendations; max 1024 per item. |
| **internal_link_guidance** | string | No | Internal linking recommendations; max 2048. |
| **local_seo_posture** | string | No | Local/entity SEO posture (e.g. strong, moderate, minimal); max 512. |
| **faq_emphasis** | string | No | FAQ section emphasis guidance; max 1024. |
| **review_emphasis** | string | No | Review/testimonial emphasis guidance; max 1024. |
| **entity_cautions** | string | No | Entity-page or local-page cautions; max 1024. |
| **metadata** | map | No | Optional metadata (no secrets). |

---

## 3. Validation and safety

- **guidance_rule_key**, **industry_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. **guidance_rule_key** unique within registry.
- **status**: One of `active`, `draft`, `deprecated`.
- **version_marker**: Must match supported schema version; unsupported → reject at load.
- **page_family**: When present, non-empty string; max 64.
- Text fields: When present, string or list of strings; bounded lengths. Invalid or malformed rule objects **fail safely** (skipped at load); no throw.

---

## 4. Relation to industry pack

- Industry pack **seo_guidance_ref** may reference a **guidance_rule_key** (or a set key). Resolution via Industry_SEO_Guidance_Registry::get( key ) or list_by_industry( industry_key ).
- Rules are **internal/config-driven**; no live third-party plugin mutation.

---

## 5. Implementation reference

- **Industry_SEO_Guidance_Registry** (plugin/src/Domain/Industry/Registry/Industry_SEO_Guidance_Registry.php): load(array), get(key), get_all(), list_by_industry(industry_key), list_by_status(status). Invalid entries skipped at load.
