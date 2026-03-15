# ACF Registration — Memory and Query Profile Report Template

**Prompt**: 310  
**Contracts**: acf-conditional-registration-contract.md  
**Protocol**: acf-registration-benchmark-protocol.md

---

## 1. Purpose

Template for recording query-count and memory-profile evidence alongside timing and registration mode for the ACF conditional-registration retrofit. Use for release review; internal only. No sensitive content.

---

## 2. How to capture

- **Query count**: At end of the measured request (or immediately after in a follow-up admin request that reads state), record `$wpdb->num_queries` if available. Alternatively use a query-logging plugin or WP_DEBUG + SAVEQUERIES in a controlled benchmark run.
- **Memory**: Record `memory_get_peak_usage( true )` (bytes) at the same point. Optionally record `memory_get_usage( true )` for current usage.
- **Context**: Record registration mode from `ACF_Registration_Benchmark_Service::get_evidence_snapshot_with_profile()` or from diagnostics `get_last_registration()` plus manual query/memory capture.
- **Single request per scenario**: Run one scenario (e.g. load homepage, or load post.php?post=X&action=edit), then capture snapshot so the numbers reflect that request.

---

## 3. Report template (fill per run)

| Scenario | Registration mode | Query count | Memory peak (bytes) | Notes |
|----------|-------------------|-------------|---------------------|------|
| Front-end (homepage) | *(none recorded)* | ___ | ___ | Expect no ACF registration; low query count vs admin. |
| Existing-page edit (post.php?post=Y) | existing_page | ___ | ___ | Section key count: ___. Cache used: yes/no. |
| New-page edit (template chosen) | new_page | ___ | ___ | Section key count: ___. |
| Non-page admin (Dashboard) | non_page_admin | ___ | ___ | Zero groups. |

**Run date**: ___________  
**Environment**: WP ___, PHP ___, ACF ___, plugin version ___

---

## 4. Expected improvements (interpretation)

| Context | Before retrofit (typical) | After retrofit (expected) |
|---------|----------------------------|----------------------------|
| Front-end | High query count from full section definition load; higher memory from all blueprints. | No section load; query count and memory should not include bulk section/blueprint queries. |
| Admin page edit | Same bulk load as front-end plus registration. | Only assignment-map + single-section blueprint lookups per resolved key; cache can reduce repeated work. |
| Non-page admin | Full section load. | No registration; no section load. |

- **Query pattern**: Before retrofit, every request triggered `list_all_definitions( 9999, 0 )` or equivalent; after, only scoped or tooling paths do. Compare query count for front-end and non-page admin before vs after.
- **Memory**: Peak memory should be lower on front-end and non-page admin when bulk blueprint/section load is removed. Admin page edit may show lower peak than before if section count per page is small.
- **Correlation**: Registration mode (from diagnostics) must match scenario; if front-end shows a mode, that would indicate a bug (diagnostics only record in admin).

---

## 5. Reproducibility

- Use the same environment and data set for before/after comparison.
- Run each scenario in a fresh request; avoid reusing a request that already ran registration.
- Document any plugins/themes active during the run that might affect queries or memory.
- Store completed reports internally for release review; do not expose publicly.

---

## 6. Cross-references

- [acf-registration-benchmark-protocol.md](acf-registration-benchmark-protocol.md)
- [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md)
- ACF_Registration_Benchmark_Service::get_evidence_snapshot_with_profile()
