# Compositions — list, builder, validation, and when to use them

**Audience:** Operators who create or review **governed custom page stacks** built only from **registered section templates**.  
**Capability:** `aio_manage_compositions` for **Compositions** list and **Composition builder**.  
**Related:** [page-templates-deep-dive.md](page-templates-deep-dive.md); [template-system-overview.md](template-system-overview.md); [section-templates-deep-dive.md](section-templates-deep-dive.md); [template-library-operator-guide.md §6, §7, §9](../../guides/template-library-operator-guide.md); [template-library-editor-guide.md §5](../../guides/template-library-editor-guide.md).

---

## 1. What a composition is

A **composition** is a **saved, ordered list of section template keys** that together describe **one scrollable page** (one-pager-style) under product rules. It is **not** freeform HTML: only **registry sections** are valid building blocks, and **CTA placement/sequencing** rules apply (see §6).

**Differs from a page template:** A **page template** is a **registry** definition with a fixed pattern and versioning. A **composition** is **your site’s** (or your team’s) governed assembly—often used when no single registry page template fits, or when you standardize a **custom** stack. A composition may record a **Source template** if it was derived from or tied to a page template key.

---

## 2. List view

**Menu:** **AIO Page Builder → Compositions** (`aio-page-builder-compositions`).

**What you see:**

- Intro copy: governed compositions from section templates; **Build** for category- and CTA-aware selection.
- **Build composition** — Opens the builder (`view=build`).
- **Table** (when entries exist): **Name**, **ID**, **Status**, **Validation**, **Sections** (count), **Source template** (page template key or **—**), **Edit**.
- **Empty:** Prompt to use **Build** to create from the section library.
- **List cap:** Up to **100** compositions loaded for performance; if you have more, pagination or other access may be required outside this table—confirm with your operator workflow.

**Capability:** Without `aio_manage_compositions`, the screen is inaccessible.

---

## 3. Build view (composition builder)

**URL:** `admin.php?page=aio-page-builder-compositions&view=build`  
**Edit existing:** `&composition_id=<id>`.

**Purpose:** Review and plan **current sections**, **CTA guidance**, **validation**, and a **filtered section catalog**. The on-screen message states that **save is handled via Compositions API or Settings** (or integrated flows)—**changing** the stored composition is not implied to be a single anonymous “Save” button on this page alone.

**Panels:**

- **Back to Compositions** — Return to list.
- **Editing** line — Name, ID, status, validation when editing an existing composition.
- **CTA guidance** — Warning notice listing messages when the current order conflicts with CTA rules.
- **Insertion hint** — Textual hint for where/how the next section should be added under rules.
- **Readiness badges:**
  - **Preview ready** vs **Preview: add section preview data**
  - **One-pager ready** vs **One-pager: add when saving**
- **Validation codes** — Machine-oriented codes when present (for support or implementers).
- **Current sections** — Ordered list: **section key**, **human name**, **CTA** label when the section is classified as CTA.
- **Section library (filtered)** — Table of candidate sections (key, name, category, purpose family, CTA classification, status) with **filters**: purpose family (text), CTA class, search, status, **Apply filters**, pagination.

**Empty composition:** Copy explains no sections yet and points to the library below and **save via Compositions API or Settings**.

**No arbitrary blocks:** You cannot add non-registry blocks or paste layout HTML here—the product contract is **section templates only**.

---

## 4. How compositions relate to page templates and Build Plans

- **Page template** may embed or reference the same logical order as a composition; the composition’s **Source template** column can document that relationship.
- **Build Plan** and execution flows consume template choices according to product configuration; compositions are **inputs** to “what shape is this page” alongside page templates.
- **Overlap confusion:** Two artifacts (page template vs composition) can describe **similar** section orders. Use **Source template** and your **internal naming** to tell which is canonical for a given page type.

---

## 5. Previews and “one-pager ready” in the builder

- Badges reflect **readiness for preview** and **one-pager-style completeness** after save—not a live public URL by themselves.
- **Detail previews** for sections/pages use synthetic data ([template-system-overview.md §3](template-system-overview.md)); builder badges are **state hints**, not guarantees of final front-end output.

**Helper docs:** Section-level **Open helper doc** is on **Section Template Detail**, not on the composition builder table. Use the directory/detail to read guidance for keys you consider adding.

---

## 6. CTA rules (operator expectation)

- The builder surfaces **CTA guidance** and **validation** when the ordered list violates sequencing or placement rules (e.g. bottom-of-page CTA, non-adjacent CTAs—see product contracts and [template-library-operator-guide.md §7](../../guides/template-library-operator-guide.md)).
- **Do not expect** to bypass these rules from the UI; fix the order or section choices until validation clears (or follow your governance exception process).

---

## 7. Step-by-step — using compositions where they exist

1. Open **Compositions**; scan **Status**, **Validation**, and **Sections** count.
2. Note **Source template** when set—that links the custom stack to a registry page template if documented.
3. Click **Edit** to open the builder for that ID.
4. Read **Current sections** top-to-bottom (your page story).
5. If **CTA guidance** or **validation codes** appear, adjust the planned order per messages (or escalate to support with codes).
6. Use **Section library** filters to find additional keys; confirm each with **Section Templates** detail if needed.
7. Apply changes through your site’s **Compositions API or Settings** path as documented for your environment.
8. After save, re-open the builder or list to confirm **Validation** and readiness badges updated.

---

## 8. Step-by-step — deciding page template vs composition

1. Check **Page Templates** for a registry pattern that matches the page class and industry fit.
2. If an exact match exists and is **Active** and recommended, **page template** is usually simpler to communicate in plans.
3. If you need a **unique order**, experimental stack, or site-specific variant not in the registry, use or create a **composition** (still only registry sections).
4. If both exist with similar sections, prefer the artifact your **Build Plan** or **execution** path expects (template key vs composition ID)—they are not always interchangeable labels.

---

## 9. Edge cases

| Situation | Notes |
|-----------|--------|
| **Composition vs page template confusion** | Compare **Source template**, names, and **Used sections** on the page template detail with the composition’s ordered list. |
| **Builder shows library but no obvious “Add” on screen** | Persisting order changes follows **Compositions API or Settings** (per UI copy); coordinate with whoever owns composition mutations. |
| **Preview / one-pager badges not green** | May require more sections, valid preview data, or a successful save—badges are **hints**, not publish state. |
| **Validation fails with codes** | Capture **Validation codes** and CTA messages for support; do not assume the UI will allow invalid CTA stacks. |
| **More than 100 compositions** | List may truncate; use API, export, or internal tools per operator docs. |
| **Previews suggestive only** | Same as page/section detail: synthetic, theme-dependent live site may differ. |

---

## 10. FAQ and troubleshooting

**Can I edit compositions without `aio_manage_compositions`?**  
No.

**Is a composition the same as a one-pager?**  
In this product, a one-pager **shape** is implemented as an **ordered section list**—often a **composition** or a **page template** that references such a list. The words differ; the **section order** is what matters.

**Why does my composition duplicate a page template?**  
Intentional forking, legacy saves, or different execution bindings. Use **Source template** and team docs to pick the **canonical** artifact.

**Where do helper docs live?**  
On **section** template detail (and external **one-pager** on **page** template detail when set)—not inside the composition builder grid.

---

## 11. Cross-links

- Page templates: [page-templates-deep-dive.md](page-templates-deep-dive.md)  
- Sections: [section-templates-deep-dive.md](section-templates-deep-dive.md)  
- Umbrella: [template-system-overview.md](template-system-overview.md)
