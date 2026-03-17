# Industry Release Candidate Manifest and Evidence Bundle Contract (Prompt 455)

**Spec:** release gate docs; pre-release validation pipeline; pack diff and signoff; sandbox promotion workflow.  
**Purpose:** Define the release-candidate manifest and evidence bundle for industry subsystem changes so each pack or subtype release is tied to a clear set of validated artifacts, reports, diffs, and known risks. Internal-only; human review required.

---

## 1. Scope

- **Release candidate:** A set of industry pack, subtype, or bundle changes proposed for release, with supporting evidence. Remains internal and evidence-based; no public release distribution format.
- **Manifest:** Structured description of what is in the release candidate (identifiers, version markers, scope).
- **Evidence bundle:** Required and optional artifacts that support release candidacy (lint, health, diff, coverage, benchmarks, risk notes). Incomplete evidence blocks release candidacy unless explicitly waived and recorded.

---

## 2. Release candidate manifest structure

The manifest SHALL include:

| Field | Description | Required |
|-------|-------------|----------|
| scope | One of: pack_set, subtype_layer, bundle_set, mixed | Yes |
| pack_identifiers | List of industry_key (and optional version_marker) for packs in scope | When scope includes packs |
| subtype_identifiers | List of subtype_key (and optional parent_industry_key) for subtypes in scope | When scope includes subtypes |
| bundle_identifiers | List of bundle_key for starter bundles in scope | When scope includes bundles |
| release_candidate_label | Short human-readable label (e.g. "realtor_subtypes_2025_03") | Yes |
| validation_run_at | ISO 8601 timestamp of last validation pipeline run | Recommended |

Manifest is a document or structured object (e.g. JSON) for review. It does not replace the overall plugin release process; it is additive for industry changes.

---

## 3. Required evidence bundle components

For a release candidate to be considered complete, the following evidence SHALL be produced and reviewed (or explicitly waived with rationale):

| Evidence | Source | Blocking if missing |
|----------|--------|---------------------|
| Lint report | Industry_Definition_Linter::lint() | Yes (errors) |
| Health report | Industry_Health_Check_Service::run() | Yes (errors) |
| Pre-release validation pipeline run | Steps 1–9 in industry-pre-release-validation-pipeline.md | Yes |
| Regression guard results | Industry_Recommendation_Regression_Guard_Test (and related tests) | Yes (failures) |
| Known risks | known-risk-register.md §3 (industry); mitigations or waiver | Yes if new risks unrecorded |
| Diff report (if pack/bundle changes) | Pack diff tooling or bundle comparison; what changed vs previous | Recommended |
| Coverage / gap analysis | Industry_Coverage_Gap_Analyzer (informational) | No |
| Recommendation benchmark | Industry_Recommendation_Benchmark_Service (quality) | Per gate; optional for first ship |
| Performance benchmark | Industry_Performance_Benchmark_Service (informational) | No |
| Sandbox promotion summary (if from sandbox) | Industry_Sandbox_Promotion_Service::get_release_ready_summary() | When promoting from sandbox |

Incomplete evidence: If any blocking evidence is missing or failing, release candidacy is not achieved until resolved or formally waived and documented in sign-off.

---

## 4. Link to pack / subtype / version

- Manifest pack_identifiers and subtype_identifiers SHALL align with the actual definitions under validation (e.g. industry_key from pack definitions, subtype_key from subtype definitions).
- Version markers (when used) SHALL match schema version_marker in definitions. Evidence (lint, health) SHALL refer to the same definition set as the manifest.

---

## 5. Bounded and review-friendly

- Manifest and evidence bundle SHALL be bounded: no unbounded raw payloads, no secrets. Reports and summaries only.
- Human review is required before release; the manifest contract does not define an automated release decision. Sign-off documents (e.g. industry-subsystem-final-signoff.md) record that evidence was reviewed and accepted.

---

## 6. Integration with signoff and maintenance

- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) criteria reference acceptance report and validation; the manifest and evidence bundle support those criteria.
- **Final signoff:** [industry-subsystem-final-signoff.md](../release/industry-subsystem-final-signoff.md) compiles evidence; the manifest identifies the scope of the release candidate being signed off.
- **Sandbox promotion:** When candidates are promoted via [industry-sandbox-promotion-workflow.md](../operations/industry-sandbox-promotion-workflow.md), the promotion summary (pack_keys, bundle_keys) can feed into the manifest identifiers for the resulting release candidate.
- **Maintenance:** [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) and pre-release checklist reference this contract for what evidence to gather before release.

---

## 7. Security and constraints

- Internal-only documentation/artifacts; no public exposure of evidence bundle.
- No secrets in evidence bundles; redact or omit sensitive data.
- No unsafe auto-release behavior; human approval required.

---

## 8. References

- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md)
- [industry-subsystem-final-signoff.md](../release/industry-subsystem-final-signoff.md)
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md)
- [industry-sandbox-promotion-workflow.md](../operations/industry-sandbox-promotion-workflow.md)
- [industry-pack-diff-contract.md](industry-pack-diff-contract.md) (when diff tooling used)
