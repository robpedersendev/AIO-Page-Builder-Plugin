# Template system — how sections, pages, compositions, and docs fit together

**Audience:** Anyone choosing or explaining templates without reading internal specs.  
**Start here** for the whole template library; then open the focused guides below.  
**Menus:** **AIO Page Builder** → **Page Templates**, **Section Templates**, **Template Compare**, **Compositions**; hidden routes for template detail and **Documentation** (helper content).

---

## 1. Plain-language map

| Thing | What it is | Where you work with it |
|-------|------------|-------------------------|
| **Section template** | One reusable **slice** of a page (hero, proof strip, CTA block, legal snippet, form section, etc.). It has fields, rules, and often a **CTA role** (CTA vs non-CTA). | **Section Templates** directory → **View** opens **Section Template Detail**. |
| **Page template** | A **full page pattern**: an ordered set of sections (and metadata) meant to stand alone as a page type (hub, one-pager-style layout, detail page, etc.). | **Page Templates** directory → **View** opens **Page Template Detail**. |
| **Composition** | A **custom ordered list** of **registered section templates** you (or your team) save as a governed “mini page template.” Same building blocks as page templates, but **you** pick order under **CTA rules**. | **Compositions** → list or **Build composition**. |
| **Helper documentation** | Editorial / implementer **guidance** (usage, copy hints, variants) stored as **documentation** objects, opened from a template when the registry has a match. | Link **Open helper doc** on section detail (when available); opens **Documentation** screen (`aio-page-builder-documentation-detail`). |
| **One-pager link (page template)** | On **Page Template Detail**, an **Open one-pager** link when the product has a URL for that template’s one-pager material; otherwise the UI shows **Not available**. | Same detail screen, **One-pager** block in the metadata panel. |
| **Rendered preview** | A **safe, read-only** visual preview on detail screens using **dummy (synthetic) content** and the **same rendering path** as real pages/sections where applicable—not your live site copy. | Right-hand preview panel on section and page template detail. |
| **Template Compare** | Side-by-side **metadata and compact preview excerpts** for up to **10** section templates **or** **10** page templates—**look only**; nothing is applied to the site from this screen. | **Template Compare**. |

---

## 2. When to use what

- **Pick a section template** when you need **one block** (e.g. a new hero, a single CTA band, a FAQ) inside a larger page, a Build Plan line item, or when **adding a row** in the composition builder.
- **Pick a page template** when the plan or site needs a **known full-page pattern** from the registry (e.g. standard marketing page, hub, legal utility page) **without** hand-assembling every section yourself.
- **Use a composition** when the right **page shape is not** a single stock page template, but you still want a **governed** stack of **registry sections** (e.g. custom one-pager) with **validation** and **CTA placement** rules enforced in the builder.
- **Open helper documentation** when you need **how to write** or **how to use** a section—not the raw field list alone.
- **Use rendered preview** when you need to **see layout and structure**; use **metadata + field summary** when you need **names, versions, deprecation, and compatibility** in text form.
- **Use Template Compare** when you are **deciding between a few** similar templates and want them **next to each other** before you commit in a plan or composition.

---

## 3. Metadata, field summaries, previews, helper docs (user-facing)

- **Metadata** on detail screens is the **identity card** of the template: name, families/categories, **status**, **version**, **deprecation** (when set), compatibility notes, and (for pages) how sections or compositions relate. It is **descriptive**, not a live editor for the registry.
- **Field summary** (especially on **section** detail) is a **readable outline of the fields** the section expects—helpful for implementers and editors to know what will need content later. It is **not** a guarantee of every theme or builder edge case on the public site.
- **Rendered preview** uses **synthetic data** so nothing private leaks into the admin. It shows **structure and typical layout**, not your real headlines or images. Theme and global styling can still make the live site look different; **reduced-motion** can be toggled on detail URLs where supported (`reduced_motion=1`).
- **Compare** previews are **short excerpts** from the same kind of pipeline—still **not** live content.
- **Helper doc availability**: On **Section Template Detail**, the resolver looks up documentation by **section key** (and optional **helper ref**). If nothing resolves, the UI shows the same message as code: **“Helper documentation not available for this template version.”** That is normal for templates **without** a registered doc, **or** when routing/registry cannot produce a URL. Opening the doc requires **`aio_manage_section_templates`** (the Documentation screen uses that capability).

