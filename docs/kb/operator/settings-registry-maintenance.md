# Settings — Registry maintenance and seed actions

**Audience:** Operators with settings access.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §2.  
**Related:** [admin-operator-guide.md §1.1](../../guides/admin-operator-guide.md); screen `aio-page-builder-settings`.

---

## Scope (architecture only)

This page will document **Settings** workflows beyond the summary in the admin operator guide: plugin version and links, and each **idempotent registry seed** action (section/page template batches, form templates, expansion packs, etc.). Content to add in a later pass: per-button purpose, prerequisites, failure notices, and when re-seeding overwrites registry keys.

---

## Target outline for full article

- Prerequisites (`aio_manage_settings` or as implemented for your site).
- How seed results surface (admin notices / query args).
- Grouping of seeds (form provider, section batches, page template batches).
- Safety notes: idempotent overwrite behavior; coordination with Import/Export and production change windows.
- Links: [form-provider-operator-guide.md](../../guides/form-provider-operator-guide.md), [template-library-operator-guide.md](../../guides/template-library-operator-guide.md).
