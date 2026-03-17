# Industry What-If Simulation Integrity Audit Report (Prompt 598)

**Spec:** What-if simulation contracts; comparison/reporting docs; profile and recommendation docs.  
**Purpose:** Audit what-if simulation so simulated industry/subtype/goal/bundle/secondary-goal scenarios remain read-only, deterministic, correctly isolated from live state, and accurately rendered in comparison outputs.

---

## 1. Scope audited

- **Service:** `plugin/src/Domain/Industry/Reporting/Industry_What_If_Simulation_Service.php` — run_simulation( params ). PARAM_ALTERNATE_PRIMARY, PARAM_ALTERNATE_SUBTYPE, PARAM_ALTERNATE_BUNDLE, PARAM_ALTERNATE_CONVERSION_GOAL. build_simulated_profile( live, params ) merges params over live copy; validate_simulated_refs() checks refs against registries. comparison_simulated and comparison_live from Industry_Subtype_Comparison_Service when valid.
- **Extender:** `Conversion_Goal_What_If_Extender` — adds conversion_goal_key to simulated profile for what-if comparison; merge into params for run_simulation.
- **Contract:** "No live state mutation"; "Admin-only; read-only; no persistence."

---

## 2. Findings summary

| Area | Result | Notes |
|------|--------|--------|
| **No live state mutation** | Verified | run_simulation() reads live via profile_repo->get_profile(); build_simulated_profile() returns a new array (copy + param overrides); no set_profile or merge_profile call. Simulated profile used only for validation and comparison. |
| **Comparison outputs use simulated context** | Verified | When valid, comparison_simulated = comparison_service->get_comparison( sim_summary['primary'], sim_summary['subtype'] ); comparison_live uses live summary. So comparison reflects simulated vs live correctly. |
| **Invalid/partial input fallback** | Verified | validate_simulated_refs() returns invalid_refs list; valid = empty( invalid_refs ). When invalid, comparison_simulated/comparison_live may still be populated from refs that are valid; invalid refs reported in result. Warnings (e.g. simulated_primary_is_deprecated) added without failing. |
| **Cache isolation** | Verified | Simulation does not write to profile repository or shared mutable state. Comparison service get_comparison() is read-oriented. No evidence simulated results written to industry read-model cache keyed by live profile; simulation uses its own simulated profile for downstream calls. |
| **Determinism** | Verified | Same params and live profile produce same simulated profile and comparison. |
| **Admin-only** | Verified | Service is used by admin comparison/readiness screens; not exposed on public endpoints. |

---

## 3. Recommendations

- **No code changes required.** Simulation is read-only, isolated, and comparison outputs match simulated inputs.
- **Tests:** Add simulation isolation tests and comparison parity tests for representative simulated contexts per prompt 598.

---

## 4. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
