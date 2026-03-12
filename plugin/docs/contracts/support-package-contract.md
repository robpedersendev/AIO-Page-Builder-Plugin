# Support Package Contract

**Document type:** Authoritative contract for support-safe bundle generation (spec §52.1 Support bundle, §48.10 Log export, §45.9 Redaction, §59.15 Production Readiness).  
**Governs:** Support_Package_Generator, Support_Package_Result, manifest extensions, category rules, and redaction.

---

## 1. Purpose

A **support package** is a curated, support-safe ZIP produced for production-readiness and support review. It is distinct from a full operational backup:

- **Not** a forensic or full dump: only approved categories are included.
- **Redaction is mandatory:** settings, profile, and any included log/context must be redacted per §45.9.
- **No secrets:** API keys, passwords, auth/session tokens, runtime lock rows, and prohibited raw payloads must never appear.
- **Useful for support:** environment summary, plugin/build metadata, key plan/run references, and selected safe artifacts.

---

## 2. Support Package Type and Manifest

- **support_package_type:** Always `support_bundle` (aligned with Export_Mode_Keys::SUPPORT_BUNDLE).
- **Manifest:** Extends export bundle manifest (export-bundle-structure-contract.md) with:
  - **redaction_summary** (object): `{ "applied": true, "keys_redacted": ["api_key", ...] }` — indicates redaction was applied; keys_redacted is a list of key names or categories that were redacted (no values).
  - **restore_notes:** Must state that the package is for support only and not for full restore.

---

## 3. Included Support Categories

Approved support-safe categories (aligned with §52.1 support bundle):

- **Always included (with redaction where applicable):** `settings`, `profiles`, `registries`, `compositions`, `plans`, `token_sets`, `uninstall_restore_metadata`.
- **Optional (caller may request):** `logs`, `reporting_history`. When included, content must be redacted per log export rules (§48.10) and error redaction (§45.9).

---

## 4. Excluded Categories

The following must **never** appear in a support package:

- From Export_Bundle_Schema::EXCLUDED_CATEGORIES: `api_keys`, `passwords`, `auth_session_tokens`, `runtime_lock_rows`, `temporary_cache`, `corrupted_remnants`.
- Support-bundle-specific exclusions: `raw_ai_artifacts`, `normalized_ai_outputs`, `crawl_snapshots`, `rollback_snapshots`.

---

## 5. Redaction Rules

- **Settings and profile:** Keys matching secret-like names (e.g. api_key, password, token, credential, secret, auth) are omitted or replaced with a placeholder; nested structures are recursively redacted.
- **Logs and reporting_history:** Messages and context must be redacted via Reporting_Redaction_Service (or equivalent); no raw stack traces or unredacted credentials.
- **Environment summary:** PHP version, WordPress version, plugin version — no hostnames or paths that could expose sensitive structure if prohibited by policy.

---

## 6. Package Location and Naming

- **Controlled path:** Support packages may be written to the plugin exports directory using the same filename pattern as other exports: `aio-export-support_bundle-YYYYMMDD-HHMMSS-{site_slug}.zip`, so that existing download and listing UI can serve them. Alternatively, implementations may use the plugin `support-bundles/` child directory with a distinct filename pattern (e.g. `aio-support-YYYYMMDD-HHMMSS-{site_slug}.zip`); in that case, admin screens must expose download via a separate, permission-gated action.
- **Package reference:** A stable identifier for the package (e.g. filename or a generated reference) is returned in the result for UI display and safe download metadata. No full server path is exposed to the client.

---

## 7. Result Shape (Support_Package_Result)

Stable fields for UI and logging (no secrets):

- **success** (bool)
- **message** (string)
- **package_path** (string, server-side only; empty when exposing to client)
- **package_filename** (string)
- **support_package_type** (string): `support_bundle`
- **included_support_categories** (list<string>)
- **redaction_summary** (object): `{ "applied": bool, "keys_redacted": list<string> }`
- **package_reference** (string): Safe identifier for admin/UI (e.g. filename)
- **generation_log_reference** (string): Log reference for this generation run
- **checksum_count** (int), **package_size_bytes** (int)

---

## 8. Permissions and Security

- Only authorized users (e.g. `aio_export_data` or equivalent) may trigger support package generation.
- Package contents and paths are permission-aware and server-side; download links must be nonce-protected and capability-checked.
- No automatic upload to remote support destinations within this contract; no public download endpoints.

---

## 9. References

- export-bundle-structure-contract.md (§2.1 support bundle)
- Master spec §52.1, §48.10, §45.4, §45.9, §59.15
