# Post-Release Health Review Template (Prompt 131)

Internal template for conducting a post-release health review using the Post-Release Health screen and exported summary. Spec refs: §45, §46, §49.11, §59.15, §60.8.

## Purpose

- Review real operational behavior of the first release across key domains.
- Produce internal review artifacts and tuning guidance for subsequent versions.
- No automatic product changes; observational and tuning-oriented only.

## Review Period

- **From:** _______________ (Y-m-d)
- **To:** _______________ (Y-m-d)
- **Reviewer:** _______________
- **Date of review:** _______________

## 1. Summary

- **Overall status (from screen):** [ ] ok  [ ] attention  [ ] critical
- **Summary message:** _______________________________________________

## 2. Domain health scores

| Domain              | Status   | Notes / action |
|---------------------|----------|----------------|
| reporting           |          |                |
| queue               |          |                |
| build_plan_review   |          |                |
| ai_run_validity     |          |                |
| rollback            |          |                |
| import_export       |          |                |
| support_package     |          |                |

## 3. Recommended investigation items

List any high/medium priority items from the screen and follow-up actions:

- Item 1: _______________________________________________
- Item 2: _______________________________________________
- Item 3: _______________________________________________

## 4. Deep-link verification

Confirm links from the screen open the correct authoritative screens:

- [ ] Queue & Logs (queue tab)
- [ ] Queue & Logs (reporting tab)
- [ ] Build Plan Analytics
- [ ] AI Runs
- [ ] Support Triage
- [ ] Import / Export

## 5. Export and retention

- [ ] Summary exported to JSON via "Export summary (JSON)" and stored for records.
- File name: `post-release-health-summary-YYYY-MM-DD.json`

## 6. Tuning and follow-up planning

- **Actions for next release / backlog:** _______________________________________________
- **Sign-off (if required):** _______________

---

*This template supports internal operational review only. No customer-facing telemetry or external analytics.*
