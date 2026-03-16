# Industry Subtype Extension Contract (Prompt 413)

**Spec**: industry-pack-extension-contract.md; industry-subtype-schema.md; industry-profile-schema.md; industry-subsystem-roadmap-contract.md.

**Status**: Contract. Defines how industry subtypes layer onto parent industry packs so major industries can support structured sub-variants (e.g. luxury nail salon, mobile nail tech, residential/commercial plumber, buyer-agent/listing-agent realtor) without forking whole packs.

---

## 1. Purpose

- Define the **subtype object model** and **allowed override scope** (CTA posture, page-family emphasis, starter bundles, helper/one-pager overlays, caution rules).
- Define how **subtype selection** relates to the Industry Profile and how **subtype overlays** interact with the parent industry pack.
- Define **fallback** when no subtype is selected or ref is invalid.
- Document **limits** of subtype customization; subtypes are overlays, not separate products.

---

## 2. Principles

- **Parent industry pack** remains the **base layer**. Subtypes extend it; they do not replace it.
- **Subtypes are overlays/extensions**, not separate products. All subtype behavior is schema-driven and exportable.
- **Multi-industry** behavior (primary + secondary industries) remains compatible with subtype use; subtype applies to the primary industry only unless otherwise specified.

---

## 3. Subtype object and allowed override scope

- **Subtype object**: See industry-subtype-schema.md. Required: subtype_key, parent_industry_key, label, summary, status, version_marker. Optional: cta_posture_ref, page_family_emphasis, starter_bundle_ref, helper_overlay_refs, one_pager_overlay_refs, caution_rule_refs, metadata.
- **Allowed override scope** (what can vary at the subtype level):
  - **CTA posture**: Ref or refinement for CTA pattern usage for this subtype.
  - **Page-family emphasis**: Which page template families to emphasize (overlay on parent supported_page_families).
  - **Starter bundles**: Optional starter_bundle_ref recommended for this subtype.
  - **Helper overlays**: Section-helper overlay refs to add or prioritize.
  - **One-pager overlays**: Page one-pager overlay refs to add or prioritize.
  - **Caution rules**: Advisory compliance/caution rule refs for this subtype.
- **Limits**: Subtypes do not define new industry_key, do not replace section/page template registries, and do not introduce freeform arbitrary behavior. All refs must resolve to existing registries or be optional.

---

## 4. Subtype selection and Industry Profile

- **Profile field**: Industry Profile may store an optional **industry_subtype_key** (or equivalent) that references a subtype_key.
- **Validation**: industry_subtype_key is valid only when it references a subtype whose **parent_industry_key** equals the profile’s **primary_industry_key**. Mismatched or unknown subtype refs are invalid; validators must fail safe (clear or ignore invalid subtype ref).
- **Resolution**: A resolver (e.g. Industry_Subtype_Resolver) returns effective context: when a valid subtype is selected, context includes both parent industry and subtype overrides; when no subtype or invalid ref, context is parent industry only.

---

## 5. Subtype overlays and parent pack interaction

- **Layering**: When a subtype is selected and valid, recommendation and overlay consumers may **merge** subtype overrides with parent pack data: e.g. subtype page_family_emphasis refines parent supported_page_families; subtype helper_overlay_refs add to or reorder parent overlay refs. Exact merge rules are implementation-defined but must remain deterministic and documented.
- **Fallback**: When subtype ref is missing or invalid, **only parent industry pack** is used. No partial subtype application.
- **Registry**: Subtype definitions are loaded via Industry_Subtype_Registry keyed by subtype_key and by parent_industry_key. Invalid or duplicate keys are skipped at load per schema validation. Industry_Subtype_Validator validates single definitions (schema and optional parent-industry reference check); use for authoring and tests.

---

## 6. Fallback when no subtype is selected

- When profile has no industry_subtype_key (or empty), behavior is **parent industry only**. No subtype-specific overlays or emphasis apply.
- When industry_subtype_key is set but invalid (unknown key or parent mismatch), behavior is **parent industry only**; invalid ref must not crash and may be reported in validation/diagnostics.

---

## 7. Diagnostics and export/restore

- **Diagnostics**: Resolved subtype (subtype_key, label, parent_industry_key) may appear in industry diagnostics snapshot when present. Bounded; no secrets.
- **Export/restore**: industry_subtype_key is part of Industry Profile payload when present. Restore re-validates subtype ref against parent_industry_key; invalid ref may be cleared or warned. Subtype definitions (when a registry exists) may be exported/restored with industry pack data per product decisions.

---

## 8. Security and constraints

- **No arbitrary freeform override injection.** All override refs are registry-driven; invalid refs fail safely.
- **Admin-only** mutation of subtype selection (same capability as Industry Profile).
- **Registry-driven only**: Subtype behavior is determined by registered subtype definitions and schema; no client-supplied arbitrary payloads that change behavior.

---

## 9. Cross-references

- **Schema**: industry-subtype-schema.md.
- **Profile**: industry-profile-schema.md (optional industry_subtype_key).
- **Pack extension**: industry-pack-extension-contract.md (parent pack remains base).
- **Roadmap**: industry-subsystem-roadmap-contract.md (subtype expansion category).
- **Resolver**: Industry_Subtype_Resolver (Prompt 414) for profile + subtype resolution and fallback.
