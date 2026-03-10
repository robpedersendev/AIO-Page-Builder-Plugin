# Versioning Contract

**Document type:** Authoritative contract for plugin versioning, schema versions, and constants.  
**Governs:** All code that reads or writes version identifiers; migrations, exports, and registries depend on this contract.  
**Reference:** Master Specification §6.5, §6.6, §6.7, §6.8, §57.2, §57.5, §58.1, §58.4, §58.5.

---

## 1. Single source of truth

- **Constants:** All plugin path, URL, version, and minimum environment values are defined in `src/Bootstrap/Constants.php`. The root plugin file must not define duplicate constants; it only loads Constants and calls `Constants::init()`.
- **Version map:** Schema and contract versions are exposed via `src/Infrastructure/Config/Versions.php`. The map keys are stable; values are advanced only by prompts that own migrations, registries, or export manifests.

**Rule:** Version and identity changes happen centrally in Constants and Versions. No inline version definitions or duplicate identifiers in unrelated modules.

---

## 2. Stable version map keys

The following keys are part of the contract. Future prompts may **append** domain-specific keys but **must not rename** these roots:

| Key | Purpose |
|-----|---------|
| `plugin` | Plugin release version (synced with plugin header and Constants). |
| `global_schema` | Global schema contract version for compatibility and migration. |
| `table_schema` | Custom table schema version for DB migrations. |
| `registry_schema` | Section/page template registry schema version. |
| `export_schema` | Export manifest and backup schema version. |

All keys are machine-readable and exposed via `Versions::all()` and individual accessors (`Versions::plugin()`, `Versions::global_schema()`, etc.).

---

## 3. Constants and identity

Constants (or their accessors) provide:

- Plugin file path (absolute)
- Plugin directory path (absolute, trailing slash)
- Plugin directory URL (trailing slash)
- Plugin version string
- Plugin basename (for WordPress)
- Minimum supported WordPress version
- Minimum supported PHP version

Minimum versions must match the spec exactly: WordPress 6.6 (§6.7), PHP 8.1 (§6.8).

---

## 4. Namespace

The PHP namespace root remains **`AIOPageBuilder`**. Constants live under `AIOPageBuilder\Bootstrap`; Versions under `AIOPageBuilder\Infrastructure\Config`. No second namespace root.

---

## 5. Security and immutability

- Constants and version values are internal, immutable at runtime, and not writable through request data.
- No secrets or user-controlled values in Constants or Versions.

---

## 6. Manual verification checklist (Prompt 003)

- [ ] Constants load before Plugin run (root requires Constants and calls `Constants::init()` before requiring Plugin).
- [ ] No duplicate version or path definitions remain in the root plugin file.
- [ ] Minimum WordPress version is 6.6 and minimum PHP version is 8.1 (match spec §6.7, §6.8).
- [ ] `Versions::all()` returns the five stable keys: `plugin`, `global_schema`, `table_schema`, `registry_schema`, `export_schema`.
- [ ] Version changes are made only in Constants.php or Versions.php, not inline in random modules.
