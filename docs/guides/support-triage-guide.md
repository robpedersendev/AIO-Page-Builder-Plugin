# AIO Page Builder — Support Triage Guide

**Audience:** Support staff and operators performing diagnostics and issue triage.  
**Spec:** §59.15, §60.6, §46 (reporting), security and redaction standards.  
**Companion:** [Master FAQ](../kb/master-faq.md) (short answers and repeated confusion).  
**Knowledge base:** [KB index — Start here (three paths)](../kb/index.md#start-here-three-paths); [FILE_MAP.md](../kb/FILE_MAP.md).

Use this guide **by symptom first**, then use logs and exports. Do not ask for API keys, passwords, or unredacted secrets.

---

## Start here: symptom router

| Symptom | Jump to |
|---------|---------|
| Nothing works / can’t get started | [§ I can’t start](#i-cant-start) |
| AI provider errors or failed connection test | [§ A provider won’t connect](#a-provider-wont-connect) |
| Crawl missing, old, or doesn’t match expectations | [§ Crawl looks stale or missing](#my-crawl-looks-stale-or-missing) |
| Build Plan steps, approvals, or wording unclear | [§ Build Plan is confusing](#my-build-plan-is-confusing) |
| Queue item failed or stuck | [§ An item won’t execute](#an-item-wont-execute) |
| No rollback button or rollback failed | [§ Rollback is unavailable](#rollback-is-unavailable) |
| ZIP import or validation errors | [§ Import/restore is blocked](#importrestore-is-blocked) |
| Tabs full of rows, unclear next step | [§ Logs but no clear action](#i-see-logs-but-dont-know-what-to-do) |
| Reporting, privacy, uninstall concerns | [§ Data, privacy, reporting](#im-worried-about-dataprivacyreporting) |
| Odd combinations (profile vs crawl, permissions, etc.) | [§ Weird edge cases](#weird-edge-cases) |

---

## I can’t start

**Quick checks**

1. **Capabilities** — User may lack menu access. See [concepts-and-glossary.md](../kb/concepts-and-glossary.md) (default caps by role).
2. **Diagnostics** — **AIO Page Builder → Diagnostics** runs environment checks (blocking vs warning rows). See [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md) § Diagnostics.
3. **Plugin version / PHP / WordPress** — **Privacy, Reporting & Settings** shows environment summary; compare to [compatibility-matrix.md](../qa/compatibility-matrix.md).

**Likely causes:** Blocking PHP extension, wrong WP version, capability mismatch, or another plugin breaking admin.

**Self-serve:** Fix blocking Diagnostics rows; grant correct role/cap; clear conflicting admin plugins on a staging copy.

**Escalate:** Reproducible crash with Diagnostics + support bundle; see [known-risk-register.md](../release/known-risk-register.md) for release-scope limits.

**Deeper docs:** [admin-operator-guide.md](admin-operator-guide.md) §1–2; [onboarding-and-profile.md](../kb/operator/onboarding-and-profile.md).

---

## A provider won’t connect

**Quick checks**

1. **AI Providers** screen — credential fields, connection test result, spend cap / month-to-date if shown.
2. **Queue & Logs** — failed jobs tied to AI may surface in Execution or AI Runs tabs.
3. **Hosting** — outbound HTTPS blocked, clock skew, or rate limits from the vendor.

**Likely causes:** Invalid key, wrong model/region, quota exhausted, firewall, or provider outage.

**Self-serve:** Re-enter key, run test again, check provider dashboard; confirm caps not zeroed.

**Escalate:** Persistent failures with redacted log export (`aio_export_data`) and provider correlation id (no secrets in ticket).

**Deeper docs:** [ai-providers-credentials-budget.md](../kb/operator/ai-providers-credentials-budget.md); [ai-runs-and-run-details.md](../kb/operator/ai-runs-and-run-details.md).

---

## My crawl looks stale or missing

**Quick checks**

1. **Crawl Sessions** — session status, last run, retry if offered.
2. **Crawl Comparison** — compare sessions; freshness vs onboarding prefill is a common confusion.
3. **Profile** — onboarding may still use **draft** or older crawl-linked data until submitted.

**Likely causes:** Session failed, not started, or editor is looking at onboarding draft while a newer crawl exists.

**Self-serve:** Start or retry crawl; complete/submit onboarding steps that consume crawl; compare sessions.

**Escalate:** Crawl stuck in error with logs; crawler integration conflicts.

**Deeper docs:** [crawler-sessions-and-comparison.md](../kb/operator/crawler-sessions-and-comparison.md); [onboarding-and-profile.md](../kb/operator/onboarding-and-profile.md).

---

## My Build Plan is confusing

**Quick checks**

1. **Which phase?** Review (steps 1–6) vs execution (queue) vs logs/rollback — [build-plan-overview.md](../kb/operator/build-plan-overview.md).
2. **Item status** — approved vs denied vs pending; bulk bars on review steps.
3. **Industry** — changing industry after approvals can diverge recommendations (warnings, not auto-fix). See [known-risk-register.md](../release/known-risk-register.md) IND-2.

**Likely causes:** Skipping steps, mixed approvals, or expecting execution before confirmation.

**Self-serve:** Walk the stepper in order; read step KBs linked from overview; check plan list filters.

**Escalate:** Data inconsistency (items approved but missing from execution) with plan id and screenshots (no secrets).

**Deeper docs:** [build-plan-review-existing-and-new-pages.md](../kb/operator/build-plan-review-existing-and-new-pages.md); [build-plan-hierarchy-navigation-tokens-seo.md](../kb/operator/build-plan-hierarchy-navigation-tokens-seo.md); [build-plan-finalization-logs-rollback.md](../kb/operator/build-plan-finalization-logs-rollback.md).

---

## An item won’t execute

**Quick checks**

1. **Queue & Logs → Queue** — status, `failure_reason`, related plan link.
2. **Queue health** banner — stale locks, bottleneck, retry-eligible count.
3. **Capability** — execution vs viewing ([concepts-and-glossary.md](../kb/concepts-and-glossary.md)).

**Likely causes:** Prerequisites missing (template, page, lock conflict), host timeouts, or job type not retry-eligible.

**Self-serve:** Retry if button shown and `aio_manage_queue_recovery`; fix environment; resolve lock conflicts per executor behavior.

**Escalate:** Repeat failures across job types with redacted queue export.

**Deeper docs:** [build-plan-execution-actions.md](../kb/operator/build-plan-execution-actions.md); [build-plan-rollback-and-recovery.md](../kb/operator/build-plan-rollback-and-recovery.md).

---

## Rollback is unavailable

**Quick checks**

1. **Eligibility** — Only certain action types keep rollback snapshots; see [build-plan-rollback-and-recovery.md](../kb/operator/build-plan-rollback-and-recovery.md).
2. **UI** — Rollback from plan workspace vs queue retry are different tools.
3. **Already rolled back** — Second rollback may not apply.

**Likely causes:** Action type not eligible, snapshot missing, or user conflating **profile snapshot restore** with **Build Plan rollback**.

**Self-serve:** Confirm action type; use queue retry for transient failures where allowed.

**Escalate:** Eligible action with missing rollback after documented success path.

**Deeper docs:** [build-plan-finalization-logs-rollback.md](../kb/operator/build-plan-finalization-logs-rollback.md); [profile-snapshots-and-history.md](../kb/operator/profile-snapshots-and-history.md) (profile vs execution snapshot).

---

## Import/restore is blocked

**Quick checks**

1. **Error message** on Import / Export — size (50 MB), MIME, schema version, manifest, validation list.
2. **Scope vs conflicts** — full vs settings/profile only; every conflict needs replace/skip when required.
3. **Not ZIP** — Industry JSON bundle uses **Industry Bundle Import Preview**, not ZIP restore ([industry-bundle-import-and-apply.md](../kb/industry/industry-bundle-import-and-apply.md)).

**Likely causes:** Oversized file, wrong package type, newer export schema than plugin, or unresolved conflicts.

**Self-serve:** Re-export from supported version; split template-only export; complete conflict radios; check PHP upload limits.

**Escalate:** Valid package repeatedly fails validation with same schema.

**Deeper docs:** [import-export-and-restore.md](../kb/operator/import-export-and-restore.md).

---

## I see logs but don’t know what to do

**Quick checks**

1. **Which tab matches the symptom?** Queue (jobs), Execution (completed/failed jobs), Reporting (outbound delivery), Critical (failed **developer error report** deliveries only — not all PHP errors). See [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md).
2. **Import/Export Logs tab** — Often **empty** until a dedicated activity store exists; use Reporting Logs + Import / Export screen for operations.
3. **Reporting health degraded** — Outbound email issues; core site behavior may still be fine.

**Likely causes:** Reading the wrong tab, or expecting import/export rows that are not populated.

**Self-serve:** Follow row links to Build Plan / AI Run; export redacted JSON if authorized.

**Escalate:** Critical tab full of failures with mail/server evidence (no secrets).

**Deeper docs:** § Logs and export below; [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md).

---

## I’m worried about data, privacy, reporting

**Quick checks**

1. **Privacy, Reporting & Settings** — disclosure blocks (what is sent, what is excluded), retention note, report destination summary, privacy policy helper text.
2. **Built pages** — Survive deactivation; uninstall removes plugin-owned data per choices — [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).
3. **WP Tools** — Export/Erase personal data registration described in helper text.

**Likely causes:** Misunderstanding mandatory private-distribution reporting vs optional features.

**Self-serve:** Read disclosure sections; use site privacy policy helper as a draft.

**Escalate:** Legal/compliance questions outside product docs — involve site counsel.

**Deeper docs:** [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md); [admin-operator-guide.md](admin-operator-guide.md) §10; [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md) § Privacy.

---

## Weird edge cases

| Scenario | Practical note |
|----------|----------------|
| **Mixed plan states** | Some items approved, some denied, execution partial — normal. Use Queue and plan workspace to see what actually ran; don’t assume “Confirm” re-ran everything. |
| **Partial import/restore** | Restore can skip categories (scope, conflicts, styling skip reasons). Read result banner and [import-export-and-restore.md](../kb/operator/import-export-and-restore.md). |
| **Stale crawl, fresh profile** | Crawl timestamps ≠ profile submit time. Re-run crawl or re-open comparison; onboarding may need re-save to pick up context. |
| **Fresh profile, old AI runs** | AI Runs list is historical; new profile does not erase old runs. Use run detail for context; don’t assume latest run matches current profile. |
| **Helper docs unavailable** | Appendices/previews regenerate from registries; after restore, may need time or navigation. [template-system-overview.md](../kb/templates/template-system-overview.md). |
| **Unknown or zero cost values** | Provider or run metadata may omit estimates; treat as “not available” not bug unless billing integration promised. [ai-runs-and-run-details.md](../kb/operator/ai-runs-and-run-details.md). |
| **Snapshot vs rollback confusion** | **Profile History** = brand/business profile store. **Build Plan rollback** = execution snapshots for specific actions. [profile-snapshots-and-history.md](../kb/operator/profile-snapshots-and-history.md) vs [build-plan-rollback-and-recovery.md](../kb/operator/build-plan-rollback-and-recovery.md). |
| **View vs execute permissions** | Users can review plans or logs but lack queue execution or rollback — check glossary caps. [concepts-and-glossary.md](../kb/concepts-and-glossary.md). |

---

## Logs and where to find them

- **Screen:** **AIO Page Builder → Queue & Logs** (`aio-page-builder-queue-logs`).  
- **Capability:** `aio_view_logs` to view; `aio_export_data` to export logs.

**Tabs:**

| Tab | Content |
|-----|--------|
| Queue | Job ref, type, status, failure reason; plan link when present. |
| Execution Logs | Completed/failed job summaries; plan link. |
| AI Runs | Run id, status, created (no raw prompts). |
| Reporting Logs | Outbound reporting delivery attempts. |
| Import/Export Logs | **Placeholder** in current builds — often empty; use Reporting Logs and the Import / Export screen for package operations. |
| Critical Errors | **Failed developer-error report deliveries** — not a full PHP error log. |

**Reporting health:** Banner on Queue & Logs (failures window, heartbeat month). Reporting failure does **not** mean the editor or queue is broken — see [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md).

**Support Triage screen** (`aio-page-builder-support-triage`): Aggregated operational view when enabled; still correlate with Queue & Logs and KB task docs.

Industry-safe summaries: [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md); export summary contract [industry-documentation-summary-export-contract.md](../contracts/industry-documentation-summary-export-contract.md); training [industry-support-training-packet.md](../operations/industry-support-training-packet.md).

---

## Log export (authorized users)

- **Where:** Queue & Logs → **Export logs** (`aio_export_data`).
- **Output:** Redacted JSON; nonce download. Authorized use only.

---

## Support package (export bundle)

- **Where:** **Import / Export** → Create export → **Support bundle**.
- **Contents:** Redacted settings/profile; registries, plans, tokens; optional logs. No raw AI artifacts. See [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md).

---

## Redaction rules (support guidance)

- No API keys, passwords, tokens, or personal data in tickets or public channels.
- Assume exports may still contain site identifiers; handle per policy.

---

## Diagnostics screen

- **Screen:** **Diagnostics** (`aio-page-builder-diagnostics`) — **`aio_view_sensitive_diagnostics`**.
- **Behavior:** Runs **`Environment_Validator`**; shows blocking vs non-blocking rows. Not a placeholder in current code.
- **Related:** [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md); [diagnostics-screens.md](../kb/operator/diagnostics-screens.md).

---

## Classic triage sequence (checklist)

1. Reproduce; note WP/PHP/plugin versions ([compatibility-matrix.md](../qa/compatibility-matrix.md), [known-risk-register.md](../release/known-risk-register.md)).
2. Use **symptom router** above.
3. Open **Queue & Logs** → correct tab.
4. **Support bundle** (not full backup) for structure/config.
5. **Log export** if deeper timeline needed and user is authorized.

---

## Cross-references

| Topic | Doc |
|-------|-----|
| Master FAQ (short Q&A) | [master-faq.md](../kb/master-faq.md) |
| Monitoring, reporting disclosure | [monitoring-analytics-and-reporting.md](../kb/operator/monitoring-analytics-and-reporting.md) |
| Template support | [template-library-support-guide.md](template-library-support-guide.md) |
| Form provider | [form-provider-operator-guide.md](form-provider-operator-guide.md) |
| Admin screens / caps | [admin-screen-inventory.md](../contracts/admin-screen-inventory.md); [admin-operator-guide.md](admin-operator-guide.md) |
| Security / redaction | [security-redaction-review.md](../qa/security-redaction-review.md) |
| Industry lifecycle | [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) |
