# Template Library — Operator Guide

**Audience:** Administrators and operators managing the expanded template library.  
**Spec:** §0.10.7, §1.9.4, §49, §50, §55.8, §57.9, §60.6.  
**Purpose:** How to browse, compare, preview, compose, and operate the section/page template directories, detail screens, compare workspace, and compositions. Product-accurate; no aspirational behavior.  
**Knowledge base:** [KB index](../kb/index.md); template workflows in [FILE_MAP.md](../kb/FILE_MAP.md) §7.

---

## 1. Menu and screens

Under **AIO Page Builder**:

| Menu label | Screen slug | Purpose |
|------------|-------------|---------|
| Page Templates | `aio-page-builder-page-templates` | Browse page templates by category and family. |
| Section Templates | `aio-page-builder-section-templates` | Browse section templates by purpose family and CTA/variant. |
| Template Compare | `aio-page-builder-template-compare` | Side-by-side comparison of section or page templates (observational only). |
| Compositions | `aio-page-builder-compositions` | List and build governed custom compositions from section templates. |

**Detail screens** (no menu entry; opened via **View** from directory or links):

- **Section Template Detail** — `admin.php?page=aio-page-builder-section-template-detail&section=<key>`
- **Page Template Detail** — `admin.php?page=aio-page-builder-page-template-detail&template=<key>`

**Capabilities:** Section templates and directory/detail use `aio_manage_section_templates`. Page templates and compare use `aio_manage_page_templates`. Compositions use `aio_manage_compositions`. Without the capability, the user cannot access the screen.

---

## 2. Section Templates directory

- **Screen:** **AIO Page Builder → Section Templates** (`aio-page-builder-section-templates`).
- **Hierarchy:** Root (purpose-family tree) → Purpose (CTA/variant nodes) → List (section rows with key, name, category, status, version, helper, compare links).
- **Filters:** `purpose_family`, `cta_classification`, `variation_family_key`, `status`, `search`, `all=1` to show all. Pagination: `paged`, `per_page` (capped by large-library limits).
- **Breadcrumbs:** Navigate back up the hierarchy.
- **Actions per row:** **View** (detail), **Add to compare** / **Remove from compare**, helper-doc link when template has a helper reference.
- **No execution:** Directory is observational. Building pages happens from Build Plans, not from this screen.
- **Form sections:** Section templates with category **form_embed** (e.g. Form section) embed a form from a registered provider. On the **Section Template Detail** screen, a **Form binding** panel shows the current form provider, form identifier, and shortcode preview when valid. Set form_provider and form_id when editing a page that uses the form section (ACF fields). See [form-provider-operator-guide.md](form-provider-operator-guide.md) and [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md).

---

## 3. Page Templates directory

- **Screen:** **AIO Page Builder → Page Templates** (`aio-page-builder-page-templates`).
- **Hierarchy:** Root (category tree) → Category (family list) → List (template rows with key, name, category, status, version, compare links).
- **Filters:** Category, family, status, search, pagination (same pattern as section directory).
- **Actions per row:** **View** (detail), **Add to compare** / **Remove from compare**.
- **No execution:** Observational. Page creation is via Build Plan execution.

---

## 4. Template Compare workspace

- **Screen:** **AIO Page Builder → Template Compare** (`aio-page-builder-template-compare`).
- **Type:** Section templates or Page templates (switcher at top).
- **Compare list:** Stored in user meta (site-scoped in multisite; see [template-ecosystem-multisite-site-isolation-report.md](../qa/template-ecosystem-multisite-site-isolation-report.md)). **Maximum 10 items** per type for performance; adding when full does not add.
- **Adding/removing:** From directory rows or detail screen: **Add to compare** / **Remove from compare**. URLs use nonce; list updates on load.
- **Content:** Side-by-side metadata and compact preview excerpts. **Observational only** — no execution, no Build Plan mutation, no apply-to-page.
- **Links:** Quick links to Section Templates directory and Page Templates directory.

---

## 5. Section and Page Template Detail screens

**Section Template Detail**

- **URL:** `admin.php?page=aio-page-builder-section-template-detail&section=<internal_key>`.
- **Content:** Breadcrumbs, metadata panel (name, purpose family, CTA classification, category, status, version, deprecation if set, compatibility notes, field summary, helper-doc link), and **rendered preview** using synthetic data and the real section renderer.
- **Preview:** Safe for viewing only; no insertion or publishing. Optional `reduced_motion=1` for reduced-motion preference.
- **Compare:** **Add to compare** / **Remove from compare** / **Compare workspace** link.
- **Back:** **Back to Section Templates** to directory.

**Page Template Detail**

- **URL:** `admin.php?page=aio-page-builder-page-template-detail&template=<internal_key>`.
- **Content:** Similar layout: metadata (name, category, template family, status, version, deprecation), composition/section summary, and **rendered preview** (synthetic context).
- **Compare:** Same compare links as section.
- **Back:** **Back to Page Templates**.

**Version and deprecation:** Shown in metadata when present. Deprecated templates are still viewable; replacement suggestions may be shown. Do not remove deprecated templates from the registry without a governed process.

---

## 6. Compositions

