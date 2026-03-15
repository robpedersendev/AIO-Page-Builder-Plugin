# Industry Substitute Suggestion Contract

**Spec**: industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md; section/template taxonomy and family metadata.

**Status**: Defines the substitute suggestion engine that proposes better-fit sections or templates when a user encounters discouraged or weak-fit choices (Prompt 378).

---

## 1. Purpose

- **Propose better-fit alternatives** when a section or page template is discouraged or weak-fit for the active industry, using recommendation data and similarity rules (purpose family, CTA, page family, hierarchy) rather than hardcoded one-off suggestions.
- **Reuse** the same engine across section library, page template directory, composition builder, and create-page assistant.
- **Remain advisory**: No auto-replace; suggestions are explainable and deterministic.

---

## 2. Principles

- **Read-only**: Engine consumes resolver results and definition metadata; does not mutate registries or user choices.
- **Explainable**: Each suggestion includes a reason and optional fit score delta.
- **Deterministic**: Same inputs produce same substitute list (ordered by preference).
- **Fail safely**: When no good substitute exists, return empty list. Missing metadata does not throw.

---

## 3. Substitute suggestion result shape

| Field | Type | Description |
|-------|------|-------------|
| **original_key** | string | Section or template key the user chose (discouraged/weak-fit). |
| **suggested_replacement_key** | string | Suggested alternative key. |
| **substitute_reason** | string | Short reason code or message (e.g. same_family_better_fit, recommended_alternative). |
| **fit_score_delta** | int | Difference in recommendation score (suggested minus original); positive = better. |
| **warning_flags** | array | Optional warning flags that apply to the original (for context). |

- Industry_Substitute_Suggestion_Result: create() and from_array(); array shape for API consistency.

---

## 4. Engine API

- **Industry_Substitute_Suggestion_Engine**:
  - **suggest_section_substitutes**( string $original_section_key, string $section_fit, Industry_Section_Recommendation_Result $result, array $section_definitions, int $max = 5 ): array of result shape.
  - **suggest_template_substitutes**( string $original_template_key, string $template_fit, Industry_Page_Template_Recommendation_Result $result, array $template_definitions, int $max = 5 ): array of result shape.

- **When to suggest**: Only when original fit is `discouraged` or `allowed_weak_fit`. For `recommended` or `neutral`, return empty (no substitute needed).
- **Candidates**: Items with fit `recommended` (and optionally `allowed_weak_fit` if config allows). Exclude the original key.
- **Ordering**: Prefer same purpose_family (sections) or template_family (templates); then by score descending; then by key. Deterministic.
- **Reason codes**: same_family_better_fit, recommended_alternative, same_category_better_fit (when category/purpose family matches).

---

## 5. Integration

- **Section library / composition assistant**: Pass section resolver result and section definitions; call suggest_section_substitutes for discouraged/weak section keys.
- **Page template directory / create-page assistant**: Pass template resolver result and template definitions; call suggest_template_substitutes for discouraged/weak template keys.
- **Consumers** may show substitute list with reason and score delta; user chooses whether to switch. No automatic replacement.

---

## 6. Files

- **Contract**: docs/contracts/industry-substitute-suggestion-contract.md (this file).
- **Result**: plugin/src/Domain/Industry/Registry/Industry_Substitute_Suggestion_Result.php.
- **Engine**: plugin/src/Domain/Industry/Registry/Industry_Substitute_Suggestion_Engine.php.
- **Consumers**: Industry_Create_Page_Assistant (get_substitute_suggestions_for_template), Industry_Composition_Assistant (get_substitute_suggestions_for_section), and optionally section/page template read model builders or their callers. Assistants accept optional Industry_Substitute_Suggestion_Engine in constructor and cache resolver result when building state so the engine can be used for structured suggestions.

---

## 7. Related

- industry-section-recommendation-contract.md: Section fit and score source.
- industry-page-template-recommendation-contract.md: Template fit and score source.
- Section_Schema / Page_Template_Schema: purpose_family, template_family, category for similarity.
