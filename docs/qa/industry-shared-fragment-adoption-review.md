# Industry Shared Fragment Adoption Review (Prompt 477)

**Spec**: [industry-shared-fragment-contract.md](../contracts/industry-shared-fragment-contract.md); [industry-shared-fragment-schema.md](../schemas/industry-shared-fragment-schema.md); overlay and authoring guides.

**Purpose**: Document the bounded adoption of shared fragments in launch-industry overlays. Adoption is limited and reviewable; industry-specific authored content remains primary.

---

## 1. Adoption scope

- **Bounded set**: One industry (cosmetology_nail), one section overlay entry (tp_badge_01), one fragment ref (compliance_cautions_fragment_ref => caution_testimonial_genuine).
- **No mass refactor**: Other overlays and rules are unchanged. Direct authored guidance remains the main source.
- **Composer support**: Industry_Helper_Doc_Composer accepts optional Industry_Shared_Fragment_Resolver; when an overlay (or subtype overlay) includes cta_usage_fragment_ref, seo_notes_fragment_ref, or compliance_cautions_fragment_ref, the composer resolves the fragment and appends content to the corresponding region.

---

## 2. What was adopted

| Artifact | Change | Fragment |
|----------|--------|----------|
| Section helper overlay: cosmetology_nail / tp_badge_01 | Added compliance_cautions_fragment_ref => 'caution_testimonial_genuine'. Existing compliance_cautions text retained; fragment content is appended in composition. | caution_testimonial_genuine (Testimonials and reviews must be genuine; consent.) |

---

## 3. Why this adoption

- **tp_badge_01** is a trust/proof section; testimonial and endorsement language is a natural fit for the shared caution snippet.
- **Single entry** keeps the adoption set small and verifiable; no regression in output clarity.
- **Append semantics**: Direct overlay text is kept; fragment adds consistent cross-industry caution. Composed output remains clear and industry-appropriate.

---

## 4. Composition behavior

- Order: base helper → industry overlay (direct fields) → fragment refs resolved and appended to their target fields → subtype overlay (same rules).
- Invalid or missing fragment ref: resolver returns null; composition continues without that fragment (safe failure).
- Consumer scope for section-helper overlays: section_helper_overlay.

---

## 5. Regression and quality

- **Regression**: Composed helper doc for cosmetology_nail + tp_badge_01 now includes both the existing compliance_cautions string and the resolved fragment content (appended). No removal of existing content.
- **Quality**: Fragment content is editorial and aligned with compliance/caution guidance; industry nuance remains in the direct overlay text.
- **Determinism**: Resolution is deterministic; no recursion; cache key unchanged (fragment content is part of composed result when resolver is present).

---

## 6. Optional fragment ref fields (section-helper overlay)

Overlay and subtype overlay arrays may include (optional):

- **cta_usage_fragment_ref** (string): fragment_key; resolved content appended to cta_usage_notes.
- **seo_notes_fragment_ref** (string): fragment_key; resolved content appended to seo_notes.
- **compliance_cautions_fragment_ref** (string): fragment_key; resolved content appended to compliance_cautions.

Schema and contract: see industry-shared-fragment-schema.md and industry-shared-fragment-contract.md. Section-helper overlay schema may be extended to document these optional keys.

---

## 7. Cross-references

- [industry-shared-fragment-catalog.md](../appendices/industry-shared-fragment-catalog.md)
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — authors may use fragment refs sparingly where reuse is high-value.
