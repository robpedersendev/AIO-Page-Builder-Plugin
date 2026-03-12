# Hardening and Release Gate Matrix

**Document type:** Authoritative contract for hardening acceptance, release gates, and milestone sign-off (spec §59.14, §59.15, §60.2–60.8, §57.9, §58.6).  
**Governs:** Issue categories, severity classification, closure vs waiver rules, evidence requirements, release-gate checklist, sign-off matrix, and blocker vs non-blocker classification.  
**Out of scope:** Actual security patching, accessibility remediation, performance tuning, migration test implementation, or release package creation.  
**Audience:** Admin/internal only. Operational readiness criteria; may reference sensitive issue classes.

---

## 1. Purpose

This contract defines the **objective closure standard** for hardening and release readiness. No high-severity issue may be silently ignored. Hardening is tied to documented product promises: portability, planner/executor separation, reporting transparency, rollback safety, redaction, and compatibility. Formal waivers must be explicit and attributable. The gate is strict enough to prevent premature "done" claims.

---

## 2. Hardening Issue Categories

Issues are classified by **category** and **severity**. Categories align with §59.14 deliverables and product constraints.

### 2.1 Category codes (stable, machine-readable)

| Code | Category | Description | Evidence type |
|------|----------|-------------|---------------|
| `security` | Security | Capability, nonce, validation, sanitization, escape, secrets, REST/AJAX permission, personal data. | Security review checklist; no critical/high open. |
| `accessibility` | Accessibility | WCAG-relevant admin UI; keyboard, focus, labels, contrast, screen-reader. | A11y audit or checklist pass. |
| `performance` | Performance | Query load, long-running work (queue/chunk/schedule), asset size, blocking UI. | Performance checklist; no blocking regressions. |
| `migration` | Migration | Schema/table/option/registry migrations; upgrade paths; version consistency. | Migration test pass or documented N/A. |
| `compatibility` | Compatibility | WordPress version, PHP version, theme/block/ACF compatibility; Plugin Check. | Compatibility matrix + Plugin Check. |
| `redaction` | Redaction | No secrets in logs, exports, reports, UI, or diagnostics. Redaction rules applied. | Redaction checklist; sample audit. |
| `documentation` | Documentation | Spec impacts, changelog, implementation notes, QA notes, user/admin guidance. | §60.6 artifacts present. |
| `rollback_safety` | Rollback safety | Rollback/diff behavior; no data loss on revert; clear user feedback. | Rollback test or N/A. |
| `reporting_transparency` | Reporting transparency | Outbound reporting disclosed; payloads documented; failure does not break core. | Disclosure + payload doc. |
| `portability` | Portability | Export/import/uninstall behavior; built-page survival; no hidden dependencies. | Export/restore/uninstall checklist. |

### 2.2 Severity levels

| Severity | Code | Definition | Blocker for release? |
|----------|------|------------|----------------------|
| Critical | `critical` | Data loss, security breach, unrecoverable failure, or violation of a hard product promise (e.g. built pages deleted). | **Yes.** Must be closed; no waiver for release. |
| High | `high` | Major functional failure, significant security or privacy risk, or breach of documented standard (e.g. missing capability check). | **Yes** unless formally waived. |
| Medium | `medium` | Notable defect or gap that affects correctness, UX, or compliance but has workaround or limited scope. | No. May be waived or deferred with rationale. |
| Low | `low` | Minor defect, cosmetic, or improvement that does not affect correctness or safety. | No. |

**Rule:** No critical or high-severity unresolved defect may remain in milestone scope for exit (§60.4). High-severity issues may be waived only via the formal waiver process below.

---

## 3. Closure vs Waiver Criteria

### 3.1 Closure (issue resolved)

An issue is **closed** only when:

- The root cause is addressed (fix, configuration, or documented acceptable behavior).
- Acceptance criteria for that issue type are met (see evidence requirements below).
- Relevant tests added or updated and passing.
- Changelog or internal notes updated if user-facing or significant.

### 3.2 Waiver (issue explicitly accepted unresolved)

A **waiver** is permitted only for **high** severity (never for critical). Medium/low may be deferred without a formal waiver.

Waiver conditions (all required):

1. **Documented:** Waiver record exists with required fields (§5.2).
2. **Attributable:** Named approver(s) and date.
3. **Rationale:** Clear reason (e.g. acceptable risk, fixed in next milestone, external constraint).
4. **Scope:** Explicit scope and time bound (e.g. "Waived for 1.0.0 only").
5. **Sign-off:** At least one required signatory for the milestone (§6) approves the waiver.

**Rule:** No high-severity issue may be silently ignored. Every unresolved high must have a waiver record.

---

## 4. Evidence Requirements

### 4.1 Test evidence

| Gate | Requirement | Artifact |
|------|-------------|----------|
| Acceptance tests | Minimum happy-path and failure-path tests per milestone scope. | Test run log or CI result. |
| Migration/compatibility | Where applicable, migration or compatibility tests pass. | Test run or N/A rationale. |
| Role/capability | Where applicable, capability checks covered by tests. | Test run or N/A. |

### 4.2 Documentation evidence (§60.6, §57.9)

| Artifact | Required for milestone exit |
|----------|-----------------------------|
| Spec impacts | Recorded for changed behavior or contracts. |
| Changelog draft | What changed, added, fixed; migrations; deprecations; limitations (§58.6). |
| Internal implementation notes | Class/service purpose, data contracts, migration notes, extension points where relevant. |
| QA notes | Known limitations, test coverage summary, manual check results. |
| User/admin guidance | For new or changed workflows exposed to users. |

