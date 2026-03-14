# Form Provider Retrofit Impact Analysis

**Document type:** Retrofit impact analysis for form provider integration (Prompt 226).  
**Purpose:** Identify every subsystem affected by `form_provider` and `form_id` support so the integration is normalized into the existing architecture rather than remaining a bolt-on.  
**Spec refs:** §0.4, §0.10.8–0.10.10, §0.10.12, §20.1–20.3, §59.5, §60.4, §60.6.

---

## 1. Executive summary

The plugin already includes form provider integration: a form section template (`form_section_ndr`), a request/contact page template (`pt_request_form`), Form_Provider_Registry, Form_Integration_Definitions, Form_Template_Seeder, and rendering of form shortcodes in Native_Block_Assembly_Pipeline. This document audits all code paths touched by `form_provider` and `form_id`, lists gaps and duplication risks, and provides a dependency map and implementation plan for normalizing the contract. No runtime rendering, admin screens, or execution services are modified in this prompt; the deliverable is analysis and contract documentation.

---

## 2. Dependency map (subsystems affected)

| Subsystem | Touchpoints | Current state | Gap / risk |
|-----------|-------------|---------------|-------------|
| **Registries** | Section_Schema category `form_embed`; section definition `form_section_ndr` with embedded field_blueprint (form_provider, form_id, headline); page template `pt_request_form` with ordered section form_section_ndr; ExpansionPack/GapClosing/LPU batch references to `form_section_ndr` or `form_embed` | Section and page template definitions exist; category and keys are used. | Contract for `form_embed` and provider-backed templates was referenced in code but not written; backward compatibility for existing sections with different form slot patterns (e.g. LPU `form_embed_slot`) should be explicit. |
| **ACF blueprints** | Form_Integration_Definitions embeds field_blueprint with `field_form_provider`, `field_form_id`, `field_headline`. Section_Field_Blueprint_Service picks up embedded blueprints from section definitions (so form_section_ndr is included once seeded). Field_Key_Generator, registration, repair, migration verification, debug exporter all consume blueprints generically. | Form section blueprint is embedded in section definition; ACF registration and repair treat it like any other section-owned blueprint. | None; field names are stable and governed by Form_Provider_Registry constants. |
| **Rendering** | Native_Block_Assembly_Pipeline receives Form_Provider_Registry (optional); in field_values_to_inner_html, if form_provider and form_id present, builds shortcode via registry and emits it; form_provider/form_id are skipped for text HTML. Rendering_Provider wires form_provider_registry into the pipeline. | Rendering path is implemented; shortcode output is escaped via esc_attr. | Missing-provider or invalid form_id returns null shortcode; behavior (show nothing vs placeholder) should be stated in contract. |
| **Preview** | Synthetic preview data and section/page preview rendering use the same section definitions and ACF field values. Form section preview may render with synthetic form_provider/form_id; preview does not validate that the provider or form exists. | No form-specific preview logic; synthetic data can include form_provider/form_id. | Contract should define: preview may show shortcode or placeholder; no live form validation in preview. |
| **Page-template composition** | Page template `pt_request_form` aggregates one section (form_section_ndr). Compositions can reference form section like any other section. Build Plan and execution do not special-case form templates. | Normal registry and composition flow. | None. |
| **Build Plan / recommendation** | Template recommendation and Build Plan payloads use template keys and families; form_section_ndr and pt_request_form are part of the registry. No explicit “form provider” in recommendation payloads. | No form-specific recommendation logic. | Contract can state: recommendations may include form-bearing templates; no separate form_provider/form_id in plan envelope. |
| **Execution** | Execution creates/replaces pages using template keys and section ordering; ACF field values (including form_provider, form_id) are stored with the page. No execution-time validation of provider or form existence. | Execution is template-agnostic. | Contract: execution persists form_provider/form_id; validation of provider/form existence is optional and diagnostic only. |
| **Export / restore** | Section and page template definitions (including form section and request page template with embedded blueprint) are part of registry export. Form_provider and form_id are stored in post meta as ACF values; export includes registries and content. | Registries export includes form templates; restore re-imports definitions. | Contract: form references in exported content survive restore; no separate “form reference” export schema beyond registry + content. |
| **Diagnostics** | ACF diagnostics (blueprint health, registration, assignment) include all blueprints, so form section is included. No dedicated “form provider dependency” or “missing form” diagnostic today. | Generic ACF and registry diagnostics. | Contract: diagnostics may add classification of “third-party form dependencies” (provider/form_id) for support; not required for MVP. |
| **Capability / admin** | Seed form templates: Settings screen exposes “Seed form section and request page template”; action `admin_post_aio_seed_form_templates` with nonce; capability currently not explicitly scoped (follows Settings). Admin_Menu and Settings_Screen reference Form_Template_Seeder. | Nonce-protected; capability should be aligned with registry management. | Contract: seed action must be capability-gated (e.g. manage section/page templates or equivalent); document in admin-screen-inventory. |
| **Lifecycle** | Activation phase `seed_form_templates` runs Form_Template_Seeder after CPT registration. | Seeder runs on activation; idempotent overwrite for same keys. | None. |
| **Provider registry** | Form_Provider_Registry: register(provider_id, shortcode_tag, id_attr); build_shortcode(provider_id, form_id); get_registered_provider_ids(); NDR registered by default. Injected into Native_Block_Assembly_Pipeline. | Single registry; no UI to add providers yet. | Contract: future providers added via registry only; storage contract (form_provider, form_id) does not change when new providers are added. |

