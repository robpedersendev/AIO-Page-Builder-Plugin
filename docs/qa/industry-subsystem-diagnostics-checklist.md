# Industry Subsystem Diagnostics Checklist (Prompt 356)

**Purpose:** QA and support verification for industry diagnostics and support surfacing. Bounded, admin-only; no secrets.

---

## 1. Diagnostics snapshot (Industry_Diagnostics_Service::get_snapshot())

- [ ] **No industry configured:** Snapshot has `primary_industry` empty, `profile_readiness` `none`, `recommendation_mode` `inactive`, `industry_subsystem_available` true when industry module is loaded (or false when not).
- [ ] **Primary industry set:** Snapshot has `primary_industry` and `active_pack_refs` populated; `profile_readiness` is `partial` or `complete`; `recommendation_mode` is `active`.
- [ ] **Overlay counts:** When primary industry is set, `section_overlay_count` and `page_overlay_count` reflect overlays for that industry (non-negative integers).
- [ ] **Applied preset:** When a style preset is applied, `applied_preset_ref` is the preset key; otherwise null.
- [ ] **Warnings:** If primary industry key has no matching pack, `warnings` contains `primary_industry_pack_not_found`.
- [ ] **No secrets:** Snapshot contains only keys defined in the service (no raw profile content, API keys, or user data).

---

## 2. Support triage integration

- [ ] **Support Triage screen:** When industry diagnostics service is registered, state includes `industry_snapshot` with the same shape as get_snapshot().
- [ ] **When industry not loaded:** Support triage state does not include `industry_snapshot` (key omitted).
- [ ] **Admin-only:** Support Triage and diagnostics are gated by VIEW_LOGS (or equivalent); no public access.

---

## 3. Bounded output

- [ ] Snapshot keys are fixed: `primary_industry`, `secondary_industries`, `profile_readiness`, `active_pack_refs`, `applied_preset_ref`, `section_overlay_count`, `page_overlay_count`, `recommendation_mode`, `warnings`, `industry_subsystem_available`.
- [ ] No unbounded arrays of raw content; overlay counts are integers only.

---

## 4. Industry Health Report (Prompt 390)

- [ ] **Screen:** Industry Health Report is available under AIO Page Builder menu; capability VIEW_LOGS; no public exposure.
- [ ] **Health check service:** `Industry_Health_Check_Service::run()` returns `errors` and `warnings`; each issue has `object_type`, `key`, `severity`, `issue_summary`, `related_refs`.
- [ ] **Pack refs:** Missing token_preset_ref, seo_guidance_ref, lpagery_rule_ref, starter_bundle_ref, or CTA refs produce errors; unresolved helper/one_pager overlay refs produce warnings.
- [ ] **Profile:** Primary or secondary industry key not in pack registry produces error; disabled primary pack produces warning; selected_starter_bundle_key not found produces error; bundle industry mismatch with primary produces warning.
- [ ] **Starter bundles:** Bundle industry_key with no matching pack produces warning.
- [ ] **No auto-fix:** Report is observational only; no automatic repair or mutation.
- [ ] **Empty/healthy:** When no issues, screen shows success notice and no error/warning tables.

---

## 5. CLI / scripted inspection (Prompt 398)

- [ ] **Inspection service:** `Industry_Inspection_Command_Service` provides read-only `get_profile_summary()`, `get_health_summary()`, `get_diagnostics_snapshot()`, `get_recommendation_preview( industry_key, top_templates, top_sections )`, `get_starter_bundles_for_industry( industry_key )`. No mutation.
- [ ] **Usage:** Internal/support only; see [industry-cli-inspection-guide.md](../operations/industry-cli-inspection-guide.md) for intended use from WP-CLI or scripts.
- [ ] **Bounded output:** Summaries and previews are capped (e.g. sample_errors/sample_warnings up to 5, top_template_keys limited). No secrets in output.
