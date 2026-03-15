# ACF Registration — Benchmark Protocol

**Prompt**: 301  
**Contract**: acf-conditional-registration-contract.md

---

## 1. Purpose

Repeatable procedure to capture before/after evidence for the ACF conditional-registration retrofit: registration mode, section-key count, and cache usage per scenario. Internal/admin-only; no sensitive content.

---

## 2. Scenarios

| Scenario | How to run | Expected (post-retrofit) |
|----------|------------|---------------------------|
| **Front-end** | Load site front-end (e.g. homepage). | No ACF registration; diagnostics does not record on front-end. Evidence: verify no `register_all()` or bulk section load (e.g. one-off test or debug log). |
| **Existing-page edit** | Open `post.php?post=<page_id>&action=edit` (page) as admin. | `last_registration.mode` = `existing_page`; `section_key_count` = that page's sections; `cache_used` true if cache hit. |
| **New-page edit** | Open `post-new.php?post_type=page`, choose template or composition. | `last_registration.mode` = `new_page`; `section_key_count` = sections for chosen template/composition. |
| **Non-page admin** | Open Dashboard or Plugins (no page edit). | `last_registration.mode` = `non_page_admin`; `section_key_count` = 0. |

---

## 3. How to capture (admin scenarios)

1. Use a **single** admin request for the scenario (e.g. load the edit screen once).
2. Immediately after that request, obtain the evidence snapshot (same or next request, admin-only):
   - **Option A**: Call `ACF_Registration_Benchmark_Service::get_evidence_snapshot()` from an internal admin endpoint or support tool that has access to the container.
   - **Option B**: If diagnostics is exposed on a diagnostics/support screen, read `last_registration` and `timestamp` from there.
3. Record `mode`, `section_key_count`, `cache_used`, `full_registration_invoked` (expect `false` in normal flow).

No sensitive field values or page content are included in the snapshot.

---

## 4. Before vs after comparison

| Metric | Before (unconditional) | After (conditional) |
|--------|-------------------------|---------------------|
| Front-end | `register_all()` + full section load every request | No registration; no section load |
| Existing-page edit | Full section load then register all | Scoped: only that page's sections; optional cache |
| New-page edit | Full section load | Scoped: only template/composition sections |
| Non-page admin | Full section load | No registration (0 groups) |

Run the same scenarios before and after the retrofit; compare snapshot `mode` and `section_key_count` to confirm heavy-load elimination.

---

## 5. Repeatability and safety

- Run each scenario in a clean request (e.g. new tab or fresh request).
- Benchmark is bounded: snapshot only; no permanent dashboard or profiling.
- Evidence must not include sensitive content; `get_evidence_snapshot()` returns only mode, counts, and booleans.

---

## 6. Query and memory profile (Prompt 310)

- **Query count**: In a controlled run, after the scenario request, record `$wpdb->num_queries` (or use query logging). Correlate with registration mode: front-end and non-page admin should not run section/blueprint bulk queries.
- **Memory**: Record `memory_get_peak_usage( true )` (bytes) at the same point. Compare before/after retrofit for each scenario.
- **Snapshot with profile**: Call `ACF_Registration_Benchmark_Service::get_evidence_snapshot_with_profile()` from an admin context after the measured request to get `last_registration`, `timestamp`, `query_count` (if wpdb available), and `memory_peak_bytes`. Use for release review evidence.
- **Report template**: [acf-memory-and-query-profile-report-template.md](acf-memory-and-query-profile-report-template.md) defines the table and interpretation. No sensitive data in reports.

---

## 7. References

- [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md)
- [acf-conditional-registration-acceptance-report.md](acf-conditional-registration-acceptance-report.md)
- [acf-memory-and-query-profile-report-template.md](acf-memory-and-query-profile-report-template.md)