---

## 3. Code path inventory (form_provider / form_id)

- **Definition / schema:** Form_Integration_Definitions::form_section_definition() (field_blueprint with form_provider, form_id, headline); Form_Provider_Registry::FIELD_FORM_PROVIDER, FIELD_FORM_ID. Section_Schema allowed category `form_embed`. Legal_Policy_Utility and GapClosing batches use `form_embed` or reference form_section_ndr.
- **Persistence:** Section and page definitions stored via Section_Template_Repository and Page_Template_Repository (Form_Template_Seeder). ACF field values (form_provider, form_id) stored in post meta per normal ACF/section flow.
- **Rendering:** Native_Block_Assembly_Pipeline::field_values_to_inner_html() reads form_provider and form_id from field_values, calls Form_Provider_Registry::build_shortcode(), emits shortcode in block markup; form_provider/form_id excluded from text HTML.
- **Admin:** Settings_Screen: “Seed form section and request page template” form; Admin_Menu::handle_seed_form_templates() (nonce, then Form_Template_Seeder::run).
- **Lifecycle:** Lifecycle_Manager::run_activation_phase('seed_form_templates') loads Form_Integration_Definitions and Form_Template_Seeder, runs seeder.
- **Container:** Rendering_Provider registers form_provider_registry and passes it into the block assembly pipeline.

---

## 4. Gaps and alignment notes

- **Incomplete:** The code references `form-provider-integration-contract.md` in docblocks but that document did not exist; this retrofit creates it.
- **Missing-provider / invalid-form behavior:** build_shortcode returns null when provider unknown or form_id invalid; pipeline then does not emit a form shortcode. Contract must define: no fallback markup (or optional placeholder) and no runtime error; missing form is a content/configuration concern.
- **Duplicate patterns:** LPU uses `form_embed_slot` (free-text shortcode/block identifier) while form section uses structured form_provider + form_id. Contract should state: provider-backed form sections use form_provider and form_id only; other sections may use form_embed category with different field semantics (e.g. slot).
- **Security:** Provider and form_id are validated/sanitized in Form_Provider_Registry (sanitize_provider_id, sanitize_form_id with pattern); shortcode attribute is output-escaped. Contract must state: AIO validates its own provider IDs and form IDs before rendering or saving; provider plugins are external dependencies.
- **Export/import:** Form references are part of section/page definitions and ACF field values; no separate form-reference export schema. Contract confirms: export/restore persistence of form references is via existing registry and content export.

---

## 5. Implementation plan (documentation and contract only)

1. **Create form-provider-integration-contract.md** – Canonical contract: stable field names (form_provider, form_id), category form_embed, rendering behavior, provider registry responsibilities, fallback/missing-provider behavior, diagnostics expectations, survivability (export/restore), security (validation, capability, nonce).
2. **Update admin-screen-inventory.md** – Document the form template seed action (Settings screen), intended capability, and reference to form-provider-integration-contract.
3. **Update template-library-coverage-matrix.md** – Already references form_embed and form_section_ndr/pt_request_form; add cross-reference to form-provider-integration-contract if not already explicit.
4. **Update data-schema-appendix.md** – Add subsection for form provider integration: form_provider, form_id, form_embed category, page-template aggregation of provider-backed form references.
5. **Update glossary.md** – Add entries: form provider, form_id, form_embed (category), provider-backed form section, request-form page template.
6. **Retrofit QA checklist** – Add section below to prove all identified subsystems were audited and no hidden provider-specific assumptions remain undocumented.

---

## 6. Retrofit QA checklist

Use this checklist to verify that form provider integration is fully audited and documented. No hidden provider-specific assumptions should remain outside the contract.

- [ ] **Registries:** Section_Schema category `form_embed` and section key `form_section_ndr`, page template key `pt_request_form` are documented; usage in batch definitions (ExpansionPack, GapClosing, LPU) is consistent with contract.
- [ ] **ACF blueprints:** Field names form_provider and form_id are defined in contract; embedded blueprint in form section definition is the only source for these fields in provider-backed form sections.
- [ ] **Rendering:** Native_Block_Assembly_Pipeline form shortcode emission is described; behavior when provider missing or form_id invalid is stated (null shortcode, no throw).
- [ ] **Preview:** Contract states that preview may use synthetic form_provider/form_id and does not validate provider/form existence.
- [ ] **Build Plan / execution:** Contract states that Build Plan and execution do not add form-specific envelope fields; form references are in template definitions and ACF values only.
- [ ] **Export/restore:** Contract states that form references persist via registry and content export; no separate form-reference schema.
- [ ] **Diagnostics:** Contract states whether diagnostics classify third-party form dependencies (optional/future).
- [ ] **Admin / capability:** Seed form templates action is capability-gated and nonce-protected; documented in admin-screen-inventory.
- [ ] **Security:** Contract states that AIO validates provider ID and form ID before rendering or saving; provider plugins are external; no secrets in reports.
- [ ] **Extensibility:** Contract states that new providers are added via Form_Provider_Registry only; storage contract (form_provider, form_id) remains stable.
