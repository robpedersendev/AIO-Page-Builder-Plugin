# ACF Conditional Registration — Support Runbook

**Prompt**: 302  
**Contracts**: acf-conditional-registration-contract.md, acf-registration-exception-matrix.md

---

## 1. Purpose

Support-facing guide for the ACF conditional-registration performance retrofit: expected behavior by context, common regression symptoms, how to verify diagnostics and benchmark evidence, and when a temporary broader-registration path may be used. Internal/admin only.

---

## 2. Expected behavior by request context

| Context | Expected registration | Evidence |
|---------|------------------------|----------|
| **Front-end** (public, non-admin) | No ACF groups registered; no section definition load. | No `register_all()` or bulk section query on front-end requests. |
| **Existing-page edit** (`post.php?post=X&action=edit`, page) | Only that page's assigned section-owned groups registered. Resolution from assignment map; optional section-key cache. | Diagnostics: `mode` = `existing_page`; `section_key_count` = that page's sections. |
| **New-page edit** (`post-new.php?post_type=page`) | When template/composition chosen: only those sections' groups. Otherwise no groups. | Diagnostics: `mode` = `new_page`; `section_key_count` = chosen template/composition sections. |
| **Non-page admin** (Dashboard, Plugins, etc.) | Zero groups registered. | Diagnostics: `mode` = `non_page_admin`; `section_key_count` = 0. |
| **Tooling** (debug export, regeneration, diagnostics screen) | Full registration allowed only from explicit tooling entry points; never from `acf/init`. | See acf-registration-exception-matrix.md. |

---

## 3. Common regression symptoms

| Symptom | Likely cause | Action |
|---------|--------------|--------|
| ACF groups missing on page edit | Assignment map empty or wrong; or cache stale (should not persist after assignment/template/composition change). | Confirm assignment map for that page; reassign from template/composition if needed. Cache invalidates on `aio_acf_assignment_changed`, `aio_page_template_definition_saved`, `aio_composition_definition_saved`. |
| All section groups loading on every request | Full registration path triggered from bootstrap (bug). | Check that `acf/init` calls `run_registration()` only, never `run_full_registration()`. Exception matrix: only documented tooling may call full registration. |
| Front-end slow or high DB load | Full section load on front-end (regression). | Verify front-end skips registration; no `register_all()` or `list_all_definitions(9999)` on public requests. |
| New-page edit shows no/wrong groups | Template/composition not chosen or derivation wrong; or cache stale for template/composition. | Confirm template/composition selection; confirm definition saved; cache invalidates on definition save. |

---

## 4. Verifying diagnostics and benchmark evidence

- **Diagnostics**: `ACF_Registration_Diagnostics_Service::get_last_registration()` returns the last admin registration run (mode, section_key_count, cache_used, full_registration_invoked). Only recorded in admin; front-end does not record.
- **Benchmark**: `ACF_Registration_Benchmark_Service::get_evidence_snapshot()` returns `last_registration` plus timestamp. Use for before/after comparison. See [acf-registration-benchmark-protocol.md](../qa/acf-registration-benchmark-protocol.md).
- **No sensitive data**: Snapshot contains only mode, counts, and booleans; no field values or page content.

---

## 5. When a temporary broader-registration path may be used

- **Who**: Developers or support with explicit policy approval; not end users.
- **When**: Diagnosis of a context-specific bug where full registration is temporarily needed to isolate whether the issue is scoping vs ACF/blueprint. Must be internal, documented, and reverted after diagnosis.
- **How**: Use only the documented exception paths (e.g. tooling that calls `run_full_registration()` from an explicit admin/tooling entry point). Do not add a public or user-facing toggle to disable conditional registration unless explicitly authorized.
- **Reference**: [acf-registration-exception-matrix.md](../contracts/acf-registration-exception-matrix.md); [acf-conditional-registration-rollback-playbook.md](acf-conditional-registration-rollback-playbook.md).

---

## 6. Cross-references

- [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md)
- [acf-registration-exception-matrix.md](../contracts/acf-registration-exception-matrix.md)
- [acf-registration-cache-invalidation-matrix.md](../contracts/acf-registration-cache-invalidation-matrix.md)
- [acf-conditional-registration-rollback-playbook.md](acf-conditional-registration-rollback-playbook.md)
- [acf-registration-performance-release-gate.md](../release/acf-registration-performance-release-gate.md)
