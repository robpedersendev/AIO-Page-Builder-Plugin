# Industry Subtype Fallback Audit (Prompt 456)

**Purpose:** Audit fallback behavior when subtype selection is absent, invalid, deprecated, inactive, or incompatible with the selected industry. Ensures subtype support never destabilizes the parent-industry model.

---

## 1. Fallback matrix

| Area | Absent (no subtype_key) | Invalid (unknown / wrong parent) | Deprecated / inactive (status) | Expected behavior |
|------|-------------------------|----------------------------------|--------------------------------|-------------------|
| **Subtype resolver** | industry_subtype_key ''; has_valid_subtype false | Same; resolved_subtype null | Same (only STATUS_ACTIVE is valid) | Parent-only context |
| **Profile validator** | Valid when subtype empty | Errors or warnings; invalid ref should not crash | N/A (validator checks registry presence + parent match) | Safe validation; invalid cleared or warned |
| **Starter bundle registry** | get_for_industry(industry, '') → industry bundles | N/A (subtype_key passed through; no subtype bundles → industry fallback) | N/A | get_for_industry(industry, subtype): subtype bundles if any, else industry bundles |
| **Helper / one-pager composers** | subtype_key '' → no subtype overlay | Same; overlay lookup returns null for invalid | N/A | Base → industry overlay → subtype overlay (when valid) |
| **Section / page preview resolvers** | Subtype context from resolver; empty subtype → parent-only | Resolver returns has_valid_subtype false | Resolver returns has_valid_subtype false for non-active | Parent-only recommendation + composition |
| **Subtype comparison service** | has_subtype false; subtype_bundles empty; subtype_top_* empty | Same | Not explicitly checked (get_comparison uses registry get; parent match only) | Parent columns filled; subtype columns empty |
| **Build Plan scoring** | Subtype extender receives context; empty subtype → no subtype layer | Same | Resolver already filters non-active | Parent industry scoring only |
| **Diagnostics / health** | Profile may store subtype_key; diagnostics show resolved state | Invalid ref may appear in profile; health does not fail on invalid subtype | N/A | Bounded snapshot; no crash |
| **Bundle selection (profile)** | selected_starter_bundle_key can be any; bundle industry match checked | Health reports selected_starter_bundle_key not found / industry mismatch | N/A | Warnings only; no hard dependency on subtype |

---

## 2. Implementation notes

- **Industry_Subtype_Resolver::resolve_from_profile():** Returns has_valid_subtype true only when subtype exists in registry, parent_industry_key matches profile primary, and status === STATUS_ACTIVE. Otherwise effective_subtype_key '' and has_valid_subtype false. Covers absent, invalid, deprecated, draft.
- **Industry_Starter_Bundle_Registry::get_for_industry( industry_key, subtype_key ):**
  - Empty subtype_key → industry-scoped bundles only.
  - Non-empty subtype_key → subtype-scoped bundles for (industry_key, subtype_key) if any; otherwise industry-scoped bundles (fallback). No crash on invalid subtype key.
- **Industry_Subtype_Comparison_Service::get_comparison():** has_subtype true only when subtype_key exists in registry and parent matches. Otherwise parent_bundles and parent_top_* filled; subtype_* empty. No dead end.
- **Preview resolvers:** Use subtype context from Subtype_Resolver; when has_valid_subtype false, helper/onepager and recommendation use parent-only. Subtype influence section shows “none” when no valid subtype.
- **Profile validator:** Validates industry_subtype_key against subtype registry and parent_industry_key; invalid ref produces validation error/warning; profile save can clear or retain (implementation-specific); no crash.

---

## 3. Gaps and hardening

- **Subtype comparison service (hardened):** get_comparison() now requires subtype status === STATUS_ACTIVE (in addition to registry get and parent match). Deprecated or draft subtypes yield has_subtype false and subtype_bundles / subtype_top_* empty; parent-only columns filled. Aligned with Industry_Subtype_Resolver behavior.
- **Diagnostics:** Snapshot may include industry_subtype_key from profile even when invalid; resolved context (from Subtype_Resolver) would show has_valid_subtype false. No change required; bounded.
- **Health report:** Does not currently report “profile subtype key invalid or deprecated” as a dedicated warning. Could add in future; out of scope for this audit.

---

## 4. Tests

- **Industry_Subtype_Resolver_Test:** Covers valid subtype; mismatched parent (fallback); no subtype key (fallback); unknown subtype key (fallback); null registry (fallback); deprecated status (fallback). All pass.
- **Subtype comparison:** Consider adding test for deprecated subtype key → has_subtype false and subtype_bundles empty. (Optional hardening.)

---

## 5. References

- [industry-degraded-mode-contract.md](../contracts/industry-degraded-mode-contract.md) — Fail-safe and degraded-mode contract (Prompt 467); subtype and bundle fallback categories.
- [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md) — Fallback principles (§5, §6).
- [industry-subtype-schema.md](../schemas/industry-subtype-schema.md) — Status values (active, draft, deprecated).
- Industry_Subtype_Resolver, Industry_Starter_Bundle_Registry, Industry_Subtype_Comparison_Service, preview resolvers.
