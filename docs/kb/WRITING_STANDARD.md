# AIO Page Builder — Knowledge Base Writing Standard

**Applies to:** All articles under `docs/kb/` and substantive additions to `docs/guides/*.md` that are indexed from the KB.  
**Authority:** Product behavior is defined in [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md); KB articles describe the implemented admin and editor experience, not future intent.  
**Vocabulary:** Use the same terms as the UI; see [concepts-and-glossary.md](concepts-and-glossary.md) for canonical definitions and capability names.

---

## 1. Tone

- Write for busy practitioners: direct, precise, and calm. Prefer active voice and imperative steps where the user acts.
- Match WordPress admin language: use the same menu labels, button text, and tab names as the UI (sentence case as shown).
- Do not market or oversell. State what the feature does, what it does not do, and when to escalate.
- Private distribution: when documenting reporting, heartbeats, or outbound diagnostics, stay aligned with on-screen disclosure and [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md). Never instruct anyone to disable mandatory reporting.

---

## 2. Default section order (long articles)

Use this order unless a shorter article only needs a subset:

1. **Audience and intent** — Who should read this and what they will accomplish.
2. **Where in the product** — Menu path and screen slug in backticks (e.g. `aio-page-builder-build-plans`).
3. **Prerequisites** — Roles, capabilities, and required prior steps (onboarding, providers, crawl, industry profile, etc.).
4. **Workflow** — Numbered steps in UI order.
5. **Outcomes and limits** — What “done” looks like; queue/async behavior; data retention caveats.
6. **Edge cases and failure modes** — See §4 below.
7. **Related articles** — KB + guides + contracts (contracts for implementers, not for end users).

---

## 3. Permissions and prerequisites

- **Capabilities:** Name the WordPress capability (e.g. `aio_view_build_plans`) when the workflow is gated. If typical sites map roles to capabilities differently, say “users with permission to …” and point to [admin-screen-inventory.md](../contracts/admin-screen-inventory.md) or [FILE_MAP.md](FILE_MAP.md).
- **Order of operations:** Call out dependencies (e.g. AI providers before expecting runs; onboarding before relying on plan quality).
- **Non-goals:** If a screen is read-only or diagnostic-only, state that no in-place fix exists and name the follow-up screen or owner.

---

## 4. Edge cases

- Document edge cases **after** the happy path. Use a short subsection or bullet list: trigger → visible symptom → expected behavior → what the user should do.
- Distinguish **product limits** (by design) from **defects** (unexpected). Uncertain cases should direct the reader to logs/support paths without guessing.
- For async work (queue, rollback, import), always mention that completion is not immediate and where to monitor status.

---

## 5. Troubleshooting and FAQ

- **Troubleshooting:** Symptom-first. Link to **Queue & Logs**, **Support Triage**, or **Import / Export** as appropriate; keep redaction and export rules consistent with [support-triage-guide.md](../guides/support-triage-guide.md).
- **FAQ:** Use only for genuinely frequent questions; avoid duplicating the main workflow. Each FAQ entry: one question, a short answer, optional “see also” link.
- Do not use FAQ to smuggle implementation detail that belongs in contracts or QA checklists.

---

## 6. Cross-linking rules

- One **canonical** KB or guide article per workflow cluster (see [FILE_MAP.md](FILE_MAP.md)). Other pages link in; avoid parallel full explanations.
- Prefer relative links from `docs/kb/` to `../guides/…`, `../contracts/…`, `../standards/…`, `../release/…`.
- When referencing class or builder names for implementers, add a plain-language clause for operators on the same line or in a footnote.
