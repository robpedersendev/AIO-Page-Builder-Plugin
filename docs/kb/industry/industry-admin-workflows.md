# Industry Pack subsystem — admin workflows

**Audience:** Operators, industry authors, and support.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §12–§14.  
**Related:** [admin-screen-inventory.md §2.1](../../contracts/admin-screen-inventory.md); [industry-support-training-packet.md](../../operations/industry-support-training-packet.md); [industry-operator-curriculum.md](../../operations/industry-operator-curriculum.md).

**JSON bundle preview and apply** (upload, conflicts, scopes): [industry-bundle-import-and-apply.md](industry-bundle-import-and-apply.md) — separate from **Import / Export** ZIP ([import-export-and-restore.md](../operator/import-export-and-restore.md)).

---

## Where in the product

Under **AIO Page Builder**, Industry entries are registered in this **sidebar order** (labels match `Admin_Menu.php`):

| Sidebar label | Slug | Typical capability |
|---------------|------|-------------------|
| Industry Profile | `aio-page-builder-industry-profile` | `aio_manage_settings` |
| Industry Overrides | `aio-page-builder-industry-overrides` | `aio_manage_settings` |
| Industry Author Dashboard | `aio-page-builder-industry-author-dashboard` | `aio_view_logs` |
| Industry Health Report | `aio-page-builder-industry-health-report` | `aio_view_logs` |
| Stale content report | `aio-page-builder-industry-stale-content-report` | `aio_view_logs` |
| Pack family comparison | `aio-page-builder-industry-pack-family-comparison` | `aio_view_logs` |
| Future industry readiness | `aio-page-builder-industry-future-readiness` | `aio_view_logs` |
| Future subtype readiness | `aio-page-builder-industry-future-subtype-readiness` | `aio_view_logs` |
| Maturity delta report | `aio-page-builder-industry-maturity-delta-report` | `aio_view_logs` |
| Drift report | `aio-page-builder-industry-drift-report` | `aio_view_logs` |
| Scaffold promotion readiness | `aio-page-builder-industry-scaffold-promotion-readiness-report` | `aio_view_logs` |
| Guided Repair | `aio-page-builder-industry-guided-repair` | `aio_manage_settings` |
| Subtype comparison | `aio-page-builder-industry-subtype-comparison` | `aio_view_logs` |
| Bundle comparison | `aio-page-builder-industry-bundle-comparison` | `aio_view_logs` |
| Conversion goal comparison | `aio-page-builder-industry-conversion-goal-comparison` | `aio_view_logs` |
| Industry Bundle Import | `aio-page-builder-industry-bundle-import-preview` | `aio_import_data` |
| Industry Style Preset | `aio-page-builder-industry-style-preset` | `aio_manage_settings` |
| Style layer comparison | `aio-page-builder-industry-style-layer-comparison` | `aio_view_logs` |

Per-screen nonce/action detail and edge-case behavior for complex reports live in `docs/contracts/industry-*.md` and [admin-screen-inventory.md](../../contracts/admin-screen-inventory.md). When a capability cell disagrees with code, **trust the screen registration in source** and file a doc bug.

---

## Primary documentation by intent

| Intent | Open first |
|--------|------------|
| Set or review industry profile, starter bundle assistant, pack warnings | This article + **Industry Profile** in admin; contract §2.1 in [admin-screen-inventory.md](../../contracts/admin-screen-inventory.md) |
| Manage section/page/plan overrides | **Industry Overrides**; support routing in [support-triage-guide.md](../../guides/support-triage-guide.md) if conflicts persist after save |
| Import or merge an industry JSON bundle | [industry-bundle-import-and-apply.md](industry-bundle-import-and-apply.md) |
| Authoring / task workflows | [industry-operator-curriculum.md](../../operations/industry-operator-curriculum.md); **Industry Author Dashboard** |
| Health, drift, maturity, readiness, comparisons (read-only analytics style) | Open the named report; use [support-triage-guide.md](../../guides/support-triage-guide.md) if numbers disagree with template library or Build Plan behavior |
| Guided repair migrations | **Guided Repair** (`aio_manage_settings`); pair with diagnostics if environment errors appear |
| Style preset vs site-wide tokens | **Industry Style Preset** vs **Global Style Tokens** — [global-styling.md](../operator/global-styling.md) |
| Template directories (industry filters, badges, assistants) | [template-library-operator-guide.md](../../guides/template-library-operator-guide.md); [FILE_MAP.md](../FILE_MAP.md) §14 |

---

## Edge cases

| Situation | Guidance |
|-----------|----------|
| **Can see reports but not Industry Profile / Overrides / Guided Repair / Style Preset** | Those screens require **`aio_manage_settings`**, not only `aio_view_logs`. |
| **Cannot open Industry Bundle Import** | Requires **`aio_import_data`**. ZIP restore is under **Import / Export**, not this menu. |
| **Bundle import vs ZIP restore** | JSON industry bundle pipeline: [industry-bundle-import-and-apply.md](industry-bundle-import-and-apply.md). Full-site operational ZIP: [import-export-and-restore.md](../operator/import-export-and-restore.md). |
| **Report says “OK” but templates feel wrong** | Reports aggregate registry/profile signals; confirm **Build Plans**, **Template Analytics**, and **Support Triage** — [support-triage-guide.md](../../guides/support-triage-guide.md). |
| **Mixed industry profile and old crawl / AI run** | Symptom-first: [support-triage-guide.md](../../guides/support-triage-guide.md) § Weird edge cases; [crawler-sessions-and-comparison.md](../operator/crawler-sessions-and-comparison.md); [ai-runs-and-run-details.md](../operator/ai-runs-and-run-details.md). |

---

## FAQ / troubleshooting

**Do Industry menus replace the template library?**  
No. They tune recommendations, overlays, and pack state. Browsing templates stays under **Page Templates**, **Section Templates**, and **Compositions**.

**Where is operational reporting explained?**  
**Privacy, Reporting & Settings** and [monitoring-analytics-and-reporting.md](../operator/monitoring-analytics-and-reporting.md) — industry screens do not disable outbound reporting.

**Support handoff**  
Use [support-triage-guide.md](../../guides/support-triage-guide.md) with **Support Triage** and, where appropriate, the industry training packet linked above.
