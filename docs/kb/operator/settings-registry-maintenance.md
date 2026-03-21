# Settings — Registry maintenance and seed actions

**Audience:** Operators with settings access.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §2.  
**Related:** [admin-operator-guide.md §1.1](../../guides/admin-operator-guide.md); screen `aio-page-builder-settings`.

---

## Prerequisites

- **Screen:** **Settings** (`aio-page-builder-settings`).
- **Capability:** `aio_manage_settings` for most controls; **Seed form section and request page template** uses **`manage_options`** in the handler (see [admin-screen-inventory.md §2.2](../../contracts/admin-screen-inventory.md)).
- **Summary of what lives here:** Plugin version, link to **Privacy, Reporting & Settings**, and **idempotent registry seed** actions (section/page template batches, form templates, expansion packs, etc.). Full per-button copy: [admin-operator-guide.md §1.1](../../guides/admin-operator-guide.md).

---

## Workflow (high level)

1. Open **AIO Page Builder → Settings**.
2. Run a **seed** only when you intend to refresh curated registry definitions for the listed batch.
3. Read the admin **notice** after redirect for success or failure.

**Form-backed seed:** [form-provider-operator-guide.md](../../guides/form-provider-operator-guide.md). **Template directories after seed:** [template-library-operator-guide.md](../../guides/template-library-operator-guide.md).

---

## Edge cases

| Situation | Guidance |
|-----------|----------|
| **Re-seeding** | Same internal keys are generally **overwritten**; do not use seeds as a “merge” for custom-edited registry rows unless you understand overwrite rules. |
| **Production site** | Coordinate with change windows; pair with **Import / Export** backup if needed — [import-export-and-restore.md](import-export-and-restore.md). |
| **Seed fails** | Check **Diagnostics**, caps, and the notice text; support path — [support-triage-guide.md](../../guides/support-triage-guide.md). |
