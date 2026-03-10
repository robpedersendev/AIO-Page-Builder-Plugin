# File Architecture Contract

**Document type:** Authoritative contract for plugin file and folder architecture.  
**Governs:** All implementation prompts; Cursor and developers must follow this contract for every later prompt.  
**Reference:** AIO Page Builder Master Specification — §0.4, §0.8, §0.9, §0.10, §5.1, §5.2, §5.3, §6.3, §57.2, §57.3, §57.8, §59.1, §59.2.

---

## 1. Plugin root and top-level paths

The **plugin root** is the directory that contains the main plugin file `aio-page-builder.php`. All paths in this contract are relative to that root.

**Canonical top-level paths:**

| Path | Purpose |
|------|---------|
| `aio-page-builder.php` | Single main plugin entry point. |
| `uninstall.php` | Uninstall cleanup only; no business logic. |
| `src/` | PHP source code by layer and domain. |
| `assets/admin/` | Admin UI assets (CSS, JS, images). |
| `assets/front/` | Front-end assets used by rendered content. |
| `docs/contracts/` | Authoritative contracts (this document and others). |
| `docs/schemas/` | Schema definitions (AI, export, registry, etc.). |
| `docs/migrations/` | Migration and upgrade runbooks/specs. |
| `tests/Unit/` | Unit tests. |
| `tests/Integration/` | Integration tests. |

No other top-level files or directories may be added unless this contract is updated first.

---

## 2. Namespace root

The **single** PHP namespace root is:

- **`AIOPageBuilder`**

All plugin PHP classes must live under this root. No second namespace root (e.g. no alternate vendor or product prefix) may be introduced. Subnamespaces reflect directory structure under `src/` (e.g. `AIOPageBuilder\Domain\Storage`).

---

## 3. Directory responsibilities and “do not mix” rules

### 3.1 `src/Bootstrap/`

- **Responsibility:** Plugin bootstrap, loader, and wiring of services. Registers hooks, loads autoloader, and delegates to domain and infrastructure only via registered services.
- **Do not:** Put domain logic, execution logic, registry logic, or business rules here. Bootstrap files may wire services but **must not absorb domain logic**.

### 3.2 `src/Admin/`

- **Responsibility:** Admin UI: menus, screens, settings pages, and administration of plugin features. Renders and administers; calls domain or REST/AJAX for data and actions.
- **Do not:** Put execution logic (page creation, replacement, menu updates, rollback execution) here. Admin code may **render and administer** but **must not contain execution logic**.

### 3.3 `src/Domain/`

Domain layer. Each subdirectory is one domain; cross-domain access is only via explicit services or interfaces.

#### 3.3.1 `src/Domain/Storage/`

- **Responsibility:** Persistence of plugin data: options, post meta, custom tables, and storage abstractions used by other domains.
- **Do not:** Orchestration, AI, rendering, or execution logic.

#### 3.3.2 `src/Domain/Registries/`

- **Responsibility:** Section template registry, page template registry, composition registry, and other canonical indexes of system definitions.
- **Do not:** ACF field rendering, execution, or AI prompt logic.

#### 3.3.3 `src/Domain/ACF/`

- **Responsibility:** ACF field definitions, field groups, and assignment rules.
- **Do not:** Registry definition ownership, execution, or rendering of final front-end output.

#### 3.3.4 `src/Domain/Rendering/`

- **Responsibility:** Translation of templates and structured data into native block content and front-end markup; CSS/ID/class contract ownership for output.
- **Do not:** Execution (create/replace pages), AI, or crawl logic.

#### 3.3.5 `src/Domain/Crawler/`

- **Responsibility:** Public-site crawl and analysis; crawl snapshots and site inventory.
- **Do not:** AI provider calls, execution, or registry definitions.

#### 3.3.6 `src/Domain/AI/`

- **Responsibility:** AI provider abstraction, prompt packs, AI run orchestration, and structured output validation.
- **Do not:** Direct execution of site mutations; execution belongs in Execution domain.

#### 3.3.7 `src/Domain/BuildPlan/`

