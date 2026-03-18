# Execution Brief — Post–620/630 Synthesis

**Purpose:** Convert all actionable data from the decision/stabilization/backlog prompt sequence (620–630) into a single “what to do next” execution brief for the project owner. Controlling sources: decision records, ledgers, backlog-close report, stabilization summary, security-privacy remediation, and QA docs in this repo.

**Not re-audit.** This brief synthesizes decisions and outcomes and states exactly what actions to take with that information.

---

## 1. Executive Summary

- **Done:** ZIP upload cap (50 MB pre-move), UPDATE_PAGE_METADATA de-scope, token/SEO/finalization truthful copy, ACF/industry/restore stabilization, security-privacy remediation (SPR-001–SPR-011 fixed or intentionally deferred), industry audit ledger (no defect findings in batch 611).
- **Blocked on you:** (1) **Industry bundle apply** — approved in scope but implementation blocked on defining where to persist applied bundle (or how registries merge). (2) **Build Plan Step 2 Deny / workspace detail–table** — no decision or scope; clarify if in scope and define acceptance if yes.
- **Verification only:** Security/privacy closeout and industry recommendation regression guard can be re-run for confidence; no open code gaps.
- **Deferred (leave parked):** Privacy scope expansion, token application, profile snapshot persistence, cost_placeholder, History/Rollback step execution from workspace — all intentionally out of scope or no spec.
- **Nothing to remove:** De-scoped items (e.g. UPDATE_PAGE_METADATA) are already removed from mapping and retry lists; UI is truthful.

---

## 2. Action Matrix

Each row: **Title | Source (doc/artifact) | Files/classes/subsystems | Why it matters | What to do | Next actor | Urgency**

### DO NOW — Approved and ready for implementation

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| *(None)* | — | — | All implementation-ready work from 620–630 is already implemented (ZIP cap, de-scope, stabilization, security fixes). | — | — | — |

### REVIEW NOW — Requires product/spec decision

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| Industry bundle apply — persistence design | backlog-close-report.md §2.1, §4; industry-bundle-apply-decision.md; industry-bundle-apply-acceptance-criteria.md (all unchecked) | Industry_Packs_Module, registries (built-in only); Industry_Bundle_Import_Preview_Screen; industry-pack-bundle-format-contract, industry-pack-import-conflict-contract | Apply is in scope per decision but cannot be implemented until persistence or registry-merge is defined. | Choose: where to persist applied bundle (e.g. site options, custom table, or runtime merge of applied + built-in) and document in spec or decision. | You / spec owner | High |
| Build Plan Step 2 Deny / workspace improvements | backlog-close-report.md §2.6, §4; approved-backlog-implementation-summary.md §3 | Build_Plan_Workspace_Screen; master spec §33 (Step 2) | No decision record; unclear if “Step 2 Deny” or workspace detail–table is a real product request. | Decide: in scope (write decision + acceptance criteria) or out of scope (close as N/A). | You / spec owner | Medium |

### VERIFY NOW — Validation, QA, or manual confirmation

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| Security/privacy remediation closure | security-privacy-remediation-ledger.md §8; security-privacy-audit-close-report.md §0, §1 | All state-changing admin_post handlers; capability checks; Import_Export_Screen; Industry_Bundle_Upload_Validator; Personal_Data_Exporter/Eraser | Ledger marks SPR-001–SPR-011 fixed or deferred with evidence. | Re-run spot checks if desired: nonce on state-changing actions, plugin capabilities (no manage_options), bundle upload 10 MB/MIME, exporter/eraser registered. | QA / you | Low |
| Industry recommendation regression guard | industry-recommendation-regression-guard.md §3–4 | Industry_Pack_Registry; Industry_Recommendation_Benchmark_Service; section/page resolvers; Industry_Substitute_Suggestion_Engine | Protects launch industries and recommendation invariants. | Run regression tests: pack/ref integrity, scoring expectations, no-industry fallback, substitute suggestion quality. | QA / Cursor | Medium |
| ZIP upload cap (50 MB) | backlog-close-report.md §2.2; Import_Export_Zip_Size_Limit_Test | Import_Export_Screen.php (MAX_ZIP_UPLOAD_BYTES, pre-move check); unit tests | Already implemented. | Optional: manual smoke test (upload >50 MB ZIP → rejected with clear message). | QA | Low |

