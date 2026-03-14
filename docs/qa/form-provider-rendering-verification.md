# Form Provider Rendering Verification

**Document type:** QA verification for provider-backed form section rendering, preview, and asset detection (Prompt 229).  
**Purpose:** Confirm shortcode injection, no visible form_provider/form_id leakage, page-level aggregation, and scoped asset behavior.  
**Spec refs:** §7.5, §7.7, §17–19.12, §59.5, §60.5, §60.7.

---

## 1. Shortcode injection

- [ ] When a section has valid form_provider and form_id (registered provider, non-empty form_id), the assembly pipeline emits the provider shortcode (e.g. `[ndr_forms id="contact"]`) in the section inner HTML. Shortcode is output-escaped (e.g. esc_attr for attribute value).
- [ ] When form_provider is not registered or form_id is empty, no shortcode is emitted; no error is thrown. Section may still show headline/other fields.
- [ ] Shortcode survives into post_content where intended (durable block markup); do_blocks() runs so the shortcode is expanded on front-end view.

---

## 2. No visible form_provider / form_id text

- [ ] form_provider and form_id field values are never echoed as visible text in the block markup. They are skipped in field_values_to_inner_html (only shortcode or nothing is emitted for those keys).
- [ ] Preview and detail screens do not display raw form_provider or form_id as content; form binding panel shows them as metadata only (e.g. Section Template Detail form binding subsection).

---

## 3. Page-level form reference aggregation

- [ ] Page_Form_Reference_Aggregator::aggregate( ordered_section_results ) returns a list of unique (form_provider, form_id) pairs for sections that have both set and a registered provider. Invalid or empty refs are excluded.
- [ ] Page template detail state includes page_form_references when Page_Form_Reference_Aggregator is available; compare/detail views can use this for dependency display or asset scoping.
- [ ] Aggregation is additive and does not change stored content; used for preview metadata, diagnostics, and asset detection only.

---

## 4. Preview and detail behavior

- [ ] Provider-backed form sections in preview render with synthetic form_provider/form_id when provided by Synthetic_Preview_Data_Generator; shortcode is emitted when valid.
- [ ] Preview remains safe: no raw provider/system metadata beyond what an authorized operator needs; provider and form IDs are validated/sanitized before shortcode build.
- [ ] Detail/compare state can show provider dependency (page_form_references) without exposing secrets.

---

## 5. Asset loading scoping

- [ ] Provider assets (scripts/styles) load only on pages or previews that contain provider-backed form sections, when such scoping is implemented. Not loading on unrelated pages avoids regressions.
- [ ] If a provider slug–detection helper exists, it uses the aggregated page_form_references or equivalent so enqueue is page-scoped.

---

## 6. Risk notes

- **Single pipeline:** Form shortcode emission is part of the same Native_Block_Assembly_Pipeline used for all sections; no separate rendering path for forms.
- **Preview cache:** Cached preview HTML may contain the shortcode; cache invalidation follows template/version rules. No special form-specific cache key required.
- **Export/restore:** Stored form_provider and form_id in content are part of normal export; no separate form-reference payload.
