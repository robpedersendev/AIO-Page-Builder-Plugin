# Industry Pack Completeness Scoring Contract (Prompt 519)

**Spec:** industry-subsystem-maturity-matrix.md; industry-pack-release-gate.md; industry-pack-authoring-guide.md; scaffold-incomplete-state-guardrail-contract.md.  
**Status:** Contract. Defines an **advisory** completeness scoring model for industry packs, subtypes, bundles, overlays, rules, docs, and QA evidence. Maintainers use it to assess whether an asset set is minimally complete, strong, or release-grade. Score alone does not imply release approval; human review and release gate remain required.

---

## 1. Purpose

- **Systematic assessment:** Provide a bounded, explainable way to measure completeness across many artifact types (packs, subtypes, bundles, overlays, rules, docs, QA evidence).
- **Advisory only:** Completeness score supports authoring and release workflows; it does not replace release review, release gate, or pre-release validation. No auto-approval of releases based on score.
- **Align with subsystem:** The model reflects actual subsystem artifact needs (registries, schemas, authoring guide, release gate).

---

## 2. Completeness dimensions

| Dimension | What is measured | Weight / note |
|-----------|------------------|---------------|
| **Pack definition** | Pack exists; required fields present; status active; version_marker valid; refs (CTA, SEO, preset, LPagery, overlays) declared and resolvable. | Core; must pass for any completeness. |
| **Starter bundle(s)** | At least one starter bundle for the pack (or subtype); bundle_key, industry_key, subtype_key (if any), label, status; recommended_* refs present and resolvable. | Core for pack usability. |
| **Overlays (section + page)** | Section-helper and/or page one-pager overlays present for key section/page keys; content_body authored (not placeholder); status active; refs in pack/subtype. | Strong completeness. |
| **Rules (CTA, SEO, LPagery, compliance)** | CTA patterns, SEO guidance, LPagery rules, compliance/caution rules as referenced by pack or subtype; definitions present and resolvable. | Per pack refs; minimal = refs resolve. |
| **Docs** | Pack/subtype documented in catalog or authoring docs; operator/support guidance updated where relevant; scaffold README or placeholder replaced. | Minimum: catalog or equivalent. |
| **QA evidence** | Lint pass; health check pass; optional benchmark or regression evidence; pre-release checklist run. | Release-grade signal. |
| **Subtype (optional)** | If pack supports subtypes: subtype definitions present; overlay/bundle refs resolvable; resolver and fallback documented. | Only when pack has subtypes. |
| **Goal support (optional)** | If pack supports conversion goals: goal overlays or caution rules present; no broken refs. | Only when goal layer is in scope. |

Dimensions are additive: a pack can score on each applicable dimension. Dimensions not applicable (e.g. no subtypes) are skipped or scored N/A.

---

## 3. Scoring ranges and bands

- **Per-dimension score:** Use a simple scale (e.g. 0–3) per dimension:
  - **0** — Missing or invalid (e.g. pack not present, refs broken, status draft with no authoring path).
  - **1** — Minimal viable (e.g. pack present and valid; at least one bundle; refs resolve; docs placeholder or minimal).
  - **2** — Strong (e.g. overlays present and authored; rules and docs in place; QA run but not full gate).
  - **3** — Release-grade (e.g. full ref coverage; QA evidence and pre-release checklist complete; ready for gate review).

- **Aggregate band:** Sum or weighted sum of dimension scores yields a total. Map total to a **band** for communication only (not for automatic decisions):
  - **Minimal viable:** Total above a low threshold; no dimension at 0 for core dimensions (pack, bundle).
  - **Strong:** Total above a higher threshold; overlay and rule dimensions at least 1; docs and QA at least 1.
  - **Release-grade:** Total at or above release threshold; QA evidence dimension at 2 or 3; all core dimensions at 2 or 3. **Release-grade band does not imply release approval;** it means "meets completeness bar for gate submission." Human review and release gate criteria still apply.

Exact thresholds (e.g. "minimal = 5+, strong = 12+, release-grade = 18+") are defined in implementation or a separate scoring runbook so the contract stays stable while numbers can be tuned.

---

## 4. What is in scope for scoring

- **Packs:** Single industry pack (industry_key); all dimensions that apply to that pack.
- **Subtypes:** When scoring a pack that has subtypes, include subtype definitions, subtype overlays, subtype bundles; when scoring a standalone subtype (e.g. future-subtype scaffold), dimensions apply to the subtype artifact set only.
- **Bundles:** Each starter bundle counted in bundle dimension; aggregate can require "at least one" or "one per subtype" depending on policy.
- **Overlays:** Section-helper and page one-pager (industry and subtype); count present and authored vs placeholder.
- **Rules:** CTA, SEO, LPagery, compliance/caution refs; count resolvable and defined.
- **Docs:** Catalog entry, authoring docs, operator/support references; minimum = one doc artifact or catalog entry.
- **QA evidence:** Lint result, health check result, optional benchmark/regression; release-grade = pre-release checklist or equivalent run.

---

## 5. Future-industry and future-subtype authoring

- **Future industry:** Apply the same dimensions to a scaffold or in-progress industry pack; score will be low until authoring completes (draft status, placeholder refs). Use score to track progress toward minimal viable and release-grade; do not treat scaffold as "complete" until status active and refs resolved (see scaffold-incomplete-state-guardrail-contract).
- **Future subtype:** Apply dimensions to subtype definition, subtype overlays, subtype bundle(s), and refs; parent pack must already be complete or scored separately.

---

## 6. Intended use in authoring and release workflows

- **Authoring:** Authors can run completeness score (or a checklist derived from it) to see which dimensions are missing or weak. Use to prioritize next steps (e.g. add overlays, fix refs, add QA run). Does not replace industry-pack-authoring-guide or industry-pack-author-checklist.
- **Release:** Maintainers can use release-grade band as a **readiness signal** before submitting to release gate. Gate criteria (industry-pack-release-gate) and pre-release validation pipeline remain the blocking requirements; score is advisory "are we complete enough to submit?"
- **No auto-approval:** No code or process may approve a release solely because completeness score is above a threshold. Human review and sign-off are required.

---

## 7. Bounded and explainable

- **Bounded:** Only the dimensions and artifact types defined in this contract and in the subsystem (packs, bundles, overlays, rules, docs, QA) are scored. No open-ended or subjective dimensions without definition.
- **Explainable:** Each dimension has a clear meaning; per-dimension scores and aggregate band can be reported with a short rationale (e.g. "overlay dimension = 2 because section overlays present, page overlays partial").

---

## 8. Security and constraints

- **Internal-only:** Completeness scoring is for internal maintainers and authors. No public API or end-user-facing score.
- **No misleading claims:** Documentation and tooling must not state or imply that achieving a given score or band alone equals release approval. Release approval requires release gate and sign-off.

---

## 9. Cross-references

- [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) — Capability-area maturity; completeness score can inform "evidence gaps" for a pack or subtype.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria; score does not replace gate.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Required pieces and implementation order; dimensions align with these.
- [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) — Scaffold/incomplete assets; score for scaffold will reflect incomplete state until authoring clears it.
- [future-industry-scaffold-pack-template.md](../operations/future-industry-scaffold-pack-template.md) — Future industry artifact set; dimensions cover these artifact classes.
- [future-subtype-scaffold-pack-template.md](../operations/future-subtype-scaffold-pack-template.md) — Future subtype artifact set; dimensions cover subtype-specific artifacts.
