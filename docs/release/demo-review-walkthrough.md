# Demo / Review Walkthrough

**Governs:** Spec §60.7 Demo / Review Requirements.  
**Purpose:** Script or walkthrough outline for the formal review/demo to the Product Owner. Release milestone requires QA review. Use this to run the demo and to verify acceptance paths.  
**Audience:** Product Owner (demo); QA (review evidence). Internal only.

---

## 1. Scope and objectives

- **Demo:** Show the implemented product along the main operator path: install → onboarding → provider → (optional crawl) → AI run → Build Plan review → execution/queue → Queue & Logs; then reporting disclosure, Import/Export, uninstall choices.
- **Review:** Confirm that acceptance tests and evidence (happy-path, failure-path, migration/compat where applicable) are traceable and that the product matches release notes and known limitations.

---

## 2. Pre-demo checklist

- [ ] Environment: WordPress 6.6+; PHP 8.1+; ACF Pro 6.2+; GenerateBlocks 2.0+ (or as per [compatibility-matrix.md](../qa/compatibility-matrix.md)).
- [ ] Plugin build/tag for release candidate installed and activated.
- [ ] No secrets or site-specific data on screen or in shared materials.
- [ ] [release-notes-rc1.md](release-notes-rc1.md) and [release-review-packet.md](release-review-packet.md) available for reference.

---

## 3. Walkthrough outline

### 3.1 Install and activation

- **Action:** Activate the plugin (or install then activate).
- **Show:** Activation succeeds when WP/PHP and required plugins (ACF Pro, GenerateBlocks) are met.
- **Optional:** Show activation **blocked** on a test site missing a requirement (e.g. WP &lt; 6.6 or missing ACF Pro) — evidence for environment enforcement.
- **Screens:** None yet; activation only.

### 3.2 Dashboard and menu

- **Action:** Open **AIO Page Builder** in the admin menu.
- **Show:** Dashboard (readiness cards, quick actions, last activity, queue warnings, critical errors if any). Submenus: Dashboard, Settings, Diagnostics, Onboarding & Profile, Crawl Sessions, Crawl Comparison, AI Runs, AI Providers, Build Plans, Queue & Logs, Privacy, Reporting & Settings, Import / Export.
- **Point:** First-run entry point; no mutation actions on Dashboard.

### 3.3 Onboarding and profile

- **Action:** Open **Onboarding & Profile**. Step through the guided flow (or show a saved draft).
- **Show:** Steps for brand/business profile; **Save draft**; advance/back. Data used later by planner/AI.
- **Point:** Incomplete profile is acceptable for demo; completion improves plan quality.

### 3.4 AI Providers

- **Action:** Open **AI Providers**. Show provider list and credential status (no raw keys).
- **Show:** **Test connection** for a configured provider; success or error notice after redirect.
- **Point:** Connection test confirms transport only; no secrets in UI.

### 3.5 Crawl (optional)

- **Action:** Open **Crawl Sessions** and/or **Crawl Comparison**.
- **Show:** List of sessions (if any); session detail with pages. Crawler scoped to this site; public-only.
- **Point:** Crawl data feeds planning; not required for a minimal demo if no crawl has been run.

### 3.6 AI Runs

- **Action:** Open **AI Runs**. Open a run detail if one exists.
- **Show:** List (Run ID, status, provider, model, prompt pack, created); detail with artifact summaries. Link to Build Plan when present.
- **Point:** Raw prompts/responses restricted; summarized data only unless sensitive diagnostics enabled.

### 3.7 Build Plans

- **Action:** Open **Build Plans**. Open a plan to enter the workspace.
- **Show:** Stepper: Step 1 (existing page updates) — approve/deny, bulk approve/deny; Step 2 (build intent) — approve, Build All/Build selected; Step 3 (navigation) — approve/deny, Apply All/Deny All. Later steps (execution confirmation, Step 7 logs/rollback). Rollback request when eligible (queued).
- **Point:** Planner/executor separation; no execution without review and approval. Rollback is queued, not immediate.

### 3.8 Queue & Logs

- **Action:** Open **Queue & Logs**. Switch tabs: Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors.
- **Show:** Reporting health summary at top. Row links to Build Plan or AI Run where applicable. Log export section (if user has export capability): log types, optional dates, Export logs, download via nonce link.
- **Point:** All data redacted or non-secret; no raw payloads in tables.

### 3.9 Privacy, Reporting & Settings

- **Action:** Open **Privacy, Reporting & Settings**.
- **Show:** Reporting disclosure (mandatory reporting; what is sent/excluded); retention; uninstall/export choices (full backup, settings/profile only, skip export, cancel); built pages remain; environment/version; report destination; privacy-policy helper text.
- **Point:** Operators must be able to see disclosure and uninstall choices; consistent with release notes.

### 3.10 Import / Export

- **Action:** Open **Import / Export**. Show Create export (mode dropdown: full backup, pre-uninstall, support bundle, template only, plan/artifact, uninstall settings/profile only). Show export history and download (nonce). Optionally: validate package upload; restore with conflict resolution (if safe for environment).
- **Point:** Support bundle is redacted; no secrets. Validate-before-restore; no silent overwrite.

### 3.11 Settings and Diagnostics (brief)

- **Action:** Open **Settings** (form template seeding if shown). Open **Diagnostics**.
- **Show:** Settings: form template seed action if applicable. Diagnostics: placeholder (“Not yet implemented”); structured logging noted for internal use.
- **Point:** Diagnostics full UI may come in a later release.

---

## 4. Acceptance test traceability (§60.5)

| Test type | Evidence / how to show |
|-----------|-------------------------|
| Happy path | Demo §3: install → onboarding → provider → (crawl) → AI run → Build Plan → execution → Queue & Logs. Record in [release-candidate-closure.md](../qa/release-candidate-closure.md) §2. |
| Failure path | Optional: activation blocked (missing deps); validation failure on import; rollback ineligible. Record in closure §2 or test run log. |
| Migration/compatibility | [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md), [compatibility-matrix.md](../qa/compatibility-matrix.md); execute scenarios and fill Observed. |
| Role/capability | [security-redaction-review.md](../qa/security-redaction-review.md); optional: show reduced UI when logged in as user without certain caps. |

---

## 5. Post-demo / QA review

- [ ] Record demo date and attendees.
- [ ] Record any discrepancies between demo and release notes or known limitations; update docs or register as needed.
- [ ] Confirm [sign-off-checklist.md](sign-off-checklist.md) is ready for role sign-offs (gates marked Approved/Waived/Blocked; waivers listed if any).
- [ ] QA: Confirm test evidence and acceptance criteria are sufficient and linked from [release-review-packet.md](release-review-packet.md).

---

*This walkthrough supports §60.7 (formal review/demo to Product Owner; QA review for release). Keep internal; no secrets or unsafe diagnostics.*
