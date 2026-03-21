# AIO Page Builder — Master FAQ

**Audience:** Support, operators, and power users.  
**Primary runbook (symptoms, logs, escalation):** [support-triage-guide.md](../guides/support-triage-guide.md).  
**Vocabulary and caps:** [concepts-and-glossary.md](concepts-and-glossary.md).  
**Workflow index:** [index.md](index.md).

Answers here are **short**; follow links for procedures and edge cases.

---

## Getting started and access

**Why don’t I see a menu item?**  
Your role may lack the capability for that screen. See [concepts-and-glossary.md](concepts-and-glossary.md).

**What’s the difference between Dashboard and Settings?**  
High-level readiness vs configuration seeds and links — [admin-operator-guide.md](../guides/admin-operator-guide.md) §1.

---

## AI, providers, and costs

**Connection test fails — is the plugin broken?**  
Often key, quota, or network. [ai-providers-credentials-budget.md](operator/ai-providers-credentials-budget.md); [support-triage-guide.md](../guides/support-triage-guide.md) § Provider.

**Why is token/cost “—” or zero?**  
Estimates may be missing depending on provider/run metadata — [ai-runs-and-run-details.md](operator/ai-runs-and-run-details.md). Not always a defect.

---

## Crawl and onboarding

**Crawl looks old but I just updated the profile**  
Crawl sessions are separate from profile save time. [crawler-sessions-and-comparison.md](operator/crawler-sessions-and-comparison.md); [support-triage-guide.md](../guides/support-triage-guide.md) § Crawl.

**Onboarding draft vs submitted profile**  
Drafts don’t replace stored profile until submit flows complete — [onboarding-and-profile.md](operator/onboarding-and-profile.md).

---

## Build Plans and execution

**I approved items — why didn’t pages change?**  
Execution is queued and may fail per item; check **Queue & Logs** and [build-plan-execution-actions.md](operator/build-plan-execution-actions.md).

**What’s the difference between deny and skip?**  
Use review-step docs: [build-plan-review-existing-and-new-pages.md](operator/build-plan-review-existing-and-new-pages.md).

**Rollback button missing**  
Only some actions are rollback-eligible — [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md).

---

## Profile History vs rollback

**I restored Profile History — why didn’t my Build Plan undo?**  
Profile snapshots only change **brand/business profile** in `Profile_Store`. Build Plan rollback is separate — [profile-snapshots-and-history.md](operator/profile-snapshots-and-history.md) vs [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md).

---

## Import, export, restore, industry bundle

**ZIP restore vs industry JSON bundle**  
ZIP = full plugin backup pipeline. JSON = industry pack bundle preview/apply — [import-export-and-restore.md](operator/import-export-and-restore.md) vs [industry-bundle-import-and-apply.md](industry/industry-bundle-import-and-apply.md).

**Why must I pick replace/skip for conflicts I don’t care about (settings-only import)?**  
The scanner runs on the whole package; scope only limits what is stored — [import-export-and-restore.md](operator/import-export-and-restore.md).

---

## Logs and monitoring

**Import/Export Logs tab is empty**  
Expected until a dedicated activity store exists — [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md).

**Reporting “degraded” but site works**  
Outbound reporting can fail without breaking core behavior — [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md).

**Critical Errors tab empty — no bugs?**  
That tab lists failed **developer error report** deliveries, not all PHP errors — same guide.

---

## Templates and helper docs

**One-pager or appendix missing after upgrade**  
Regenerate from template directories; appendices are derived — [template-system-overview.md](templates/template-system-overview.md); [template-library-support-guide.md](../guides/template-library-support-guide.md).

---

## Privacy and reporting

**Can we turn off operational reporting?**  
Disclosure on **Privacy, Reporting & Settings** states private-distribution reporting is mandatory in-product — [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md) § Privacy.

**Will uninstall delete my pages?**  
Built pages are intended to remain; plugin-owned data follows uninstall choices — [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md); [import-export-and-restore.md](operator/import-export-and-restore.md).

---

## Where to go next

| Need | Open |
|------|------|
| Symptom-first triage | [support-triage-guide.md](../guides/support-triage-guide.md) |
| Deep task doc | [FILE_MAP.md](FILE_MAP.md) |
| Product risks | [known-risk-register.md](../release/known-risk-register.md) |