### DOCUMENT NOW — Docs, UX copy, or decision-log follow-through

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| Master spec: industry bundle apply | industry-bundle-apply-spec-note.md | docs/specs/aio-page-builder-master-spec.md (§4.14 or §0.4 / industry extension) | Decision accepted apply in scope; spec note exists but suggested text is not yet inserted into master spec. | If you proceed with apply: insert the suggested paragraph from industry-bundle-apply-spec-note.md into the master spec (§4.14 or industry pack section). | You / spec owner | High (if implementing apply) |
| Privacy scope boundary | privacy-exporter-eraser-scope-decision.md; privacy-exporter-eraser-scope-boundary.md | — | Boundary already documented; no expansion. | No action unless you later expand scope; then update boundary doc and decision. | — | Low |

### DEFER — Intentionally postponed; leave parked

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| Token application | token-application-scope-decision.md; shell-placeholder-backlog.md §1 | Tokens_Step_UI_Service; execution-action-contract.md | Out of scope; step is recommendation-only; UI and contract truthful. | Nothing. Leave as-is unless product brings token apply in scope. | — | — |
| Privacy scope expansion | privacy-exporter-eraser-scope-decision.md | Exporter/eraser (actor-linked only) | Explicitly no expansion to site-level, onboarding, reporting, industry profile, diagnostics, execution log table. | Nothing. | — | — |
| Profile snapshot persistence | shell-placeholder-backlog.md §3; Profile_Snapshot_Data.php; SPR-010 | Domain/Storage/Profile/Profile_Snapshot_Data.php | Schema only; no persistence contract. | Nothing unless spec defines persistence store and lifecycle. | — | — |
| Cost/usage reporting | shell-placeholder-backlog.md §1, §3; ai-provider-contract.md §9; SPR-010 | cost_placeholder in AI provider drivers | Reserved for future; not implemented. | Nothing unless spec defines schema, storage, UI. | — | — |
| Rollback from Build Plan workspace | shell-placeholder-backlog.md §3; History_Rollback_Step_UI_Service | Step 7 (logs/rollback) | Shell only; rollback not initiated from workspace. | Nothing unless spec defines how to wire “Request rollback” and history rows. | — | — |

### REMOVE — Delete, de-scope, or hide (misleading / out of scope)

| Title | Source | Files/subsystems | Why it matters | What to do | Next actor | Urgency |
|-------|--------|------------------|----------------|------------|------------|---------|
| *(None)* | — | — | UPDATE_PAGE_METADATA already de-scoped (mapping and retry lists updated); no misleading UI or code to remove. | — | — | — |

---

## 3. What I Should Do Next

**Strict recommended next-step sequence:**

1. **You:** Decide whether to implement **industry bundle apply**. If yes → choose persistence approach (new options key, custom table, or registry merge) or assign spec owner to document it.
2. **You or spec owner:** If apply is in scope, **add the industry-bundle-apply-spec-note text** to the master spec (e.g. after §4.14 or in industry pack scope). File: `docs/specs/aio-page-builder-master-spec.md`.
3. **Cursor (or dev):** After persistence/merge is defined, **implement apply** per `docs/operations/industry-bundle-apply-acceptance-criteria.md` via a new implementation prompt. No Cursor work before step 1–2.
4. **You:** Decide whether **Build Plan Step 2 Deny** and/or **workspace detail–table improvements** are in scope. If yes → add a short decision record and acceptance criteria; if no → close as out of scope (e.g. note in approved-backlog-implementation-summary or backlog-close-report).
5. **QA or you:** Run **industry recommendation regression guard** tests (pack integrity, scoring, fallback, substitute) per `docs/qa/industry-recommendation-regression-guard.md`. Optionally re-verify security/privacy assertions from `docs/qa/security-privacy-audit-close-report.md`.

---

## 4. Decision Queue

