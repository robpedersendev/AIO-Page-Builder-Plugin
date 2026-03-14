# Template Library Expansion — Release Candidate Packaging and Approval Addendum

**Governs:** Spec §6.1 Private Distribution Method; §6.2 Installation Package Format; §59.15 Production Readiness Phase; §60.4 Exit Criteria; §60.8 Sign-Off Requirements.  
**Purpose:** Packaging completeness and go/no-go addendum for the expanded template-library initiative so the expansion is cleanly folded into the private-distribution release candidate. Internal only. No secrets or unsafe diagnostics.

**Scope:** This addendum is **in addition to** the main [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md), [final-approval-runbook.md](final-approval-runbook.md), and [release-review-packet.md](release-review-packet.md). The expansion has its own asset footprint, QA evidence, and sign-off; they must be explicitly verified before release.

---

## 1. Packaging completeness — Template library assets

Verify the following are correctly represented in the release candidate build (ZIP or staged directory). The plugin ZIP is built from the plugin source tree; repo-root docs/appendices and docs/release may be separate or bundled per your build procedure.

### 1.1 Runtime and registry

| # | Asset | Expected | Pass/Fail |
|---|--------|----------|-----------|
| 1.1.1 | Section template registry (CPT + definitions) | Section templates (254) and batch definitions present in `src/`; no dev-only fixture code in production path. | ☐ |
| 1.1.2 | Page template registry (CPT + definitions) | Page templates (580) and batch definitions present in `src/`; same. | ☐ |
| 1.1.3 | Composition registry and builder | Composition schema, validator, builder state; Compositions screen and list/builder views. | ☐ |
| 1.1.4 | Template_Library_Upgrade_Helper and lifecycle phase | `Template_Library_Upgrade_Helper.php`; `template_library_upgrade_compatibility` in Lifecycle_Manager. | ☐ |
| 1.1.5 | Preview and compare support | Section/Page detail state builders, preview pipeline, Preview_Cache_Service; Template_Compare_Screen; compare list user meta (no dev-only dump). | ☐ |

### 1.2 Docs and appendices

| # | Asset | Expected | Pass/Fail |
|---|--------|----------|-----------|
| 1.2.1 | Template library operator guide | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) (or equivalent path in bundle). | ☐ |
| 1.2.2 | Template library editor guide | [template-library-editor-guide.md](../guides/template-library-editor-guide.md). | ☐ |
| 1.2.3 | Template library support guide | [template-library-support-guide.md](../guides/template-library-support-guide.md). | ☐ |
| 1.2.4 | Template library release notes addendum | [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md). | ☐ |
| 1.2.5 | Section/Page inventory appendices | If appendices are bundled: [section-template-inventory.md](../appendices/section-template-inventory.md), [page-template-inventory.md](../appendices/page-template-inventory.md) or generated equivalents. If not bundled: release notes state that appendices are generated from registries at export/on-demand. | ☐ |

### 1.3 QA and sign-off references

| # | Asset | Expected | Pass/Fail |
|---|--------|----------|-----------|
| 1.3.1 | Template library expansion review packet | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) present and linked. | ☐ |
| 1.3.2 | Template library expansion sign-off checklist | [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) present and linked. | ☐ |
| 1.3.3 | Compliance and compatibility | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md), [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md) referenced in release packet and addendum. | ☐ |
| 1.3.4 | Migration and performance | [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md) (or docs/qa path), [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) referenced. | ☐ |
| 1.3.5 | Known-risk register (template library) | [known-risk-register.md](known-risk-register.md) TLE-1–TLE-4 and template-library release addendum cross-ref. | ☐ |

**Rule:** All rows in §1.1 must **Pass** for the expansion to be packaged correctly. §1.2 and §1.3 may reference artifacts outside the production ZIP (e.g. in repo or support bundle); confirm they exist and are linked from release notes and this addendum.

---

## 2. Artifact completeness pass

Before marking the release candidate ready, confirm:

| # | Check | Pass/Fail |
|---|--------|-----------|
| 2.1 | Release notes and changelog reference the template library (counts, screens, limitations). | ☐ |
| 2.2 | [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) is complete and cross-links release notes, known risks, and sign-off artifacts. | ☐ |
| 2.3 | [known-risk-register.md](known-risk-register.md) includes template-library risks (TLE-1–TLE-4) and link to addendum. | ☐ |
| 2.4 | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) and [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) exist; expansion criteria and evidence links resolve. | ☐ |
| 2.5 | No appendix drift: if appendices are shipped, they match the registry version; if not shipped, docs state appendices are generated from live registry. | ☐ |
| 2.6 | No development-only template fixtures or test registries in the production ZIP (exclude per main packaging checklist §3). | ☐ |

---

## 3. Template library go/no-go (expansion-specific)

Execute **after** the main [final-approval-runbook.md](final-approval-runbook.md) prerequisites and **before** or **as part of** the main go/no-go. The expansion must not block the release unless its criteria are explicitly failed or waived.

| # | Gate | Criterion | Go / No-go |
|---|------|-----------|------------|
| 3.1 | Packaging (expansion) | §1 (Packaging completeness) and §2 (Artifact completeness) passed or explicitly scoped (e.g. docs outside ZIP). | ☐ Go ☐ No-go |
| 3.2 | Sign-off (expansion) | [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) all criteria **Met** or **Waived**; no **Not met** without waiver. | ☐ Go ☐ No-go |
| 3.3 | Evidence linkage | [release-review-packet.md](release-review-packet.md) §2.7 and this addendum link to review packet, sign-off checklist, compliance/compatibility/migration/performance reports. | ☐ Go ☐ No-go |
| 3.4 | Release notes and risks | [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) and [known-risk-register.md](known-risk-register.md) template-library entries are current; no internal-only risk detail in operator-facing notes. | ☐ Go ☐ No-go |

**Decision:** If any row is **No-go**, resolve (fix, waiver where allowed, or defer expansion scope) before proceeding. Record result in §4.

---

## 4. Record (template library addendum)

| Field | Value |
|-------|--------|
| Date of pass | _______________________ |
| §1 Packaging completeness | ☐ Pass  ☐ Fail (list item: _______________) |
| §2 Artifact completeness | ☐ Pass  ☐ Fail (list item: _______________) |
| §3 Go/no-go (expansion) | ☐ Go  ☐ No-go (first failing: _______________) |
| Approver / reviewer | _______________________ |

---

## 5. Cross-references

| Artifact | Role |
|----------|------|
| [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) | Main packaging; §7 references this addendum for template-library expansion. |
| [final-approval-runbook.md](final-approval-runbook.md) | Main go/no-go; template-library addendum run as part of or immediately after main checklist. |
| [release-review-packet.md](release-review-packet.md) | Evidence packet; §2.7 template library expansion; link to this addendum. |
| [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) | Release content for expansion; counts, screens, compatibility, limitations. |
| [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) | Expansion evidence (counts, category, CTA, preview, appendix, a11y, performance, versioning, planner). |
| [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) | Expansion-specific criteria and role approval. |
| [known-risk-register.md](known-risk-register.md) | TLE-1–TLE-4 and addendum reference. |

---

*This addendum ensures the expanded template ecosystem is cleanly represented in the final release candidate workflow. Complete §1–§4 before marking the release candidate approved for handoff. Internal only.*
