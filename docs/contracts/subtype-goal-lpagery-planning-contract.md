# Combined Subtype + Goal LPagery Planning Contract (Prompt 550)

**Spec:** LPagery planning contracts; subtype LPagery rule contracts; conversion-goal contracts; LPagery binding contracts.

**Status:** Planning-only. Defines how subtype and goal layers jointly refine LPagery planning posture without altering LPagery execution contracts or token naming.

---

## 1. Purpose

- **Joint planning posture:** When both an industry subtype and a conversion goal are set, planning may combine subtype LPagery rules and goal-aware posture into a single **combined subtype+goal LPagery planning state** for Build Plans and explanation layers.
- **No execution changes:** LPagery execution, token binding, and field naming remain authoritative per existing contracts. This contract is advisory and planning-focused only.
- **Deterministic and bounded:** Precedence, conflict handling, and fallback are explicit so planners and Build Plans get predictable behavior.

---

## 2. Combined planning object / resolution rules

- **Inputs:** `industry_key`, `industry_subtype_key` (optional), `conversion_goal_key` (optional).
- **Output:** A **combined LPagery planning result** (or equivalent planning state) with:
  - **lpagery_posture:** Resolved from parent (industry) rules, then subtype rules (when present), then goal-aware refinement (when present), per precedence below.
  - **required_tokens / optional_tokens / suggested_page_families / warning_flags / hierarchy_guidance / weak_page_warnings:** Merged from applicable layers with conflict handling.

- **Resolver semantics:** A planner or advisor may implement a **combined subtype+goal resolver** that:
  1. Resolves **parent (industry)** LPagery rules for `industry_key`.
  2. Resolves **subtype** LPagery rules when `industry_subtype_key` is non-empty and valid for `industry_key`.
  3. Applies **goal-aware** refinement when `conversion_goal_key` is non-empty (e.g. goal-specific posture or token hints from conversion-goal or secondary-goal contracts, if defined).
  4. Merges results according to **precedence** and **conflict handling** below.

---

## 3. Precedence between subtype and goal

- **Base:** Parent (industry) LPagery rules are the foundation. Subtype and goal layers refine only when present and valid.
- **Subtype vs goal:** When both subtype and goal apply:
  - **Subtype** refines **after** parent and **before** goal in composition order (parent → subtype → goal).
  - **Goal** refines last; it may soften or tighten posture (e.g. “optional” when goal is lead_capture, “central” when goal is calls) per product rules. Goal layer does not override subtype’s required_tokens or hierarchy_guidance unless the contract explicitly allows goal overrides for specific fields.
- **Conflict:** If subtype and goal imply different postures (e.g. subtype says `central`, goal says `discouraged`), the **conflict handling** rule applies (see §4).

---

## 4. Conflict handling and warning behavior

- **Posture conflict:** When subtype and goal yield different lpagery_posture values, the resolver uses a **defined precedence**: e.g. `central` > `optional` > `discouraged`, or product may define “goal wins for posture” / “subtype wins for posture”. The chosen rule must be documented in the resolver implementation and in this contract’s appendix or implementation notes.
- **Token / guidance conflict:** required_tokens and hierarchy_guidance are **merged** (union); duplicates removed. If subtype and goal both add the same token, it appears once. No removal of tokens by a later layer unless explicitly allowed by schema.
- **Warnings:** warning_flags and weak_page_warnings are **additive** across layers. When a conflict is detected (e.g. posture mismatch), the resolver may add a **warning flag** (e.g. `subtype_goal_posture_mismatch`) so the planner or UI can surface it. No automatic block of planning or publication.

---

## 5. Fallback to single-layer LPagery rules

- **No subtype:** When `industry_subtype_key` is empty or invalid, combined result equals **parent + goal** (or parent only if no goal).
- **No goal:** When `conversion_goal_key` is empty or invalid, combined result equals **parent + subtype** (or parent only if no subtype).
- **Neither:** When neither subtype nor goal is set, combined result equals **parent (industry)** LPagery planning result only.
- **Invalid refs:** Invalid subtype_key (e.g. wrong parent industry) or invalid goal_key causes that layer to be skipped; no throw; safe fallback to the remaining layers.

---

## 6. Documentation-level examples

### Example 1: Subtype + goal, no conflict

- Industry: `plumber`; subtype: `commercial`; goal: `calls`.
- Parent rules: posture `optional`, required_tokens `['location_name']`.
- Subtype rules: posture `central`, add required_tokens `['service_area']`.
- Goal refinement: no posture change.
- **Combined:** posture `central` (subtype wins), required_tokens `['location_name', 'service_area']`.

### Example 2: Subtype + goal, posture conflict

- Industry: `realtor`; subtype: `buyer_agent`; goal: `lead_capture`.
- Subtype rules: posture `central`.
- Goal refinement: posture `optional` (lead_capture softens local-page emphasis).
- **Conflict:** subtype says central, goal says optional. Resolver uses **subtype wins** (or product rule: “goal wins for posture”). warning_flags may include `subtype_goal_posture_mismatch`.
- **Combined:** e.g. posture `central`, warning_flags `['subtype_goal_posture_mismatch']`.

### Example 3: Fallback when subtype invalid

- Industry: `plumber`; subtype_key: `unknown_subtype`; goal: `calls`.
- Subtype layer skipped (invalid ref). **Combined:** parent + goal only.

---

## 7. Limits and cross-references

- **Planning only:** No LPagery execution or token injection changes. No changes to token naming or binding contracts.
- **Reusable:** The model is intended for use by planners, Build Plans, and any UI that explains “why this LPagery posture” for a given industry/subtype/goal.
- **Schema:** If a formal combined planning state schema is added later, it lives in docs/schemas and is referenced from the data-schema appendix.
- **Cross-refs:** [industry-lpagery-planning-contract.md](industry-lpagery-planning-contract.md); [large-scale-acf-lpagery-binding-contract.md](large-scale-acf-lpagery-binding-contract.md); subtype LPagery rule contracts; conversion-goal contracts.
