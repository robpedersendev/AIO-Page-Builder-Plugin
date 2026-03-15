# Industry CTA Pattern Contract

**Spec**: industry-pack-extension-contract.md; section/page template CTA classification; documentation and one-pager conventions.

**Status**: CTA pattern registry and conversion-model contract so industry packs can declare preferred, discouraged, and required CTA patterns for docs, template ranking, and AI planning.

---

## 1. Purpose

- Define **CTA pattern objects** (e.g. consult, book-now, call-now, emergency-dispatch, valuation-request, claim-assistance, gallery-to-booking) that overlay existing CTA classifications.
- Allow **industry packs** to reference one or more CTA patterns with **preferred**, **discouraged**, and **required** semantics.
- Support **urgency**, **trust**, and **action framing** notes per pattern for use in docs and AI planning.
- Keep CTA pattern definitions **reusable** across docs, AI, and UI; no change to frontend CTA rendering in this contract.

---

## 2. CTA pattern object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **pattern_key** | string | Yes | Stable key (e.g. `consult`, `book_now`, `call_now`, `emergency_dispatch`). Pattern `^[a-z0-9_]+$`; max 64 chars. |
| **name** | string | Yes | Human-readable name. |
| **description** | string | No | Short description of the CTA pattern. |
| **urgency_notes** | string | No | When to use urgency framing; max 512 chars. |
| **trust_notes** | string | No | Trust-building or credibility notes; max 512 chars. |
| **action_framing** | string | No | Recommended action framing or copy direction; max 512 chars. |

- Pattern keys are **system-owned** and stable; used by industry packs in preferred_cta_patterns, discouraged_cta_patterns, required_cta_patterns (and optionally default_cta_patterns for preferred).
- Invalid or unknown pattern references must **fail safely**: resolution returns null or empty; no crash.

---

## 3. Industry pack reference

Industry packs reference CTA patterns by **pattern_key** in:

- **default_cta_patterns** or **preferred_cta_patterns**: list of pattern keys preferred for this industry.
- **discouraged_cta_patterns**: list of pattern keys to deprioritize or avoid for this industry.
- **required_cta_patterns**: list of pattern keys that are required or strongly recommended for this industry.

References are validated only at use time (e.g. when building AI context or ranking); unknown keys are skipped. No frontend schema changes in this contract.

---

## 4. Registry behavior

- **Industry_CTA_Pattern_Registry**: load(array of pattern definitions), get(pattern_key), get_all().
- Loading validates pattern_key and required fields; invalid entries are skipped. Duplicate pattern_key: first wins.
- Registry is **deterministic** and **read-only** after load. Exposed via container or passed to services that need CTA pattern resolution.

---

## 5. Overlay on existing CTA metadata

- Section and page templates keep existing **cta_classification**, **cta_intent_family**, and CTA section helpers unchanged.
- CTA patterns **overlay** industry-specific semantics; they do not replace existing CTA metadata.
- Existing CTA sections and docs remain valid; industry CTA patterns inform ranking and planning when an industry pack is active.

---

## 6. Implementation reference

- **Industry_CTA_Pattern_Registry**: Domain\Industry\Registry\Industry_CTA_Pattern_Registry.
- **industry-pack-schema.md**: preferred_cta_patterns, discouraged_cta_patterns, required_cta_patterns; default_cta_patterns as preferred alias.
- **Industry_Pack_Schema**: optional field constants for CTA pattern list refs.