- **Responsibility:** Build plan generation from AI/local output, plan structure, and plan presentation data. Planner-only.
- **Do not:** Execution of plan steps; that belongs in Execution.

#### 3.3.8 `src/Domain/Execution/`

- **Responsibility:** Executor: performing approved actions (page create/replace, hierarchy, menus, tokens, status). Acts only on validated inputs.
- **Do not:** AI calls, plan generation, or rendering logic.

#### 3.3.9 `src/Domain/Rollback/`

- **Responsibility:** Snapshots, diffs, and rollback recovery operations.
- **Do not:** Initial execution of changes or AI logic.

#### 3.3.10 `src/Domain/Reporting/`

- **Responsibility:** Operational reporting (install notification, heartbeat, diagnostics) per private-distribution policy.
- **Do not:** Core page content, execution logic, or unrelated domain logic.

#### 3.3.11 `src/Domain/ExportRestore/`

- **Responsibility:** Export, import, restore, and survivability-related data handling.
- **Do not:** Execution, AI, or rendering.

**Domain cross-boundary rule:** Domain code **may not** reach directly into unrelated domains except through **explicit services or interfaces**. No ad hoc coupling between domains.

### 3.4 `src/Infrastructure/`

- **Responsibility:** Adapters to WordPress and external systems: REST routes, AJAX handlers, WP-Cron, HTTP client, database access wrappers where not part of Domain/Storage.
- **Do not:** Business rules and domain logic; infrastructure implements or delegates to domain.

### 3.5 `src/Support/`

- **Responsibility:** Shared utilities, helpers, and cross-cutting support (e.g. logging, sanitization, small utilities). No single domain owns Support.
- **Do not:** Domain-specific business logic, execution logic, or registry/ACF/rendering/AI/execution rules. No catch-all “helpers” dumping ground; keep responsibilities narrow.

### 3.6 `assets/admin/`

- **Responsibility:** CSS, JavaScript, and images for the WordPress admin experience.
- **Do not:** Secrets, API keys, or mutation logic; all mutation-capable behavior must live in server-side PHP.

### 3.7 `assets/front/`

- **Responsibility:** CSS, JS, or assets used by front-end rendered content.
- **Do not:** Secrets or privileged operations; front-end is non-privileged.

### 3.8 `docs/contracts/`

- **Responsibility:** Authoritative contracts (file architecture, API contracts, behavior contracts).
- **Do not:** Code, executable examples with real secrets, or ad hoc notes that override the contract.

### 3.9 `docs/schemas/`

- **Responsibility:** Schema definitions for AI I/O, export manifests, registry structures, and other structured data.
- **Do not:** Implementation code or secrets.

### 3.10 `docs/migrations/`

- **Responsibility:** Migration and upgrade specifications and runbooks.
- **Do not:** Implementation code or secrets.

### 3.11 `tests/Unit/`

- **Responsibility:** Unit tests for classes and small units.
- **Do not:** Production business logic; only test code and test fixtures.

### 3.12 `tests/Integration/`

- **Responsibility:** Integration tests (WordPress, DB, external boundaries as needed).
- **Do not:** Production business logic; only test code and test fixtures.

---

## 4. Contract rules (mandatory for all later prompts)

1. **No new top-level directories**  
   No later prompt may create new top-level directories (sibling to `src/`, `assets/`, `docs/`, `tests/`) without updating this file-architecture contract and having that change approved.

2. **Bootstrap does not absorb domain logic**  
   Bootstrap files may wire services and register hooks only. They must not contain registry, ACF, rendering, crawler, AI, build plan, execution, rollback, reporting, or export logic.

3. **Admin does not contain execution logic**  
   Admin code may render UI and administer (e.g. settings, list screens, forms). It must not perform execution of build plans, page creation/replacement, menu updates, or rollback execution; those belong in Domain/Execution and related domains.

4. **Domain boundaries**  
   Domain code may not reach directly into unrelated domains except through explicit services or interfaces. No ad hoc cross-domain coupling.

