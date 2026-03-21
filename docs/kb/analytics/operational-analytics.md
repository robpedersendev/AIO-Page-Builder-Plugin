# Operational analytics and post-release health

**Audience:** Operators reviewing plan/template trends and post-release posture.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §8.  
**Screens:** `aio-page-builder-build-plan-analytics`, `aio-page-builder-template-analytics`, `aio-page-builder-post-release-health`.

---

## Scope (architecture only)

Single KB home for **read-only analytics** views: Build Plan Analytics, Template Analytics, and Post-Release Health. Will document date filters, what each summary means, and deep links to Build Plans, Queue & Logs, and Support Triage. No duplication of [support-triage-guide.md](../../guides/support-triage-guide.md) triage procedures.

---

## Target outline for full article

- Observational-only nature (no mutations from these screens).
- Build Plan Analytics: approval/denial trends, blockers, execution/rollback summaries (as shown in UI).
- Template Analytics: aggregate template usage signals (as shown in UI).
- Post-Release Health: period selection, export JSON (if enabled for role), relationship to reporting health.
- Cross-links: [admin-operator-guide.md §6–§9](../../guides/admin-operator-guide.md), [admin-screen-inventory.md](../../contracts/admin-screen-inventory.md) §§4–5.
