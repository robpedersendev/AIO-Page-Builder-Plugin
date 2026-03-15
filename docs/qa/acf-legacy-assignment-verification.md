# ACF Conditional Registration — Legacy Assignment Verification

**Prompt**: 309  
**Contracts**: acf-conditional-registration-contract.md, acf-page-visibility-contract.md

---

## 1. Purpose

Verifies that the scoped-registration model works correctly for legacy pages and incomplete assignment-map scenarios. Defines safe fallback behavior and confirms that no silent full registration is used as a default.

---

## 2. Legacy page states

| State | Description | Resolution path |
|-------|-------------|-----------------|
| **No assignment data** | Page has no assignment map entries (PAGE_TEMPLATE, PAGE_COMPOSITION, PAGE_FIELD_GROUP). May be pre-retrofit or never built from template/composition. | `get_visible_groups_for_page()` returns empty (or derived from template/composition if stored elsewhere). Section keys = empty → register **no** groups. |
| **Template/composition ref only** | Page has PAGE_TEMPLATE or PAGE_COMPOSITION ref but no or partial PAGE_FIELD_GROUP refs. | Service derives visible groups from template/composition; section keys resolved; scoped registration runs. |
| **Incomplete migration** | Assignment map partially populated or corrupted. | Resolver returns what it can; missing data → empty section keys → register no groups. No full registration. |

---

## 3. Safe fallback behavior (current)

- When **section keys resolve to empty** (no assignment, or derivation returns nothing): `run_registration()` registers **zero** groups. Edit screen may show no section-owned ACF groups until assignment is (re)applied.
- **No silent full registration**: The bootstrap controller does not fall back to `run_full_registration()` when assignment is missing or incomplete. See acf-conditional-registration-contract.md §7 Safe-failure behavior.
- **Field values**: Existing post meta (ACF field values) remain unchanged. Only which groups are *registered* for the edit UI is affected; saved data is not cleared.

---

## 4. Verification

- For a page with no assignment: open edit screen; expect zero section-owned groups registered (inspection utility or diagnostics shows section_key_count 0). No bulk section load.
- For a page with template/composition ref but legacy state: if derivation succeeds, scoped registration runs; if derivation fails or returns empty, zero groups. No full registration in either case.
- Manual QA: legacy page edit does not trigger full registration; repair guidance (reassign from template/composition) restores expected groups.

---

## 5. Cross-references

- acf-conditional-registration-contract.md §4.2, §7
- acf-page-visibility-contract.md
- acf-legacy-page-repair-guide.md (operations)
