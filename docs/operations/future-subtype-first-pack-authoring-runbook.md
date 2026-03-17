# Future Subtype First-Pack Authoring Runbook (Prompt 540)

**Spec:** Subtype planning docs; subtype scaffold docs; authoring and release docs.

**Purpose:** End-to-end runbook for taking a future subtype scaffold from planning through authored overlays, bundles, rules, QA, and release readiness. Internal-only; human review required; no bypass of release gates.

---

## 1. Scope and principles

- **One run per first subtype pack:** Use this runbook when authoring the **first** real subtype layer for an existing (active) parent industry (post-subtype scaffold). Additional subtypes for the same parent follow the same sequence.
- **Parent industry is base:** Subtype extends an existing industry pack; parent_industry_key must be active. Subtype scaffold does not create or modify the parent pack.
- **Bounded overlays:** Subtype adds definition, overlays, bundle(s), and optional caution/CTA/SEO/LPagery refs; no new core registries or seams.
- **Release rigor intact:** Linting, health check, completeness, benchmarks, and release gate are required checkpoints. No shortcuts.

---

## 2. Prerequisites (before starting the runbook)

| Prerequisite | Reference | Checkpoint |
|--------------|-----------|------------|
| **Parent industry active** | Industry pack with parent_industry_key is active; refs resolve; health check passes. | Pack exists and is release-ready or already released. |
| **Subtype justified** | [launch-subtype-second-wave-planning-framework.md](launch-subtype-second-wave-planning-framework.md) — admission criteria, meaningful differentiation, no new core seams. | Candidate list and justification documented. |
| **Subtype scaffold created** | [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md); [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) | Subtype definition, overlay placeholders, bundle placeholder(s), docs/QA placeholders in place; status = draft. |

---

## 3. Authoring sequence (first subtype pack)

Execute in order. Each step has a **signoff checkpoint** before proceeding.

| Step | Action | Required vs optional | Signoff checkpoint |
|------|--------|----------------------|--------------------|
| **1. Subtype definition** | Replace placeholder label/summary; set parent_industry_key (must be active); add starter_bundle_ref, helper_overlay_refs, one_pager_overlay_refs, caution_rule_refs only when dependencies exist. Validate with subtype schema. | Required | Definition passes schema; parent_industry_key resolves to active pack. |
| **2. Starter bundle(s)** | Author subtype-scoped bundle(s) per [future-industry-starter-bundle-scaffold-template.md](future-industry-starter-bundle-scaffold-template.md) (subtype_key set); fill recommended_* refs; set status = active. Set subtype starter_bundle_ref. | Required if subtype is to have a distinct bundle | Bundle(s) pass schema; subtype_key matches; refs resolve. |
| **3. Section helper overlays** | Author subtype section-helper overlays; set status active; add to subtype helper_overlay_refs when ready. | Optional (recommended for differentiation) | Overlay refs resolve; no placeholder content in refs. |
| **4. Page one-pager overlays** | Author subtype page one-pager overlays; set status active; add to subtype one_pager_overlay_refs when ready. | Optional (recommended for differentiation) | Overlay refs resolve. |
| **5. Caution/compliance rules** | Add subtype caution rules if subtype references caution_rule_refs; register in Subtype_Compliance_Rule_Registry (or equivalent). | Optional | Refs resolve. |
| **6. CTA posture / SEO / LPagery / style** | If subtype definition references cta_posture_ref, seo_guidance_ref, lpagery_rule_ref, or style overrides, add or reuse definitions and link. | Optional (per subtype design) | All refs resolve. |
| **7. Conversion goals** | Goal overlays apply at Build Plan/recommendation layer; subtype does not embed goal keys. Goal caution rules may apply when profile has conversion_goal_key; add if in scope. | Optional | Document intent if goal rules will be added. |
| **8. Docs and QA** | Replace scaffold README/QA placeholders; document subtype in catalog or authoring docs; link to pre-release checklist and release gate. | Required (minimal) | Scaffold "incomplete" marker removed. |
| **9. Set status active** | Change subtype (and bundle, overlays) status from draft to active only after steps 1–8 are complete and all refs resolve. | Required | No draft subtype in release set; health check passes for profile when subtype selected. |

