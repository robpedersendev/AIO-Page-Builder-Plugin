# Industry Shared Fragment Schema (Prompt 474)

**Spec**: industry-pack-extension-contract.md; industry-section-helper-overlay-schema.md; industry-page-onepager-overlay-schema.md; industry-cta-pattern-contract.md; industry-seo-guidance-schema.md; industry-compliance-rule-schema.md.

**Purpose**: Schema for reusable cross-industry artifact fragments (CTA notes, SEO segments, caution snippets, helper/page guidance) so repeated patterns can be centralized without weakening industry specificity. Fragments are **bounded building blocks**, not a templating language.

---

## 1. Purpose

- Provide a **typed, keyed** place for repeated cross-industry guidance segments.
- Allow **helper overlays**, **one-pager overlays**, **CTA guidance**, **SEO guidance**, and **caution systems** to reference fragments instead of duplicating text.
- Keep **industry and subtype overlays** as the primary authored artifacts; fragments are optional references.
- **Composition order** remains deterministic; invalid fragment refs fail safely.

---

## 2. Fragment object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **fragment_key** | string | Yes | Stable, unique key (pattern `^[a-z0-9_-]+$`; max 64). |
| **fragment_type** | string | Yes | One of: `cta_notes`, `seo_segment`, `caution_snippet`, `helper_guidance`, `page_guidance`. |
| **allowed_consumers** | array | Yes | List of consumer scope strings: `section_helper_overlay`, `page_onepager_overlay`, `cta_guidance`, `seo_guidance`, `compliance_caution`. Fragment may only be resolved when consumer is in this list. |
| **content** | string | Yes | Editorial content payload; max 2048 chars. No HTML/script; plain text or allowed inline markup per consumer. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/fragment version; max 32 chars. |

- **fragment_key** is unique within the registry (first wins on duplicate load).
- **fragment_type** determines semantics; consumers must be validated against **allowed_consumers** at resolution.
- **content** is editorial only; no arbitrary code or unsafe dynamic content. Invalid or malformed content causes the fragment to be skipped at load.
- Invalid fragment objects (missing required fields, invalid key/type, empty allowed_consumers) must **fail safely** at load (skipped).

---

## 3. Fragment types

| Type | Description | Typical consumers |
|------|-------------|-------------------|
| **cta_notes** | Repeated CTA posture or conversion notes. | section_helper_overlay, page_onepager_overlay, cta_guidance |
| **seo_segment** | Repeated SEO hierarchy or meta guidance. | section_helper_overlay, page_onepager_overlay, seo_guidance |
| **caution_snippet** | Repeated compliance or caution wording. | section_helper_overlay, page_onepager_overlay, compliance_caution |
| **helper_guidance** | Repeated section-helper guidance block. | section_helper_overlay |
| **page_guidance** | Repeated page one-pager guidance block. | page_onepager_overlay |

---

## 4. Consumer scopes

| Consumer | Description |
|----------|-------------|
| **section_helper_overlay** | Industry or subtype section-helper overlay (tone_notes, cta_usage_notes, seo_notes, compliance_cautions, media_notes). |
| **page_onepager_overlay** | Industry or subtype page one-pager overlay (allowed regions per page-onepager schema). |
| **cta_guidance** | CTA pattern or pack-level CTA guidance. |
| **seo_guidance** | SEO guidance rule or pack-level SEO ref. |
| **compliance_caution** | Compliance rule or overlay compliance_cautions. |

Resolution must validate that the **caller’s consumer scope** is in the fragment’s **allowed_consumers**; otherwise resolution returns null or empty (safe failure).

---

## 5. Composition and conflict rules

- **No recursion**: Fragments may not reference other fragments unless a future contract explicitly allows and defines depth limits.
- **Deterministic order**: When an overlay composes base content + fragment refs, order is defined by the overlay contract (e.g. base first, then fragment content in ref order).
- **Conflict**: If the same overlay region is filled by both direct authored content and a fragment ref, direct content takes precedence unless the overlay contract specifies otherwise (additive vs override).
- **Missing ref**: Invalid or missing fragment_key at resolution returns empty string or null; no exception; overlay composition continues without that fragment.

---

## 6. Registry behavior

- **Industry_Shared_Fragment_Registry**: load(array of fragment objects), get(fragment_key), get_all(), get_by_type(fragment_type). Read-only after load.
- Load validates required fields, fragment_type enum, allowed_consumers (non-empty array of allowed strings), and key pattern; invalid entries skipped. Duplicate fragment_key: first wins.
- **Industry_Shared_Fragment_Resolver**: resolve(fragment_key, consumer_scope) returns content string or null; returns null when fragment not found, not active, or consumer_scope not in allowed_consumers.

---

## 7. Export and validation

- Fragment definitions are part of industry registry data; export/restore may include them when the industry payload schema is extended.
- Validation: fragment_key, fragment_type, allowed_consumers, content length and safety (no script/executable), status.

---

*Fragments remain sparse and high-value; industry-specific authored guidance stays primary.*
