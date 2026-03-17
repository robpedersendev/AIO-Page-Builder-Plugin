# Goal Shared-Fragment Adoption Review (Prompt 514)

**Spec**: [industry-shared-fragment-contract.md](../contracts/industry-shared-fragment-contract.md); [conversion-goal-caution-rule-schema.md](../schemas/conversion-goal-caution-rule-schema.md); conversion-goal-caution-rule-contract.md.

**Purpose**: Document the bounded adoption of shared fragments in conversion-goal caution rules. Adoption is limited; goal-specific authored content remains primary.

---

## 1. Adoption scope

- **Bounded set**: Two goal caution rules (goal_calls_urgency_language, goal_bookings_urgency_language), one fragment ref (guidance_text_fragment_ref => caution_urgency_accuracy).
- **No mass refactor**: Other goal overlays and rules are unchanged. Direct authored caution_summary and guidance_text remain the main source.
- **Resolver support**: Industry_Compliance_Warning_Resolver accepts optional Industry_Shared_Fragment_Resolver; when a goal rule includes guidance_text_fragment_ref, the resolver appends the fragment content to caution_summary at display time (consumer_scope: compliance_caution).

---

## 2. What was adopted

| Artifact | Change | Fragment |
|----------|--------|----------|
| Goal caution rule: goal_calls_urgency_language | Added guidance_text_fragment_ref => 'caution_urgency_accuracy'. Existing caution_summary retained; fragment content appended in display. | caution_urgency_accuracy (Urgency and response-time claims must be accurate; avoid guaranteed availability or specific response times unless you can deliver.) |
| Goal caution rule: goal_bookings_urgency_language | Same guidance_text_fragment_ref. | caution_urgency_accuracy |

---

## 3. New fragment

- **caution_urgency_accuracy** (type: caution_snippet): "Urgency and response-time claims must be accurate; avoid guaranteed availability or specific response times unless you can deliver." Allowed consumers: section_helper_overlay, page_onepager_overlay, compliance_caution. Seeded in builtin-fragments.php (Prompt 514).

---

## 4. Why this adoption

- **Calls and bookings** both stress urgency/availability language; the shared snippet avoids duplication and keeps messaging consistent.
- **Two rules only** keeps the adoption set small and verifiable; no regression in output clarity.
- **Append semantics**: Direct rule caution_summary is kept; fragment adds consistent cross-goal caution. Display output remains clear and goal-appropriate.

---

## 5. Composition and resolution

- Order: industry rules → subtype rules → goal rules. For each goal rule with guidance_text_fragment_ref, fragment is resolved with consumer_scope `compliance_caution` and appended to caution_summary (space-separated).
- Invalid or missing fragment ref: resolver returns null; display uses only direct caution_summary (safe failure).
- Determinism: Resolution is deterministic; no recursion; fragment content is resolved at get_for_display() time.

---

## 6. Schema and contract

- **conversion-goal-caution-rule-schema.md**: Optional field **guidance_text_fragment_ref** (string, fragment_key; max 64). Resolved at display by Industry_Compliance_Warning_Resolver when Industry_Shared_Fragment_Resolver is present.
- **Goal_Caution_Rule_Registry**: Does not resolve fragments; stores the ref. Registry load does not validate fragment_key (optional field).

---

## 7. Regression and quality

- **Regression**: get_for_display( industry, subtype, 'calls' ) and get_for_display( industry, subtype, 'bookings' ) now include appended fragment content for the two adopted rules when fragment resolver is set. No removal of existing content.
- **Quality**: Fragment content is editorial and aligned with urgency/accuracy guidance; goal nuance remains in the direct rule text.
- **Fallback**: When fragment_resolver is null or fragment_key invalid, only direct caution_summary is shown.

---

## 8. Cross-references

- [industry-shared-fragment-catalog.md](../appendices/industry-shared-fragment-catalog.md)
- [conversion-goal-caution-rule-schema.md](../schemas/conversion-goal-caution-rule-schema.md)
- [industry-compliance-rule-catalog.md](../appendices/industry-compliance-rule-catalog.md)
