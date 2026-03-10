# Environment Validation Contract

**Document type:** Authoritative contract for environment and dependency validation.  
**Governs:** Activation gate, admin diagnostics, and high-impact workflow pre-checks (spec §6.13, §53.1).  
**Reference:** Master Specification §6.7, §6.8, §6.9, §6.10, §6.11, §6.12, §6.13.

---

## 1. Validation categories

| Category | Description |
|----------|-------------|
| `platform` | WordPress version, PHP version. |
| `required_dependency` | Required plugins (ACF Pro, GenerateBlocks): presence and minimum version. |
| `optional_integration` | Optional plugins (e.g. LPagery): presence only; missing triggers warning, not block. |
| `theme_posture` | Theme compatibility / GeneratePress posture. Placeholder for later. |
| `runtime_readiness` | Uploads directory, mail/report transport, scheduler, provider readiness. Placeholders for later. |

---

## 2. Result severities

| Severity | Meaning |
|----------|---------|
| `blocking_failure` | Workflow (e.g. activation) must stop. User is informed. |
| `warning` | Non-blocking; visible and loggable; related features may degrade. |
| `informational` | Notice only; no block, no required action. |

Blocking failures must stop the affected workflow. Warnings must be visible and logged. Optional plugin absence must not block activation.

---

## 3. Stable validation codes

Each rule has a stable code for diagnostics and tests. Do not rename.

### Platform

| Code | Severity | When |
|------|----------|------|
| `wp_version_blocking` | blocking_failure | WordPress below minimum (6.6). |
| `php_version_blocking` | blocking_failure | PHP below minimum (8.1). |

### Required dependency

| Code | Severity | When |
|------|----------|------|
| `acf_pro_missing_blocking` | blocking_failure | ACF Pro not active. |
| `acf_pro_version_blocking` | blocking_failure | ACF Pro below 6.2. |
| `generateblocks_missing_blocking` | blocking_failure | GenerateBlocks not active. |
| `generateblocks_version_blocking` | blocking_failure | GenerateBlocks below 2.0. |

### Optional integration

| Code | Severity | When |
|------|----------|------|
| `lpagery_missing_warning` | warning | LPagery not active; token workflows disabled. |

### Theme posture (reserved)

| Code | Severity | When |
|------|----------|------|
| `theme_posture_warning` | warning | Reserved for theme compatibility notice. |

### Runtime readiness (reserved)

| Code | Severity | When |
|------|----------|------|
| `uploads_readiness_info` | informational | Reserved for uploads directory check. |
| `mail_transport_warning` | warning | Reserved for mail/reporting transport. |
| `scheduler_readiness_warning` | warning | Reserved for WP-Cron/scheduler. |
| `provider_readiness_warning` | warning | Reserved for AI provider config. |

---

## 4. Minimum versions (exact)

- **WordPress:** 6.6 (spec §6.7).
- **PHP:** 8.1 (spec §6.8).
- **ACF Pro:** 6.2 (spec §6.11.1).
- **GenerateBlocks:** 2.0 (spec §6.11.2).

Defined in `Constants` (WP/PHP) and `Dependency_Requirements` (plugins). No undeclared plugin may become required.

---

## 5. Result structure

Each result has: `category`, `severity`, `code`, `message`, `is_blocking`. Implemented as `Validation_Result`. Messages are admin-safe; no secrets or sensitive server details.

---

## 6. Reuse

- **Activation:** `Lifecycle_Manager::validate_environment()` runs `Environment_Validator::validate()` and converts to `Lifecycle_Result`. No custom branching in the lifecycle manager.
- **Admin diagnostics:** Same validator and `get_results()`; full list for diagnostics UI (future).
- **High-impact workflows:** Same validator can be run before execution; blocking results stop the workflow.

Validation is server-side only. Do not trust client-reported versions.
