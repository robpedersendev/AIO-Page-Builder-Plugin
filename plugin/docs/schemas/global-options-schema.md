# Global Options Schema

**Document type:** Authoritative contract for plugin-owned WordPress options (spec §8.2, §8.7, §8.8, §9.4, §52.4, §52.6, §62.3).  
**Governs:** Option key names, sensitivity, exportability, default structures, ownership.  
**Rule:** Future prompts may add fields within approved option structures; option roots must not be renamed without a migration.  
**Related:** storage-strategy-matrix.md defines which data classes use options vs CPT, custom tables, user meta, etc. Provider credentials and secret storage (location, redaction, exclusion) are defined in docs/contracts/provider-secret-storage-contract.md.

---

## 1. Namespaced root strategy

All plugin options use the prefix `aio_page_builder_`. Keys are defined as constants in `Infrastructure\Config\Option_Names`. No ad hoc or domain-sprayed option names.

---

## 2. Option matrix

| Option name | Purpose | Sensitivity | Exportable | Default structure | Owning bucket/domain |
|-------------|---------|-------------|------------|-------------------|----------------------|
| `aio_page_builder_settings` | Main plugin configuration | admin-visible restricted | Yes (exclude any nested secrets) | `{}` | bootstrap / settings |
| `aio_page_builder_version_markers` | Version and migration markers | internal operational | No | `{}` | bootstrap / lifecycle |
| `aio_page_builder_reporting` | Reporting settings (destination, frequency placeholders) | admin-visible restricted | Yes (no secrets) | `{}` | reporting |
| `aio_page_builder_dependency_notices` | Dismissed dependency notices | internal operational | No | `{}` | bootstrap / diagnostics |
| `aio_page_builder_uninstall_prefs` | Uninstall/restore preferences | user-configured | Yes | `{}` | bootstrap / uninstall |
| `aio_page_builder_provider_config` | Provider config reference only; secrets in separate storage | privileged restricted / secret elsewhere | No | `{}` | AI provider (future) |
| `aio_page_builder_profile_current` | Current editable brand and business profile (single option; shape: `{ brand_profile, business_profile }` per profile-schema.md) | admin-visible restricted | Yes | `{}` | storage / profile |

---

## 3. Sensitivity and exportability

- **Exportable:** Included in full export by default per §52.4; must not contain API keys, passwords, or auth tokens (§52.6).
- **Internal/runtime:** Not exported; used for versioning, dismissals, or operational state.
- **Secret-bearing:** Provider credentials and tokens must not be stored inside exportable options; use separate storage and reference only non-secret metadata in options if needed.

---

## 4. User-configurable vs runtime/internal

| Classification | Options |
|----------------|---------|
| User-configurable | `aio_page_builder_settings`, `aio_page_builder_reporting`, `aio_page_builder_uninstall_prefs`, `aio_page_builder_profile_current` |
| Runtime / internal | `aio_page_builder_version_markers`, `aio_page_builder_dependency_notices` |
| Reference / future | `aio_page_builder_provider_config` |

---

## 5. Settings service and permissions

- **Settings_Service** (`Infrastructure\Settings\Settings_Service`) provides typed `get( key )` and `set( key, value )` for known keys only. Unknown keys throw.
- **Writes** must be capability-gated at the call site (e.g. `aio_manage_settings`). This prompt does not implement handlers; the requirement is documented here.
- **Sanitize** before persistence; **escape** before output. Do not store secrets in clear text inside exportable settings objects.

---

## 6. Migration rule

- New **fields** (or nested keys) may be added within an existing option structure when the owning domain introduces them.
- **Option roots** (top-level keys such as `aio_page_builder_settings`) must not be renamed or removed without a documented migration and data migration path.
