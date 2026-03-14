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
| `aio-page-builder` | Dashboard | Bootstrap / Admin | `manage_options` (placeholder) | Overview: environment/dependency/provider readiness; last crawl, last AI run, active Build Plans; queue warnings; critical errors; quick actions (Start/Resume Onboarding, Crawl Sessions, AI Runs, Build Plans, Queue & Logs, AI Providers, Import/Export); welcome/first-run and resume state | Implemented |
| `aio-page-builder-settings` | Settings | Bootstrap / Admin | `manage_options` (placeholder) | Plugin settings, reporting disclosure; **Seed form section and request page template** (form-provider; nonce `aio_seed_form_templates`; capability TBD—align with registry management). See [form-provider-integration-contract.md](../../docs/contracts/form-provider-integration-contract.md). | Implemented (partial) |
| `aio-page-builder-diagnostics` | Diagnostics | Bootstrap / Admin | `manage_options` (placeholder) | Environment status, validation summary | Placeholder |
| `aio-page-builder-acf-diagnostics` | ACF Field Architecture | Domain: ACF / Diagnostics | `aio_view_logs` | Field-architecture health, registration status, assignment mismatches, LPagery compatibility, regeneration readiness; filtered views; repair entry (capability-gated) | Implemented |

### 2.2 Crawler screens (locked slugs)

**Contract:** Crawler list/detail/comparison, readiness copy, diagnostics panels, and action placeholders are defined in [crawler-admin-screen-contract.md](crawler-admin-screen-contract.md). Do not invent crawler slugs or panels elsewhere.

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder-crawler-sessions` | Crawl Sessions | Domain: Crawler | `manage_options` (placeholder) | List runs; View pages (detail); future crawl start/retry placeholder | Implemented |
| (detail via `run_id`) | Crawl Session Detail | Domain: Crawler | `manage_options` (placeholder) | View page snapshots (URL, title, classification, nav, status) | Implemented |
| `aio-page-builder-crawler-comparison` | Crawl Comparison | Domain: Crawler | `manage_options` (placeholder) | Select prior/new run; Compare; view summary and page changes | Implemented |

### 2.3 Registry screens (locked slugs)

**Contract:** Full screen IA, list/detail/create/edit flows, validation, deprecation, documentation and snapshot visibility are defined in [registry-admin-screen-contract.md](registry-admin-screen-contract.md). Do not invent slugs or panels elsewhere.

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder-section-templates` | Section Templates | Domain: Registries | `aio_manage_section_templates` | List, View detail, Filter, Search, helper-doc (Prompt 200) | Implemented |
| (detail via link) | Section Template detail | Domain: Registries | `aio_manage_section_templates` | View metadata, compatibility, Add to compare (Prompt 200) | Implemented |
| `aio-page-builder-page-templates` | Page Templates | Domain: Registries | `aio_manage_page_templates` | List, View detail, Filter, Search (Prompt 200) | Implemented |
| (detail via link) | Page Template detail | Domain: Registries | `aio_manage_page_templates` | View metadata, one-pager, Add to compare (Prompt 200) | Implemented |
| `aio-page-builder-template-compare` | Template Compare | Domain: Registries | `aio_manage_page_templates` | Side-by-side compare; add/remove from compare list (Prompt 200) | Implemented |
| `aio-page-builder-compositions` | Compositions | Domain: Registries | `aio_manage_compositions` | List, Build composition, Filter (Prompt 200) | Implemented |
| `aio-page-builder-composition-edit` | Add / Edit Composition | Domain: Registries | `aio_manage_compositions` | Save, Validate, Duplicate, Archive, Cancel | Not implemented |
| `aio-page-builder-documentation` | Documentation | Domain: Registries | `aio_manage_documentation` | List, Filter, Edit | Not implemented |
| `aio-page-builder-documentation-edit` | Edit Documentation | Domain: Registries | `aio_manage_documentation` | Save, Cancel | Not implemented |
| `aio-page-builder-snapshots` | Version Snapshots | Domain: Registries | `aio_view_version_snapshots` | List, Filter, View detail (read-only) | Not implemented |

### 2.4 Build Plan screens (locked slugs)

