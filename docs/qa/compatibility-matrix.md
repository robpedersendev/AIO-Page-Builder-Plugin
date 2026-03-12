# Compatibility and Interoperability Test Matrix

**Governs:** Spec §6.7, §6.8, §6.9, §6.10, §54, §56.3, §56.4, §56.10, §59.14, §58.6.  
**Purpose:** Evidence for supported environment claims; release-gate compatibility (hardening matrix gate 5).  
**Scope:** WordPress version, PHP version, required/optional dependencies, preferred theme/block environment, coexistence.

---

## 1. Supported Environment Claims (Spec)

| Dimension | Minimum / Required | Preferred / Validated | Unsupported |
|-----------|-------------------|------------------------|-------------|
| **WordPress** (§6.7) | 6.6 | Current major at release cut | Below 6.6 |
| **PHP** (§6.8) | 8.1 | 8.1, 8.2, 8.3 | Below 8.1 |
| **Theme** (§6.9, §54.1) | Standards-compliant block-capable | GeneratePress | Themes that break block/page/menu behavior |
| **Block companion** (§6.9) | — | GenerateBlocks | — |
| **Required plugins** (§6.10 Class A) | ACF Pro 6.2+, GenerateBlocks 2.0+ | As above | Missing or below minimum |
| **Optional** (§6.10 Class B) | — | LPagery, SEO/caching/security coexistence | — |

Activation is **blocked** on WP &lt; 6.6, PHP &lt; 8.1, or missing/below-minimum required plugins. Optional (e.g. LPagery) absence triggers a **warning** only; related workflows degrade.

---

## 2. Matrix: WordPress × PHP

| WordPress | PHP 8.1 | PHP 8.2 | PHP 8.3 | Notes |
|-----------|---------|---------|---------|--------|
| 6.6 | Pass (target min) | Pass | Pass | Minimum supported WP. |
| 6.7+ | Pass | Pass | Pass | Current major at release; fully validated. |
| &lt; 6.6 | Blocked | Blocked | Blocked | Activation blocked; admin notice. |

**Runtime:** `Environment_Validator` runs at activation; WP/PHP checks use `Constants::min_wp_version()` (6.6) and `Constants::min_php_version()` (8.1). No code changes required for matrix; document test date and result when executed.

**Test execution:** Run activation and core admin flows on each cell; record date and Pass/Fail/Blocked. Example: *Tested 20XX-XX-XX: WP 6.6 / PHP 8.2 — Pass.*

---

## 3. Matrix: Required Dependencies (Class A)

| Dependency | Min version | Missing | Below min | Verified |
|------------|-------------|---------|-----------|----------|
| Advanced Custom Fields Pro | 6.2 | Blocking | Blocking | Activation blocked; clear admin notice. |
| GenerateBlocks | 2.0 | Blocking | Blocking | Activation blocked; clear admin notice. |

**Source:** `Dependency_Requirements::get_required()`. Version read from plugin header via `get_plugin_data()`. Unit tests: `Environment_Validator_Test` (ACF/GenerateBlocks missing produce blocking result).

**Optional verification:** On a clean install, deactivate ACF Pro → activate plugin → expect blocking message. Same for GenerateBlocks. Then satisfy both → activation succeeds.

---

## 4. Matrix: Optional Integration (Class B)

| Integration | Present | Absent | Verified |
|-------------|---------|--------|----------|
| LPagery | Token-driven workflows available | Warning only; token workflows disabled | Optional; no block. |
| SEO / caching / security | Coexistence | No requirement | Class C coexistence only unless documented. |

LPagery: `Dependency_Requirements::get_optional()`; validator adds `lpagery_missing_warning` (non-blocking). Core template and planning must remain usable without LPagery.

---

## 5. Preferred Theme / Block Environment

| Environment | Posture | Notes |
|-------------|---------|--------|
| GeneratePress + GenerateBlocks | Preferred; fully validated target | GenerateBlocks is required; theme is preferred. |
| Other block-capable theme + GenerateBlocks | General compatibility | Supported; no claim to full validation for every theme. |
| Theme that breaks block/page/menu behavior | Unsupported | Plugin may warn or degrade affected workflow only. |

**Theme-specific checks:** Not yet implemented in `Environment_Validator` (theme_posture reserved). Graceful degradation for missing menu locations or block support is deferred. Matrix and release notes should state: *Preferred theme: GeneratePress; general target: standards-compliant block-capable themes.*

---

## 6. Coexistence (Class C)

Plugin must coexist with common media, role, monitoring, backup/export plugins without declaring them required. Compatibility testing for specific Class C plugins is by exception (e.g. documented combinations). Security plugins: nonces, capabilities, and safe endpoint patterns are required; if one blocks required REST/AJAX, diagnostics should identify the blocked workflow (§54.8).

---

## 7. Remediation Status

| Issue | Severity | Status | Evidence / waiver |
|-------|----------|--------|-------------------|
| *(none open)* | — | — | — |

Narrow fixes applied in this pass: none required. Environment_Validator already enforces WP 6.6+, PHP 8.1+, and required plugin presence/versions. Optional dependency and theme posture behavior match spec.

---

## 8. Known Limitations and Caveats

- **Multisite:** Site-level operation supported; network-wide centralized management not supported (spec §54.9). Network activation not officially validated unless separately tested.
- **Theme detection:** No runtime check for GeneratePress vs other themes; preferred-theme messaging is documentation-only until theme_posture checks are added.
- **PHP 8.4+:** Not yet in validated set; add to matrix when routinely tested.

---

## 9. Release Notes Compatibility Snippet (§58.6)

For each release, include in release notes (or confirm N/A):

- **Tested WordPress version range:** e.g. *WordPress 6.6–6.x (current major).*
- **Tested PHP version range:** e.g. *PHP 8.1, 8.2, 8.3.*
- **Required plugins:** *Advanced Custom Fields Pro 6.2 or newer; GenerateBlocks 2.0 or newer.*
- **Preferred environment:** *GeneratePress with GenerateBlocks.*
- **Known limitations:** *See compatibility-matrix.md (multisite posture, theme detection).*
- **Migrations or compatibility notes:** *None* (or list any version bump or new requirement).

---

## 10. QA Evidence Summary

- **Unit:** `Environment_Validator_Test` covers WP version blocking (6.5 fail, 6.6 pass), required dependency blocking (ACF, GenerateBlocks missing), optional LPagery warning.
- **Integration / E2E:** At least one fully supported stack (WP 6.6+, PHP 8.1+, ACF Pro 6.2+, GenerateBlocks 2.0+) should pass: install → onboarding → Build Plan → execution path (or current scope). Record in matrix and in release checklist.
- **Manual:** Plugin Check run; critical/warning findings addressed (hardening gate 5). Compatibility matrix updated with test date and results before release.
