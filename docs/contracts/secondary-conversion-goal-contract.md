# Secondary Conversion-Goal Profile Contract (Prompt 528)

**Spec:** conversion-goal profile contracts; CTA and Build Plan contracts; roadmap and guardrail docs.  
**Status:** Contract. Defines the optional secondary conversion-goal layer so a site can declare a primary and secondary funnel objective without collapsing into arbitrary multi-goal complexity. No storage or UI implementation in this prompt.

---

## 1. Purpose

- **Bounded secondary goal:** Allow one optional secondary conversion goal in addition to the primary goal (e.g. primary = bookings, secondary = lead_capture for mixed-funnel sites).
- **Primary remains authoritative:** Primary goal is the main funnel context; secondary refines or adds nuance where supported.
- **No sprawl:** No unlimited goal lists; no redesign of the primary-goal model. Industry and subtype remain higher-level context layers.

---

## 2. Scope and constraints

- **In scope:** Optional secondary-goal object/state shape; allowed primary/secondary combinations or constraints; precedence and fallback; where secondary may influence planning; exportable and versioned state.
- **Out of scope:** Storage or UI implementation in this prompt; unlimited goal lists; changing primary-goal authority.

---

## 3. Secondary-goal state shape

- **secondary_conversion_goal_key** (string, optional): Stable key from the same launch goal set as primary (e.g. `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture`). Empty or missing = no secondary goal.
- **Resolved state:** Consumers receive both primary and optional secondary; when secondary is unset or invalid, behavior is primary-goal-only (or no-goal when primary is also unset).

---

## 4. Allowed primary/secondary combinations

- **Same key:** Primary and secondary MUST NOT be the same key. If they are equal, treat as primary-only (ignore secondary for resolution).
- **Valid keys:** Both must be from the allowed goal set (or registry). Invalid secondary is stripped at resolution; invalid primary remains a validation error per existing profile contract.
- **No goal:** When primary is empty, secondary is ignored (no secondary-only mode).
- **Limit:** At most one secondary goal. No tertiary or list of goals.

---

## 5. Precedence and fallback

- **Primary goal** remains the main funnel context for recommendations, Build Plan emphasis, and overlay selection.
- **Secondary goal** may apply **low-weight additive refinement** where consumers support it (e.g. section/page recommendation, Build Plan explanation). It never overrides primary.
- **Fallback order:** (1) Use primary + secondary when both valid and distinct. (2) Use primary only when secondary is empty, invalid, or same as primary. (3) No-goal when primary is empty.
- **Conflict:** If a consumer does not support secondary, it is ignored; no error. Safe degradation.

---

## 6. Where secondary may influence planning

- **Recommendation:** Optional low-weight refinement (e.g. section or page-template scoring) when recommendation contracts support it. Primary precedence is preserved.
- **Build Plan:** Optional refinement to plan emphasis or explanation metadata when Build Plan scoring supports it. Primary remains authoritative.
- **Overlays / bundles:** Secondary may be passed as context where overlays or bundles have secondary-goal-aware variants; absence of such variants implies primary-only behavior.
- **Documentation:** Prioritization and roadmap docs may reference secondary as an optional expansion; no requirement that all subsystems support it.

---

## 7. Export and versioning

- **Export:** When industry profile is exported, secondary_conversion_goal_key is included if present. Same schema versioning as profile.
- **Restore:** Restore validates secondary key; invalid or duplicate (same as primary) is stripped. No fatal failure.
- **Auditability:** State is part of profile; no separate audit trail required beyond existing profile audit.

---

## 8. Limits (summary)

- At most **one** secondary goal.
- Secondary **must not equal** primary.
- Secondary **optional**; primary remains the main goal.
- **No automatic execution** or mutation based on secondary alone.
- Invalid secondary refs or invalid combinations **fail safely** (strip or ignore).

---

## 9. Cross-references

- [conversion-goal-profile-contract.md](conversion-goal-profile-contract.md) — Primary goal; profile field conversion_goal_key.
- [conversion-goal-conflict-precedence-contract.md](conversion-goal-conflict-precedence-contract.md) — Layer precedence; goal refines within industry/subtype.
- [industry-profile-schema.md](../schemas/industry-profile-schema.md) — Profile storage; additive field for secondary.
- [secondary-conversion-goal-schema.md](../schemas/secondary-conversion-goal-schema.md) — Schema for secondary-goal state and validation.
- [secondary-goal-starter-bundle-contract.md](secondary-goal-starter-bundle-contract.md) — Bounded secondary-goal starter-bundle overlay model (Prompt 541).
- [secondary-goal-helper-overlay-contract.md](secondary-goal-helper-overlay-contract.md) — Secondary-goal section-helper overlay layer (Prompt 543).
- [secondary-goal-page-onepager-overlay-contract.md](secondary-goal-page-onepager-overlay-contract.md) — Secondary-goal page one-pager overlay layer (Prompt 545).
- [secondary-goal-caution-rule-contract.md](secondary-goal-caution-rule-contract.md) — Secondary-goal caution rules for mixed-funnel messaging (Prompt 547).
