# Industry Subsystem — Support Training Packet (Prompt 459)

**Audience:** Support staff and internal operators.  
**Spec:** support/runbook docs; maintenance and authoring guides; release signoff and risk docs; troubleshooting guides.  
**Purpose:** Single internal training artifact so support and QA understand packs, subtypes, bundles, overrides, cautions, diagnostics, and failure modes consistently.

---

## 1. Core concepts (terminology)

Use the same terms as in [industry-contract-consistency-audit.md](../contracts/industry-contract-consistency-audit.md).

| Term | Meaning |
|------|--------|
| **Primary industry** | The site’s main vertical (e.g. realtor, plumber). Stored as `primary_industry_key` in the Industry Profile. |
| **Industry pack** | Definition for one vertical: industry_key, name, status (active/draft/deprecated), refs to CTA, SEO, overlays, starter bundle, etc. Only **active** packs are used. |
| **Subtype** | Optional structured variant of a pack (e.g. realtor_buyer_agent). Stored as `industry_subtype_key` in profile; must match a subtype whose `parent_industry_key` equals primary. |
| **Starter bundle** | Preset set of page/section recommendations for an industry (and optionally a subtype). User selects one via `selected_starter_bundle_key`. |
| **Overlay** | Section-helper or page one-pager content keyed by industry (and optionally subtype). Overlays add industry-specific guidance; they do not replace base templates. |
| **Override** | User decision to accept or reject a specific section, page template, or Build Plan item (stored per target). Used when pack/profile are correct but the user needs a local exception. |
| **Caution / compliance rule** | Subtype or pack can attach rules that surface warnings (e.g. “ensure disclaimer present”). Resolved by compliance/warning resolvers; no auto-fix. |

---

## 2. Representative launch industries

- **cosmetology_nail**, **realtor**, **plumber**, **disaster_recovery** are the first four. Each has pack definition, optional subtypes, starter bundles, overlays, and CTA/SEO/preset refs where applicable.
- When triaging, confirm which industry (and subtype if any) the site uses; then check pack status, bundle selection, and overlay coverage for that vertical.

---

## 3. Troubleshooting and failure modes

- **Recommendations too generic or wrong vertical:** See [industry-bad-fit-recommendation-troubleshooting.md](industry-bad-fit-recommendation-troubleshooting.md). Check profile completeness (§3.1), pack activation (§3.2), subtype selection (§3.3), overrides (§3.4), starter bundle (§3.5), overlays (§3.6).
- **Health report errors/warnings:** Use **Industry Health Report** (AIO Page Builder menu). Errors = broken refs or invalid profile (e.g. primary pack not found, selected bundle missing). Warnings = advisory (e.g. bundle industry mismatch). No auto-fix; guide user to fix profile or enable pack.
- **Override counts / state:** Use override audit (Industry_Override_Audit_Report_Service) or the **Industry Documentation Summary Export** for a single bounded report; see [industry-documentation-summary-export-contract.md](../contracts/industry-documentation-summary-export-contract.md).
- **No industry applied:** If user set industry but behavior is generic, confirm primary_industry_key is saved, pack is active, and no restore overwrote profile. Use diagnostics snapshot (Support Triage or Industry_Diagnostics_Service) to verify.

---

## 4. Diagnostics and bounded output

- **Industry snapshot:** Industry_Diagnostics_Service::get_snapshot() — primary_industry, secondary_industries, profile_readiness, active_pack_refs, overlay counts, recommendation_mode, warnings. Shown in Support Triage when industry is loaded. No secrets.
- **Health check:** Industry_Health_Check_Service::run() — errors and warnings (object_type, key, issue_summary, related_refs). Shown on Industry Health Report screen.
- **Documentation summary export:** Industry_Documentation_Summary_Export_Service::generate() — single report with profile_state, pack refs, override summary (counts), health (counts + capped samples), major_warnings. Use for support handoffs or migration review; see contract and [support-triage-guide.md](../guides/support-triage-guide.md).

---

## 5. Overrides and cautions

- **Overrides** are per target (section, page_template, build_plan_item). They record accept/reject and optional reason. Do not use overrides to “fix” a wrong profile or pack; fix the source first.
- **Cautions / compliance rules** surface warnings (e.g. disclaimer, licensing). Support does not auto-apply fixes; guide user to content or settings as documented.

---

## 6. Escalation paths

- **Pack missing or broken refs:** Escalate to maintainers; ensure Industry_Definition_Linter and pre-release pipeline were run (see [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md)).
- **Restore or export/restore issues:** Check industry-export-restore-contract and restore logs; escalate if schema version unsupported or data loss.
- **Recommendation logic or scoring:** Escalate to product/engineering; reference [industry-bad-fit-recommendation-troubleshooting.md](industry-bad-fit-recommendation-troubleshooting.md) and acceptance/regression evidence.

---

## 7. References

- [industry-contract-consistency-audit.md](../contracts/industry-contract-consistency-audit.md) — terminology and lifecycle
- [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md) — diagnostics verification
- [industry-bad-fit-recommendation-troubleshooting.md](industry-bad-fit-recommendation-troubleshooting.md) — recommendation issues
- [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) — maintenance baseline
- [support-triage-guide.md](../guides/support-triage-guide.md) — logs, support bundle, redaction
