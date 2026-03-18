# Privacy Exporter/Eraser Scope Boundary

**Decision:** [privacy-exporter-eraser-scope-decision.md](privacy-exporter-eraser-scope-decision.md)  
**Spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §47  
**Ledger:** [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md) §6 (SPR-004)

---

## 1. Purpose

This document is the single reference for what is **in scope** and **out of scope** for the WordPress Tools → Export Personal Data and Erase Personal Data flows (wp_privacy_personal_data_exporters / wp_privacy_personal_data_erasers). It does not change scope; it records the agreed boundary.

---

## 2. In scope (actor-linked / user-keyed data)

Data that can be attributed to a specific user (by user ID or email) and is exported or erased when that user requests their data:

| Data class | Storage | Export behavior | Erase behavior |
|------------|---------|------------------|-----------------|
| AI run records | CPT + meta (actor) | Include run metadata (no secrets) for requestor’s runs | Redact actor to anonymized value; retain record for audit |
| Job queue records | Custom table (actor_ref) | Include job rows for requestor’s actor_ref | Redact actor_ref to anonymized value; retain row for audit |
| Template compare lists | User meta | Export keys/labels for user’s compare lists | Delete user meta entries |
| Bundle preview cache | Transient (per-user key) | Export a note that preview cache exists | Delete transient |

**Rule:** If the plugin stores data that is **keyed by or attributable to a specific user** (e.g. actor, user_id, actor_ref, or user-scoped meta/transient), that data is in scope for exporter/eraser unless a documented exception applies.

---

## 3. Out of scope (not keyed by user)

Data that is site-level, operational, or not attributable to a single user is **not** included in Export/Erase for a given user:

| Category | Examples | Reason |
|----------|----------|--------|
| Site-level options | Onboarding draft (site option), industry profile, applied preset | Not keyed by user; one value per site |
| Reporting / audit | Reporting log, industry profile audit trail | Operational/site-level; not per-user |
| Diagnostics | Environment/dependency checks, diagnostics screen data | Not keyed by user |
| Execution log table | Schema has actor_ref but no writes in codebase | Not currently used for writes; UI uses job queue |

**Rule:** Data that is **not** keyed by or attributable to a specific user is out of scope for the WordPress privacy exporter/eraser. Full site backup/restore is handled by the Import/Export (ZIP) flow, not by Tools → Export/Erase Personal Data.

---

## 4. Redaction and audit (current behavior)

- **Export:** No secrets or credentials are included. Exporter only includes data the plugin stores for the requestor; redaction is applied per existing rules (e.g. prompt/artifact redaction where defined).
- **Eraser:** For AI runs and job queue, the **actor** (or actor_ref) is redacted to an anonymized value; the **record is retained** for audit. User meta and transients are deleted. No destruction of records required for system or audit integrity.

---

## 5. Future changes

- If the plugin adds **new storage** that is keyed by user (e.g. per-user activity log), that data class should be evaluated for inclusion in exporter/eraser and this boundary document updated.
- Scope expansion (e.g. including site-level or non–user-keyed data in export/erase) would require a new product/spec decision and implementation criteria; it is not implied by this boundary.
