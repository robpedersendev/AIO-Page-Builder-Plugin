# Provider Credentials and Secret Storage Contract

**Document type:** Authoritative contract for provider credentials, secret references, and sensitive configuration (spec §23, §25, §43.13, §43.14, §45.9, §52.6, §59.8).  
**Governs:** Where credentials live, how they are referenced vs stored, redaction rules, validation rules, credential states, rotation/update posture, and exclusion rules for exports, logs, reports, diagnostics, and support bundles.  
**Reference:** Master Specification §43.13 Secrets Handling Rules, §43.14 Logging Redaction Rules, §45.9 Error Redaction Rules, §52.6 Excluded Data Categories; global-options-schema.md; storage-strategy-matrix.md; ai-provider-contract.md.

---

## 1. Scope and principles

- **Secrets boundary:** API keys, tokens, passwords, and any privileged authentication values are never part of exportable options, logs, reports, diagnostics, support bundles, or front-end code. This contract is strict; later prompts must not relax it.
- **Reference vs stored:** Provider configuration stored in options (e.g. `aio_page_builder_provider_config`) holds only non-secret metadata and references (e.g. provider_id, credential_state). Actual secret values live in segregated storage and are retrieved only server-side via a dedicated secret store interface.
- **No debug leakage:** There is no exception for "temporarily log the key for debugging." Redaction is absolute.
- **Provider-agnostic:** The storage and redaction contract is provider-agnostic. Provider drivers consume credentials via the secret store API or explicit references only; they never read raw secrets from general settings or option blobs.

---

## 2. Storage location and ownership

| Concept | Location | Owner | Exportable | Spec ref |
|--------|----------|--------|------------|----------|
| **Provider config (metadata only)** | Option `aio_page_builder_provider_config` | AI / provider domain | No | §9.4, global-options-schema.md |
| **Provider secrets (API keys, tokens)** | Segregated storage (not inside options blob) | AI / provider domain | No — always excluded | §43.13, §52.6, storage-strategy-matrix.md |

- **Segregated storage** may be implemented as a dedicated option (e.g. a separate option key used only for secret material), a custom table with access control, or another mechanism that ensures:
  - Secrets are never merged into exportable option structures.
  - Reads/writes are capability-gated and server-side only.
  - No serialization of this storage is included in export, restore payloads, logs, or reports.
- **Provider config option** holds only: provider identifiers, credential state (enum), non-secret metadata (e.g. default model id, endpoint override labels). It must not hold API keys, tokens, or passwords.

---

## 3. Credential states

Credentials for a given provider are in exactly one of the following states. State is stored in non-secret config (e.g. provider config option) so that UI and orchestration can act without reading secret values.

| State | Description | Allowed transitions (example) |
|-------|-------------|------------------------------|
| `absent` | No credential has been stored for this provider. | → `pending_validation`, `configured` (when user supplies and saves a credential). |
| `configured` | A credential is stored and has been accepted (e.g. after validation or first successful use). | → `invalid`, `rotated`, `absent` (on delete). |
| `invalid` | Stored credential failed validation or auth (e.g. 401). | → `configured` (after user updates and re-validates), `absent` (on delete). |
| `rotated` | Credential was replaced; previous value is no longer valid. Treated like configured for the new value. | → `configured`, `invalid`, `absent`. |
| `pending_validation` | Credential was just stored; not yet confirmed by a successful auth or validation step. | → `configured`, `invalid`, `absent`. |

- State is **not** a secret; it may appear in admin displays and in redacted diagnostics (e.g. "provider openai: configured").
- Transitions are driven by user actions (save, delete, test) or by operational results (auth failure → `invalid`). The contract does not mandate when validation runs; it only defines the state model and that state is stored in non-secret config.

---

## 4. Secret-bearing vs secret-free configuration (schema)

### 4.1 Records that must NOT contain secret values

| Record / artifact | Allowed content | Forbidden content |
|-------------------|-----------------|--------------------|
| Option `aio_page_builder_provider_config` | provider_id, credential_state, default_model_id, endpoint_label (non-secret), last_validated_at (optional) | api_key, secret, token, password, authorization header value |
| Export payloads (any format) | Same as above for provider config; no secret store dump | Any key or value that holds API keys, tokens, passwords |
| Logs (all levels) | Redacted placeholders only (see §6) | Raw secret values, partial keys, token prefixes |
| Reports (heartbeat, install, diagnostics) | credential_state per provider, provider_id | Any secret value or reference that could be used to authenticate |
| Support bundles / diagnostics dumps | Same as reports; redaction pass mandatory | Secrets, tokens, raw config that contains secrets |
| Front-end / JS / REST responses to admin | credential_state, provider_id, safe error messages | Any field containing or echoing a secret |

