# Styling Subsystem Retrofit Impact Analysis

**Spec**: §17.10, §18; [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md)  
**Purpose**: Identify all plugin surfaces that the styling subsystem will extend or touch. No implementation in this document.

---

## 1. Impact Summary

The styling subsystem is additive. It will **extend** (not replace) existing rendering, options, export, preview, and diagnostics. Existing selector and token **names** remain unchanged; only value sources and optional metadata are added.

---

## 2. Rendering

| Surface | Location / component | Impact |
|---------|----------------------|--------|
| Section rendering | `plugin/src/Domain/Rendering/Section/Section_Renderer_Base.php`, `Section_Render_Context.php`, `Section_Render_Context_Builder.php` | Future: optional style context (token values, scope) for wrapper; no change to markup or selector output. |
| Page assembly | `Page_Instantiator.php`, `Page_Block_Assembly_Result.php`, `Native_Block_Assembly_Pipeline.php` | Future: assembly may receive global/per-entity style context for emitter; no change to block serialization or post_content. |
| Block output | `Section_Render_Result`, block serialization | No structural change; styling applied outside block markup (stylesheet or root-scoped inline). |
| Assets | `Render_Asset_Controller.php`, `Render_Asset_Requirements.php` | Future: optional stylesheet enqueue or inline style block from styling subsystem; version/cache key. |
| Wrapper attributes | Class/ID per css-selector-contract | Unchanged; styling subsystem does not add/rename classes or IDs. |

---

## 3. Options and Storage

| Surface | Current | Impact |
|---------|---------|--------|
| Applied design tokens | `Token_Set_Job_Service::OPTION_APPLIED_TOKENS` (`aio_applied_design_tokens`); get_option/update_option in Token_Set_Job_Service | Subsystem aligns with this option; may add version/cache marker or wrapper key; no change to key name or structure without schema revision. |
| Other options | Option_Names (reporting, heartbeat, install notice, etc.) | Styling may add new option keys for global styling settings; documented in data-schema-appendix. |
| Per-entity style payloads | Not present | Future: optional storage (e.g. post meta or dedicated structure) for page/composition/section overrides; plugin-owned; documented when added. |

---

## 4. Export and Support Bundles

| Surface | Location | Impact |
|---------|----------|--------|
| Support package / export | `Support_Package_Generator.php`, `Template_Library_Support_Summary_Builder.php`, `ExportRestore_Provider.php` | Future: optional inclusion of bounded styling summary (e.g. global token set present, spec version); redacted; no secrets. |
| Template library export | `Template_Library_Export_Validator.php`, export manifest | Styling metadata (if any) additive to manifest schema; documented. |
| Restore | Restore pipeline | Optional reapplication of styling payloads from export; sanitization required. |

---

## 5. Preview

| Surface | Location | Impact |
|---------|----------|--------|
| Preview cache | `Preview_Cache_Service.php`, `Preview_Cache_Record.php` | Future: preview render may receive style context (token values) so previews reflect current styling; cache key may include style version if needed. |
| Preview render | `Render_Preview_Helper.php`, synthetic data | Optional style context passed to render path; no change to template structure or selector output. |
| Section/page preview | Section and page template detail screens | Previews may show token-driven appearance; no new selectors or markup. |

---

## 6. Diagnostics and Logs

| Surface | Location | Impact |
|---------|----------|--------|
| Rendering diagnostics | `Rendering_Diagnostics_Service.php`, `Content_Survivability_Checker.php`, `Content_Survivability_Result.php` | Styling does not alter survivability criteria; optional diagnostic field (e.g. styling_subsystem_version) additive. |
| Logs / reporting | Reporting log, heartbeat, error report | No styling payload in logs; optional high-level “styling applied” flag in diagnostics summary only; no token values or secrets. |
| Admin diagnostics screens | Diagnostics menu, Logs screen, etc. | Optional read-only styling summary (e.g. spec version, global token keys present); no raw config or secrets. |

---

## 7. Build Plan and Execution

| Surface | Location | Impact |
|---------|----------|--------|
| Design token step | `Tokens_Step_UI_Service.php`, Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS, Token_Set_Job_Service | Already present; styling subsystem consumes same option and token names; no breaking change. |
| Token set job | `Token_Set_Job_Service.php`, Bulk_Executor (APPLY_TOKEN_SET) | Continues to write to `aio_applied_design_tokens`; future emitter reads from same. |
| Rollback / snapshots | `Rollback_Token_Set_Handler.php`, Operational_Snapshot_Schema (token_set) | Unchanged; snapshot/rollback remain valid for token values. |

---

## 8. Hooks and Registries

| Surface | Impact |
|---------|--------|
| Section registry | No change to section definition schema for structural selectors; optional future field for “style scope” or token usage is additive. |
| Page template registry | No change to template structure; optional style overrides per template are additive and separate from ordered_sections. |
| Asset registration | Future: register stylesheet or inline style handle from styling subsystem; standard wp_enqueue or inline API. |

---

## 9. Dependency Map (Conceptual)

```
css-selector-contract.md (fixed selectors & token names)
         |
         v
styling-subsystem-contract.md (layers, root scope, sanitization, portability)
         |
         +---> Options (aio_applied_design_tokens; future global/per-entity)
         +---> Specs (pb-style-*-spec.json) + style registry
         +---> Sanitizers (whitelist vs specs)
         +---> Emitters (stylesheet / scoped inline only)
         |
         v
Rendering: Section_Render_Context, Page assembly, Render_Asset_Controller
Preview:   Preview_Cache_Service, Render_Preview_Helper
Export:    Support_Package_Generator, Template_Library_Support_Summary_Builder
Diagnostics: Optional styling summary in diagnostics/support bundle
```

---

## 10. QA Checklist (Manual Verification)

Use this checklist to confirm the impact analysis and contract alignment:

- [ ] **Rendering**: Impact analysis lists all section and page render entry points; contract states no change to post_content or selector output.
- [ ] **Settings/options**: aio_applied_design_tokens and any future styling options are documented; no structural change to existing option without schema revision.
- [ ] **Previews**: Preview cache and render path are identified; styling context is optional and does not change template markup.
- [ ] **Export/restore**: Support bundle and export manifest extension points are identified; styling data bounded and redacted.
- [ ] **Uninstall/portability**: Contract and PORTABILITY_AND_UNINSTALL state built content survives; styling options may be removed on uninstall.
- [ ] **Selector/token preservation**: Contract and impact analysis explicitly preserve aio-* selectors and --aio-* token **names**; no new structural selectors or token names.
- [ ] **No content injection**: Contract explicitly forbids editing saved content structure or injecting styles into block markup.

---

## 11. Cross-References

- [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md)
- [css-selector-contract.md](../contracts/css-selector-contract.md)
- [rendering-contract.md](../contracts/rendering-contract.md)
- [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md)

---

## 12. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 242 | Initial retrofit impact analysis. |
