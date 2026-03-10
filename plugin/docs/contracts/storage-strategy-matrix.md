# Storage Strategy Matrix

**Document type:** Authoritative contract for placement of every major data class (spec §8, §9).  
**Governs:** Which storage primitive (options, CPT, post meta, custom tables, user meta, transients, uploads/ZIP) is used for each data class before any CPT, table, or repository is implemented.  
**Reference:** global-options-schema.md for option keys; spec §8.2–8.10, §9.1–9.9.

---

## 1. Design rules

- **No operational data in options:** High-volume logs, queue records, and runtime records use custom tables or CPTs, not options (§9.4).
- **No authoritative business state in transients:** Transients are for temporary caches only (§9.7).
- **No secrets in uploads or exportable blobs:** Secrets live in segregated storage; uploads and export packages are permission-gated and cleanup-managed (§9.8, §52.6).
- **User meta is non-authoritative for shared state:** Per-user preferences only; not for site-wide operational data (§9.6).
- **Built content survivability:** Generated page content must not depend solely on plugin-only stores for front-end usefulness (§9.10, §9.11).

---

## 2. Storage primitive summary

| Primitive | Use for |
|-----------|--------|
| **Options** | Global plugin configuration, version/migration markers, reporting settings, dependency dismissals, uninstall prefs, provider config reference (no secrets in exportable option values). |
| **CPT** | Section templates, page templates, compositions, Build Plans, AI run metadata, prompt packs, helper docs / one-pagers (human-meaningful, inspectable objects). |
| **Post meta** | Object-level data attached to a CPT or built page: template metadata, composition metadata, field assignment references, execution provenance, status markers. |
| **Custom tables** | Crawl snapshots, AI artifacts (raw), queue records, execution logs, rollback records, token sets, assignment maps, reporting delivery records (volume/structure/retention). |
| **User meta** | User view preferences, dismissed notices (per-user), saved filters, per-user workflow preferences. |
| **Transients** | Temporary crawl summaries, provider capability cache, comparison results, validation cache (ephemeral only). |
| **Uploads directory** | Export package files, temporary package preparation, downloadable artifacts (permission-gated, cleanup-managed). |
| **ZIP archives** | Export/restore packages (manifest, versioned, permission-gated); not for secrets. |

---

## 3. Full matrix

