# Page templates — directory, detail, previews, and decisions

**Audience:** Operators and editors who evaluate **full-page patterns** from the registry.  
**Capability:** `aio_manage_page_templates` for **Page Templates** directory, **Page Template Detail**, and **Template Compare** (page type).  
**Related:** [template-system-overview.md](template-system-overview.md); [compositions-deep-dive.md](compositions-deep-dive.md); [section-templates-deep-dive.md](section-templates-deep-dive.md); [template-library-operator-guide.md §3, §5, §8](../../guides/template-library-operator-guide.md); [template-library-editor-guide.md §4](../../guides/template-library-editor-guide.md).

---

## 1. What a page template is for

A **page template** is a **registry-defined full page pattern**: a named layout with metadata and an **ordered set of section templates** (and optional links to compositions or other registry data). Use it when you want a **standard, versioned whole page**—for example a marketing landing shape, hub, or utility page—**without** hand-picking every section in the composition builder for that case.

**Not the same as:** a **composition** (site-saved ordered stack you govern) or a **single section** (see [section-templates-deep-dive.md](section-templates-deep-dive.md)).

---

## 2. How page templates differ from compositions

| | **Page template** | **Composition** |
|--|-------------------|-----------------|
| **Source** | Central **registry** (product definitions) | **Saved on your site** (governed custom stacks) |
| **Who changes it** | Product/versioning process, not day-to-day editors | Operators with `aio_manage_compositions` (plus API/Settings flows) |
| **Typical use** | “Pick pattern X for this page class” | “Our site’s custom one-pager Y” or fork from a template |
| **Overlap** | A page template may **reference** or align with a composition; list rows can show **Source template** on the composition side | **Source template** column may reference the page template key it was derived from |

**Decision hint:** Prefer a **page template** when the registry already matches your page class and you want stability and documentation tied to that key. Prefer a **composition** when the right shape is **not** in the registry as-is, but you still need a **CTA-valid**, section-only stack (see [compositions-deep-dive.md](compositions-deep-dive.md)).

---

## 3. Browsing the directory

**Menu:** **AIO Page Builder → Page Templates** (`aio-page-builder-page-templates`).

**Hierarchy:**

1. **Root** — Category tree with counts per category class.
2. **Category** — Template **families** within that category.
3. **List** — Rows with **Key**, **Name**, **Purpose**, **Section Order** (count, e.g. “N sections”), **Version**, **One-pager** column, **Status**, **Actions**.

**Search** — A non-empty search query switches to a **search** view (breadcrumbs reflect the query).

**Filters:** **Industry fit** (show all / recommended + weak / recommended only), **Search** (name or key), **Status** (Any, Stable, Draft), **Apply**; **pagination** with `paged` / `per_page` (capped by large-library limits).

**Breadcrumbs** — Navigate up through category and family.

**No execution** — The directory does not publish pages; it is for discovery, compare, and opening detail.

---

## 4. Row actions (list)

- **View detail** — Full metadata, **Used sections** order, preview, industry block when present, optional styling panel.
- **Structural preview** — Same URL as **View detail**; the detail preview title is **Preview** or **Structural preview** depending on whether HTML rendered.
- **Open one-pager** (directory) — In the current UI this control is **not wired per row** (it appears disabled with a “not available” style). **Do not rely on the directory row for one-pager access.**
- **Add to compare** / **Remove from compare** — Up to **10** page templates in **Template Compare**.
- **Use anyway** — Shown for some **industry** recommendation states when no override exists (posts a governed override).
- **Overridden** — Industry override present (tooltip may show reason).
- **Composition** — Label shown for users who can manage templates; it is **not** a substitute for the **Compositions** menu for editing saved compositions.

---

## 5. Page Template Detail — what to read

**URL:** `admin.php?page=aio-page-builder-page-template-detail&template=<key>` (optional `category_class`, `family`, `reduced_motion=1`).

**Metadata (structural guidance, not live content):**

- **Name**, **description**, **Category**, **Purpose / CTA direction**, **Differentiation** notes when present.
- **Version** and **Deprecation** (**Active** or **Deprecated** only in the main list—same pattern as sections).
- **Used sections** — Ordered list: **section internal key** and **position**. This is the **authoritative section order** for the pattern at a glance.

**One-pager (page-level helper material):**

- Block **One-pager**: **Open one-pager** opens an **external** URL in a new tab when the registry provides one; otherwise **Not available**.
- This is **separate** from **section** helper docs (internal **Documentation** route). For copy/usage of **individual** sections inside the page, open each section’s **Section Template Detail** and use **Open helper doc** when available ([section-templates-deep-dive.md §5](section-templates-deep-dive.md)).

