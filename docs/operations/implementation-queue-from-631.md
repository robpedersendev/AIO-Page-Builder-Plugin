# Prioritized Implementation Queue — From Prompt 631

**Controlling source:** [execution-brief-620-630.md](execution-brief-620-630.md) (Prompt 631).  
**Purpose:** Single prioritized implementation queue for Cursor; only implementation-capable or operationally actionable work. Blocked, deferred, and remove items are parked or listed separately.

---

## 1. Executive Summary

- **Prompt 631** states that all feature implementation from 620–630 is **already done** (ZIP cap, de-scope, stabilization, security fixes). The only new **feature** work — industry bundle apply — is **blocked** on your persistence design and spec-note insertion.
- **Actionable now:** Validation and QA only: (1) Run industry recommendation regression guard tests. (2) Re-verify security/privacy closure (optional spot checks). (3) Optional ZIP upload cap smoke test.
- **Actionable after you unblock:** Industry bundle apply (one Cursor implementation prompt, after persistence design and master spec update).
- **Blocked (not in active queue):** Industry bundle apply (until persistence + spec); Build Plan Step 2 Deny / workspace (no decision). Deferred and remove items from 631 are excluded and remain parked.

---

## 2. Prioritized Implementation Queue

Only items that are implementation-capable or operationally actionable and not blocked/deferred/remove.

| # | Title | Source (631) | Related 620–630 | Files / classes / subsystems | Why in queue | Dependencies | Owner | Risk | Effort | Acceptance criteria | Standalone / grouped / manual |
|---|-------|--------------|-----------------|------------------------------|--------------|--------------|-------|------|--------|---------------------|-------------------------------|
| 1 | Industry recommendation regression guard — run and extend tests | §2 Action Matrix VERIFY NOW; §5 Build Queue context; §9 Final Recommendation | industry-recommendation-regression-guard.md; security-privacy-audit-close-report | Industry_Pack_Registry; Industry_Recommendation_Benchmark_Service; Industry_Recommendation_Benchmark_Service_Test; section/page resolvers; Industry_Substitute_Suggestion_Engine; industry-recommendation-regression-guard.md §3–4 | Confirms launch-industry and recommendation invariants; 631 says “run once before or after next release.” | None. | Cursor (run/add tests) or QA | Medium | Medium | Pack/ref integrity; scoring expectations; no-industry fallback; substitute suggestion quality per guard doc §3. | Standalone Cursor: QA/regression prompt |
| 2 | Security/privacy remediation — spot-check re-verification | §2 VERIFY NOW; §3 step 5 | security-privacy-remediation-ledger.md §8; security-privacy-audit-close-report.md §0, §1 | State-changing admin_post handlers; capability checks; Import_Export_Screen; Industry_Bundle_Upload_Validator; Personal_Data_Exporter/Eraser | 631: re-run spot checks for confidence; no open code gaps. | None. | QA / you | Low | Small | Nonce on state-changing actions; plugin caps (no manage_options); bundle upload 10 MB/MIME; exporter/eraser registered. | Manual / QA outside Cursor |
| 3 | ZIP upload cap — optional smoke test | §2 VERIFY NOW | backlog-close-report §2.2; Import_Export_Zip_Size_Limit_Test | Import_Export_Screen.php; unit tests already exist | Already implemented; optional manual confirmation. | None. | QA | Low | Small | Upload >50 MB ZIP → rejected with clear message. | Manual outside Cursor |

**Blocked (see §6):** Industry bundle apply (blocked on persistence design + spec note). Build Plan Step 2 Deny (blocked on scope decision).

---

## 3. Phase 1 — Immediate next implementation work

Items that can start **immediately** with no product/spec dependency.

| Priority | Title | Owner | Next prompt needed |
|----------|-------|-------|--------------------|
| 1 | Industry recommendation regression guard — run and extend tests | Cursor | Needs a QA/regression prompt |

**Note:** No feature implementation is unblocked in 631. Phase 1 contains only this validation work so Cursor can begin at once.

---

## 4. Phase 2 — Follow-on implementation work

Items that start **after** a decision or prerequisite.

| Priority | Title | Prerequisite | Owner | Next prompt needed |
|----------|-------|--------------|-------|--------------------|
| 1 | Industry bundle apply (apply handler, persistence, conflict resolution, UX) | (1) You define persistence store or registry-merge design. (2) You or spec owner insert industry-bundle-apply-spec-note text into master spec. | Cursor | Needs a new focused implementation prompt (after unblock) |

**Note:** Step 2 Deny / workspace is not in Phase 2 because it has no decision; it remains in Blocked until you define scope and acceptance.

---

## 5. Phase 3 — Validation / QA / closeout work

| Priority | Title | Owner | Next prompt needed |
|----------|-------|-------|--------------------|
| 1 | Industry recommendation regression guard (if not done in Phase 1) | QA / Cursor | QA/regression prompt or manual run |
| 2 | Security/privacy remediation spot-check re-verification | QA / you | No new prompt; complete manually from close report |
| 3 | ZIP upload cap optional smoke test | QA | No new prompt; manual |

---

## 6. Blocked / Not Ready for Implementation

