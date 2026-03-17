# Industry Asset Aging and Stale-Content Scoring Contract (Prompt 555)

**Spec:** maturity matrix docs; authoring and maintenance docs; release and completeness docs.  
**Status:** Contract. Defines an **internal** scoring model that identifies aging or stale industry assets so maintainers can spot overlays, rules, docs, presets, and bundles that may need refresh even when technically valid. Scoring is advisory only; no auto-edit, no public status system.

---

## 1. Purpose

- **Maintenance visibility:** Long-term maintenance requires visibility into which assets are old, rarely reviewed, or sensitive to product changes. Completeness and correctness are covered elsewhere; this contract addresses **stale-content risk**.
- **Advisory only:** Stale score is for maintainer planning and triage. It does not replace release review, does not auto-edit assets, and does not create a public "stale" status. Registry assets remain authoritative even when flagged as stale.
- **Bounded and explainable:** Scoring dimensions are fixed and documented. Scores are interpretable and auditable.

---

## 2. Scoring dimensions

| Dimension | What is measured | Benign vs risky |
|-----------|------------------|-----------------|
| **Asset age** | Time since asset definition was last materially updated (file mtime or version_marker / review date if tracked). | Benign: recently updated. Risky: no update in many release cycles. |
| **Review recency** | Time since last human or tool review (e.g. pre-release checklist, coverage audit, lint run). | Benign: reviewed in current or prior release. Risky: no review in long period. |
| **Usage criticality** | How central the asset is to active flows (e.g. overlays for high-affinity sections, bundles for popular subtypes). | Higher criticality + stale = higher priority for refresh. |
| **Change-sensitivity** | Likelihood that upstream changes (schema, contract, dependency) affect this asset. | High sensitivity + old = higher risk of silent drift. |

Dimensions are combined (e.g. weighted or banded) to produce a **stale-content score** or **stale-risk band** per asset. Exact formula is implementation- or runbook-defined; this contract fixes the dimensions and intent.

---

## 3. Asset types in scope

The model applies to:

- **Overlays:** Section-helper, page one-pager, subtype, goal, secondary-goal, combined subtype+goal (bundle and doc).
- **Rules:** CTA patterns, SEO guidance, LPagery rules, compliance rules, goal and secondary-goal caution rules.
- **Docs:** Catalog entries, one-pagers, helper doc overlays (as authored content), authoring/maintenance docs that reference assets.
- **Bundles:** Starter bundle definitions and bundle overlays.
- **Presets:** Style presets, token presets, goal/style overlay refs.
- **Scaffolds:** Scaffold templates and generator outputs that reference asset shapes.

Each asset type may have type-specific rules (e.g. overlay section_key vs page_key, bundle target_bundle_ref). Age and review recency are universal; usage criticality and change-sensitivity may be derived per type.

---

## 4. Benign age vs risky stale age

- **Benign age:** Asset is old but low criticality, low change-sensitivity, or recently reviewed. No immediate action required; schedule for next maintenance window.
- **Risky stale:** Asset is old and either high criticality, high change-sensitivity, or unreviewed for many cycles. Flag for maintainer review; do not auto-edit. Human decides refresh, deprecate, or leave as-is.

Stale score alone does **not** mean the asset is broken. It means "consider reviewing this asset."

---

## 5. Intended operational use

- **Internal maintenance:** Stale-content report or dashboard is for maintainers and release owners only. Not exposed to end users or as a product status.
- **Release and planning:** Use stale scores to prioritize which assets to include in a refresh pass or pre-release review. Align with [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) and [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md).
- **No misleading claims:** Do not claim that "stale = broken" or that a low stale score implies correctness. Document in runbook or report that scoring is advisory and human review is required.

---

## 6. Relation to completeness and maturity

- **Completeness (Prompt 519):** Answers "is the asset set complete?" (packs, overlays, rules, docs, QA). Stale scoring answers "among existing assets, which may need refresh?"
- **Maturity matrix:** Maturity levels (production-ready, stable, experimental, draft, gap) are about capability and evidence. Stale scoring does not change maturity level; it informs **maintenance** and **next steps** (e.g. "refresh overlays for subtype X").
- **Release review:** Release review remains human-owned. Stale report can be an input to the checklist; it does not replace the checklist.

---

## 7. References

- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness dimensions and bands.
- [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) — Maturity levels and capability areas.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Maintenance baseline and overlay/rule change steps.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Authoring workflow and validation.
