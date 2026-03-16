# Industry Content Gap Contract (Prompt 408)

**Spec:** Industry Profile validation contracts; starter bundle contracts; Build Plan and recommendation contracts; existing diagnostics/support patterns.

**Status:** Contract for the Industry Content Gap Detector. Advisory only; no auto-generation or blocking.

---

## 1. Purpose

- Compare **Industry Profile** and **selected starter bundle / recommended site structure** against **available assets, selected templates, or chosen sections** so users see where important content inputs are missing.
- Surface **gap categories** and **severity** with **recommended action summary** and optional **related page/section families**.
- Support launch industries (cosmetology_nail, realtor, plumber, disaster_recovery). Detector remains **advisory and bounded**.

---

## 2. Content gap result shape

| Field | Type | Description |
|-------|------|-------------|
| **gap_type** | string | Stable identifier (e.g. `missing_staff_bios`, `missing_gallery_assets`, `missing_trust_proof`, `missing_service_area_detail`, `missing_emergency_response_details`, `missing_valuation_conversion_assets`). |
| **severity** | string | `info`, `caution`, or `warning`. |
| **related_page_families** | list&lt;string&gt; | Page template families this gap relates to (e.g. about, services, contact). |
| **related_section_families** | list&lt;string&gt; | Section purpose families (e.g. proof, listing, contact). |
| **recommended_action_summary** | string | Short, actionable summary (max 256 chars). |
| **subtype_influence** | object (optional) | When subtype context refines this gap (Prompt 448): `refined_action_summary` (string, optional), `additive_note` (string, optional). Exposed when Industry_Subtype_Content_Gap_Extender is used and profile has valid industry_subtype_key. |

Invalid or unknown gap types are not emitted. Results are **read-only** and safe for diagnostics/onboarding/Build Plan explanation surfaces.

---

## 3. Detector behavior

- **Industry_Content_Gap_Detector**: Read-only. Method: `detect( array $profile, ?string $bundle_key, array $options = array() ): array` returning list of gap result objects.
- **Inputs**: Normalized Industry Profile (primary_industry_key, selected_starter_bundle_key, **industry_subtype_key** (optional), question_pack_answers as desired); optional bundle key override; optional `available_page_template_keys`, `available_section_keys`, `content_hints` (e.g. has_staff_bios, has_gallery, has_trust_proof, has_service_area_detail, has_emergency_details, has_valuation_assets). When content_hints or availability are omitted, detector may still emit **expected** gaps for the industry based on recommended structure.
- **Subtype-aware extension (Prompt 448):** When **Industry_Subtype_Content_Gap_Extender** is injected and profile has a non-empty industry_subtype_key, the detector merges parent-industry expectations with subtype-specific expectations (subtype may add or override severity per gap type). Gap results may include **subtype_influence** (refined_action_summary, additive_note) where the extender provides refinement. When subtype is empty or invalid, behavior is **parent-only** (safe fallback).
- **No mutation.** No auto-generation of content. No blocking of planning or execution. Safe fallback when profile or bundle missing (empty or generic gaps only).
- **Admin/support-only** surfacing; no public mutation. Safe handling when partial data exists.

---

## 4. Integration points

- **Diagnostics/support**: Snapshot or support tools may include a content-gap summary from the detector when profile and optional bundle are set.
- **Onboarding / profile readiness**: Readiness or guidance docs may reference gap results to suggest completing staff bios, gallery, trust proof, service-area detail, emergency-response details, or valuation/conversion assets.
- **Build Plan explanation**: Build Plan explanation docs may reference that content gaps can be reviewed via the detector for fuller recommendations.

---

## 5. Limits

- Does not inspect private external systems. Does not auto-generate missing content. Does not block planning.
- Generic fallback remains available when industry or bundle unknown. Results are advisory only.
