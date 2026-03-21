# Section templates — directory, detail, helpers, and previews

**Audience:** Operators and editors who pick or review **section templates** (reusable page slices).  
**Capability:** `aio_manage_section_templates` for **Section Templates** directory, **Section Template Detail**, and **Documentation** (helper) routes.  
**Related:** [template-system-overview.md](template-system-overview.md); [template-library-operator-guide.md §2, §5, §8](../../guides/template-library-operator-guide.md); [template-library-editor-guide.md §3](../../guides/template-library-editor-guide.md); [form-provider-operator-guide.md](../../guides/form-provider-operator-guide.md) (form sections).

---

## 1. What a section template is

A **section template** is one **governed block** of a page (hero, proof, CTA, FAQ, form embed, etc.). Sections carry **purpose family**, **CTA classification**, **placement** hints, optional **compatibility** notes, and a **field blueprint** so you know what content the section expects. They are **not** whole pages; full-page patterns are **page templates** or **compositions**.

---

## 2. Browsing the directory

**Menu:** **AIO Page Builder → Section Templates** (`aio-page-builder-section-templates`).

**Views (levels):**

1. **Root** — List of **purpose families** (hero, proof, offer, CTA, legal, …) with counts. Click a family to go deeper.
2. **Purpose** — For that family, **sub-groups**: for **cta** and **contact**, choices like Primary CTA / Contact CTA / …; for others, **variant** nodes. Pick one to see the **list** of sections, or use an **All** path when the URL includes `all=1` (clears CTA/variant filters per product behavior).
3. **List** — Table rows: **Key**, **Name**, **Category** (purpose label plus CTA/variant line), **Version**, **Helper Doc**, **Status**, **Actions**.
4. **Search** — A search query switches to a **search** view (breadcrumbs show **Search: …**).

**Filters (form on list/search views):**

- **Industry fit:** **Show all**, **Recommended + weak fit**, or **Recommended only** (when Industry Pack context applies).
- **Search:** Placeholder **Name or key…**
- **Status:** Any, **Active**, **Draft**, **Inactive**, **Deprecated**
- **Apply** — Refreshes with query args; **pagination** uses `paged` / `per_page` (per-page is capped by the large-library service).

**Breadcrumbs** — Use them to move up (Section Templates → family → …).

**No execution here** — The directory is for discovery and links; it does not publish or change the live site.

---

## 3. Row actions (list)

Each row’s **Actions** column includes:

- **View detail** — Opens **Section Template Detail** (same as choosing the section for full metadata and preview).
- **Open helper doc** — When documentation resolves, a normal link opens the **Documentation** screen in a new tab. When it does not resolve, the UI shows **Not available** in the Helper Doc column; in Actions, **Open helper doc** appears as **disabled-style** text with a **tooltip** carrying the full message: **“Helper documentation not available for this template version.”**
- **Structural preview** — Links to the **detail** URL (same destination as **View detail**); the detail screen’s preview title will read **Preview** or **Structural preview** depending on whether rendered HTML was produced.
- **Add to compare** / **Remove from compare** — Maintains the **Template Compare** list (section type, max **10** items).
- **Use anyway** — Appears only for certain **industry** recommendation states (discouraged / weak fit) when no override exists; posts a governed override (operators only).

**Overridden** — Shown when an industry **section override** exists (tooltip may show reason).

---

## 4. Opening and reading detail

**URL pattern:** `admin.php?page=aio-page-builder-section-template-detail&section=<key>` (optional `purpose_family`, `reduced_motion=1`).

**Layout:**

- **Breadcrumbs** — Back through directory levels.
- **Left: metadata** — Name, description, **Purpose family**, **CTA classification**, **Placement tendency**, **Version**, **Deprecation** (**Active** vs **Deprecated**), optional **Field blueprint** ref, **Helper documentation** (see §5), optional **Industry fit** block (§6), **Compatibility notes** (bulleted strings from the definition), **Field summary** table (**Name**, **Label**, **Type**), optional **Form binding** (provider, form id, shortcode, messages), optional **Styling** panel.
- **Right: preview** — Title **Preview** or **Structural preview**; notice text: **synthetic data**; if rendering fails or is empty, you still get the **structural** framing copy. Preview may load **preview-only** stylesheets/context for fidelity.

**Compare** — Links to **Compare workspace**, **Add to compare** / **Remove from compare**.

---

## 5. Helper documentation

- **Resolver:** The product looks up documentation by **section key** via the documentation registry, or by explicit **`helper_ref`** (e.g. `doc-helper-…`) when present.
- **Detail screen:** The **Helper documentation** subsection appears only if the template has a **helper_ref** **or** the resolver returns **available**.  
  - If **available:** **Open helper doc** (new tab).  
  - If not: inline text **“Helper documentation not available for this template version.”**
- **Documentation screen** (`aio-page-builder-documentation-detail`) is **hidden** from the sidebar; it opens from these links. Same capability as section templates (`aio_manage_section_templates`).
- **Helper docs are not one-pagers for the whole page** — they are **guidance for that section**. (Page templates expose a separate **One-pager** link where configured.)

---

## 6. Industry fit block (when present)

If Industry Pack preview data exists, a block **Industry fit** may show:

- **Industry** label, **Fit** (e.g. recommended / discouraged — values are normalized for display).
- **Tone** / **CTA notes** excerpts when overlays supply them.
- **Warning** list lines.
- **Suggested alternatives** — Up to **five** links to other section keys’ detail pages.
- **Subtype** / **Conversion goal** context and caution notes when applicable.

