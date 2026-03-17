# Industry Guided Repair Workflow Contract (Prompt 526)

**Spec:** repair suggestion contracts; override conflict contracts; pack migration/deprecation contracts; support/runbook docs.  
**Status:** Contract. Defines the step-by-step internal repair workflow for missing refs, deprecated refs, inactive assets, and stale/conflicted overrides. No repair UI implementation in this prompt; no auto-repair; auditability preserved.

---

## 1. Purpose

- **Structured path:** Move from detection (health check, override conflict detector, repair suggestion engine) to bounded correction with clear checkpoints.
- **Operator-driven:** Every repair step is explicit; no silent mutation. Confirmation and review are required before applying changes.
- **Support-safe:** Workflow is documented so support and operators can follow it; rollback and failure expectations are clear.

---

## 2. Issue types and workflow entry points

| Issue type | Detection source | Workflow entry |
|-----------|------------------|----------------|
| **Missing ref** | Health check (object_type + key invalid); import conflict. | Health Report or Import preview shows broken ref; repair suggestion may attach suggested_ref. |
| **Deprecated ref** | Health check; pack/bundle definition has replacement_ref. | Health Report or Industry Profile shows deprecated warning; repair suggestion type deprecated_replacement. |
| **Inactive asset** | Health check (pack/bundle exists but inactive). | Health Report or Profile; suggestion type inactive_activate. |
| **Stale / conflicted override** | Industry_Override_Conflict_Detector (missing_target, deprecated_ref, removed_ref, subtype_context_stale). | Override Management or Health Report lists conflicts with suggested_review_action. |

---

## 3. Workflow steps by issue type

### 3.1 Missing ref

1. **Identify:** Health or import reports the broken_ref (e.g. pack key, bundle key, overlay ref).
2. **Suggest:** Call Repair_Suggestion_Engine::suggest_for_issue(). If suggestion_type is valid_alternative or fallback_bundle, present "Suggested: &lt;suggested_ref&gt;" with confidence_summary and explanation.
3. **Decision:** Operator chooses: (a) Apply suggested ref (if high/medium confidence and intent clear), (b) Manually pick another valid ref, or (c) Remove/clear the ref (e.g. clear selected_starter_bundle_key). No auto-apply.
4. **Apply:** Single explicit action (e.g. update profile, or fix import payload) with nonce and capability. Persist; then re-run health to confirm.
5. **Rollback:** If apply was to profile, previous value is overwritten; no built-in undo. Support may restore from backup or re-import if needed. Document "before" state in audit or diagnostics when available.

### 3.2 Deprecated ref

1. **Identify:** Health or Profile shows deprecated/superseded warning; replacement_ref and deprecation_note may be present.
2. **Suggest:** Repair suggestion type deprecated_replacement with suggested_ref = replacement_ref. Show "Replaced by: &lt;replacement_ref&gt;" and link to migration path.
3. **Decision:** (a) Migrate to replacement using Industry_Pack_Migration_Executor::run_migration_to_replacement(deprecated_pack_key), or (b) Keep current ref (accept warning) and defer migration, or (c) Manually switch to a different pack/bundle. Migration requires explicit trigger (e.g. "Migrate to &lt;replacement&gt;" button with nonce).
4. **Apply:** Migration executor updates profile only (primary_industry_key, secondary_industry_keys, selected_starter_bundle_key per migration contract). No Build Plan or override mutation. Result: success, migrated_refs, warnings, errors, audit_note.
5. **Rollback:** Migration does not rewrite history. To "undo" a migration, operator would run a second migration back to the previous pack (if still valid) or manually set profile keys. Migration result is logged for audit.

### 3.3 Inactive asset

1. **Identify:** Health reports that ref points to an inactive (disabled) pack or asset.
2. **Suggest:** Suggestion type inactive_activate; explanation "Pack is present but disabled; enable the pack."
3. **Decision:** (a) Activate the pack via pack toggle controller (if permitted and intended), or (b) Change profile/selection to a different active pack/bundle. No automatic activation.
4. **Apply:** If activating, use Industry_Pack_Toggle_Controller (or equivalent) with capability and nonce. If changing ref, same as missing-ref flow (update profile or selection).
5. **Rollback:** Deactivate pack again or revert profile change; no special rollback beyond normal config revert.

### 3.4 Stale or conflicted override

