# Industry Shared Fragment Contract (Prompt 474)

**Spec**: [industry-shared-fragment-schema.md](../schemas/industry-shared-fragment-schema.md); industry-section-helper-overlay-schema.md; industry-page-onepager-overlay-schema.md; industry-cta-pattern-contract.md; industry-compliance-rule-contract.md; industry-pack-extension-contract.md.

**Purpose**: Contract for reusable cross-industry artifact fragments so CTA notes, SEO segments, caution snippets, and helper/page guidance can be centralized without weakening industry specificity. Bounded building blocks only; no freeform content-token system.

---

## 1. Scope and principles

- **Industry and subtype overlays** remain the **primary** authored artifacts. Fragments are optional references to reduce duplication.
- **Shared fragments** are bounded by type and consumer scope; they are not a general templating language.
- **Composition order** is deterministic; invalid fragment refs **fail safely** (no crash; empty or skip).
- **Exportability and registry validation** are preserved; fragments are part of the industry registry domain.

---

## 2. Fragment object types and scope

- **Fragment types** (per schema): `cta_notes`, `seo_segment`, `caution_snippet`, `helper_guidance`, `page_guidance`.
- **Allowed consumers**: `section_helper_overlay`, `page_onepager_overlay`, `cta_guidance`, `seo_guidance`, `compliance_caution`.
- A fragment is **only** resolved when the resolver is called with a **consumer_scope** that is in the fragment’s **allowed_consumers**.
- Fragments have a **content** payload (plain text or allowed inline markup per consumer); no arbitrary code or unsafe dynamic content.

---

## 3. Where fragments can be referenced safely

| Consumer | Safe use |
|----------|----------|
| **section_helper_overlay** | Overlay definitions may reference fragment_key in allowed regions (e.g. cta_usage_notes, seo_notes, compliance_cautions) when the overlay contract supports fragment refs. Composer merges base + overlay + resolved fragment content. |
| **page_onepager_overlay** | Same idea for page one-pager overlay regions. |
| **cta_guidance** | CTA pattern or pack-level guidance may reference a fragment for repeated urgency/trust/action notes. |
| **seo_guidance** | SEO guidance rule or pack may reference a fragment for repeated hierarchy/meta text. |
| **compliance_caution** | Caution rules or overlay compliance_cautions may reference a fragment for repeated wording. |

**Safe reference** means: resolution is read-only; invalid or missing ref returns null/empty; no public mutation surfaces.

---

## 4. Fragment composition and conflict rules

- **Order**: When composing, base content and overlay content order is defined by the existing overlay/helper/one-pager contracts. Fragment content is inserted in a defined position (e.g. after direct overlay text in that region).
- **Conflict**: If both direct authored text and a fragment ref exist for the same region, **direct authored text takes precedence** unless a specific overlay contract says otherwise (e.g. additive append).
- **No recursion**: Fragments do not reference other fragments unless a future contract explicitly allows it with a depth limit.
- **Missing ref**: Resolver returns null or empty; composer continues without that fragment; no exception.

---

## 5. Bounded use and documentation

- Fragment usage must remain **bounded and documented** (e.g. industry-shared-fragment-catalog.md).
- Overlay authors may use fragments for clearly repeated patterns; they must not over-fragment or replace industry-specific nuance with generic fragments.
- New fragment types or consumer scopes require a schema and contract update.

---

## 6. Security and permissions

- **No arbitrary code or unsafe dynamic content** in fragment content.
- **Invalid fragment refs** must fail safely (no crash, no injection).
- **No public mutation surfaces** for fragment definitions; load is from built-in definitions or controlled import only.

---

## 7. Registry and resolver

- **Industry_Shared_Fragment_Registry**: load(array), get(fragment_key), get_all(), get_by_type(fragment_type). Built-in definitions from `Registry/SharedFragments/builtin-fragments.php` (Prompt 476). Wired in Industry_Packs_Module as `industry_shared_fragment_registry`.
- **Industry_Shared_Fragment_Resolver**: resolve(fragment_key, consumer_scope) returns content string or null. Enforces allowed_consumers and active status. Wired as `industry_shared_fragment_resolver`.

---

## 8. Cross-references

- [industry-shared-fragment-schema.md](../schemas/industry-shared-fragment-schema.md) — Fragment object shape, types, consumers, registry/resolver behavior.
- [industry-section-helper-overlay-schema.md](../schemas/industry-section-helper-overlay-schema.md) — Overlay regions that may reference fragments.
- [industry-pack-extension-contract.md](industry-pack-extension-contract.md) — Subsystem boundary; fragments extend, do not replace.

---

*Fragments support future reuse in helper/page/CTA/SEO/caution layers while preserving direct authored overlays as the main content source.*