This is **advisory**; it does not remove the section from the registry.

---

## 7. Metadata, field summary, compatibility — how to use them

| Surface | Use it to … |
|--------|-------------|
| **Purpose family / CTA / Placement** | Match the **role** of the block (e.g. hero vs bottom CTA) and CTA sequencing rules in compositions. |
| **Version** | See registry version; prefer current **active** rows for new work. |
| **Deprecation** | **Deprecated** still appears in the library; treat as **legacy**. Prefer replacements suggested in **Industry** alternatives or in planning copy when shown. The detail screen shows **Active** or **Deprecated** only (no automatic replacement list in the main metadata list today). |
| **Compatibility notes** | Read bullet items for theme, builder, or content constraints supplied by the registry. |
| **Field summary** | See **which fields** exist (**name**, **human label**, **type**)—plan copy and media without opening PHP schema. **Empty table** means no blueprint was resolved for summary (not “zero fields” guaranteed). |
| **Form binding** | For **form** sections: provider, identifier, shortcode preview, and **warning** messages (missing provider, stale binding, etc.). |

---

## 8. Preview — what it is and is not

- Uses **synthetic (dummy)** field values and the **real section rendering pipeline** when possible.
- **Preview** vs **Structural preview** labels come from whether rendered HTML was produced (`Template_Preview_Presenter`).
- On-screen notice: synthetic data; if nothing renders, **structural** context still applies.
- **Not** your site’s real text/images; **not** a guarantee of pixel-perfect match with the public theme.
- **`reduced_motion=1`** in the URL requests reduced-motion preview behavior where supported.
- Industry-specific dummy **overrides** may apply when a primary industry is configured.

---

## 9. Step-by-step — finding a section by need

1. Open **Section Templates**.
2. Pick the **purpose family** closest to the job (e.g. **hero**, **proof**, **cta**).
3. Narrow **CTA** or **variant** node if shown.
4. Use **Industry fit** + **Status: Active** to reduce noise.
5. Use **Search** by name or internal key if you know it.
6. Open **View detail** on candidates; compare **metadata**, **field summary**, and **preview**.
7. Optional: **Add to compare** for two to ten sections, then open **Template Compare**.

---

## 10. Step-by-step — is this section suitable?

1. Check **Deprecation** — If **Deprecated**, default to a non-deprecated alternative for **new** pages unless you have a reason to keep it.
2. Read **description** and **compatibility notes**.
3. Scan **Field summary** — Confirm you can supply the expected content types.
4. Watch **Industry fit** (if shown) — Warnings and **Suggested alternatives** are hints, not hard blocks (except separate governance elsewhere).
5. Use **preview** for layout; if unclear, open **helper doc** when the link works.
6. For **form** sections, read **Form binding** messages before relying on the section in production.

---

## 11. Step-by-step — helper docs

1. From directory or detail, click **Open helper doc** when it is a normal link.
2. Read guidance in the **Documentation** view; use **Back to Section Templates** to return.
3. If the link is missing or shows **not available**, rely on **metadata + field summary + preview** and operator docs.

---

## 12. Edge cases

| Situation | What to expect |
|-----------|------------------|
| **Helper doc unavailable** | Registry has no doc for this key/version, or URL build failed; message as quoted in §5. Detail may **hide** the whole Helper block when there is **no helper_ref** and resolver says unavailable. |
| **Preview empty or “structural” only** | Renderer or data path produced no HTML; still read metadata and field summary. |
| **Preview doesn’t answer layout** | Theme/CSS, optional fields omitted in dummy data, or animation/motion differ on the live site. |
| **Deprecated** | Status column and detail **Deprecation** row; section remains visible. |
| **Legacy in old pages** | Old content may still reference deprecated keys; **new** selections should prefer active replacements where your process or Industry suggestions indicate them. |
| **No sections in filters** | Message: **No sections match the current filters.** Widen status, search, or industry fit. |
| **No purpose families** | **No section purpose families available.** (Empty or broken registry state — escalate.) |
| **Use anyway** | Only for specific industry fit states; records an explicit override—use with care. |

---

## 13. FAQ and troubleshooting

**Why is a section visible if it’s “not ideal”?**  
The library lists **all governed sections** matching filters. **Industry fit** and **status** tell you whether it’s recommended or deprecated; visibility ≠ endorsement.

**Why do helper links differ?**  
Each section has its own registry entry and optional **helper_ref**. Some sections have **no** documentation object—**not available** is expected.

**I still can’t tell if it fits—what now?**  
Use **Template Compare**, ask in your team’s **Build Plan** review, or check **template-library-support-guide.md** for diagnostics. For form sections, resolve **Form binding** warnings first.

**Why does the directory say “Not available” but detail shows a helper link?**  
Both use the same resolver; they should align. If they diverge, refresh or check version/cache; treat **detail** as authoritative after load.

**Can editors without `aio_manage_section_templates` read helper docs?**  
No — the Documentation route is gated the same as section management.

---

## 14. Cross-links

- Umbrella: [template-system-overview.md](template-system-overview.md)  
- Page templates: [page-templates-deep-dive.md](page-templates-deep-dive.md) (stub or expanded)  
- Compositions: [compositions-deep-dive.md](compositions-deep-dive.md)  
- Compare / large library: [template-library-operator-guide.md §4, §9](../../guides/template-library-operator-guide.md)