| Data class | Owner | Storage primitive | Scope | Retention | Sensitivity | Exportable by default | Excluded by default | Uninstall cleanup | Migration/versioning | Spec ref |
|------------|-------|--------------------|-------|-----------|--------------|------------------------|---------------------|-------------------|----------------------|----------|
| Global settings | human | options | site | permanent until user deletion | admin-visible restricted | Yes | No | remove if uninstall-prefs say so | Option structure versioned; add fields only within roots | §9.4, §62.3 |
| Version markers | system | options | site | long-lived operational | internal operational | No | Yes | remove on uninstall | Version key in option; migration scripts | §9.4 |
| Reporting settings | human | options | site | permanent until user deletion | admin-visible restricted | Yes (no secrets) | No | remove if uninstall-prefs say so | Option structure | §9.4 |
| Dependency notice dismissals | human/system | options | site | medium-lived operational | internal operational | No | Yes | remove on uninstall | Optional | §9.4 |
| Uninstall preferences | human | options | site | permanent until user deletion | user-configured | Yes | No | N/A (prefs govern cleanup) | Option structure | §9.4 |
| Provider config reference | human | options (metadata only) | site | permanent until user deletion | privileged restricted | No | Yes | remove on uninstall | Secrets in separate storage; option holds ref/non-secret only | §9.4, §43.13 |
| Brand profile | human | options (or dedicated option under same root) | site | permanent until user deletion | admin-visible restricted | Yes | No | remove if uninstall-prefs say so | Schema: profile-schema.md, profile-snapshot-schema.md; §22 | §8.3, §52.4 |
| Business profile | human | options | site | permanent until user deletion | admin-visible restricted | Yes | No | remove if uninstall-prefs say so | Schema: profile-schema.md, profile-snapshot-schema.md; §22 | §8.3, §52.4 |
| Section templates | human/system | CPT | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice / export-before-remove | Object schema: object-model-schema.md §3.1; post meta for template metadata | §9.1 |
| Page templates | human/system | CPT | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice | Object schema: object-model-schema.md §3.2 | §9.1 |
| Compositions | human | CPT | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice | Object schema: object-model-schema.md §3.3; CPT + post meta | §9.1, §9.3 |
| Helper docs / one-pagers | system (generated) | CPT | site | long-lived operational | admin-visible restricted | Yes (optional) | No | preserve by choice | Object schema: object-model-schema.md §3.7 (Documentation) | §9.1, §52.5 |
| AI run (metadata/identity) | system | CPT | site | long-lived operational | admin-visible restricted | Yes (optional) | No | preserve by choice | Object schema: object-model-schema.md §3.5; links to artifacts table | §9.1 |
| Prompt packs | human/system | CPT | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice | Object schema: object-model-schema.md §3.6 | §9.1 |
| AI artifacts (raw payloads) | system | custom table | site | long-lived operational | admin-visible restricted | Optional | No | preserve by choice / retention policy | custom-table-manifest.md §3.2; redaction on export | §9.5, §29 |
| Build Plans | human/system | CPT | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice | Object schema: object-model-schema.md §3.4; CPT + post meta for status/provenance | §9.1, §9.3 |
| Crawl snapshots | system | custom table | site | medium-lived operational | internal operational | Optional | No | retention-managed / remove by policy | custom-table-manifest.md §3.1 | §9.5, §8.5 |
| Execution logs | system | custom table | site | medium-lived operational | internal operational | Optional | No | retention-managed | custom-table-manifest.md §3.4; no secrets in logs | §9.5, §45 |
| Rollback records | system | custom table | site | long-lived operational | admin-visible restricted | Optional | No | preserve by choice / retention | custom-table-manifest.md §3.5 | §9.5 |
| Queue records | system | custom table | site | short-lived operational | internal operational | No | Yes | routine cleanup | custom-table-manifest.md §3.3 | §9.5, §8.5 |
| Token sets | human/system | custom table | site | permanent until user deletion | admin-visible restricted | Yes | No | preserve by choice | custom-table-manifest.md §3.6 | §9.5, §52.4 |
| Assignment maps | system | custom table | site | long-lived operational | admin-visible restricted | Yes | No | preserve by choice | custom-table-manifest.md §3.7 | §9.5 |
| Field assignments (per-object) | human/system | post meta | object | permanent until user deletion | admin-visible restricted | Yes (with parent) | No | with parent CPT | Meta key stability | §9.3 |
| Reporting delivery records | system | custom table | site | long-lived operational | internal operational | No | Yes | retention-managed | custom-table-manifest.md §3.8; no secrets | §9.5 |
| User view preferences | human | user meta | user | permanent until user deletion | admin-visible restricted | No | Yes | remove with user or leave | Key namespaced | §9.6 |
| User dismissed notices | human | user meta | user | medium-lived operational | internal operational | No | Yes | remove with user or leave | Key namespaced | §9.6 |
| Transient caches (crawl summary, provider capability, etc.) | system | transient | site | ephemeral cache | internal operational | No | Yes | expire naturally | No migration | §9.7 |
| Export packages (files) | system | uploads directory | site | export package only | admin-visible restricted | N/A (are the export) | N/A | cleanup-managed | Naming, permission-gated | §9.8 |
| Export/restore ZIP archives | system | uploads (ZIP) | site | export package only | admin-visible restricted | N/A | N/A | cleanup-managed; permission-gated download | Manifest, version, validation | §9.9, §52 |
| Provider secrets (API keys, tokens) | human | segregated storage (not options blob) | site | permanent until user deletion | secret | No | Yes (always excluded) | remove on uninstall | Never in exportable options or logs | §9.4, §43.13, §52.6 |

---

## 4. Owner and retention legend

- **Owner:** human = user-authored/configured; system = plugin-generated; mixed = both.
- **Retention:** permanent until user deletion | long-lived operational | medium-lived operational | short-lived operational | ephemeral cache | export package only | uninstall-removable | uninstall-preserved by choice.
- **Excluded by default:** data class is not included in default export (e.g. queue records, reporting records, transients, secrets).

