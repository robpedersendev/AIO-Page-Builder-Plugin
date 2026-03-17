# Subtype Starter Bundle Contract (Prompt 428)

**Spec**: industry-starter-bundle-schema.md; industry-subtype-extension-contract.md; industry-pack-extension-contract.md.

**Status**: Contract. Defines how subtype-scoped starter bundles extend the industry starter bundle model so subtypes can offer refined starting structures without duplicating full industry bundles.

---

## 1. Purpose

- Allow **subtype-scoped** starter bundles: bundles that apply when a given industry **and** subtype are selected (e.g. realtor + buyer_agent, plumber + commercial).
- Preserve **parent-industry bundles** as the base layer; subtype bundles are an **additive, optional** layer.
- Document **fallback**: when no subtype bundle exists for (industry, subtype), callers receive **industry-level bundles** only. No duplicate bundle explosion; subtype bundles are curated where subtype nuance justifies a different starting set.

---

## 2. Principles

- **Parent-industry bundles** remain the default. Subtype bundles are returned only when they exist for the requested (industry_key, subtype_key) and are active.
- **Fallback**: `get_for_industry(industry_key, subtype_key)` returns subtype-scoped bundles when any exist; otherwise returns industry-scoped bundles (subtype_key ignored for filtering). So the API is backward-compatible and safe when no subtype bundles are defined.
- **Advisory**: Bundle selection remains advisory until converted into a Build Plan; no auto-apply. Same as industry bundles.
- **Exportable / versioned**: Subtype bundle definitions use the same schema versioning and export rules as industry starter bundles; optional `subtype_key` is part of the bundle object.

---

## 3. Bundle object extension

- **industry-starter-bundle-schema.md** is extended with an optional field:
  - **subtype_key** (string, optional): When set, the bundle is **subtype-scoped**. It belongs to the given industry_key and is intended for the given subtype (e.g. `realtor_buyer_agent`). Must match a valid subtype_key from Industry_Subtype_Registry whose parent_industry_key equals the bundle’s industry_key. Validation may enforce parent match at load or at use time; invalid refs are skipped at load.
- Bundles **without** subtype_key behave exactly as today: industry-scoped only, returned by `get_for_industry(industry_key)` or `get_for_industry(industry_key, subtype_key)` when no subtype-scoped bundles exist for that (industry, subtype).

---

## 4. Registry behavior

- **Industry_Starter_Bundle_Registry**:
  - **get_for_industry(string $industry_key, string $subtype_key = '')**: When `subtype_key` is non-empty, first return active bundles that have both `industry_key` and `subtype_key` matching. If none exist, return active bundles for `industry_key` only (fallback). When `subtype_key` is empty, return active bundles for `industry_key` only (unchanged behavior).
  - **load() / validate_bundle() / normalize_bundle()**: Accept optional `subtype_key`; bundles without it remain valid. Invalid subtype_key (e.g. pattern/length) does not invalidate the bundle if subtype_key is optional; unknown or parent-mismatch subtype_key may cause the bundle to be skipped at load per product policy (or accepted and filtered at get_for_industry). This contract recommends: if subtype_key is present, it must match KEY_PATTERN and length; parent match can be enforced at load or at resolution.
- **No new mutation surfaces**: Registry remains read-only; no public API to add/change bundles at runtime.

---

## 5. Fallback summary

| Request | Subtype bundles exist for (industry, subtype)? | Returned |
|--------|------------------------------------------------|----------|
| get_for_industry('realtor', '') | N/A | Industry bundles for realtor |
| get_for_industry('realtor', 'realtor_buyer_agent') | Yes | Subtype-scoped bundles for realtor + realtor_buyer_agent |
| get_for_industry('realtor', 'realtor_buyer_agent') | No | Industry bundles for realtor (fallback) |

---

## 6. Security and constraints

- **No arbitrary execution** or bundle mutation; invalid subtype refs fail safely (bundle skipped at load or excluded from subtype filter).
- **Registry-driven only**: Subtype bundle behavior is determined by registered definitions; no client-supplied payload that adds or changes bundles.
- **Stable keys and versioning**: Same as industry-starter-bundle-schema; subtype_key is a stable key part for filtering only.

---

## 7. Cross-references

- **Schema**: industry-starter-bundle-schema.md (optional subtype_key).
- **Subtype extension**: industry-subtype-extension-contract.md (starter bundle ref, overlay scope).
- **Registry**: Industry_Starter_Bundle_Registry (get_for_industry with optional subtype_key).
- **Bundle-to-Build Plan**: **Industry_Subtype_Starter_Bundle_To_Build_Plan_Service** converts a subtype or industry starter bundle into a draft Build Plan; when the bundle has subtype_key, the plan stores source_industry_subtype_key for rationale. Fallback to parent industry bundle when the requested bundle key is invalid or inactive and industry_key is in context. Plans remain reviewable and approval-gated.
- **Combined subtype+goal overlays:** For exceptional (subtype, goal) pairs, optional joint overlays are defined by [subtype-goal-starter-bundle-contract.md](subtype-goal-starter-bundle-contract.md) (Prompt 551). Subtype bundles and goal overlays remain the primary layers; combined overlays are bounded and admission-gated.
