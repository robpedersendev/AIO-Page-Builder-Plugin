# Launch Subtype Second-Wave Planning Framework (Prompt 479)

**Spec**: industry-subtype-extension-contract.md; industry-subtype-schema.md; subtype catalogs; roadmap and backlog docs; future-industry and subtype planning guides.

**Purpose**: Bounded planning framework for evaluating and prioritizing a second wave of subtypes within existing launch industries. Reduces subtype sprawl risk and keeps expansion disciplined.

---

## 1. Scope and principles

- **Parent industries remain the base layer**: Subtypes extend cosmetology_nail, realtor, plumber, disaster_recovery (and future launch industries); they do not replace them.
- **Subtypes are bounded overlays**: Schema-driven; resolver and fallback in place; no new core seams.
- **New subtype additions** must be justified by **meaningful behavior differences** (CTA, page emphasis, overlays, bundles, or caution rules)—not nominal labels only.
- **One-plugin architecture** and v2 guardrails (subtype count, no unbounded sprawl) remain intact.

---

## 2. Subtype admission criteria

A candidate second-wave subtype should satisfy:

| Criterion | Requirement |
|-----------|--------------|
| **Parent industry** | Belongs to an existing launch (or approved) industry; parent_industry_key is valid and active. |
| **Meaningful differentiation** | At least one of: distinct CTA posture, page-family emphasis, starter bundle, helper/one-pager overlays, or caution rules that differ meaningfully from parent or existing subtypes. |
| **Schema and resolver** | Subtype definition is schema-valid; Industry_Subtype_Resolver and fallback behavior apply without change. |
| **No new core seams** | All refs (CTA, bundle, overlays, caution) resolve to existing registries; no new registry types for the subtype itself. |
| **Documented justification** | Short rationale for why the subtype warrants support (e.g. "buyer vs listing agent drives different page emphasis and CTA") rather than remaining a parent-industry use case. |

Failure on any criterion implies the candidate should **not** be added as a subtype (or should be deferred until criteria are met).

---

## 3. Subtype prioritization factors

When ordering second-wave candidates:

| Factor | Use |
|--------|-----|
| **Differentiation strength** | Stronger differentiation (e.g. distinct overlays + bundle + CTA) ranks higher. |
| **User/value impact** | Subtype represents a significant user segment or use case for the parent industry. |
| **Maintenance cost** | Lower overlay/bundle/rule surface and clearer boundaries rank higher. |
| **Consistency with launch set** | Aligns with existing subtype patterns (realtor buyer/listing, plumber residential/commercial, etc.) for predictability. |
| **Roadmap and maturity** | Fits current maturity matrix and roadmap; no conflict with v2 guardrails (e.g. subtype count per pack). |

---

## 4. When a business variation is not worth subtype support

Do **not** add a subtype when:

- **Nominal only**: The variation is a label or marketing segment with no meaningful difference in CTA, overlays, bundles, or caution. Prefer parent-industry with optional metadata or content guidance.
- **Overlap with existing subtype**: The candidate largely duplicates an existing subtype’s behavior; consolidate or refine the existing one instead.
- **High sprawl risk**: Adding the subtype would push the parent’s subtype count toward or past team limits (e.g. >10 per pack) without clear structure or justification.
- **New core seams required**: The variation would require new registry types or core code paths; out of scope for subtype overlay model.
- **Unbounded or fuzzy**: The “subtype” cannot be clearly defined (e.g. many overlapping segments); prefer parent + optional guidance.

---

## 5. Second-wave subtype planning outputs

Planning should produce:

- **Candidate list**: Proposed subtype_key, parent_industry_key, label, and one-line justification.
- **Differentiation summary**: What differs from parent and from existing subtypes (CTA, overlays, bundle, caution).
- **Authoring path**: Once a candidate is approved, create a subtype scaffold per [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md) and follow [future-subtype-first-pack-authoring-runbook.md](future-subtype-first-pack-authoring-runbook.md) (Prompt 540) for authoring through release readiness.
- **Priority order**: Ordered by prioritization factors (§3); optional go/review/defer per candidate.
- **Documentation**: Update subtype catalog and extension contract references when a subtype is approved; update roadmap/backlog when deferred.

These outputs support future prompt generation and backlog ordering; they do not auto-create subtypes. When implementing an approved subtype, use the concrete [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md) for the file and artifact skeleton (Prompt 517).

---

## 6. Alignment with roadmap and maturity

- **Roadmap**: Second-wave subtypes fall under “Industry subtypes” in industry-subsystem-roadmap-contract §6; new subtypes must use approved seams only.
- **Maturity matrix**: Subtype capability area (registry, resolver, fallback) remains stable; new subtypes do not lower maturity but should not increase unresolved risk.
- **v2 guardrails**: Subtype count per pack and “no unbounded subtype sprawl” (industry-subsystem-v2-guardrails.md §4) apply; second-wave planning must stay within these boundaries.
- **Future-industry overlap**: When evaluating new industries, subtype complexity is a dimension in the future-industry scorecard; second-wave planning does not replace that but can inform which parent industries are in scope for more subtypes.

---

## 7. Cross-references

- [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md)
- [industry-subtype-schema.md](../schemas/industry-subtype-schema.md)
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md)
- [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md)
- [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md)
- [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) (subtype complexity dimension)
- [industry-subtype-benchmark-protocol.md](../qa/industry-subtype-benchmark-protocol.md) — Benchmark to measure meaningful vs weak subtype differentiation; can inform second-wave prioritization.
- [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md) — Concrete scaffold template for new subtypes (Prompt 517); artifact classes, placement, placeholders, docs/QA minimums.