### 4.2 Records that may hold secret values (strictly controlled)

| Record / store | Purpose | Access | Must never be |
|----------------|---------|--------|----------------|
| Segregated secret store (implementation-specific) | Persist API keys/tokens for provider use | Server-side only; capability-gated reads/writes | Exported, logged, sent to front-end, or included in reports/diagnostics |

### 4.3 Provider config reference → secret store linkage

- **Config record (option):** For each provider, the option holds at most: `provider_id`, `credential_state`, and any non-secret settings. Option may hold a **reference** (e.g. "credentials for provider_id X are in the secret store under key X") but never the secret value.
- **Retrieval:** Code that needs a credential for a provider (e.g. a driver) calls the secret store interface with the provider identifier. The store returns the value only to the caller; the caller must not log, serialize, or expose that return value.
- **No scattered reads:** Provider logic must not read credentials from generic "settings" or from arbitrary option keys. All credential access goes through the defined secret store interface (or equivalent capability-gated API).

---

## 5. Redaction rules

### 5.1 Diagnostics and admin display

- **Display of provider config:** Only non-secret fields and credential state may be shown. If a UI shows "configured" or "invalid," it must not show the actual key or token.
- **Error messages:** Per §45.9, errors must be redacted before display. Replace or remove: secrets, tokens, passwords, sensitive payloads. Use generic messages (e.g. "Authentication failed") rather than provider messages that might contain keys or tokens.

### 5.2 Logs

- **All log levels:** Secrets, tokens, passwords, and confidential headers must never be written. Use a redaction layer before any log call that might receive config or request/response data.
- **Redaction format:** Replace secret values with a fixed placeholder (e.g. `[REDACTED]` or `[SECRET]`). Do not log partial keys, last-four characters, or token prefixes.
- **Structured data:** When logging arrays or objects that may contain known secret keys (e.g. `api_key`, `secret`, `token`, `password`), either strip those keys or replace their values with the placeholder. See §6 for key names and examples.

### 5.3 Reports (heartbeat, install, diagnostics)

- **Payloads:** Before submission, all payloads must pass through a redaction pass. Exclude: API keys, passwords, auth/session tokens (§52.6, §43.14).
- **Provider-related fields:** Only include provider_id and credential_state (e.g. "configured" / "absent" / "invalid"). Do not include any field that could be used to reconstruct or use a credential.

### 5.4 Exports and restore

- **Export:** Secret store and any blob containing secrets are always excluded from export packages. Provider config in the export must be the secret-free subset only (state + non-secret metadata).
- **Restore:** On restore, provider config (state + metadata) may be restored; secret values are not restored from the package. User must re-enter or re-authorize credentials after restore. Document this in restore UX.

### 5.5 Error redaction (§45.9)

- **Before display or external report:** Remove or mask secrets, tokens, passwords, sensitive raw payloads, and prohibited personal data from error content.
- **Provider errors:** If the provider returns an error message that might contain a key or token, do not forward it verbatim. Map to a normalized, safe message (see ai-provider-contract.md §5).

---

## 6. Redaction examples and key names

### 6.1 Known secret-bearing key names (non-exhaustive)

Keys that must be redacted when present in any structure that could be logged, exported, or reported:

- `api_key`, `apiKey`, `apikey`
- `secret`, `client_secret`, `client_secret_key`
- `token`, `access_token`, `refresh_token`, `bearer_token`
- `password`, `passwd`, `pwd`
- `authorization`, `auth_header`
- Any key ending in `_key`, `_secret`, `_token`, `_password` when used for authentication

### 6.2 Example: object before and after redaction

**Before (must never appear in log/export/report):**

```json
{
  "provider_id": "openai",
  "api_key": "sk-abc123xyz",
  "default_model": "gpt-4o"
}
```

**After redaction:**

```json
{
  "provider_id": "openai",
  "api_key": "[REDACTED]",
  "default_model": "gpt-4o"
}
```

Alternatively, the key may be removed: `{"provider_id": "openai", "default_model": "gpt-4o"}`. Contract requires that the value is never present in logs, exports, or reports.

### 6.3 Example: error message redaction

**Unsafe (must not be shown):** "OpenAI returned 401: Invalid API key sk-abc123xyz."

**Safe:** "OpenAI returned 401: Invalid API key." or "Authentication failed (invalid credentials)."

