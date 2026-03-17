# Industry LPagery Planning Contract

**Spec**: large-scale-acf-lpagery-binding-contract.md; industry-lpagery-rule-schema.md; industry-build-plan-scoring-contract.md.

**Status**: Planning-only advisor layer. Translates industry LPagery rules into planning guidance and warnings. No mutation of LPagery binding or auto-generation of pages.

---

## 1. Purpose

- **Translate** active industry LPagery rules into **planning guidance**: when local pages are central, when service-area hubs should be proposed, what token sets are required, and when weak/low-value local-page generation should be discouraged.
- **Expose** structured advice to Build Plan generation and UI explanation layers.
- **Fail safely** when LPagery rules are absent or incomplete. Core LPagery binding contract remains authoritative; no changes to field keys, naming, or injection behavior.

---

## 2. Planning result shape

The advisor returns an **Industry_LPagery_Planning_Result** with:

| Field | Type | Description |
|-------|------|-------------|
| **lpagery_posture** | string | Aggregated posture: `central`, `optional`, or `discouraged`. From active rules for the industry; one rule wins (central > optional > discouraged). |
| **required_tokens** | list&lt;string&gt; | Token refs required when LPagery is used (e.g. `{{location_name}}`). Merged from active rules. |
| **optional_tokens** | list&lt;string&gt; | Optional token refs. Merged from active rules. |
| **suggested_page_families** | list&lt;string&gt; | Page families or hierarchy hints from hierarchy_guidance (e.g. service-area hubs). Derived from rules; advisory only. |
| **warning_flags** | list&lt;string&gt; | Warning codes (e.g. `weak_fit_local_page`, `missing_required_tokens`). For planning/UI. |
| **hierarchy_guidance** | string | Concatenated or primary hierarchy_guidance from rules; max length bounded. |
| **weak_page_warnings** | list&lt;string&gt; | Page types or patterns that are weak fit; from rules. |

---

## 3. Integration

- **Industry_LPagery_Planning_Advisor**: Reads **Industry_LPagery_Rule_Registry**; accepts `industry_key` or industry profile (primary_industry_key). Lists active rules for industry, aggregates posture/tokens/guidance/warnings into **Industry_LPagery_Planning_Result**.
- **Build Plan / scoring**: Caller may pass result into Build Plan context or merge warning_flags into plan-level industry_warnings. No automatic execution or page generation.
- **Missing/invalid rules**: When no active rules for industry, return result with posture `optional`, empty lists, and no warning_flags (or a single “no_lpagery_rules” info flag per implementation).

---

## 4. Security and constraints

- Planning-only; no execution or mutation of LPagery content.
- Malformed rule objects are skipped at registry load; advisor does not throw on missing registry or empty list.
- Token refs in result are for planning/display only; validation at build time remains per existing LPagery contract.

---

## 5. Combined subtype + goal planning

When both industry subtype and conversion goal are set, joint planning posture is defined by [subtype-goal-lpagery-planning-contract.md](subtype-goal-lpagery-planning-contract.md). Composition order: parent (industry) → subtype → goal. Conflict handling and fallback are specified there; this contract remains the base for industry-level rules only.

---

## 6. Files

- **Advisor**: plugin/src/Domain/Industry/LPagery/Industry_LPagery_Planning_Advisor.php
- **Result**: plugin/src/Domain/Industry/LPagery/Industry_LPagery_Planning_Result.php
- **Contract**: docs/contracts/industry-lpagery-planning-contract.md
