# Page Template Inventory Appendix

**Spec**: §62.12 Page Template Inventory Appendix; §57.9 Documentation Standards; §60.6 Documentation Completion Requirements.

This appendix is **generated** from the live page template registry. Do not edit by hand; regenerate after library changes (see `Page_Template_Inventory_Appendix_Generator`). Regenerate after version or deprecation workflow updates so version and deprecation columns stay aligned (Prompt 189).

**See also**: [Glossary](glossary.md) for **page template**, **template_family**, **template_category_class**, **version block**, **deprecation block**, **one-pager**; [Data Schema Appendix](data-schema-appendix.md) for page template CPT schema and version/deprecation block shapes.

**Total page templates**: *(run generator to refresh; after PT-14 gap-closing batch expect 580)*. Bundled request-form template `pt_request_form` (archetype request_page) is included when seeded via Form_Template_Seeder or Page_Template_Registry_Service::ensure_bundled_form_templates (Prompt 227).

---

## Example row (page template)

| Key | Name | Purpose | Ordered sections | Optional sections | Hierarchy | One-pager | Version | Deprecation |
|-----|------|---------|------------------|-------------------|-----------|-----------|---------|-------------|
| pt_marketing_landing | Marketing Landing | Landing page for campaigns. | st01_hero_intro, st_cta_conversion, st_faq | st_faq | top_level, marketing | yes | 1 | active |

*(Full appendix is produced by `AIOPageBuilder\Domain\Registries\Docs\Page_Template_Inventory_Appendix_Generator::generate()`.)*
