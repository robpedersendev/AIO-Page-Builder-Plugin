# AI Provider Abstraction Contract

**Document type:** Authoritative contract for the AI provider abstraction layer (spec §23, §25, §26, §43.13, §59.8).  
**Governs:** Provider interface, normalized request/response shapes, structured output, capability metadata, error normalization, and planner-safe constraints.  
**Reference:** Master Specification §25.1–25.13, §26 Prompt Pack and Input Artifact Foundations, §43.13 Secrets Handling.

---

## 1. Scope and principles

- **Provider-agnostic:** The rest of the system interacts only with a normalized provider interface. No vendor-specific logic outside the provider layer.
- **Structured outputs mandatory:** AI outputs are planning inputs, not execution authority. All provider responses that feed planning must validate against plugin-owned schemas. Freeform string outputs cannot bypass schema validation.
- **Secrets boundary:** Credentials and API keys are never part of this contract's data shapes. They are handled separately, server-side; never in logs, exports, reports, or front-end code (§43.13).
- **Planner/executor separation:** Raw provider responses must not be treated as trusted execution instructions. Normalized structured payloads drive planning; execution is gated by approval and separate services.

---

## 2. Provider interface (method list)

The following methods define the stable contract that all provider drivers must implement. A code-level interface exists at `src/Domain/AI/Providers/AI_Provider_Interface.php`; drivers implement this interface and map vendor APIs to the normalized request/response shapes below. Request and response types are normalized; drivers adapt vendor APIs to these shapes.

| Method | Purpose |
|--------|---------|
| `get_provider_id(): string` | Stable identifier for the provider (e.g. `openai`, `anthropic`). Used in capability checks and run metadata. |
| `get_capabilities(): array` | Returns capability metadata (see §6). Used to decide model selection, structured output, retries. |
| `request(Normalized_Request $request): Normalized_Response` | Performs the AI request. Accepts only the normalized request shape; returns only the normalized response shape. Throws or returns a normalized error result for failures. |
| `supports_structured_output(string $schema_ref): bool` | Whether this provider supports the given schema reference (e.g. JSON Schema identifier). Informs request assembly. |

Optional extensions (may be added later without breaking the contract):

- Model listing / default model for a use case.
- Health or connectivity check (for diagnostics only).

---

## 3. Normalized request shape

All provider requests are assembled into this structure. Drivers map it to vendor-specific APIs. No credentials are part of this structure.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `request_id` | string | Yes | Idempotency / traceability. Opaque to provider. |
| `model` | string | Yes | Model identifier (provider-specific string; e.g. `gpt-4o`, `claude-3-5-sonnet`). Selected per capability and product rules. |
| `system_prompt` | string | Yes | System prompt text (after redaction). |
| `user_message` | string | Yes | User/context message (e.g. assembled from prompt pack + artifacts). |
| `structured_output_schema_ref` | string | No | Reference to the plugin-owned schema that the response must satisfy (e.g. `aio/build-plan-draft-v1`). When present, provider must return output that validates against this schema. |
| `context_artifacts` | array | No | References or bounded excerpts (registry summary, crawl summary, profile summary). No raw secrets or unbounded payloads. |
| `max_tokens` | int | No | Upper bound on completion tokens. |
| `temperature` | float | No | 0.0–2.0 where supported. |
| `timeout_seconds` | int | No | Request timeout. |
| `options` | array | No | Provider-agnostic options (e.g. `prefer_json: true`). Provider-specific overrides stay inside the driver. |

**Rules:**

- Request assembly must be deterministic and auditable (§25.7). A redaction pass must run before submission; no secrets in `system_prompt`, `user_message`, or `context_artifacts`.
- `structured_output_schema_ref` when set means the plugin expects a structured payload; the driver must request the provider’s structured-output mechanism and the response normalizer must validate or reject.

---

## 4. Normalized response shape

