# Industry Conflict Resolution Contract

**Spec**: industry-pack-extension-contract.md; industry-profile-schema.md; industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md; industry-build-plan-scoring-contract.md.

**Status**: Defines how primary and secondary industries interact when they disagree. Deterministic precedence; no silent resolution of important conflicts (Prompt 370).

---

## 1. Purpose

- **Reconcile competing industry signals** when a site has multiple active industries (primary + secondary).
- **Precedence**: Primary industry wins in direct conflicts unless the contract explicitly allows secondary to influence or requires a warning.
- **Explainability**: Every resolution is deterministic and can be explained (source industries, resolution mode, severity).
- **Safe failure**: Prefer warnings and conservative defaults; never silently hide operator-relevant conflicts.

---

## 2. Precedence rules

| Rule | Description |
|------|-------------|
| **Primary wins on direct conflict** | When primary says "preferred" or "recommended" and secondary says "discouraged" (or vice versa with opposite polarity), the **primary** signal determines the outcome. Secondary adds or subtracts points but cannot flip a primary preference into a rejection or vice versa for classification purposes when we apply weighted resolution. |
| **Secondary influences score only within bounds** | Secondary industry points are additive (e.g. affinity) or subtractive (e.g. discouraged) but are capped so that primary preference/avoidance cannot be overridden by secondary alone. The weighted engine applies primary weight higher than secondary. |
| **Neutral when no industry signals** | If neither primary nor secondary has a signal for a given item, the item remains neutral. |
| **Single-industry unchanged** | When only primary is set (no secondary), behavior is unchanged from pre–multi-industry logic: primary pack and section/template affinity alone drive recommendation. |

---

## 3. Conflict classes

| Class | Description | Resolution | Surfaces as |
|-------|-------------|------------|-------------|
| **recommendation_only** | Industries disagree on strength of fit (e.g. one preferred, one neutral). Score is combined with primary weight dominant. | Auto-resolved by weighted scoring. | No warning; explanation can mention "primary + secondary contributed". |
| **warning_worthy** | Opposite signals (e.g. primary preferred, secondary discouraged). Primary wins but operator should see that secondary disagreed. | Primary wins; flag for UI. | Warning badge or explanation snippet: "Secondary industry X discourages this; primary recommendation applied." |
| **blocking** | Rare: e.g. primary requires a page family, secondary forbids it. Cannot satisfy both. | Do not auto-resolve; treat as unresolved or primary wins and surface blocking warning. | Blocking or high-severity warning. |
| **unresolved** | Conflict type not categorized or resolution failed. | Fall back to primary-only or neutral; do not silently pick. | Warning: "Conflicting industry guidance; primary used." |

---

## 4. CTA conflicts

- **Primary CTA pattern** from primary pack (default_cta_patterns) is the default. Secondary CTA preferences may add hints but do not override primary.
- If secondary suggests a different CTA pattern and it conflicts with primary: **primary wins**; optional warning "Secondary industry suggests different CTA pattern."
- CTA conflict class: **recommendation_only** or **warning_worthy** (surfaced as explanation, not blocking).

---

## 5. Page-family and LPagery conflicts

- **Page family**: Primary pack supported_page_families and required vs discouraged template rules take precedence. Secondary page-family preferences add to score but cannot override primary "required" or "discouraged" for classification.
- **LPagery posture**: Primary LPagery rule (token presets, required tokens) is authoritative. Secondary LPagery hints are advisory; conflict is **warning_worthy** if secondary disagrees.
- **Hierarchy notes**: Same as above; primary hierarchy fit wins; secondary disagreement is warning_worthy.

---

## 6. Style preset conflicts

- **One active style preset**: The preset linked to the primary industry (token_preset_ref) is used. Secondary industry presets do not override.
- **Conflict**: If multiple industries have different presets and both are "active", **primary preset wins**. Optional explanation: "Style preset follows primary industry."

---

## 7. What gets surfaced vs auto-resolved

| Situation | Action | Surfaces |
|-----------|--------|----------|
| Primary preferred, secondary neutral | Auto-resolve (primary + secondary score). | No conflict badge. |
| Primary preferred, secondary discouraged | Primary wins; **warning_worthy**. | Badge or snippet: secondary disagreed. |
| Primary discouraged, secondary preferred | Primary wins (stay discouraged); **warning_worthy**. | Badge: primary discourages; secondary would favor. |
| Required vs discouraged (page family / template) | Primary required or primary discouraged wins. | **warning_worthy** if secondary disagrees. |
| Unresolved or unknown conflict | Fall back to primary-only or neutral. | **unresolved** warning. |

- **No unsafe silent resolution**: Any conflict that could affect operator decisions (e.g. opposite fit signals) must be visible as a warning or explanation, not hidden.

---

## 8. Conflict result schema (Industry_Conflict_Result)

Used by the weighted engine and UI to represent a single conflict or resolved outcome:

| Field | Type | Description |
|-------|------|-------------|
| **conflict_type** | string | One of: section_fit, template_fit, cta_pattern, page_family, lpagery, style_preset, build_plan_item, generic. |
| **source_industries** | string[] | Industry keys that contributed (primary first, then secondary). |
| **resolution_mode** | string | primary_wins, secondary_influenced, combined, unresolved, none. |
| **explanation** | string | Short human-readable explanation for UI. |
| **severity** | string | info, warning_worthy, blocking, unresolved. |

- **Severity** determines whether the result is shown as a badge, a caution, or a blocking message. `info` = no badge; `warning_worthy` = show warning; `blocking` = high visibility; `unresolved` = caution.

---

## 9. Files and integration

- **Contract**: docs/contracts/industry-conflict-resolution-contract.md (this file).
- **Result object**: plugin/src/Domain/Industry/Profile/Industry_Conflict_Result.php (typed array shape; constants for conflict_type, resolution_mode, severity).
- **Consumer**: Industry_Weighted_Recommendation_Engine (Prompt 371) produces conflict results and weighted scores. Section and page template read model builders may call the engine via build_with_weighted() when profile has secondary industries; Industry_Build_Plan_Scoring_Service uses the engine to add industry_conflict_results, industry_explanation_summary, industry_has_warning to enriched items. UI (Prompt 372) surfaces conflict badges from result metadata.

---

## 10. Constraints

- **Deterministic**: Same profile + same registries → same conflict resolution and scores.
- **Primary remains meaningful**: Single-industry behavior and primary-led multi-industry behavior must not regress.
- **No public mutation**: Conflict resolution is read-only; no live profile or pack mutation from resolution logic.
- **Safe failure**: Missing data → primary-only or neutral; never throw to caller; log where appropriate.
