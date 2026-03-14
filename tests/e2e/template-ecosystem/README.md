# Template Ecosystem End-to-End Acceptance Suite

**Spec**: §56.4 End-to-End Test Scope; §60.5 Acceptance Test Requirements; **Prompt 216.**

This directory holds the **scenario manifest** and supporting material for the expanded template ecosystem end-to-end acceptance suite. The suite proves the template system works coherently across directory browsing, detail previews, compare, compositions, Build Plan recommendations, new-page creation, replacement, menu apply, diff/rollback, export/restore, and reporting enrichments.

## Contents

- **SCENARIO_MANIFEST.md** — Full list of E2E scenarios (happy-path and failure-path) with steps and expected outcomes. Each scenario has an ID (e.g. E2E-DIR-01) for evidence tracking.
- **README.md** — This file.

## How to use

1. **Prepare environment:** Use a demo/sandbox site with seeded template registries and synthetic data. See [docs/qa/demo-fixture-guide.md](../../docs/qa/demo-fixture-guide.md). Do not use production data or secrets.
2. **Execute scenarios:** Run each scenario in [SCENARIO_MANIFEST.md](SCENARIO_MANIFEST.md) manually (or via automated E2E when available). For failure-path scenarios, verify denial or validation failure, not success.
3. **Record evidence:** Log result (Pass / Fail / Waived / Skipped), run date, tester, and evidence reference in [docs/qa/template-ecosystem-end-to-end-acceptance-report.md](../../docs/qa/template-ecosystem-end-to-end-acceptance-report.md).
4. **Milestone gate:** The acceptance report supports §60.4 exit criteria and release-candidate closure. All critical-path scenarios should pass or be formally waived before milestone sign-off.

## Scope

- **In scope:** Directory (section/page), detail previews, Template Compare, Compositions, Build Plan template recommendation visibility, new-page build, page replacement, template-aware menu apply, diff/rollback summaries, export/restore, reporting enrichments (template_library_report_summary), capability restrictions, support/lifecycle visibility.
- **Out of scope:** New template creation beyond minimal blocker fixes; product QA outside template-ecosystem scope.

## Constraints

- **Planner/executor separation:** Execution flows must be driven by Build Plan / action envelopes; no ad hoc bypass.
- **CTA-law and preview safety:** Recommendations and previews must respect CTA-law and synthetic-only preview data.
- **Exportable/durable outcomes:** Export/restore and uninstall behavior must match documented promises and lifecycle summary.
