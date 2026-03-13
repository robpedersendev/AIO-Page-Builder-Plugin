# ACF Page-Level Visibility Assignment Contract (Stub)

**Spec**: §20 Field Governance; §59.5 Rendering and ACF Phase (page assignment logic)

**Status**: Stub. Full contract for which ACF field groups are assigned to which page (derivation from page template / composition, section list) to be expanded.

**Large-scale extension**: **large-scale-acf-lpagery-binding-contract.md** §6.2–6.3 defines page-level visibility assignment at scale: assignment **derived** from template/composition section list; only groups for sections on that page are assigned; registration must not load all 250+ groups on every page; deterministic assignment. Implementation of visibility and assignment logic must satisfy those rules.

---

## Cross-references

- **large-scale-acf-lpagery-binding-contract.md**: Registration scaling, derivation from section list, performance.
- **rendering-contract.md**: Field data keying `group_aio_{section_key}`.
