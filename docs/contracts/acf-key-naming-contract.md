# ACF Key Naming Contract (Stub)

**Spec**: §20 Field Governance; §18 CSS, ID, Class, and Attribute Contract (naming policy)

**Status**: Stub. Full contract for ACF field keys, group keys, and naming policy (e.g. `group_aio_{section_key}`, field name conventions) to be expanded.

**Large-scale extension**: **large-scale-acf-lpagery-binding-contract.md** requires **deterministic field naming** at scale; fixed naming policy; no ad-hoc or duplicate key patterns; cross-section reuse of field names (e.g. `headline`, `cta_text`) for shared purposes to support token maps and helpers. Loose or undocumented naming is disallowed (§5.3 invalid scaling patterns). Implementation of key naming must align with this contract and the large-scale binding contract.

---

## Cross-references

- **large-scale-acf-lpagery-binding-contract.md**: Deterministic naming, blueprint reuse, token-map consistency, invalid scaling patterns.
- **rendering-contract.md**: `group_aio_{section_key}` keying.