All successful provider responses are normalized into this structure. Raw provider-specific metadata may be retained in a separate field for debugging only; it must not drive planning logic.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `request_id` | string | Yes | Echo of the request’s `request_id`. |
| `success` | bool | Yes | `true` if the call succeeded and a structured payload is present (when required). |
| `structured_payload` | array\|object | Conditional | Present when `structured_output_schema_ref` was set and validation passed. Must validate against the referenced plugin schema. |
| `raw_content` | string | No | Raw text from the provider (e.g. when no schema was requested). Must not be used as execution authority. |
| `provider_id` | string | Yes | Echo of the provider identifier. |
| `model_used` | string | Yes | Model actually used (for cost/telemetry and run metadata). |
| `usage` | object | No | Placeholder for token/cost telemetry. Suggested shape: `{ "prompt_tokens": int, "completion_tokens": int, "total_tokens": int, "cost_placeholder": null }`. Cost may be filled by a separate telemetry layer. |
| `raw_provider_metadata` | object | No | Provider-specific response metadata (e.g. `id`, `created`). For debugging only; must not be used for branching or execution decisions. |
| `normalized_error` | object | Conditional | Present when `success` is `false`. See §5. |

**Rules:**

- Separation between raw provider response and normalized internal representation is required (§25.9). The plugin never treats raw provider JSON as trusted; validation against plugin-owned schemas is mandatory for structured outputs.
- Structured output that fails validation must be represented as a normalized error (e.g. `validation_failure`), not as success with invalid payload.

---

## 5. Error normalization

Provider errors are normalized into a single internal representation. User-facing messages must be clear; internal debugging may retain provider-specific detail in a bounded way.

### 5.1 Error object shape

| Field | Type | Description |
|-------|------|-------------|
| `category` | string | One of the categories in §5.2. |
| `user_message` | string | Short, safe message for UI (§45.3). |
| `internal_code` | string | Stable code for logging and retry logic (e.g. `auth_failure`, `rate_limit`). |
| `provider_raw` | string\|null | Optional provider-specific message or code. Must be redacted if it could contain secrets. |
| `retry_posture` | string | One of: `no_retry`, `retry_with_backoff`, `retry_once`. Informs caller whether to retry. |

### 5.2 Error categories and retry posture

| Category | Internal code (example) | Retry posture | Description |
|----------|-------------------------|---------------|-------------|
| Authentication failure | `auth_failure` | no_retry | Invalid or missing credentials. |
| Rate limit | `rate_limit` | retry_with_backoff | Provider rate limit hit. |
| Timeout | `timeout` | retry_with_backoff | Request timed out. |
| Malformed response | `malformed_response` | retry_once | Response could not be parsed or was not in expected form. |
| Validation failure | `validation_failure` | no_retry | Structured output did not validate against the plugin schema. |
| Unsupported feature | `unsupported_feature` | no_retry | Requested feature (e.g. schema) not supported by provider/model. |
| Provider error | `provider_error` | retry_with_backoff | Generic provider-side error (5xx, etc.). |
| Network / transport | `network_error` | retry_with_backoff | Transport failure before provider response. |

Retry and backoff behavior (upper bounds, backoff logic) are defined at the contract level; actual retry is implemented by the caller or a dedicated orchestration layer (§25.10).

---

## 6. Capability metadata shape

Each provider declares capabilities so the plugin can adapt behavior without vendor-specific branching in planning code.

| Field | Type | Description |
|-------|------|-------------|
| `provider_id` | string | Same as `get_provider_id()`. |
| `structured_output_supported` | bool | Whether the provider supports schema-constrained structured output. |
| `file_attachment_supported` | bool | Whether file attachments are supported. |
| `max_context_tokens` | int\|null | Approximate context window size if known. |
| `models` | array | List of model identifiers and per-model capability hints. Suggested entry: `{ "id": string, "supports_structured_output": bool, "default_for_planning": bool }`. |
| `error_format_notes` | string | Optional note on provider error format for normalization. |
| `retry_notes` | string | Optional note on recommended retry/backoff. |

A **provider capability matrix** (e.g. in docs or config) can list providers and their capabilities for reference; the runtime uses the programmatic `get_capabilities()` return value.

---

## 7. Model selection metadata

- Model choice must be visible, intentional, and recorded (§25.6). The normalized request carries `model`; the normalized response carries `model_used`.
- Compatibility checks between model and required features (e.g. structured output for a given schema) must be performed before request assembly. If the selected model does not support the required schema, the contract expects either a capability check failure or an `unsupported_feature` error at request time, not a silent fallback to freeform output.