| Item | Source (631) | Why blocked | What unblocks it |
|------|--------------|-------------|-------------------|
| **Industry bundle apply** | §1 Executive Summary; §2 REVIEW NOW; §4 Decision Queue; §5 Build Queue | Persistence/store or registry-merge design not defined; industry-bundle-apply-spec-note text not yet in master spec. | You choose persistence approach (or assign spec) and add spec note to `docs/specs/aio-page-builder-master-spec.md`. Then it moves to Phase 2, one Cursor implementation prompt. |
| **Build Plan Step 2 Deny / workspace detail–table improvements** | §2 REVIEW NOW; §4 Decision Queue | No decision record; unclear if in scope. | You decide: in scope (decision + acceptance criteria) or out of scope (close). If in scope, then add to Phase 2 with a new implementation prompt. |

**Excluded (deferred / remove per 631):** Token application, privacy scope expansion, profile snapshot persistence, cost/usage reporting, rollback from Build Plan workspace — not in queue. UPDATE_PAGE_METADATA and misleading UI already de-scoped; nothing to remove.

---

## 7. Prompts Still Needed

| Item | Prompt type | When |
|------|-------------|------|
| Industry recommendation regression guard | QA/regression prompt | Now — Cursor can run/extend tests per industry-recommendation-regression-guard.md. |
| Industry bundle apply | New focused implementation prompt | After you define persistence and add spec note; then one prompt using industry-bundle-apply-acceptance-criteria.md. |
| Build Plan Step 2 Deny / workspace | New implementation prompt (if in scope) | Only if you add a decision and acceptance criteria. |
| Security/privacy spot-check | No new prompt | Implement directly from existing approved context (close report + ledger); manual/QA. |
| ZIP cap smoke test | No new prompt | Manual; optional. |

---

## 8. Top 3 Things Cursor Should Do First

1. **Run and extend the industry recommendation regression guard** — Execute or add tests per `docs/qa/industry-recommendation-regression-guard.md` (§3–4): pack/ref integrity, scoring expectations, no-industry fallback, substitute suggestion quality. This is the only unblocked implementation-capable work in 631.
2. **No other Cursor work is unblocked** — Industry bundle apply and Step 2 Deny both depend on your decisions. After you unblock bundle apply (persistence + spec note), Cursor’s next step is a single implementation prompt against the acceptance criteria.
3. **Avoid starting bundle apply or Step 2 Deny before decisions** — 631 is explicit: no Cursor work on apply before persistence design and spec update; no work on Step 2 Deny without a decision record.

---

## 9. Top 3 Things I Need To Decide First

1. **Persistence for industry bundle apply** — Choose where to persist applied bundle (site option(s), custom table(s), or runtime merge from a stored blob). Document in a short decision or spec so Cursor can implement. Until then, apply stays blocked.
2. **Whether to add industry bundle apply to the master spec** — If you want apply implemented, insert the paragraph from `docs/operations/industry-bundle-apply-spec-note.md` into `docs/specs/aio-page-builder-master-spec.md` (§4.14 or industry pack section). This unblocks the Phase 2 implementation prompt.
3. **Build Plan Step 2 Deny / workspace** — Decide: in scope (write decision + acceptance criteria so it can enter the queue) or out of scope (close in backlog-close-report or approved-backlog-implementation-summary).

---

## 10. Top 3 Risks If Nothing Is Done

1. **Industry bundle apply never ships** — Preview-only remains; SPR-007 stays “Intentionally deferred”; acceptance criteria never met. Risk: **high** if apply is desired.
2. **Recommendation quality drift** — Without running the industry recommendation regression guard, launch-industry or recommendation invariants could regress undetected. Risk: **medium**.
3. **Step 2 Deny / workspace stays ambiguous** — Backlog item remains open; no one implements it; possible duplicate or conflicting work later. Risk: **medium** (ambiguity).

---

## 11. Final Recommended Order

**For Cursor (execution order):**

1. **Now:** Run or add tests for the industry recommendation regression guard (Phase 1). Use a QA/regression prompt referencing `docs/qa/industry-recommendation-regression-guard.md`.
2. **After you unblock:** Single implementation prompt for industry bundle apply (Phase 2), using `docs/operations/industry-bundle-apply-acceptance-criteria.md` and the persistence design you document.
3. **Only if you add decision + acceptance for Step 2 Deny:** A separate implementation prompt for that work.

**For you:**

1. Decide persistence for industry bundle apply (or explicitly park it).
2. If implementing apply: add the spec note to the master spec.
3. Decide Step 2 Deny / workspace in scope or out, and document.

**For QA:**

1. Optionally re-run security/privacy spot checks from the close report.
2. Optionally run ZIP upload cap smoke test.

---

## References

- [execution-brief-620-630.md](execution-brief-620-630.md) — Prompt 631 (controlling source)
- [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md)
- [industry-bundle-apply-acceptance-criteria.md](industry-bundle-apply-acceptance-criteria.md)
- [industry-bundle-apply-spec-note.md](industry-bundle-apply-spec-note.md)
- [security-privacy-audit-close-report.md](../qa/security-privacy-audit-close-report.md)
