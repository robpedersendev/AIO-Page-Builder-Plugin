# Template Library Decision Log

**Spec**: §61.9 Decision Log Structure; §58.2 Template Registry Versioning; §12.14–12.15, §13.12–13.13; §61.10 Escalation Rules.

This log records major template-family and registry decisions. Each entry contains: Decision ID, Date, Owner, Status, Summary, Rationale, Alternatives considered, Impacted sections/templates, Effective version.

**Status values**: proposed | approved | superseded | rejected.

**Escalation:** Implementation issue → Technical Lead; product/scope → Product Owner; security/privacy → Product Owner + security reviewer; release-blocking → formal milestone review (spec §61.10). No critical unresolved issue may be silently carried into release.

**How to append entries:** Use `Template_Deprecation_Service::build_decision_log_entry()` to generate a consistent payload; paste or sync the result into the Entries section below. See [template-ecosystem-maintenance-runbook.md](../operations/template-ecosystem-maintenance-runbook.md) §5.1.

---

## Entry format (per §61.9)

| Field | Description |
|-------|-------------|
| Decision ID | Unique identifier (e.g. DL-001). |
| Date | Date of record (YYYY-MM-DD). |
| Owner | Responsible role or person. |
| Status | proposed, approved, superseded, rejected. |
| Summary | Short one-line summary. |
| Rationale | Why this decision was made. |
| Alternatives considered | What was weighed and not chosen. |
| Impacted section keys | Section template internal_keys affected. |
| Impacted template keys | Page template internal_keys affected. |
| Effective version | Registry/template version when effective. |

---

## Example entry

**Decision ID**: DL-001  
**Date**: 2025-03-13  
**Owner**: Technical Lead  
**Status**: approved  
**Summary**: Cap hero_intro section family at 12 variants; prefer new variation_family_key for additional openers.  
**Rationale**: Prevents single-purpose-family dominance in the section library and keeps directory manageable. New opener styles use a distinct variation_family_key (e.g. hero_primary_alt) instead of inflating hero_intro count.  
**Alternatives considered**: (1) Allow unbounded hero_intro growth — rejected for discoverability. (2) Auto-archive oldest — rejected; deprecation is explicit and attributable.  
**Impacted section keys**: (none; policy applies to future batches.)  
**Impacted template keys**: (none.)  
**Effective version**: 1  

---

## Example deprecation record (section)

Applied to a section definition when deprecating (merge into definition before save). Produced by `Template_Deprecation_Service::get_section_deprecation_block( reason, replacement_key )`:

- `status` => `'deprecated'`
- `deprecation` => `{ deprecated: true, reason: '...', deprecated_at: '...', replacement_section_key: '...', retain_existing_references: true, exclude_from_new_selection: true }`
- `replacement_section_suggestions` => `['st01_hero_intro']` (when replacement is provided)

Existing pages and plans that reference the deprecated section remain interpretable; new selection excludes it unless explicitly allowed.

---

## Entries

*(Append new entries below. Use `Template_Deprecation_Service::build_decision_log_entry()` to generate consistent payloads; paste or sync into this doc.)*

---

*End of log.*
