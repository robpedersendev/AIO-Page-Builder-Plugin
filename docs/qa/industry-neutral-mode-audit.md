# Industry Neutral-Mode Audit (Prompt 391)

**Purpose:** Document expected no-industry (neutral) behavior and QA checklist so the plugin remains stable and non-confusing when no primary industry is set.

---

## 1. Expected neutral-mode behavior

When **no primary industry** is set (empty industry profile or primary_industry_key empty):

| Surface | Expected behavior |
|--------|---------------------|
| **Onboarding / Industry Profile** | Industry selection dropdown shows empty/default option; saving with empty primary is valid. No errors; copy explains that setting an industry enables industry-specific recommendations. |
| **Industry Profile screen** | Readiness shows "none" or "partial"; no "Active industry pack" section. Neutral copy: "No industry selected. Recommendations use the generic library." |
| **Industry Style Preset screen** | Message: "Set your Industry Profile (primary industry) to see presets for your industry." No preset list; no broken empty state. |
| **Starter bundle assistant** | Row hidden when no primary (no bundles to show). No empty dropdown or confusing placeholder. |
| **Section library / Page template directory** | Industry filter not applied or shows "All"; templates listed in default order. No missing previews or errors. |
| **Build Plan review** | Plan items show generic recommendations; no industry fit badges or industry context block when `has_industry_data` is false. View renders without industry section. |
| **Section / Page template detail (preview)** | Preview and recommendations resolve with empty industry_key; generic content. No "missing industry" errors. |
| **Diagnostics / Health report** | Snapshot shows primary_industry empty, recommendation_mode inactive; health check runs and reports profile/bundle issues only when relevant. |

---

## 2. Audit checklist (no-industry mode)

- [ ] **Industry Profile:** With empty primary, screen shows readiness and form; no "Active industry pack"; neutral copy present ("No industry selected…" or equivalent).
- [ ] **Industry Style Preset:** With empty primary, screen shows single sentence directing user to set Industry Profile; no table, no broken links.
- [ ] **Starter bundle:** With empty primary, starter bundle row is not rendered (assistant returns early).
- [ ] **Section library filter:** With empty profile, filter controller does not apply industry filter; list order generic.
- [ ] **Page template directory filter:** Same as section library; no industry filter applied.
- [ ] **Build Plan UI:** Industry explanation view renders nothing when `has_industry_data` is false; no empty or broken industry block.
- [ ] **Section template detail:** Industry preview/build uses empty industry_key safely; no PHP/JS errors; generic preview when no industry.
- [ ] **Page template detail:** Same as section template detail.
- [ ] **Onboarding:** Industry step can be skipped or saved with empty primary; no crash; next steps available.
- [ ] **Diagnostics snapshot:** With no industry, snapshot has primary_industry '', recommendation_mode inactive, industry_subsystem_available true (when module loaded).
- [ ] **Health report:** Runs with no industry; may report zero packs or empty profile; no fatal or misleading errors.

---

## 3. Safe fallback invariants

- **No forced industry:** No code path requires a non-empty primary_industry_key for core plugin operation.
- **Deterministic:** Same inputs (empty profile) produce same behavior; no hidden dependency on industry activation.
- **Copy:** All industry-aware surfaces that show when no industry is set include short, neutral copy (e.g. "Set industry for industry-specific guidance" or "Recommendations use the generic library").

---

## 4. Reference

- **Degraded mode:** [industry-degraded-mode-contract.md](../contracts/industry-degraded-mode-contract.md) — formal fail-safe and degraded-mode contract (Prompt 467).
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) (no-industry fallback criterion).
- **Acceptance report:** [industry-subsystem-acceptance-report.md](industry-subsystem-acceptance-report.md) row 14.
