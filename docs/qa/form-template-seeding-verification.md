# Form Template Seeding Verification

**Document type:** QA verification for bundled form template registry persistence (Prompt 227).  
**Purpose:** Confirm that form_section_ndr and pt_request_form are inserted/updated via the approved registry path, surfaced in admin and appendices, and idempotent.  
**Spec refs:** §12, §13, §52, §53.1–53.2, §59.4, §60.2, §60.5, §60.6.

---

## 1. Approved persistence path

- **Entry points:** Activation (Lifecycle_Manager seed_form_templates phase), first-time setup, or admin “Seed form section and request page template” on Settings (capability-gated, nonce-protected).
- **Implementation:** Form_Template_Seeder::run( Section_Template_Repository, Page_Template_Repository ) writes definitions to the same CPTs and meta as all other section/page templates. Alternatively, Section_Registry_Service::ensure_bundled_form_templates( Page_Template_Repository ) or Page_Template_Registry_Service::ensure_bundled_form_templates( Section_Template_Repository ) delegate to the seeder with the service’s repository. Admin seed action uses Section_Registry_Service::ensure_bundled_form_templates( page_repo ).
- **Idempotency:** save_definition() in both repositories looks up by internal_key; if a post exists for that key, it updates that post. Re-runs do not create duplicate section or page template rows.

---

## 2. Verification checklist

### 2.1 First insert

- [ ] Run seed (activation, or Settings “Seed form section and request page template”). No errors.
- [ ] Section template registry contains one section with internal_key `form_section_ndr`; category `form_embed`; embedded field_blueprint with form_provider, form_id, headline.
- [ ] Page template registry contains one page with internal_key `pt_request_form`; archetype `request_page`; ordered_sections references `form_section_ndr` (section_key in first item).

### 2.2 Idempotent rerun

- [ ] Run seed again (same or different entry point). No duplicate posts for form_section_ndr or pt_request_form.
- [ ] Section and page template counts for those keys remain one each. Definitions remain correct (category, archetype, ordered section reference).

### 2.3 Registry visibility

- [ ] Section Templates directory (admin) lists “Form section” (form_section_ndr); filter by category form_embed shows it.
- [ ] Page Templates directory lists “Request / contact page” (pt_request_form); filter by archetype request_page shows it.
- [ ] Section template detail screen opens for form_section_ndr; page template detail screen opens for pt_request_form.
- [ ] Template Compare can add form_section_ndr and pt_request_form to compare list and show them in the matrix.

### 2.4 Section reference in request page template

- [ ] Persisted pt_request_form definition has ordered_sections (or equivalent) with at least one item whose section_key is `form_section_ndr`.
- [ ] Build Plan / execution that uses pt_request_form resolves form_section_ndr from the section registry when assembling the page.

### 2.5 Inventory appendices and coverage

- [ ] Run Section_Inventory_Appendix_Generator and Page_Template_Inventory_Appendix_Generator (or equivalent). Output includes form_section_ndr and pt_request_form when they exist in the registry.
- [ ] Template library compliance / coverage manifests can include these templates (count toward form_embed and request_page totals per template-library-coverage-matrix).

---

## 3. Risk notes

- **Validator compatibility:** Form definitions must satisfy Section_Schema and Page_Template_Schema required fields. Form_Integration_Definitions is aligned; any new required field added to the schema must be added to the bundled definitions.
- **Lifecycle order:** Activation runs seed_form_templates after CPT registration; it does not use the registry service. If lifecycle is refactored to use Section_Registry_Service::ensure_bundled_form_templates, the same idempotent behavior must hold.
- **Export/restore:** Export includes section and page template definitions from the registry; restored sites will have form_section_ndr and pt_request_form if they were present at export. No separate form-template export path.