**Contract:** Build Plan list/detail entry points, three-zone layout, stepper order, context rail, step workspace, row/detail, status/error/progress/completion patterns, and capabilities are defined in [build-plan-admin-ia-contract.md](build-plan-admin-ia-contract.md). Do not invent Build Plan slugs or layout/stepper behavior elsewhere.

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder-build-plans` | Build Plans | Domain: BuildPlan | `aio_view_build_plans` (view/approve/execute/finalize/artifact distinct per IA contract) | List plans; open plan detail (stepper); Create Build Plan from AI Runs | Implemented (shell) |
| (detail via `plan_id` or `id`) | Build Plan detail (stepper) | Domain: BuildPlan | Same as list; approve/execute gated by separate capabilities | Three-zone layout: context rail, stepper, step workspace; empty-state shells; row/detail in later prompts | Implemented (shell) |

### 2.5 Other future screens (locked slugs)

| Screen slug | Title | Owning domain | Intended capability | Primary actions | Status |
|-------------|--------|----------------|----------------------|-----------------|--------|
| `aio-page-builder-onboarding` | Onboarding & Profile | Admin / Onboarding | `manage_options` | First-time setup, brand/business profile; steps, draft, prefill, readiness; shell implemented (Onboarding_Screen); steps, draft, prefill, and handoff governed by [onboarding-state-machine.md](onboarding-state-machine.md) | Implemented (shell) |
| `aio-page-builder-ai-runs` | AI Runs | Domain: AI | `aio_view_ai_runs` | List runs; view run detail (metadata + artifact summaries); raw prompts/responses gated by `aio_view_sensitive_diagnostics` | Implemented |
| (detail via `run_id`) | AI Run Detail | Domain: AI | `aio_view_ai_runs` | View run metadata (redacted), artifact summary; raw content requires `aio_view_sensitive_diagnostics` | Implemented |
| `aio-page-builder-ai-providers` | AI Providers | Domain: AI | `aio_manage_ai_providers` | Provider list; credential status (redacted); model defaults; connection test result; last successful use; disclosure (external transfer/cost); Test connection / Update credential placeholders; link to AI Runs | Implemented |
| `aio-page-builder-logs` | Logs | Infrastructure / Reporting | TBD | View operational logs | Not implemented |
| `aio-page-builder-privacy-reporting` | Privacy, Reporting & Settings | Domain: Reporting | `aio_manage_reporting_and_privacy` | Reporting disclosure (mandatory); retention state; uninstall/export behavior; environment & version summary; report destination (no secrets); privacy-policy helper text; diagnostics verbosity only if allowed | Implemented |
| `aio-page-builder-reporting` | Reporting | Domain: Reporting | TBD | Reporting disclosure, heartbeat status | Not implemented |
| `aio-page-builder-export-restore` | Import / Export | Domain: ExportRestore | `aio_export_data` (export); `aio_import_data` (restore/validate) | Create export (mode selection); export history; upload ZIP, validate, conflict summary, confirm restore; link to uninstall/export behavior | Implemented |
| `aio-page-builder-build-plan-analytics` | Build Plan Analytics | Domain: BuildPlan | `aio_view_build_plans` | View plan review trends, common blockers, execution failure trends, rollback frequency; filter by date; link to Build Plans | Implemented |
| `aio-page-builder-template-analytics` | Template Analytics | Domain: Registries / Reporting | `aio_view_logs` | View template usage by family/class, recommendation acceptance, rejection reasons, execution outcomes by family, rollback frequency, composition usage; filter by date, template family, page class; links to Build Plans, Queue & Logs, Page Templates, Compositions (spec §49.11, §59.12; Prompt 199, 200) | Implemented |

---

## 3. Rules

- **Stable slugs:** Do not rename or reuse the slugs above. New screens require a new row and a new slug following the pattern `aio-page-builder-{name}`.
- **Registry screens:** Section templates, page templates, compositions, documentation, and snapshots are fully specified in registry-admin-screen-contract.md (panels, actions, validation, deprecation, documentation/snapshot visibility). Implementations must follow that contract.
- **Build Plan screens:** List/detail slugs, three-zone layout, stepper, context rail, step workspace, row/detail, status/error/progress/completion, and capability separation are specified in build-plan-admin-ia-contract.md. Implementations must follow that contract.
- **Routing:** Screen rendering is handled by dedicated screen classes; no anonymous callbacks that embed logic. Menu registration only wires slug → screen class render method.
- **Capability:** Current screens use `manage_options` as a placeholder until capability mapping is finalized. All menu and screen access must remain capability-aware.
- **First-run:** Dashboard (and optionally Onboarding) are the intended first-run redirect targets; no implementation in this contract.
- **No hidden action URLs:** Use the registered menu slugs only; no unstructured admin routing or direct file includes based on request parameters.
