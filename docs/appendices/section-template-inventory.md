# Section Template Inventory Appendix

**Spec**: §62.11 Section Template Inventory Appendix; §57.9 Documentation Standards; §60.6 Documentation Completion Requirements.

This appendix is **generated** from the live section template registry. Do not edit by hand; regenerate after library changes (see `Section_Inventory_Appendix_Generator`). Regenerate after version or deprecation workflow updates so version and deprecation columns stay aligned (Prompt 189).

**See also**: [Glossary](glossary.md) for **section template**, **section_purpose_family**, **version block**, **deprecation block**; [Data Schema Appendix](data-schema-appendix.md) for section CPT schema and version/deprecation block shapes.

**Total section templates**: *(run generator to refresh; after SEC-09 gap-closing batch expect 254)*. Bundled form template `form_section_ndr` (category form_embed) is included when seeded via Form_Template_Seeder or Section_Registry_Service::ensure_bundled_form_templates (Prompt 227).

---

## Example row (section)

| Key | Name | Purpose | Variants | Helper | Deprecation | Version |
|-----|------|---------|----------|--------|-------------|---------|
| st01_hero_intro | Hero Intro | Hero section with headline and optional CTA. | default, compact | yes | active | 1 |

*(Full appendix is produced by `AIOPageBuilder\Domain\Registries\Docs\Section_Inventory_Appendix_Generator::generate()`.)*
