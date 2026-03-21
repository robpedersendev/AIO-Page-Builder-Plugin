# Template Library — Editor Guide

**Audience:** Content editors and implementers who choose templates, use one-pagers, and need helper documentation.  
**Spec:** §0.10.7, §1.9.4, §10.7 (Documentation object), §57.9, §60.6.  
**Purpose:** Practical guidance for choosing section and page templates, understanding one-pagers and compositions, and using helper docs. Product-accurate; no aspirational behavior.  
**Knowledge base:** [Template system overview](../kb/templates/template-system-overview.md); [KB index](../kb/index.md); [FILE_MAP.md](../kb/FILE_MAP.md) §7 (helper / documentation detail route).

---

## 1. Who this is for

- **Content editors** who work with pages built from the template library.
- **Implementers** who select section or page templates for plans and compositions.
- **Anyone** who needs to understand what a template does, how one-pagers work, and where to find helper documentation.

This guide does not replace the operator guide for admin workflows (directories, compare, compositions builder). It focuses on **choice, meaning, and documentation** around templates.

---

## 2. Where templates live (quick reference)

- **Section templates:** Reusable content blocks (hero, CTA, feature, legal, etc.). Browsed under **AIO Page Builder → Section Templates**. Each has an internal key, purpose family, CTA classification, and optional helper doc.
- **Page templates:** Full-page structures (e.g. one-pager, hub, child detail). Browsed under **AIO Page Builder → Page Templates**. Each has an internal key, category, and optional source composition.
- **Compositions:** Ordered lists of section templates that form a single page (e.g. one-pager). Under **AIO Page Builder → Compositions**. Built with category- and CTA-aware section selection.

You **choose** templates when building compositions or when the Build Plan suggests templates for new or updated pages. You do **not** edit template definitions in this workflow; you select from the registry.

---

## 3. Choosing section templates

- **By purpose:** Use the Section Templates directory. Filter by purpose family (e.g. hero, CTA, feature-benefit) and CTA classification (CTA vs non-CTA) to match the role you need (e.g. one CTA section, several non-CTA sections).
- **By category:** Categories align with content type (e.g. hero_intro, cta, legal). Use category and search to find a section that fits the message or layout you want.
- **Helper doc:** Many section templates have a **helper reference** (helper doc). In the directory or detail screen, use the helper link to open the one-pager or guidance for that section. Helper docs explain intent, suggested copy, and usage.
- **Version and status:** Prefer **active** templates. **Deprecated** templates are still viewable; the UI may show a replacement suggestion. Use the replacement when planning new content unless there is a reason to keep the deprecated one.
- **Preview:** Open **View** (detail) to see a **preview** with dummy content. Preview shows layout and structure, not your real content. Use it to compare alternatives before selecting.

---

## 4. Choosing page templates

- **User-facing KB:** [page-templates-deep-dive.md](../kb/templates/page-templates-deep-dive.md).
- **By category and family:** Page Templates directory is organized by category and family. Use it to find one-pagers, hubs, child-detail pages, etc.
- **By composition source:** Some page templates are tied to a **composition** (ordered section list). In that case, the page template is effectively a one-pager built from specific sections. Detail screen shows composition/section summary.
- **Preview:** Use **View** on a page template to see the full-page preview (synthetic data). Helps compare options before committing to a plan.
- **Compare:** Add up to 10 section or 10 page templates to **Template Compare** to see them side by side. Observational only; final selection happens in the Build Plan or composition builder.

---

## 5. One-pagers and compositions

- **User-facing KB:** [compositions-deep-dive.md](../kb/templates/compositions-deep-dive.md); page-level patterns also [page-templates-deep-dive.md](../kb/templates/page-templates-deep-dive.md).
- **One-pager:** A single-page layout built from an **ordered list of section templates**. In this product, one-pagers are implemented as **compositions** (or page templates that reference a composition). You do not "create a one-pager" as a separate object; you create or select a **composition** that defines the section order.
- **Compositions screen:** **AIO Page Builder → Compositions**. List shows existing compositions; **Build composition** opens the builder. The builder shows **current sections**, **CTA guidance**, **validation**, and a **filtered section library**; persisted changes follow **Compositions API or Settings** (per on-screen guidance). **CTA rules** (e.g. bottom-of-page CTA, non-adjacent CTAs) are enforced.
- **Using a composition:** Once a composition is saved, it can be used as the structure for a page (e.g. via Build Plan or template assignment). The same composition can back a page template so that "choose this page template" means "use this section order."
- **No freeform layout:** Compositions are built only from **registered section templates**. You cannot paste arbitrary HTML or add unregistered blocks. This keeps output predictable and CTA-law compliant.

---

## 6. Helper documentation

- **What it is:** Helper docs are guidance artifacts (one-pager materials, usage notes) associated with section templates (and optionally page templates). They are represented in the product (Documentation object, §10.7) and linked from the template (e.g. **helper_ref**).
- **Where to see it:** In the Section (or Page) Template directory, a row may show a helper link. In the **detail** screen, the metadata panel includes a **helper-doc** link when the template has a helper reference. Use it to open the guidance for that template.
- **Content:** Helper docs explain purpose, suggested copy, variants, and how to use the section in a page. They do not replace the template definition; they support editors and implementers in choosing and filling content.
- **If no helper:** Not every template has a helper. Use name, purpose summary, category, and preview to decide.

---

## 7. CTA rules (editor-relevant)

- **CTA sections** are those classified as call-to-action (e.g. signup, contact). **Non-CTA** sections are everything else (hero, feature, legal, etc.).
- **Rules** (contract): Page-level CTA count, bottom-of-page CTA, and non-adjacent CTA placement are enforced. The composition builder and validators will show errors if you break these rules.
- **As an editor:** When building a composition, add at least one CTA where the rules require it (e.g. bottom of page), and avoid stacking multiple CTAs back-to-back unless the rules allow it. The UI and validation status guide you.

---

## 8. Version and deprecation (editor-relevant)

- **Version:** Templates can have a version (e.g. 1, 2). Shown in directory and detail. Prefer newer versions when the plan or composition allows.
- **Deprecated:** A template may be marked deprecated with a reason and optional replacement key(s). You can still view and use it, but for **new** content prefer the replacement. Deprecation is shown in metadata and possibly in directory status.
- **No automatic migration:** Choosing a deprecated template does not auto-replace it with the replacement. You must select the replacement template explicitly when creating or editing a plan or composition.

---

## 9. Cross-references

| Need | Doc or screen |
|------|----------------|
| Operating directories, compare, compositions builder | [template-library-operator-guide.md](template-library-operator-guide.md) |
| Support and diagnostics for template library | [template-library-support-guide.md](template-library-support-guide.md) |
| Build Plan approval and execution | [admin-operator-guide.md](admin-operator-guide.md) §6–§7 |
| End-user workflow (if applicable) | [end-user-workflow-guide.md](end-user-workflow-guide.md) |

---

## 10. Limitations (do not assume otherwise)

- **Preview = synthetic data:** Previews use dummy content. They show structure and layout, not your site’s real text or media.
- **Selection only:** Editors and implementers **select** templates from the registry. They do not create or edit template definitions (name, schema, fields) in the editor workflow; that is a governed/operator concern.
- **Compositions are governed:** You cannot add arbitrary blocks or HTML to a composition. Section set and order must come from the section template registry and respect CTA rules.
