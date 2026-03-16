# Industry Subsystem — Internal Operator Curriculum (Prompt 459)

**Audience:** Internal implementers, QA, and support.  
**Spec:** support/runbook; maintenance and authoring guides; release signoff; troubleshooting.  
**Purpose:** Shared curriculum so operators have a consistent understanding of subsystem concepts, touchpoints, and validation.

---

## 1. Learning path (order)

1. **Terminology and architecture** — Read [industry-contract-consistency-audit.md](../contracts/industry-contract-consistency-audit.md) §1–2. Understand primary_industry_key, industry_key, industry_subtype_key, bundle_key, lifecycle states (active/draft/deprecated).
2. **Support training packet** — Read [industry-support-training-packet.md](industry-support-training-packet.md). Covers packs, subtypes, bundles, overlays, overrides, cautions, diagnostics, failure modes, escalation.
3. **Diagnostics and health** — Use [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md). Know snapshot shape, health report, documentation summary export, and bounded-output rules.
4. **Troubleshooting** — Use [industry-bad-fit-recommendation-troubleshooting.md](industry-bad-fit-recommendation-troubleshooting.md) for recommendation issues; [support-triage-guide.md](../guides/support-triage-guide.md) for logs and support bundle.
5. **Maintenance and release** — Use [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) and [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md); [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) and pre-release pipeline for release.

---

## 2. Key touchpoints

| Touchpoint | What to do |
|------------|------------|
| **Industry Profile screen** | Set primary (and optional secondary) industry; optional subtype; selected starter bundle. |
| **Industry Health Report** | View errors/warnings from Industry_Health_Check_Service; no auto-fix. |
| **Support Triage / diagnostics** | Industry snapshot (when loaded) shows profile_readiness, active_pack_refs, overlay counts, warnings. |
| **Documentation summary export** | Industry_Documentation_Summary_Export_Service::generate() for one bounded report (profile, packs, overrides, health, major_warnings). |
| **Override management** | View/accept/reject overrides per section, page template, Build Plan item; audit report for counts. |
| **Pre-release validation** | Run industry pre-release pipeline and checklist before pack/overlay releases; lint and health must pass or be waived. |

---

## 3. Release and validation

- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — additive behavior, export/restore, diagnostics, first-four industries, known risks.
- **Pre-release:** [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md), [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md).
- **Sign-off:** [industry-subsystem-final-signoff.md](../release/industry-subsystem-final-signoff.md) for production signoff.
- **Regression:** [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md); run before release when recommendation logic or packs change.

---

## 4. One-plugin, overlay-based architecture

- Industry is **additive**. No industry = core plugin unchanged. All industry behavior is gated by profile and registries.
- **Overlays** add content (section helper, page one-pager); they do not replace base templates. Registry-first; no ad hoc files.
- **Planner/executor separation** is preserved; Build Plans are reviewable and approval-gated. Industry influences recommendations and draft plans; it does not auto-execute.

---

## 5. Practical checks for support

- Confirm **primary_industry_key** and (if applicable) **industry_subtype_key** and **selected_starter_bundle_key** in profile.
- Confirm primary pack is **active** and refs (CTA, SEO, overlays, bundle) resolve (use Health Report).
- For “wrong recommendations,” follow bad-fit troubleshooting; prefer fixing profile/bundle over adding overrides.
- For handoffs, use **documentation summary export** so the next person sees profile state, override counts, and health in one place.

---

## 6. References

- [industry-support-training-packet.md](industry-support-training-packet.md)
- [industry-contract-consistency-audit.md](../contracts/industry-contract-consistency-audit.md)
- [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md)
- [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md)
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md)