### 4.3 Release-gate checklist (hardening phase §59.14)

Each of the following must be satisfied or explicitly N/A with rationale:

| # | Gate | Criterion | Owner |
|---|------|-----------|--------|
| 1 | Security | All new/modified REST/AJAX have nonce + capability; no secrets in logs/exports; permission callbacks explicit. | Technical lead |
| 2 | Accessibility | Admin UI passes agreed a11y checklist or audit; no critical a11y defects open. | QA / a11y owner |
| 3 | Performance | No blocking regressions; long-running work queued/chunked/scheduled; Plugin Check run. | Technical lead |
| 4 | Migration | Migrations updated if needed; version logic consistent; upgrade path tested or N/A. | Technical lead |
| 5 | Compatibility | WordPress/PHP compatibility matrix current; Plugin Check critical/warning addressed. | Technical lead |
| 6 | Redaction | Logs, exports, reports, diagnostics free of secrets; redaction rules documented and applied. | Technical lead |
| 7 | Documentation | §60.6 artifacts present; release notes cover §58.6. | Product Owner / Tech lead |
| 8 | Rollback / reporting / portability | Rollback safety, reporting transparency, portability (export/restore/uninstall) per product promises. | Technical lead |

---

## 5. Issue Register and Waiver Record

### 5.1 Hardening issue register (minimum fields)

Each tracked hardening issue must have:

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier (e.g. HARD-001). |
| `category` | code | One of §2.1 category codes. |
| `severity` | code | One of §2.2 severity codes. |
| `title` | string | Short description. |
| `description` | string | Sufficient to reproduce or assess. |
| `status` | enum | `open` \| `closed` \| `waived` \| `deferred`. |
| `closure_evidence` | string | Reference to test run, PR, or doc when status = closed. |
| `waiver_id` | string | Reference to waiver record when status = waived. |
| `milestone` | string | Milestone in scope (e.g. M12). |

### 5.2 Waiver record (required fields)

Each waiver must include:

| Field | Type | Description |
|-------|------|-------------|
| `waiver_id` | string | Unique id (e.g. WVR-001). |
| `issue_id` | string | Reference to hardening issue. |
| `severity` | code | `high` (only allowed severity for waiver). |
| `rationale` | string | Reason waiver is acceptable. |
| `scope` | string | e.g. "Release 1.0.0 only", "M12 hardening". |
| `approver` | string | Name or role of approver. |
| `date` | date | Date of approval (ISO 8601). |
| `signatory` | string | Required signatory per §6 for this milestone. |

---

## 6. Sign-Off Requirements (§60.8)

Release gate (M12) and production readiness (§59.15) require:

| Role | Responsibility | Sign-off meaning |
|------|----------------|------------------|
| Product Owner | Scope, user impact, release notes, known limitations. | Approves release content and messaging. |
| Technical Lead | Code quality, security/compat/migration/redaction gates, waiver approval. | Approves technical release readiness. |
| QA | Test evidence, acceptance criteria, a11y, regression. | Approves quality evidence. |
| Security (where applicable) | Security review for sensitive milestones/releases. | Approves security posture. |

**Rule:** Product Owner, QA, and Technical Lead must approve release. Security sign-off required where the release touches security-sensitive features.

---

## 7. Blocker vs Non-Blocker Summary

| Severity | Blocker for milestone exit? | Blocker for release? | Waiver allowed? |
|----------|----------------------------|----------------------|-----------------|
| Critical | Yes | Yes | No |
| High | Yes | Yes unless waived | Yes (formal waiver only) |
| Medium | No | No | Defer with rationale (no formal waiver required) |
| Low | No | No | Defer ok |

---

## 8. Sample Artifacts

### 8.1 Sample hardening issue register (one row)

| Field | Value |
|-------|--------|
| id | HARD-SAMPLE-001 |
| category | security |
| severity | high |
| title | REST route X missing permission callback |
| description | Route `GET /wp-json/aio/v1/foo` does not register permission_callback. |
| status | open |
| closure_evidence | (empty until closed) |
| waiver_id | (empty) |
| milestone | M12 |

### 8.2 Sample waiver record

| Field | Value |
|-------|--------|
| waiver_id | WVR-SAMPLE-001 |
| issue_id | HARD-SAMPLE-002 |
| severity | high |
| rationale | Known limitation: export of very large sites may timeout; documented in admin help. Mitigation: chunk export in next minor. Risk accepted for 1.0.0. |
| scope | Release 1.0.0 only |
| approver | Jane Doe |
| date | 2025-07-20 |
| signatory | Technical Lead |

---

## 9. Cross-References

- **Spec:** §59.14 Hardening and QA Phase; §59.15 Production Readiness Phase; §60.2–60.8 Milestones and sign-off.
- **Docs:** §57.9 Documentation standards; §58.6 Release notes standards.
- **Project:** [RELEASE_CHECKLIST.md](../qa/RELEASE_CHECKLIST.md), [DEFINITION_OF_DONE.md](../qa/DEFINITION_OF_DONE.md), [SECURITY_STANDARD.md](../standards/SECURITY_STANDARD.md).

---

*This matrix is the single source of truth for hardening closure and release gates. Hardening prompts and release readiness work must satisfy these criteria.*
