# Conversion-Goal AI Prompt-Pack Evaluation Fixtures (Prompt 534)

**Spec**: AI planner and evaluation contracts; conversion-goal contracts; prompt-pack overlay contracts.

**Purpose:** Internal evaluation fixtures to test whether AI planner prompt-pack overlays for the launch goal set produce structured, on-target recommendations rather than generic or contradictory outputs. Evaluation is internal-only and bounded.

---

## 1. Scope

- **Launch goals:** calls, bookings, estimates, consultations, valuations, lead_capture.
- **Evaluated dimensions:** Page-family alignment; CTA posture; proof emphasis; funnel alignment.
- **Optional:** Limited mixed-goal (primary + secondary) fixtures when secondary-goal support exists.
- **Out of scope:** Exposing raw provider internals; general AI benchmarking product; changing execution behavior.

---

## 2. Overlay output shape (reference)

Per Conversion_Goal_Prompt_Pack_Overlay_Service (Prompt 533), the goal overlay includes:

| Field | Description | Evaluation focus |
|-------|-------------|------------------|
| schema_version | `1` | Present and valid. |
| primary_goal_key | Primary conversion goal | Matches fixture input; one of launch set. |
| secondary_goal_key | Optional secondary goal | Present only when set and distinct from primary. |
| conversion_goal_guidance_text | Planning guidance for system prompt | Non-empty when primary set; emphasizes primary; secondary (if any) clearly lower priority. |

---

## 3. Fixture scenarios (launch goals)

Each scenario is **input artifact** (industry_context with primary_goal_key and optionally secondary_goal_key) plus **expected good-result pattern**. Use these to build input_artifact and call `Conversion_Goal_Prompt_Pack_Overlay_Service::get_overlay_for_artifact()`; then assert or manually compare overlay to expected pattern.

### 3.1 Calls

- **Input:** industry_context with primary_goal_key = `calls`.
- **Expected good-result pattern:** primary_goal_key === 'calls'; conversion_goal_guidance_text non-empty; guidance emphasizes phone/click-to-call, visible phone, call-focused CTAs; no secondary_goal_key. Page-family and CTA posture in downstream planning should favor call-oriented templates and sections.

### 3.2 Bookings

- **Input:** industry_context with primary_goal_key = `bookings`.
- **Expected good-result pattern:** primary_goal_key === 'bookings'; guidance emphasizes appointment/booking flow, reducing friction to book; CTA posture aligns with book_now / schedule. Funnel alignment: booking as primary conversion.

### 3.3 Estimates

- **Input:** industry_context with primary_goal_key = `estimates`.
- **Expected good-result pattern:** primary_goal_key === 'estimates'; guidance emphasizes estimate or quote request; CTAs and proof points support requesting an estimate. No contradictory “book now” as primary CTA.

### 3.4 Consultations

- **Input:** industry_context with primary_goal_key = `consultations`.
- **Expected good-result pattern:** primary_goal_key === 'consultations'; guidance supports booking or requesting a consultation; funnel alignment for consultation-first flow.

### 3.5 Valuations

- **Input:** industry_context with primary_goal_key = `valuations`.
- **Expected good-result pattern:** primary_goal_key === 'valuations'; guidance encourages valuation or appraisal request; proof/trust emphasis appropriate for valuation funnel.

### 3.6 Lead capture

- **Input:** industry_context with primary_goal_key = `lead_capture`.
- **Expected good-result pattern:** primary_goal_key === 'lead_capture'; guidance supports form submission or contact capture; nurture-before-hard-sell posture; no contradictory immediate-close emphasis.

### 3.7 Mixed goal (primary + secondary)

- **Input:** industry_context with primary_goal_key = `bookings`, secondary_goal_key = `lead_capture`.
- **Expected good-result pattern:** primary_goal_key === 'bookings'; secondary_goal_key === 'lead_capture'; conversion_goal_guidance_text contains primary emphasis first, then “Secondary objective (lower priority)” with lead_capture guidance. Primary precedence clear in text; no inversion of priority.

### 3.8 No goal (fallback)

- **Input:** industry_context without primary_goal_key, or primary_goal_key empty/invalid.
- **Expected good-result pattern:** Overlay contains only schema_version (minimal); no primary_goal_key, no conversion_goal_guidance_text. Planner receives no goal-specific constraints.

### 3.9 Invalid primary goal

- **Input:** industry_context with primary_goal_key = `unknown_goal`.
- **Expected good-result pattern:** Overlay minimal (schema_version only); no guidance text; safe fallback.

---

## 4. Evaluation procedure

1. **Build input artifact** for each scenario (industry_context with primary_goal_key and optionally secondary_goal_key; or empty/invalid for fallback).
2. **Call** `Conversion_Goal_Prompt_Pack_Overlay_Service::get_overlay_for_artifact( $input_artifact )`.
3. **Assert or review:**
   - Overlay has schema_version; when goal set, primary_goal_key matches and conversion_goal_guidance_text is non-empty.
   - For mixed goal: secondary_goal_key present and distinct; guidance text reflects primary-first, secondary second.
   - For no-goal or invalid: overlay is minimal (schema_version only).
4. **Structured comparison:** Overlay shape and key presence; guidance text contains expected keywords (e.g. “booking”, “phone”, “lead”) per goal. Prefer contract-level assertions over brittle exact string match.
5. **Repeatability:** Same input artifact yields same overlay; run as part of internal test or QA script. No public-facing surface.

---

## 5. Test integration

- **Unit tests:** Conversion_Goal_Prompt_Pack_Overlay_Service_Test covers no-goal, primary-only, primary+secondary, invalid primary, and same-as-primary secondary omitted.
- **Evaluation runs:** For each launch goal, run overlay service with minimal industry_context containing only primary_goal_key (and secondary where applicable); assert overlay shape and that guidance_text is on-target (keyword or checklist).
- **Release gate:** Goal-aware AI planning quality can reference “evaluation fixtures run and pass” or documented waiver (see release gate docs).
- **QA acceptance:** Industry and AI acceptance docs may reference this fixture set for goal-aware planning verification.

---

## 6. Expected good-result checklist (summary)

| Check | Description |
|-------|-------------|
| Schema | overlay.schema_version === '1'. |
| Primary set | When primary_goal_key valid: primary_goal_key in overlay; conversion_goal_guidance_text non-empty and goal-appropriate. |
| Secondary (optional) | When secondary set and distinct: secondary_goal_key in overlay; text includes “Secondary objective” and secondary hint. |
| Primary precedence | Guidance text emphasizes primary first; secondary explicitly lower priority. |
| No goal / invalid | When no goal or invalid key: overlay minimal; no primary_goal_key or guidance_text. |
| No secrets | All overlay fields safe for internal log; no provider internals. |
| Structured output | Overlay shape stable; comparable across runs for same input. |

---

## 7. Cross-references

- **Conversion-goal overlay:** Conversion_Goal_Prompt_Pack_Overlay_Service (Prompt 533); industry-planner-input-contract (primary_goal_key, secondary_goal_key).
- **Conversion-goal contracts:** conversion-goal-profile-contract.md; secondary-conversion-goal-contract.md.
- **Industry AI fixtures:** [industry-ai-prompt-evaluation-fixtures.md](industry-ai-prompt-evaluation-fixtures.md).
- **Goal benchmark:** [conversion-goal-benchmark-protocol.md](conversion-goal-benchmark-protocol.md).
- **Release gate:** industry-pack-release-gate.md; QA acceptance docs.