| # | Question to answer | Options | Impact of each option | Recommended default if you do nothing |
|---|--------------------|---------|------------------------|----------------------------------------|
| 1 | **Where should applied industry bundle content be persisted (or how should it merge with built-in)?** | (A) New site option(s) or options key for “applied bundle” payload. (B) Custom table(s) for applied categories. (C) Runtime merge only: no new store; registries load built-in then merge applied from a single stored blob. | (A) Simple; one place to read/write. (B) More structure; easier per-category audit. (C) No new schema; merge logic in registry layer. | Apply remains unimplemented; preview-only stays; SPR-007 remains “Intentionally deferred.” |
| 2 | **Is “Build Plan Step 2 Deny” or workspace detail–table improvement in scope?** | (A) In scope — define acceptance criteria and add decision record. (B) Out of scope — document and close. | (A) Enables a Cursor implementation prompt later. (B) Clears backlog item; no implementation. | Item remains undefined; no implementation. |

---

## 5. Build Queue

Only one implementation workstream is **approved in principle but blocked on design**:

| Item | Dependency | Owner | Acceptance criteria | New Cursor implementation prompt? |
|------|-------------|-------|---------------------|------------------------------------|
| **Industry bundle apply** | (1) Persistence/store or registry-merge design (your decision). (2) Spec note text added to master spec. | Cursor / dev (after 1–2) | `docs/operations/industry-bundle-apply-acceptance-criteria.md` (all sections 1–7). | Yes — after persistence is defined and spec updated. |

No other build items from 620–630 are pending; ZIP cap, de-scope, stabilization, and security remediations are done.

---

## 6. Deferred / Leave Alone

- **Token application** — Out of scope; UI and contract truthful; leave as-is.
- **Privacy scope expansion** — Out of scope; boundary documented; no expansion.
- **Profile snapshot persistence** — No spec; schema-only; leave until spec defines.
- **Cost/usage reporting** — cost_placeholder reserved; no implementation until spec.
- **Rollback from Build Plan workspace** — Shell only; no wiring until spec.
- **ACF conditional registration** — Tracked in other prompts (e.g. 282–284+); not in 620–630 scope.

---

## 7. Remove / Hide / De-Scope

- **UPDATE_PAGE_METADATA** — Already de-scoped: removed from `Bulk_Executor` mapping, `Queue_Health_Summary_Builder`, `Queue_Recovery_Service`, `Logs_Monitoring_State_Builder::RETRYABLE_JOB_TYPES`; contract and SEO step copy updated. Nothing further to remove.
- **No misleading UI** — Finalization, SEO, Tokens steps and bundle preview already state “not available” or “preview only” where appropriate.

---

## 8. Risks if No Action Is Taken

- **Industry bundle apply:** Never ships; preview-only remains; SPR-007 stays “Intentionally deferred”; acceptance criteria never checked off.
- **Step 2 Deny / workspace:** Stays ambiguous; no one implements it; backlog item remains open without a clear in/out call.
- **Verification skip:** No new risk; security/privacy and industry regression are already closed or guarded; re-run only increases confidence.
- **Deferred items:** No risk; leaving them parked is intended.

---

## 9. Final Recommendation

1. **Resolve the two decision-bound items:** (a) Persistence design for industry bundle apply — or explicitly park apply. (b) Step 2 Deny / workspace — in scope with acceptance criteria, or out of scope and close.
2. **If apply is in scope:** Insert the spec note into the master spec, then issue a single Cursor implementation prompt for industry bundle apply using the acceptance criteria doc.
3. **Run the industry recommendation regression guard** once before or after the next release to confirm invariants.
4. **Leave all deferred and de-scoped items as-is;** no further action required on them unless product/spec changes.

---

## References (controlling sources)

- `docs/operations/backlog-close-report.md`
- `docs/operations/approved-backlog-implementation-summary.md`
- `docs/operations/shell-placeholder-backlog.md`
- `docs/operations/stabilization-pass-summary.md`
- `docs/operations/security-privacy-remediation-ledger.md`
- `docs/operations/industry-audit-remediation-ledger.md`
- `docs/operations/industry-bundle-apply-decision.md`
- `docs/operations/industry-bundle-apply-acceptance-criteria.md`
- `docs/operations/industry-bundle-apply-spec-note.md`
- `docs/operations/privacy-exporter-eraser-scope-decision.md`
- `docs/operations/privacy-exporter-eraser-scope-boundary.md`
- `docs/qa/security-privacy-completeness-audit.md`
- `docs/qa/security-privacy-audit-close-report.md`
- `docs/qa/industry-recommendation-regression-guard.md`