1. **Identify:** Override_Conflict_Detector::detect() returns conflicts (override_ref, conflict_type, severity, suggested_review_action).
2. **Review:** Operator opens Override Management (or Health Report) and reviews each conflict. conflict_type: missing_target, deprecated_ref, removed_ref, subtype_context_stale.
3. **Decision:** (a) Apply suggested_review_action (e.g. remove override, or update override to point to valid target), or (b) Dismiss/accept risk and leave override as-is (document reason), or (c) Escalate if target is ambiguous or replacement unclear.
4. **Apply:** Override changes (remove or update) are done via explicit Override Management actions (e.g. remove override action with nonce and capability). No bulk auto-remove. Each override is auditable (override_ref, plan_id, target_type, target_key).
5. **Rollback:** Overrides are stored per plan; if removed, data is gone unless restored from backup. Update-override path should validate new target before persisting. Safe failure: invalid target → no write; show error.

---

## 4. When to suggest replacement ref vs fallback vs escalation

| Situation | Recommendation | Rationale |
|-----------|----------------|------------|
| Deprecated pack with replacement_ref | **Replacement:** Suggest migration to replacement_ref. | Contract defines replacement; migration executor supports it. |
| Missing ref with one clear valid alternative | **Valid alternative:** Suggest that key (confidence high/medium). | Repair suggestion engine returns valid_alternative or fallback_bundle. |
| Missing ref with multiple alternatives or ambiguity | **Escalation:** Do not suggest a single ref; show "Multiple options" or "Choose manually." | Avoid silent wrong choice. |
| Removed ref (no longer in registry) | **Fallback or clear:** Suggest industry default bundle if applicable; else suggest clearing the ref. | No way to "fix" removed key; clear or fallback only. |
| Override conflict with missing_target | **Review action:** suggested_review_action from detector (e.g. remove override or point to valid target). | Operator must choose remove vs update. |
| Subtype context stale | **Review action:** Update override context or remove override. | Detector provides suggested_review_action. |

---

## 5. Confirmation and review requirements

- **Before any apply:** Operator must confirm the action (e.g. "Migrate to realtor?", "Remove this override?"). No background auto-repair.
- **Capability:** All repair actions (profile update, migration, override remove/update, pack toggle) require admin capability (e.g. manage_settings or equivalent per admin-screen-contract). Nonce required for state-changing requests.
- **Post-apply:** Re-run health check or conflict detection to verify issue is resolved. If not, workflow may be repeated or escalated.

---

## 6. Safe failure and rollback expectations

- **Validation first:** Apply step must validate (e.g. new ref exists, replacement pack active) before persisting. On validation failure: no write; return clear error; no partial overwrite.
- **No silent mutation:** No automatic repair in the background. All repair is triggered by explicit operator action.
- **Auditability:** Migration result includes migrated_refs and audit_note. Override removal/update is logged where override audit exists. Profile changes go through repository with optional audit trail.
- **Rollback:** No built-in one-click rollback. Operator may: restore from backup, re-run migration to previous pack (if still valid), or manually revert settings. Document rollback options in runbook for critical paths.

---

## 7. Links to related reports and diagnostics

- **Health Report:** Full list of health errors/warnings and optional repair suggestions; entry point for missing/deprecated/inactive.
- **Override Management:** List overrides and conflict detector output; entry point for override conflicts.
- **Industry Profile:** Where profile refs (primary industry, bundle) are edited; migration and ref fixes applied here or from Health Report links.
- **Repair Suggestion Engine:** [industry-repair-suggestion-contract.md](industry-repair-suggestion-contract.md); suggest_for_issue().
- **Override conflict detector:** Industry_Override_Conflict_Detector::detect(); contract per industry-override-conflict (if documented).
- **Migration:** [industry-pack-migration-contract.md](industry-pack-migration-contract.md); Industry_Pack_Migration_Executor.
- **Deprecation:** [industry-pack-deprecation-contract.md](industry-pack-deprecation-contract.md); replacement_ref and warning behavior.
- **Support/runbook:** [industry-support-training-packet.md](../operations/industry-support-training-packet.md); [industry-operator-curriculum.md](../operations/industry-operator-curriculum.md); support-triage-guide for diagnostics export.

---

## 8. Do not

- Auto-repair data from the workflow contract; implementation must require explicit operator action.
- Weaken auditability (e.g. silent overwrite without logging where audit is expected).
- Suggest a single replacement when multiple valid alternatives exist without operator choice.
