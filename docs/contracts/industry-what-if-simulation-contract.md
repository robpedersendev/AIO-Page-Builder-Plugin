# Industry What-If Simulation Contract (Prompt 466)

**Spec**: Industry profile, subtype, and bundle contracts; recommendation and Build Plan scoring contracts; author sandbox and dry-run docs.  
**Status**: Contract. Defines a bounded what-if simulation mode for previewing alternate industry configurations without mutating live state.

---

## 1. Purpose

- **Preview only**: Allow admins to see how recommendations, bundles, or Build Plan–related outcomes would change if they switched primary industry, subtype, or starter bundle—**without persisting any changes**.
- **Decision support**: Operators can compare alternate configurations safely before committing profile updates.
- **Isolation**: Simulation state is strictly isolated from live persisted state; no cache pollution or hidden mutation.

---

## 2. Scope

- **Simulate**: Alternate primary industry key; alternate subtype key (within a parent); alternate starter bundle key.
- **Show**: Key recommendation or planning deltas (e.g. top template/section keys under simulated config, comparison with live).
- **Support**: No-industry and fallback comparisons (e.g. "what if I clear primary?" → generic fallback summary).
- **Out of scope**: Public simulator; auto-apply of simulated changes; replacement of the author sandbox for raw asset testing.

---

## 3. Simulation result shape

A simulation run returns a bounded result object:

| Field | Type | Description |
|-------|------|-------------|
| valid | bool | True when all simulated refs resolve; false when one or more invalid. |
| invalid_refs | list | Ref type + key for each invalid simulated ref (e.g. pack not found, subtype not under parent, bundle not found). |
| simulated_profile_summary | object | Short summary of the simulated profile (primary, subtype, bundle keys; no PII). |
| live_profile_summary | object | Short summary of current live profile for comparison. |
| comparison_simulated | object | Optional; comparison data (e.g. top template/section keys) under simulated config. From Industry_Subtype_Comparison_Service or equivalent. |
| comparison_live | object | Optional; comparison data under live config. |
| warnings | list | Bounded list of warnings (e.g. inactive pack, deprecated bundle). |

- No persistent schema change; result is ephemeral and not stored.

---

## 4. Behavior

- **Input**: Simulation params: `alternate_primary_industry_key` (optional), `alternate_subtype_key` (optional), `alternate_starter_bundle_key` (optional). Empty or omitted = keep live value for that slot.
- **Build simulated profile**: Copy of live profile with overrides applied for each provided param. If "no industry" is desired, caller can pass empty string for primary (simulated profile then has no primary).
- **Validation**: Before running comparison, resolve refs against registries (pack registry, subtype registry, starter bundle registry). Invalid refs → `valid: false`, `invalid_refs` populated; no comparison run.
- **Comparison**: When valid, use existing read-only comparison services (e.g. Industry_Subtype_Comparison_Service::get_comparison(simulated_primary, simulated_subtype)) to obtain top templates/sections under simulated config. Optionally run same for live keys to return side-by-side.
- **Bounded**: Cap template/section list sizes; no unbounded expansion. One simulation run does not trigger plan generation or execution.

---

## 5. Isolation and safety

- **Live state**: Never read via simulation path in a way that mutates; never write. Profile repository, options, and caches are not modified by simulation.
- **Resolvers**: Existing resolvers and scoring services remain authoritative. Simulation passes a **simulated profile** (or simulated keys) into comparison logic only; it does not replace or bypass resolver contracts.
- **Cache**: Simulation must not key cache entries with simulated profile in a way that pollutes live cache. Prefer in-memory only for simulation run, or a dedicated simulation cache namespace that is not used by live flows.
- **Author sandbox**: What-if is for **operator** preview of alternate profile choices. Author sandbox remains for pack/bundle **author** dry-run validation of definitions.

---

## 6. Security and permissions

- **Admin-only**: Simulation is available only to users with admin/settings capability (e.g. same as Industry Profile or comparison screen).
- **No persistence**: Simulated state is never persisted to live profile or options.
- **Invalid refs**: Invalid simulated refs are reported in the result; no throw or broken UX. Safe fallback when refs are missing or inconsistent.

---

## 7. Integration

- **Comparison screen/services**: Industry_Subtype_Comparison_Service (or similar) can be called with simulated keys to produce comparison_simulated. What-if service composes these calls; it does not replace the comparison service.
- **Support/admin docs**: Document that what-if is available for previewing industry/subtype/bundle changes; no apply step. The Conversion goal comparison screen (Prompt 515) uses the simulation service to compare no-goal, current-goal, and alternate-goal scenarios for bundle and Build Plan posture.
- **Cache/docs**: Document that simulation uses in-memory or isolated context so cache and docs can state simulation isolation clearly.

---

## 8. Files

- **Service**: plugin/src/Domain/Industry/Reporting/Industry_What_If_Simulation_Service.php
- **Contract**: docs/contracts/industry-what-if-simulation-contract.md
