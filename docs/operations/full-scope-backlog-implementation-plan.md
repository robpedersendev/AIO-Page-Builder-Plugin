# Full-scope backlog — implementation plan (post legal/product sign-off)

**Purpose:** Single ordered plan for backlog items that are now in full product scope. Sequenced by dependency, risk, and user impact. Aligned with [master spec](../specs/aio-page-builder-master-spec.md), [Core Governance](../../.cursor/rules/Core-Governance.mdc), [Security-and-Privacy](../../.cursor/rules/Security-and-Privacy.mdc), and [Quality-Gates](../../.cursor/rules/Quality-Gates.mdc).

**Status legend:** Planned | In progress | Done (update row when shipping).

---

## Tier A — Compliance, disclosure, and operator visibility (ship first)

| ID | Item | Owner area | Acceptance (summary) |
|----|------|------------|----------------------|
| A1 | Mandatory operational reporting copy consistent across Dashboard + Settings/Privacy | Admin copy | Same factual claims; link to Privacy & reporting; no optional/misleading telemetry wording |
| A2 | Onboarding aggregate telemetry (no PII) + dashboard diagnostics card | Domain + Admin | Event ids stable; `by_step` + `recent` bounded; capability-gated; uninstall removes option |
| A3 | Telemetry event catalog documented in code + operator-facing description on dashboard card | Docs in PHPDoc + UI | Lists event types and data shape; matches implementation |

---

## Tier B — Onboarding UX, accessibility, and progressive disclosure

| ID | Item | Owner area | Acceptance (summary) |
|----|------|------------|----------------------|
| B1 | Skip link to main step content; main landmark id | Onboarding screen | WCAG 2.x focusable skip; target is step content region |
| B2 | Form associations: submission goal field `aria-describedby` to help text + warnings group | Onboarding screen | Screen reader gets min length + non-blocking warnings |
| B3 | Crawl phase labels + `data-aio-crawl-phase` on notices/embeds | Onboarding + domain | Stable phase keys; assistive name includes phase |
| B4 | Progressive disclosure for long steps (provider guide, assets, template “additional signals”) | Onboarding screen | Reduces cognitive load; fields remain in document flow |
| B5 | Stepper buttons: visible focus styles (theme-compatible) | CSS | Keyboard users see current focus |

---

## Tier C — Crawler and planning context

| ID | Item | Owner area | Acceptance (summary) |
|----|------|------------|----------------------|
| C1 | Crawl context summarizer covers none/running/completed/partial/failed/stale/unknown | `Onboarding_Crawl_Context_Phase` | Unit tests for transitions; stale threshold respects settings |
| C2 | (Future) Deeper crawler session state machine in UI | Crawler admin | Out of scope for this doc unless spec adds states |

---

## Tier D — Build Plan workspace and execution (product-approved backlog)

| ID | Item | Owner area | Acceptance (summary) |
|----|------|------------|----------------------|
| D1 | “Step 2 Deny” / workspace detail-table improvements | Product decision + Build Plan UI | Requires written acceptance criteria ([backlog-close-report](backlog-close-report.md)) |
| D2 | Industry bundle JSON **apply** (Outcome A) | Import + Industry registries | Per [industry-bundle-apply-decision](industry-bundle-apply-decision.md); conflict service; persistence layer; audit |

---

## Tier E — Quality gates (ongoing)

| ID | Item | Acceptance |
|----|------|------------|
| E1 | PHPUnit for new/changed domain logic | Green on CI |
| E2 | PHPStan on touched paths | No new errors |
| E3 | PHPCS on touched paths | No new violations |
| E4 | E2E / a11y automation | Playwright or Cypress + axe (when harness exists) — backlog until test infra decision |

---

## Implementation waves (suggested)

1. **Wave 1 (complete):** A2/A3 telemetry + dashboard aggregates; B3/B4 crawl + disclosure; review advisories; step_advanced event.
2. **Wave 2 (in progress):** A3 PHPDoc event catalog on `Onboarding_Telemetry`; B1 skip link + step content landmark + focus style; B2 submission goal `aria-describedby`; dashboard **`by_step`** breakdown in diagnostics metrics card.
3. **Wave 3:** D2 industry bundle apply (design persistence + handler + preview “Apply” — multi-PR).
4. **Wave 4:** D1 once product defines Step 2 Deny; E4 E2E.

---

## References

- [approved-backlog-implementation-summary.md](approved-backlog-implementation-summary.md)
- [backlog-close-report.md](backlog-close-report.md)
- [v2-scope-backlog.md](../release/v2-scope-backlog.md)
- [industry-bundle-apply-decision.md](industry-bundle-apply-decision.md)
