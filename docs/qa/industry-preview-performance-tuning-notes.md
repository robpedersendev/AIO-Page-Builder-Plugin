# Industry Preview/Detail Performance Tuning Notes (Prompt 452)

**Purpose:** Document the preview/detail pipeline for industry-aware section and page admin screens, what was tuned, and residual hot spots. Preview remains read-only and non-persistent; correctness is preserved.

---

## 1. Pipeline overview

- **Section detail:** `Industry_Section_Preview_Resolver::resolve( section_key, section_definition, all_sections )` is invoked from Section Template Detail screen. It: reads profile, resolves primary pack, runs section recommendation resolver, composes helper doc, resolves subtype context, builds subtype influence, and optionally runs substitute engine.
- **Page template detail:** `Industry_Page_Template_Preview_Resolver::resolve( template_key, template_definition, all_templates )` is invoked from Page Template Detail screen. Same shape: profile → pack → page recommendation resolver → one-pager composer → subtype context → subtype influence → optional substitutes.

---

## 2. Resolver ordering (unchanged)

Order is already sensible and preserved:

1. Profile read (once per resolve).
2. Pack lookup by primary industry (once).
3. Recommendation resolution (section or page) — cacheable.
4. Helper-doc or one-pager composition — cacheable.
5. Subtype resolution (once; may call profile again internally).
6. Subtype influence / overlay lookups.
7. Substitute suggestions (only when all_sections/all_templates provided).

No reordering was required.

---

## 3. Cache usage (tuning applied)

- **Section recommendation / Page template recommendation:** Already read from and write to `Industry_Read_Model_Cache_Service` when `Industry_Cache_Key_Builder` is provided (section_recommendation / page_template_recommendation scope). No change.
- **Helper-doc composition:** Previously only wrote to cache. **Tuning:** `Industry_Helper_Doc_Composer::compose()` now checks cache first; on hit returns `Composed_Helper_Doc_Result` from cached payload (composed_doc, base_documentation_id, overlay_applied, overlay_industry_key, section_key, compliance_warnings). Same key as used for set (for_helper_doc). Site scoping is applied by the cache service.
- **Page one-pager composition:** Previously only wrote to cache. **Tuning:** `Industry_Page_OnePager_Composer::compose()` now checks cache first; on hit returns `Composed_Page_OnePager_Result` from cached payload. Same key as used for set (for_page_onepager).

Cache invalidation remains per industry-cache-contract (profile save, pack toggle, etc.). No change to invalidation.

---

## 4. Duplicate work reduced

- **Repeated composition for same (section_key, industry_key, subtype_key) or (template_key, industry_key, subtype_key):** Avoided on second and subsequent requests by the new cache read in helper and one-pager composers. First request still does full composition and populates cache.
- **Profile:** Read once per resolve() in the preview resolver; subtype resolver may call profile_repository->get_profile() again — acceptable and cheap (options read). No change.
- **Pack registry get(primary):** Once per resolve. No duplicate.

---

## 5. Residual hot spots

- **Documentation_Registry:** Built inside the preview resolver factory in `Industry_Packs_Module` (new instance per container resolve). Registry/loader may hit filesystem for base docs. Consider reusing a shared Documentation_Registry if profiling shows this as a cost.
- **Subtype_resolver->resolve():** Calls profile again; low cost. If preview were ever called many times per request, consider passing subtype_context from the single profile read.
- **Compliance_warning_resolver->get_for_display( industry_key )::** Called during composition when cache misses. Could be cached per industry_key if it becomes a bottleneck; not changed in this pass.
- **Substitute_engine:** Runs only when all_sections/all_templates are provided; typically not on single section/page detail. No change.

---

## 6. Benchmarking

Use `Industry_Performance_Benchmark_Service::run_benchmark()` (scenarios `section_preview_resolution`, `page_preview_resolution`) to compare before/after. See [industry-performance-benchmark-protocol.md](industry-performance-benchmark-protocol.md).

---

## 7. References

- [industry-cache-contract.md](../contracts/industry-cache-contract.md) — Scopes, keying, invalidation.
- [template-preview-and-dummy-data-contract.md](../contracts/template-preview-and-dummy-data-contract.md) — Preview correctness and non-persistence.
- [industry-performance-benchmark-protocol.md](industry-performance-benchmark-protocol.md) — How to run performance benchmarks.