**Industry fit (when Industry Pack context applies):**

- May include fit, hierarchy notes, LPagery posture text, warnings, **substitute suggestions**, and composed one-pager hints (CTA strategy, hierarchy hints). **Advisory**—visibility does not remove the template from the library.

**Preview (suggestive only):**

- Same notice as other template previews: **synthetic data**; if rendering is empty, **structural preview only**.
- Shows **typical layout and block structure**, not your site’s real copy, SEO, or theme-specific spacing. Optional preview style context may load for fidelity.
- **`reduced_motion=1`** requests reduced-motion behavior where supported.

**Compare / styling**

- Links to **Compare workspace** and **Add/Remove from compare**.
- Optional **Styling** panel (per-entity tokens/components) when enabled—governed save; does not change registry section order.

---

## 6. Structural guidance vs final page output

| **Structural guidance (trust for “what the page is”)** | **Final output (varies on the live site)** |
|--------------------------------------------------------|--------------------------------------------|
| Registry metadata, **Used sections** order, deprecation, industry warnings | Real headlines, media, forms, and ACF field values you enter on pages |
| Preview with dummy data | Theme CSS, global styles, optional plugin styling |
| CTA direction / differentiation text | Conversion goals, legal copy, and brand voice |

Use the template screens to **commit to a pattern**; use the page editor and execution flows to **fill and publish** content.

---

## 7. Step-by-step — review a page template before using it

1. In **Page Templates**, narrow by **category/family**, **Industry fit**, and **Status** (e.g. Stable).
2. Use **Purpose** and **Section Order** count to shortlist candidates.
3. Open **View detail**.
4. Read **Used sections** top-to-bottom—that is the **story arc** of the page (hero → proof → CTA, etc.).
5. Open **Open one-pager** when present for narrative/spec guidance.
6. For any unfamiliar **section key**, open **Section Templates** → detail for that key (field summary, helper doc, section preview).
7. Read **Deprecation** and **Industry fit** warnings; consider **substitute suggestions** or a different template for **new** work if deprecated or discouraged.
8. Scan the **preview** for layout; treat mismatches as “check theme / optional fields,” not as bugs in the registry alone.

---

## 8. Step-by-step — compare multiple page templates

1. From the directory or detail, **Add to compare** for up to **10** keys (remove items if the list is full).
2. Open **Template Compare**, set type to **Page templates**.
3. Compare **metadata** and **compact preview excerpts** side by side—**observational only**; nothing applies to the site from this screen.
4. Decide in **Build Plan** or your governance process which key to use.

---

## 9. Edge cases

| Situation | What to do |
|-----------|------------|
| **Template “looks right” but your content/context differs** | **Used sections** still define obligations (e.g. form section, legal block). Validate that your industry, legal, and CTA requirements match; use **industry** and **differentiation** notes. |
| **Directory “One-pager” always looks unavailable** | Use **Page Template Detail** for the real **Open one-pager** link; the list column is not populated per row in the current implementation. |
| **Preview suggestive, not final** | Dummy text/images; theme may change appearance; some optional fields may be omitted from synthetic data. |
| **Deprecated but still visible** | Legacy pages may keep old keys; for **new** selections prefer active alternatives when industry or planning guidance points to them. |
| **Template not found** | Detail shows an error and **Back to Page Templates**—key removed or typo in URL. |
| **No templates in filters** | Widen filters or search; empty state: no rows match. |

---

## 10. FAQ and troubleshooting

**Should I use a page template or a composition?**  
See §2. Registry-standard full page → **page template**. Custom governed stack for this site → **composition** (possibly started from a template via your process).

**Where are helper docs for a page template?**  
**One-pager** link when set (external). **Per-section** helpers live on **Section Template Detail**, not on the page detail screen.

**Why is “Open one-pager” disabled on the directory row?**  
Per-row one-pager URLs are not exposed there today; open **View detail**.

**Why does compare look different from detail preview?**  
Compare uses **short excerpts** from the same family of pipeline—still synthetic, not full-page fidelity.

**Who can see page templates?**  
Users with **`aio_manage_page_templates`**.

---

## 11. Cross-links

- Sections: [section-templates-deep-dive.md](section-templates-deep-dive.md)  
- Compositions: [compositions-deep-dive.md](compositions-deep-dive.md)  
- Umbrella: [template-system-overview.md](template-system-overview.md)
