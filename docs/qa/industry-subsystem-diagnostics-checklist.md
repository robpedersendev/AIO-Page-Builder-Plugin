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