---

## 7. Validation and update/rotation posture

- **Validation:** Credential validation (e.g. test call to provider) must be performed server-side. Result may update credential state to `configured` or `invalid`; never log or return the raw credential.
- **Update/replacement:** When the user submits a new key or token, write it only to the segregated secret store. Update the provider config option to set credential_state (e.g. to `pending_validation` or `configured`) and do not write the secret into the option.
- **Rotation:** Replacing a credential is treated as an update: write new value to secret store, update state, optionally set state to `rotated` then `configured`. Old value must not appear in logs or exports.
- **Deletion:** Removing credentials clears the secret from the store and sets state to `absent` in config. No secret material may remain in exportable or loggable data.

---

## 8. Export / restore exclusions and restore behavior

| Data | Export | Restore |
|------|--------|---------|
| Provider config (metadata + state only) | Include (secret-free subset) | Restore metadata/state; user re-enters secrets |
| Secret store / raw credentials | Always excluded | Never restored from package; user must reconfigure |
| Logs, reports, diagnostics | N/A (not restored) | N/A |

- **Exclusion checklist (export):** Before adding any field to an export payload, confirm it is not in the excluded data categories (§52.6): API keys, passwords, auth/session tokens. Provider credentials are always in that category.
- **Restore behavior:** After restore, the plugin may show provider_id and credential_state from restored config; if state was `configured`, the actual credential is missing until the user re-enters it. UI should prompt for re-entry where needed.

---

## 9. Code-level secret store interface (optional)

If a code-level interface is provided, it must be provider-agnostic and must not expose raw secrets in method signatures that could be logged or serialized. Suggested shape:

- **Get credential for provider (server-side only):** Method that accepts `provider_id` (and optionally a key name) and returns the secret value. Return value must be used only in memory for the immediate request (e.g. passed to the provider client); caller must not log, serialize, or send it.
- **Credential state (safe to log/display):** Method that returns credential state for a provider (e.g. `absent`, `configured`, `invalid`). This may be part of the same interface or a separate read-only view.
- **Write (capability-gated):** Method to set or replace a credential for a provider. Must be capability-gated; must write only to segregated storage; must not write to exportable options.
- **Redaction helper:** A dedicated redactor that accepts an array or string and returns a copy with known secret keys redacted or removed. Used by code paths that prepare data for logs, exports, or reports.

See optional implementations: `Provider_Secret_Store_Interface.php`, `Secret_Redactor.php`. Method names must be explicit and provider-agnostic.

---

## 10. Exclusion checklists

### 10.1 Before writing to a log

- [ ] Payload has been passed through the redaction layer.
- [ ] No key in the payload is a known secret-bearing key (§6.1), or its value has been replaced with a placeholder.
- [ ] No raw error message from an external API is logged without redaction.

### 10.2 Before adding data to an export package

- [ ] Data is not in the §52.6 excluded categories (API keys, passwords, auth/session tokens).
- [ ] Provider config included is the secret-free subset only (provider_id, credential_state, non-secret metadata).
- [ ] No serialization of the secret store or any option that contains secret values is included.

### 10.3 Before sending a report (heartbeat, install, diagnostics)

- [ ] Redaction pass has been applied to all payloads.
- [ ] Only credential_state and provider_id (or equivalent) are included for provider-related data; no secret values or references that could be used to authenticate.

### 10.4 Before displaying in admin or returning via REST to front-end

- [ ] No secret value is included in the response.
- [ ] Error messages have been redacted per §45.9.

---

## 11. Cross-references

- **Global options:** global-options-schema.md — `aio_page_builder_provider_config` is reference-only; secrets in separate storage.
- **Storage strategy:** storage-strategy-matrix.md — Provider secrets in segregated storage; always excluded from export.
- **Provider abstraction:** ai-provider-contract.md — Credentials are not part of request/response shapes; drivers receive credentials via a separate, secure path.
- **Spec:** §43.13 Secrets Handling Rules; §43.14 Logging Redaction Rules; §45.9 Error Redaction Rules; §52.6 Excluded Data Categories; §25.3–25.4 Authentication and API key storage; §59.8 AI and planning phase.

---

## 12. Out of scope for this contract

- Provider driver implementation.
- Onboarding submission logic or credentials UI form.
- Connection/test UI for providers.
- Secret encryption redesign beyond what the spec allows (e.g. at-rest encryption is a separate concern; this contract governs location, redaction, and exclusion).
- Implementation of export/restore pipelines; this contract defines the rules they must follow.