---

## 8. Structured-output requirement

- **Schema-based output targeting:** When a request includes `structured_output_schema_ref`, the response must include a `structured_payload` that validates against the plugin-owned schema referenced. No freeform string output may be used in place of schema-validated data for planning (§25.9).
- **Explicit rejection:** When structured output is not returned or validation fails, the response must be a normalized error (e.g. `validation_failure`), not a success with unvalidated content.
- **Plugin-owned schemas:** Schemas are defined and versioned by the plugin (e.g. build-plan draft schema). Providers may use their own mechanism (e.g. JSON Mode, tool use) to produce output that is then validated against the plugin schema.

---

## 9. Token / cost telemetry placeholders

- The normalized response includes a `usage` object with `prompt_tokens`, `completion_tokens`, `total_tokens`, and an optional cost placeholder.
- Cost calculation and reporting are out of scope for the provider contract; a separate telemetry or reporting layer may consume `usage` and compute cost. Logging must not include secrets (§43.13, §43.14); usage and cost data must not contain credentials or raw request/response bodies.

---

## 10. Valid and invalid examples

### 10.1 Valid normalized request (minimal)

```json
{
  "request_id": "req_abc123",
  "model": "gpt-4o",
  "system_prompt": "You are a planning assistant.",
  "user_message": "Analyze the following site summary.",
  "structured_output_schema_ref": "aio/build-plan-draft-v1",
  "max_tokens": 4096,
  "timeout_seconds": 60
}
```

### 10.2 Valid normalized response (success, structured)

```json
{
  "request_id": "req_abc123",
  "success": true,
  "structured_payload": { "version": "1", "sections": [] },
  "raw_content": null,
  "provider_id": "openai",
  "model_used": "gpt-4o",
  "usage": {
    "prompt_tokens": 100,
    "completion_tokens": 50,
    "total_tokens": 150,
    "cost_placeholder": null
  },
  "raw_provider_metadata": {},
  "normalized_error": null
}
```

### 10.3 Valid normalized response (error)

```json
{
  "request_id": "req_xyz",
  "success": false,
  "structured_payload": null,
  "raw_content": null,
  "provider_id": "openai",
  "model_used": "gpt-4o",
  "usage": null,
  "raw_provider_metadata": null,
  "normalized_error": {
    "category": "rate_limit",
    "user_message": "The AI service is temporarily busy. Please try again shortly.",
    "internal_code": "rate_limit",
    "provider_raw": "Rate limit exceeded",
    "retry_posture": "retry_with_backoff"
  }
}
```

### 10.4 Invalid: success with unvalidated structured payload

A response must not have `success: true` and a `structured_payload` that has not been validated against the requested schema. If validation fails, the response must be `success: false` with `normalized_error.category` set to `validation_failure`.

### 10.5 Invalid: secrets in request or response

No field in the normalized request or response may contain API keys, tokens, or other secrets. Credentials are supplied to the driver through a separate, secure path (out of scope for this contract).

---

## 11. Provider capability matrix template

| Provider ID | Structured output | File attach | Max context (approx) | Default model (example) | Retry note |
|-------------|--------------------|-------------|----------------------|--------------------------|------------|
| (to be implemented) | — | — | — | — | — |

This table is filled when providers are implemented; the contract only requires that each driver expose capabilities via `get_capabilities()` and that the plugin use them for model selection and feature gating.

---

## 12. Cross-references

- **Secrets:** §43.13 Secrets Handling Rules — credentials never in logs, exports, reports, or front-end.
- **Logging:** §43.14 Logging Redaction — redact secrets, tokens, and excessive payloads.
- **Prompt packs and input artifacts:** §26 — prompt pack structure and injection rules; input artifacts pass through redaction before submission. Full prompt-pack schema (identity, versioning, segments, placeholders, repair linkage) is defined in docs/schemas/prompt-pack-schema.md.
- **AI and planning phase:** §59.8 — provider drivers, credential handling, prompt packs, output validation, artifact storage are later deliverables; this contract defines the interface they conform to.