5. **Single namespace root**  
   All plugin PHP uses the single namespace root `AIOPageBuilder`. No second root.

6. **No framework or DI container requirement**  
   This contract does not introduce a framework or external dependency-injection container. Wiring may be done in Bootstrap or minimal loader code.

7. **No generic dumping grounds**  
   Do not collapse domain logic into `includes/`, a single `helpers.php`, or other catch-all locations. Keep module boundaries as defined above.

---

## 5. Approved path classes (schema)

| Path class | Allowed contents | Prohibited contents |
|------------|------------------|---------------------|
| Bootstrap files (`src/Bootstrap/`) | Loader, hook registration, service wiring, autoload delegation | Domain logic, execution, registries, ACF, rendering, crawl, AI, build plan, rollback, reporting, export |
| Admin screens (`src/Admin/`) | Menus, pages, settings UI, admin-only assets usage | Execution logic, direct DB/orchestration, secrets in UI |
| Domain services (`src/Domain/*`) | Business logic for that domain; interfaces; domain-internal persistence | Logic belonging to another domain (except via interfaces/services); execution in non-Execution domains |
| Infrastructure adapters (`src/Infrastructure/`) | REST, AJAX, cron, HTTP, WP API wrappers | Core business rules and domain logic |
| Support utilities (`src/Support/`) | Shared helpers, logging, sanitization, small utilities | Domain-specific business logic, execution, registries |
| `docs/contracts` | Contract documents (this file and others) | Code, secrets, executable examples with credentials |
| `docs/schemas` | Schema definitions (JSON, text, or doc) | Implementation code, secrets |
| `docs/migrations` | Migration specs and runbooks | Implementation code, secrets |
| `tests/Unit`, `tests/Integration` | Test code, mocks, fixtures | Production business logic, secrets |

---

## 6. Security and permissions (contract baseline)

- **Mutation-capable handlers** must live in **server-side PHP classes**, not in client-side code. Any action that creates, updates, or deletes data or site structure must be implemented in PHP and invoked via REST, AJAX, or form submission with server-side validation.
- **Privileged actions** (admin-only or capability-gated) must pass **capability checks** and **nonce verification** (or equivalent intent verification). This contract does not implement them but requires that all future such handlers comply.
- **Secrets** (API keys, passwords, tokens) must **never** be placed in front-end assets, docs examples, or debug fixtures. Secrets must be stored and used only in server-side, access-controlled code.

---

## 7. Manual verification checklist

Use this checklist to confirm the file architecture is in place and the contract is satisfied:

- [ ] **Top-level structure exists:** Plugin root contains `aio-page-builder.php`, `uninstall.php`, and directories `src/`, `assets/`, `docs/`, `tests/` (or equivalent as specified in §1).
- [ ] **Required domain directories exist:** Under `src/Domain/`: `Storage/`, `Registries/`, `ACF/`, `Rendering/`, `Crawler/`, `AI/`, `BuildPlan/`, `Execution/`, `Rollback/`, `Reporting/`, `ExportRestore/`.
- [ ] **No unapproved top-level directories:** No other top-level directories exist except those listed in §1 (or explicitly added by a contract update).
- [ ] **Bootstrap directory:** `src/Bootstrap/` exists and is reserved for bootstrap/wiring only (no domain logic).
- [ ] **Admin directory:** `src/Admin/` exists and is reserved for admin UI only (no execution logic).
- [ ] **Infrastructure and Support:** `src/Infrastructure/`, `src/Support/` exist and are reserved as in §3.4 and §3.5.
- [ ] **Assets:** `assets/admin/`, `assets/front/` exist.
- [ ] **Docs:** `docs/contracts/`, `docs/schemas/`, `docs/migrations/` exist.
- [ ] **Tests:** `tests/Unit/`, `tests/Integration/` exist.
- [ ] **Responsibility and prohibition:** Each directory above has a declared responsibility and a “do not” / prohibition pair as in §3 and §5.

---

*This contract locks the canonical plugin file and folder architecture. Later prompts must not invent ad hoc folders, class locations, or mixed-responsibility files.*
