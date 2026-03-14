# Template Library Animation and Fallback QA Report

**Document type:** QA report for cross-browser animation, fallback, and reduced-motion verification (Prompt 187).  
**Governs:** Animation tier metadata, fallback behavior, and reduced-motion resolution at library scale; evidence for template-library-compliance-matrix ANIMATION family and §59.14.  
**Spec refs:** §7.7 CSS and Asset Delivery Strategy; §51.10 Modal/Popup Accessibility (motion); §55.5 Asset Loading Rules; §56.6 Accessibility Test Scope; §59.14 Hardening and QA Phase.

**Authority:** animation-support-and-fallback-contract.md; template-library-compliance-matrix.md §3.5 ANIMATION.

---

## 1. Purpose

The **Animation QA** layer verifies that:

- Animation tier and family metadata are valid and contract-compliant.
- Fallback behavior is deterministic (tier none when unsupported; no undefined state).
- Reduced-motion preference resolution produces a safe effective tier (none or subtle) for every section.
- Library-level summaries support correction (tie failures back to template/section metadata).

It does **not** promise identical effects in every browser. It operationalizes the animation contract and runtime integration at scale. Manual QA remains required for tier-none layout, reduced-motion in-browser behavior, and no broken layout in low-support scenarios.

---

## 2. Machine-checkable rule codes (fallback_violation_summary)

| Code | Scope | Description |
|------|--------|--------------|
| `invalid_tier` | section | animation_tier is not none, subtle, enhanced, or premium. |
| `invalid_family` | section | animation_families contains a slug not in the allowed list (entrance, hover, scroll, focus, disclosure, stagger, micro). |
| `fallback_tier_invalid` | section | animation_fallback_tier is set but not none and not equal to animation_tier. |
| `invalid_reduced_motion_behavior` | section | reduced_motion_behavior is not honor or essential_only. |
| `invalid_tier_cap` | page | animation_tier_cap is set but not an allowed tier. |
| `invalid_families_allowed` | page | animation_families_allowed contains a non-allowed family slug. |

---

## 3. Reduced-motion check result

The service runs `Animation_Tier_Resolver::resolve( $section, null, true )` for every section and verifies:

- **all_resolve_safe_tier:** Every section’s effective tier under reduced-motion is none or subtle (no enhanced/premium when user prefers reduced motion).
- **sections_capped_count:** Number of sections whose declared tier is higher than their effective tier when reduced motion is applied.

---

## 4. Manual QA checklist (animation-support-and-fallback-contract §9)

The result includes a **manual_qa_checklist** used for human verification:

1. **Tier none:** With animation disabled or tier forced to none, every section and page renders with correct layout and all content visible.
2. **Reduced-motion:** With prefers-reduced-motion: reduce, no decorative or non-essential animation runs; content and CTAs remain visible and usable.
3. **No broken layout:** In at least one low-support scenario (e.g. animation off), confirm no overflow, overlap, or invisible critical content.
4. **Progressive enhancement:** In a full-support scenario, enhanced/premium tiers add motion without removing or hiding content.
5. **Focus and modals:** If modal or focus-related animation exists, focus trap and focus return still work when reduced motion is on (Spec §51.10).

---

## 5. How to run

- **Service:** `Animation_QA_Service` (container key: `animation_qa_service`).
- **Method:** `run()` returns `Animation_QA_Result`.
- **Payload:** `$result->to_array()` yields `animation_qa_result`; `$result->to_summary_lines()` yields human-readable summary lines.

---

## 6. Example animation QA result payload

```json
{
  "passed": false,
  "fallback_violation_summary": [
    {
      "scope": "section",
      "template_key": "st_hero_01",
      "code": "invalid_tier",
      "message": "animation_tier must be none, subtle, enhanced, or premium."
    }
  ],
  "reduced_motion_check_result": {
    "sections_checked": 120,
    "all_resolve_safe_tier": true,
    "sections_capped_count": 45
  },
  "section_summary": {
    "audited": 120,
    "by_tier": { "none": 80, "subtle": 30, "enhanced": 9, "premium": 1 },
    "violations": 1
  },
  "page_summary": {
    "audited": 45,
    "with_tier_cap": 12,
    "violations": 0
  },
  "manual_qa_checklist": [
    "Tier none: With animation disabled or tier forced to none, every section and page renders with correct layout and all content visible.",
    "Reduced-motion: With prefers-reduced-motion: reduce, no decorative or non-essential animation runs; content and CTAs remain visible and usable.",
    "No broken layout: In at least one low-support scenario (e.g. animation off), confirm no overflow, overlap, or invisible critical content.",
    "Progressive enhancement: In a full-support scenario, enhanced/premium tiers add motion without removing or hiding content.",
    "Focus and modals: If modal or focus-related animation exists, focus trap and focus return still work when reduced motion is on (Spec §51.10)."
  ]
}
```

---

## 7. Cross-references

- **Contracts:** animation-support-and-fallback-contract.md; template-library-compliance-matrix.md §3.5 ANIMATION.
- **Release gate:** hardening-release-gate-matrix.md (accessibility / reduced-motion).
- **Implementation:** `plugin/src/Domain/Registries/QA/Animation_QA_Service.php`; `Animation_QA_Result.php`; `plugin/src/Domain/Rendering/Animation/Animation_Fallback_Service.php`; `Animation_Tier_Resolver.php`; `Reduced_Motion_Service.php`.