---

## 5. Uninstall and export posture

- **Remove on uninstall:** Options (except when prefs say preserve), version markers, dependency dismissals, provider config reference, queue records (if any remain), transients.
- **Preserve by choice / export-before-remove:** Settings, profiles, templates, compositions, Build Plans, AI runs, prompt packs, artifacts, token sets, assignment maps, rollback records, logs (per policy), export packages (cleanup-managed).
- **Content survivability:** Built page content lives in standard WordPress posts; plugin removal does not delete that content. Plugin-owned metadata may be removed; content remains (§9.11, §9.12).

---

## 6. Secrets and export rules

- **Secrets** (API keys, passwords, auth tokens): Stored in segregated storage only. Prohibited from ordinary exports, logs, and reports (§52.6, §45.9).
- **Uploads-based packages:** Permission-gated download; cleanup rules; no secrets inside (§9.8).
- **User meta:** Non-authoritative for shared state; per-user only (§9.6).

---

## 7. Implementation ownership (future prompts)

| Data class | Owning bucket / prompt area |
|------------|-----------------------------|
| Global settings, version markers, reporting settings, dependency dismissals, uninstall prefs, provider config reference | Bootstrap / settings (options already scoped in Prompt 010) |
| Brand profile, business profile | Settings / onboarding |
| Section templates, page templates | Registry / templates |
| Compositions | Compositions domain |
| Helper docs, one-pagers | Documentation / execution |
| AI run (CPT), prompt packs | AI / planner |
| AI artifacts (table) | AI / storage |
| Build Plans | Build plan domain |
| Crawl snapshots | Crawler / storage |
| Execution logs | Execution / diagnostics |
| Rollback records | Execution / rollback |
| Queue records | Queue domain |
| Token sets, assignment maps | Token / assignment domain |
| Reporting delivery records | Reporting domain |
| User view preferences, dismissed notices | Admin / user meta |
| Transient caches | Respective domains (crawler, provider, etc.) |
| Export packages, ZIP archives | Export/restore domain |
| Provider secrets | AI provider / secure storage |

---

## 8. Completeness checklist

Every major object or data class named in this document and in spec §8–§9 must appear in the matrix with no empty required columns. Use this checklist to verify:

- [ ] Global settings — options, retention/sensitivity/export/uninstall filled
- [ ] Version markers — options, internal, not exportable
- [ ] Reporting settings — options, exportable (no secrets)
- [ ] Dependency notice dismissals — options, not exportable
- [ ] Uninstall preferences — options, exportable
- [ ] Provider config reference — options (metadata only), secrets elsewhere
- [ ] Brand profile — options, exportable
- [ ] Business profile — options, exportable
- [ ] Section templates — CPT, exportable
- [ ] Page templates — CPT, exportable
- [ ] Compositions — CPT, exportable
- [ ] Helper docs / one-pagers — CPT, optional export
- [ ] AI run (metadata) — CPT, optional export
- [ ] Prompt packs — CPT, exportable
- [ ] AI artifacts (raw) — custom table, optional export
- [ ] Build Plans — CPT, exportable
- [ ] Crawl snapshots — custom table, optional export
- [ ] Execution logs — custom table, optional export
- [ ] Rollback records — custom table, optional export
- [ ] Queue records — custom table, excluded
- [ ] Token sets — custom table, exportable
- [ ] Assignment maps — custom table, exportable
- [ ] Field assignments (per-object) — post meta, exportable with parent
- [ ] Reporting delivery records — custom table, excluded
- [ ] User view preferences — user meta, excluded
- [ ] User dismissed notices — user meta, excluded
- [ ] Transient caches — transient, excluded
- [ ] Export packages (files) — uploads, permission-gated
- [ ] Export/restore ZIP archives — uploads (ZIP), permission-gated
- [ ] Provider secrets — segregated storage, always excluded

All rows above have storage primitive, retention, sensitivity, exportability, and uninstall posture stated. No TBD or empty required columns.
