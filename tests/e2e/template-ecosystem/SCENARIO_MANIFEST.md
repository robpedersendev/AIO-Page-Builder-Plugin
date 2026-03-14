# Template Ecosystem End-to-End Scenario Manifest

**Spec**: §56.3 Integration Test Scope; §56.4 End-to-End Test Scope; §60.4 Exit Criteria; §60.5 Acceptance Test Requirements; §59.14 Hardening and QA Phase. **Prompt 216.**

This manifest lists representative happy-path and failure-path scenarios for the expanded template ecosystem. Execute with synthetic/demo-safe data only. Evidence is recorded in [template-ecosystem-end-to-end-acceptance-report.md](../../docs/qa/template-ecosystem-end-to-end-acceptance-report.md).

---

## 1. Directory browsing and detail previews

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-DIR-01 | Section Templates directory loads and lists templates | Happy | Open Section Templates; apply filter/search if available; paginate. | Directory loads; list bounded (MAX_PER_PAGE); no unbounded query; filters work. |
| E2E-DIR-02 | Page Templates directory loads and lists templates | Happy | Open Page Templates; filter by category/family; paginate. | Directory loads; list bounded; hierarchy/category filters work. |
| E2E-DIR-03 | Section template detail opens with preview | Happy | Open a section template detail by key; request preview. | Detail screen shows metadata; preview (synthetic) renders or loads from cache; no secrets in payload. |
| E2E-DIR-04 | Page template detail opens with preview and one-pager link | Happy | Open a page template detail; request preview; follow one-pager ref if present. | Detail shows metadata, ordered/optional sections; preview renders; one-pager link present when defined. |
| E2E-DIR-05 | Directory access denied without capability | Failure | Access Section or Page Templates directory as user lacking MANAGE_SECTION_TEMPLATES / MANAGE_PAGE_TEMPLATES. | Access denied or redirect; no template list exposed. |

---

## 2. Compare workspace

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-CMP-01 | Add templates to compare list and open Compare screen | Happy | From directory or detail, add 2–3 section (or page) templates to compare list; open Template Compare. | Compare list persists (user meta); Compare screen shows side-by-side matrix; max 10 items enforced. |
| E2E-CMP-02 | Compare screen shows template_compare_rows from registry | Happy | With compare list populated, verify each row shows key, name, purpose, and registry-authoritative data. | Rows match registry; no raw secrets; observational only. |
| E2E-CMP-03 | Compare list full – add rejected | Failure | Fill compare list to 10; attempt to add another. | Add rejected or list unchanged at 10. |
| E2E-CMP-04 | Template Compare access without capability | Failure | Open Template Compare as user without MANAGE_PAGE_TEMPLATES (or appropriate cap). | Access denied or empty/redirect. |

---

## 3. Compositions

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-COM-01 | Compositions screen loads and lists compositions | Happy | Open Compositions; apply filters if available. | List loads; bounded; composition definitions from registry. |
| E2E-COM-02 | Composition validation (CTA / compatibility) runs | Happy | Create or open a composition; trigger validation (Large_Composition_Validator). | Validation result returned; CTA/compatibility rules applied; no crash. |
| E2E-COM-03 | Composition build state reflects validation status | Happy | Build composition state (Composition_Builder_State_Builder); check validation_status. | State includes validation_status; valid/invalid reflected. |
| E2E-COM-04 | Compositions access without MANAGE_COMPOSITIONS | Failure | Open Compositions as user without capability. | Access denied or redirect. |

---

## 4. Build Plan template recommendation visibility

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-BP-01 | Build Plan step 2 shows template recommendations | Happy | Create or open a Build Plan; advance to new-pages step; verify template keys and rationale visible. | Rows show proposed_template_summary or equivalent; template detail/compare links present where applicable. |
| E2E-BP-02 | Build Plan step 1 shows existing-page template change summary | Happy | Build Plan with existing-page updates; verify step 1 rows show template change summary and replacement reason. | existing_page_template_change_summary / replacement_reason_summary present when applicable. |
| E2E-BP-03 | Template recommendation context uses template_family / preference profile | Happy | Run planning with template_preference_profile and crawl/context; verify recommendation payload includes family and guidance. | Recommendations align with template family and CTA-law; no raw secrets in UI. |
| E2E-BP-04 | Build Plan approval without execution capability | Failure | Approve/execute Build Plan as user without execution capability. | Execution denied; clear feedback. |

---

## 5. New-page creation (template build)

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-NP-01 | New-page creation produces template_build_execution_result | Happy | Execute a Build Plan step that creates a new page from a page template. | Page created; artifacts contain template_build_execution_result (template_key, template_family, hierarchy_applied, section_count, etc.). |
| E2E-NP-02 | Hierarchy applied when template is child/hub | Happy | Create new page from a child_detail or hub template with parent specified. | parent_post_id set; hierarchy_applied true in result; page linked correctly. |
| E2E-NP-03 | New-page creation failure recorded without breaking queue | Failure | Force failure (e.g. invalid template key or missing dependency). | Failure recorded; job result/artifacts indicate failure; queue and other jobs unaffected. |

---