---

## 4. When goals, presets, cautions, and LPagery are required vs optional

| Artifact | Required for first subtype pack? | When to include |
|----------|----------------------------------|------------------|
| **Conversion goals** | No | Goal overlays and goal caution rules are additive; add when funnel differentiation is in scope for this subtype. |
| **Style preset** | No | Subtype may inherit parent preset; override only when subtype needs distinct tokens. |
| **Caution rules** | No | Include when subtype has distinct compliance or liability guidance (e.g. buyer vs listing agent). |
| **CTA posture** | Optional | Include when subtype has distinct CTA emphasis (e.g. cta_posture_ref in subtype definition). |
| **SEO / LPagery** | No | Add only when subtype overrides parent SEO or LPagery guidance. |
| **Starter bundle** | Strongly recommended | Meaningful differentiation usually includes a distinct bundle; second-wave planning expects bundle or overlays. |
| **Overlays (section/page)** | Strongly recommended | At least one of overlay or bundle expected for "meaningful differentiation" per launch-subtype-second-wave-planning-framework. |

---

## 5. Linting, completeness, benchmark, and release evidence

| Expectation | When | Reference |
|-------------|------|-----------|
| **Definition linting** | Before marking subtype active and before any release. Errors = 0; warnings documented. | [industry-definition-linting-guide.md](industry-definition-linting-guide.md) |
| **Health check** | After all refs in place. Industry_Health_Check_Service::run(); errors = 0; profile with this subtype selected passes. | industry-pack-release-gate |
| **Completeness (advisory)** | Run Industry_Pack_Completeness_Report_Service::generate_report( true ) to include subtype in band; assess overlay/bundle coverage. | [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) |
| **Scaffold completeness (advisory)** | If starting from scaffold, run Industry_Scaffold_Completeness_Report_Service::generate_report() with scaffold_subtype_keys to track progress. | [industry-scaffold-completeness-report-contract.md](../contracts/industry-scaffold-completeness-report-contract.md) |
| **Subtype benchmark** | Run Industry_Subtype_Benchmark_Service::run_benchmark() to confirm meaningful vs weak differentiation. | [industry-subtype-benchmark-protocol.md](../qa/industry-subtype-benchmark-protocol.md) |
| **Subtype+goal benchmark** | If conversion goals are in scope, run Industry_Subtype_Goal_Benchmark_Service for combined quality. | [industry-subtype-goal-benchmark-protocol.md](../qa/industry-subtype-goal-benchmark-protocol.md) |
| **Pre-release pipeline** | Full pipeline before release candidate: lint → health → coverage → benchmarks → regression guards → release gate. | [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md); [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md) |
| **Release gate** | All criteria in [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) met or waived; human sign-off. | industry-pack-release-gate §1 |

---

## 6. Signoff checkpoints (summary)

1. **Post-scaffold:** Subtype scaffold created per template; parent industry active; incomplete markers present; no activation.
2. **Post-authoring:** Subtype definition, bundle(s), and referenced overlays/rules authored; status = active only when refs resolve; lint and health pass.
3. **Pre-release:** Pre-release checklist run; subtype benchmark and (if applicable) combined subtype+goal benchmark run; release gate criteria satisfied.
4. **Release sign-off:** Human review and sign-off per release gate; no auto-approval.

---

## 7. Cross-references

- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Implementation order; validation and QA; subtype step (§3.11).
- [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md) — Subtype scaffold structure and promotion path.
- [launch-subtype-second-wave-planning-framework.md](launch-subtype-second-wave-planning-framework.md) — Admission criteria and prioritization; when to add a subtype.
- [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md) — Subtype object model and overlay scope.
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) — Validation steps and order.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Gate criteria and sign-off.
- [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) — First industry pack runbook; subtypes may be created as part of that or separately.
- **Future subtype readiness screen** (Prompt 567) — Admin screen for subtype scaffold and promotion-readiness summary; use for planning and status before starting this runbook.