- **Screen:** **AIO Page Builder → Compositions** (`aio-page-builder-compositions`).
- **List view:** Table of compositions (Name, ID, Status, Validation, Sections count, Source template, **Edit**). **Build composition** button to create or open builder.
- **Build view:** `view=build` or `view=build&composition_id=<id>`. Governed builder: section selection is **category- and CTA-aware**; no freeform drag-drop of arbitrary content. Section library below for adding sections; order and validation follow composition schema and CTA rules.
- **CTA rules:** Compositions respect CTA sequencing and placement rules (e.g. bottom-of-page CTA, non-adjacent CTAs). Validation status reflects rule compliance.
- **One-pager readiness:** Compositions can be used as one-pager-style page templates; they are assembled from registered section templates only.
- **Save:** Compositions are saved via Compositions API or Settings; the UI indicates save handling.

---

## 7. CTA rules (operator-relevant)

- Section templates have **CTA classification** (e.g. CTA vs non-CTA). Page templates and compositions enforce **CTA sequencing and placement** rules (contract: cta-sequencing-and-placement).
- The directory and composition builder do not allow bypassing these rules. Validation errors appear when a composition violates CTA constraints.
- Operators should not expect to "turn off" CTA rules; they are part of the product contract.

---

## 8. Preview behavior

- **Detail previews:** Use **synthetic** (dummy) data, not live site content. Safe for all templates; no secret or user data in preview payload.
- **Compare previews:** Compact excerpts from the same preview pipeline; observational only.
- **Rendering:** When GenerateBlocks is available, section output may use GB container/grid where applicable; otherwise native block output. Preview reflects the same pipeline as built pages for consistency.

### 8.1 Styling (operator)

- **Global styling:** Managed via **Settings** (global design tokens and component overrides). Capability-gated; save/reset use nonces. Applies site-wide to surfaces that consume the styling subsystem.
- **Per-entity styling:** Section Template Detail and Page Template Detail screens may show a **Styling** panel for token/component overrides scoped to that section or page template. Save is capability- and nonce-protected; only whitelist-valid values are persisted.
- **Optional:** Styling is an enhancement; built pages remain meaningful without plugin CSS. On deactivation or uninstall, plugin CSS stops or styling options are removed; content and structure are preserved. Theme override continuity: [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md). Release evidence: [styling-release-gate.md](../release/styling-release-gate.md), [styling-acceptance-report.md](../qa/styling-acceptance-report.md).

---

## 9. Large library behavior

- **Pagination and caps:** Section and page directories use a large-library query service with configurable `per_page` and a maximum per page to avoid timeouts. List views are capped (e.g. compositions list limit 100).
- **Filtering and search:** Use purpose_family, category, status, and search to narrow results. Use **All** when you need a flat list.
- **Compare list:** Capped at 10 items per type. Remove items before adding others if the list is full.

---

## 10. Maintenance and release (operators and maintainers)

Template ecosystem maintenance is **revision-driven, decision-logged, appendix-aware, and compliance-gated**. Day-to-day operation (browse, compare, preview, compose) is covered above; the following apply when **adding, deprecating, or versioning** templates or preparing a release:

| Need | Doc |
|------|-----|
| How to add/deprecate/version templates safely; regenerate appendices; run compliance/accessibility/animation reports; escalation; decision log and changelog | [template-ecosystem-maintenance-runbook.md](../operations/template-ecosystem-maintenance-runbook.md) |
| Pre-release appendix regen, compliance gate, sign-off, release notes, and post-release evidence | [template-ecosystem-release-sop.md](../operations/template-ecosystem-release-sop.md) |

No shortcut may silently override the approved architecture; changes that affect governed rules or inventory must follow the runbook and SOP.

---

## 11. Cross-references

| Need | Doc or screen |
|------|----------------|
| Editor-focused template choice and one-pagers | [template-library-editor-guide.md](template-library-editor-guide.md) |
| Support and diagnostics for template library | [template-library-support-guide.md](template-library-support-guide.md) |
| Build Plans and execution | [admin-operator-guide.md](admin-operator-guide.md) §6–§7 |
| Export/restore (templates included) | [admin-operator-guide.md](admin-operator-guide.md) §11 |
| Styling lifecycle, uninstall, and theme continuity | [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md) |
| Capabilities and screen inventory | [admin-screen-inventory.md](../contracts/admin-screen-inventory.md) (if present) |

---

## 12. Limitations (do not assume otherwise)

- **Compare:** Observational only; no "apply to page" or "use in plan" from compare screen. Use Build Plans to select and execute.
- **Detail:** No edit-in-place of template definition from the detail screen; definitions are registry/CPT-backed and updated through governed flows.
- **Compositions:** Governed builder only; no freeform HTML or arbitrary blocks. Section set must come from the section template registry.
- **Preview:** Synthetic data only; not live site data. Reflects structure and layout, not real content.
- **Styling lifecycle:** Global and per-entity styling are plugin-owned. On uninstall, styling options are removed; built page content is preserved. Theme CSS can continue to target the same selectors (`.aio-page`, `.aio-s-*`, `--aio-*`) after plugin removal. See [styling-portability-and-uninstall.md](styling-portability-and-uninstall.md).