## 6. Page replacement flow

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-RPL-01 | Replace page produces template_replacement_execution_result and replacement_trace_record | Happy | Execute existing-page replacement from Build Plan or replace action. | Replacement completes; artifacts contain template_replacement_execution_result and replacement_trace_record (target, superseded, snapshot_ref, template_key). |
| E2E-RPL-02 | Replacement uses intended_template_key in pre-snapshot | Happy | Run replacement; verify pre-change snapshot contains intended_template_key. | Snapshot stores intended template for diff/rollback. |
| E2E-RPL-03 | Replacement failure leaves no partial state | Failure | Force replacement failure (e.g. invalid target). | No partial replace; error returned; snapshot/rollback state consistent. |

---

## 7. Menu application (template-aware)

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-MNU-01 | Menu apply with template_aware_menu produces navigation summary | Happy | Execute menu apply action with envelope containing template_aware_menu or page_class. | Template_Menu_Apply_Service runs; result includes navigation_hierarchy_summary where applicable; menu updated per plan. |
| E2E-MNU-02 | Menu apply result captured in operational snapshot | Happy | After menu apply, capture or view operational snapshot. | Snapshot includes menu apply result and template context where applicable. |

---

## 8. Diff and rollback summaries

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-DIF-01 | template_diff_summary produced from pre/post snapshots | Happy | Perform a template-driven change (new page or replacement); obtain pre/post snapshots; run Template_Diff_Summary_Builder or equivalent. | template_diff_summary contains template_key_before/after, template_family_after, section_count_after, template_structural_change, rollback_template_context. |
| E2E-DIF-02 | Rollback restores prior state using snapshot | Happy | After a replacement or change, run rollback using stored snapshot. | Rollback completes; state reverted per snapshot; no data loss. |
| E2E-DIF-03 | Diff/rollback without snapshot fails gracefully | Failure | Request rollback or diff when snapshot missing or invalid. | Clear failure; no crash; user feedback. |

---

## 9. Export and restore

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-EXP-01 | Full export includes template registries and compositions | Happy | Run full export; open bundle. | Manifest and bundle include section/page template and composition data; schema version present. |
| E2E-EXP-02 | Template-only export validates and packages registries | Happy | Run template-only export; validate package. | Export completes; template_library_export_validator passes; package restorable. |
| E2E-EXP-03 | Restore applies template registries and validates | Happy | Restore from a valid template or full export package. | Restore pipeline runs; template_library_restore_validator used; registries restored; no secret leakage. |
| E2E-EXP-04 | Restore with invalid or incompatible package fails validation | Failure | Attempt restore with invalid manifest or wrong schema. | Validation failure; clear error; no partial apply. |

---

## 10. Reporting enrichments

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-RPT-01 | Install notification payload may include template_library_report_summary | Happy | Trigger install notification (e.g. after activation with container-built service); inspect payload. | Payload may contain template_library_report_summary (counts, version markers, appendices_available, compliance_summary); no secrets. |
| E2E-RPT-02 | Heartbeat payload may include template_library_report_summary | Happy | Trigger heartbeat (e.g. via filter with container); inspect payload. | Payload may contain template_library_report_summary; redaction preserved. |
| E2E-RPT-03 | Error report payload may include template_library_report_summary | Happy | Trigger qualifying error report with template context; inspect payload. | Payload may contain template_library_report_summary; prohibited keys absent. |
| E2E-RPT-04 | Reporting failure logged without breaking core | Failure | Simulate transport failure (e.g. invalid mail); trigger install or heartbeat. | Failure logged in reporting log; activation or cron continues; no fatal. |

---

## 11. Capability and permission restrictions

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-CAP-01 | Sensitive template seed/mutation requires MANAGE_* capability | Failure | As user without MANAGE_SECTION_TEMPLATES, attempt section template seed or mutation. | Action denied; 403 or equivalent. |
| E2E-CAP-02 | Template Analytics / diagnostic screens respect VIEW_LOGS or design cap | Failure | Access Template Analytics (or template diagnostic view) without required capability. | Access denied or restricted. |
| E2E-CAP-03 | Export/restore requires appropriate capability | Failure | Attempt export or restore without required cap. | Action denied. |

---

## 12. Support and lifecycle visibility

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| E2E-SUP-01 | Privacy screen shows template library lifecycle section when summary present | Happy | Open Privacy & Reporting screen with container providing template_library_lifecycle_summary_builder. | Section "Template library: deactivation, export & restore" visible; built-pages survivability and restore guidance shown. |
| E2E-SUP-02 | Import/Export screen links to full lifecycle section | Happy | Open Import/Export; check uninstall/export behavior block. | When template_library_lifecycle_summary present, link to Privacy screen lifecycle section shown. |
| E2E-SUP-03 | Support package may include template library support summary | Happy | Generate support package when template_library_support_summary_builder available. | Package includes template support summary when configured; no secrets. |

---

## Execution notes

- **Data:** Use demo fixtures, seeded registries, or synthetic data only (§56.4; demo-fixture-guide.md). No production customer data or secrets.
- **Evidence:** For each scenario, record Pass / Fail / Waived / Skipped and evidence reference (run date, tester, log or screenshot ref) in the [acceptance report](../../docs/qa/template-ecosystem-end-to-end-acceptance-report.md).
- **Failure paths:** Permission-denied and validation-failure scenarios must demonstrate correct denial or validation message, not success.
- **Planner/executor:** New-page and replacement flows must reflect planner output driving executor; no bypass of Build Plan or action envelope validation.