---

## 4. FAQ

**Why is there no helper doc for this section?**  
Only some sections have documentation **registered and resolvable**. If the registry has no document for that key (or the admin URL cannot be built), the detail screen shows the **unavailable** message. Use **name, category, purpose, field summary, and preview** instead.

**Rendered preview vs metadata — which do I trust?**  
**Both**, for different jobs: **metadata** is the contract text (what it is, version, deprecation, CTA class). **Preview** shows **how it tends to look** with placeholders. **Disagreement** (e.g. preview looks sparse) can happen when optional fields are omitted in dummy data or when theme CSS differs—treat preview as **orientation**, not a pixel-perfect contract.

**When do I choose a section template vs a page template?**  
**Section** = building block. **Page template** = already-assembled **full page pattern** from the library. If you only need one band of content, use a **section**. If you are standardizing an entire page type from the registry, use a **page template** (or a **composition** that you then treat like a page pattern).

**What is a one-pager in this product?**  
A **single scrollable page** built from an **ordered list of section templates**. Often that list is a **composition** or referenced by a **page template**. The **Open one-pager** link on page detail is **separate** helper material when provided; if it says **Not available**, there is no link for that template in the current build.

**Can I publish or edit my site from template screens?**  
No. Directories, detail, compare, and compositions builder are **observational or planning** surfaces (plus governed composition save). **Execution** flows through **Build Plans** and the rest of the product, not the template preview buttons.

**Who can see what?**  
**Section** directory/detail/docs route: **`aio_manage_section_templates`**. **Page** directory/detail and **Template Compare** (page side): **`aio_manage_page_templates`**. **Compositions**: **`aio_manage_compositions`**. Exact role grants vary by site ([concepts-and-glossary.md](../concepts-and-glossary.md)).

---

## 5. Where to read next (KB entry points)

| Topic | Document |
|-------|----------|
| **This overview** (you are here) | `template-system-overview.md` |
| **Section templates** (directory, detail, helpers, previews, edge cases) | [section-templates-deep-dive.md](section-templates-deep-dive.md) |
| **Page templates** (deep dive stub) | [page-templates-deep-dive.md](page-templates-deep-dive.md) |
| **Compositions** (deep dive stub) | [compositions-deep-dive.md](compositions-deep-dive.md) |
| **Operator workflows** (directories, compare, builder, previews) | [template-library-operator-guide.md](../../guides/template-library-operator-guide.md) |
| **Editor / chooser lens** (helper docs, one-pagers, CTA rules) | [template-library-editor-guide.md](../../guides/template-library-editor-guide.md) |
| **Support & diagnostics** | [template-library-support-guide.md](../../guides/template-library-support-guide.md) |
| **Form-bound sections** | [form-provider-operator-guide.md](../../guides/form-provider-operator-guide.md) |
| **Screen → doc routing** | [FILE_MAP.md](../FILE_MAP.md) §7 |
| **Preview / dummy-data rules (technical)** | [template-preview-and-dummy-data-contract.md](../../contracts/template-preview-and-dummy-data-contract.md) |

---

## 6. Implementation notes (one line each)

Helper URLs: `Helper_Doc_Url_Resolver` + `Documentation_Detail_Screen`. Section/page detail: `Section_Template_Detail_Screen`, `Page_Template_Detail_Screen` with `Section_Template_Detail_State_Builder` / `Page_Template_Detail_State_Builder` and `Template_Preview_Presenter`. Compositions copy: `Compositions_Screen`.
