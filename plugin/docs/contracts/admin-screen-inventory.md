# Admin Screen Inventory

**Document type:** Authoritative contract for admin screen slugs, titles, ownership, and capabilities.  
**Governs:** Menu registration, routing, and all admin screen implementations (spec §62.10).  
**Reference:** Master Specification §6.3, §7.8, §45.3, §53.2, §62.10.

---

## 1. Top-level menu

| Property | Value |
|----------|--------|
| Menu title | AIO Page Builder |
| Menu slug (parent) | `aio-page-builder` |
| Icon | Dashicons or placeholder |
| Position | Default |

All submenu pages use parent slug `aio-page-builder`. Screen slugs below are the `page` query argument and must not be invented elsewhere.

---

## 2. Screen inventory

### 2.1 Implemented (bootstrap placeholders)

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder` | Dashboard | Bootstrap / Admin | `manage_options` (placeholder) | Landing; future first-run redirect target | Placeholder |
| `aio-page-builder-settings` | Settings | Bootstrap / Admin | `manage_options` (placeholder) | Plugin settings, reporting disclosure | Placeholder |
| `aio-page-builder-diagnostics` | Diagnostics | Bootstrap / Admin | `manage_options` (placeholder) | Environment status, validation summary | Placeholder |

### 2.2 Future screens (locked slugs)

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder-onboarding` | Onboarding & Profile | Admin / Onboarding | TBD | First-time setup, brand/business profile | Not implemented |
| `aio-page-builder-registries` | Registries | Domain: Registries | TBD | Section/page template registry management | Not implemented |
| `aio-page-builder-ai-runs` | AI Runs | Domain: AI | TBD | View AI runs, artifacts, validation | Not implemented |
| `aio-page-builder-build-plans` | Build Plans | Domain: BuildPlan | TBD | Review and execute build plans | Not implemented |
| `aio-page-builder-logs` | Logs | Infrastructure / Reporting | TBD | View operational logs | Not implemented |
| `aio-page-builder-reporting` | Reporting | Domain: Reporting | TBD | Reporting disclosure, heartbeat status | Not implemented |
| `aio-page-builder-export-restore` | Export & Restore | Domain: ExportRestore | TBD | Export backup, restore, survivability | Not implemented |

---

## 3. Rules

- **Stable slugs:** Do not rename or reuse the slugs above. New screens require a new row and a new slug following the pattern `aio-page-builder-{name}`.
- **Routing:** Screen rendering is handled by dedicated screen classes; no anonymous callbacks that embed logic. Menu registration only wires slug → screen class render method.
- **Capability:** Current screens use `manage_options` as a placeholder until capability mapping is finalized. All menu and screen access must remain capability-aware.
- **First-run:** Dashboard (and optionally Onboarding) are the intended first-run redirect targets; no implementation in this contract.
- **No hidden action URLs:** Use the registered menu slugs only; no unstructured admin routing or direct file includes based on request parameters.
