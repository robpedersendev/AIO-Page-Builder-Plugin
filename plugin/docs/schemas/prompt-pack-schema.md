# Prompt Pack Schema

**Document type:** Authoritative contract for prompt-pack structure, versioning, composition, and compatibility (spec §23, §25.1, §26, §29.2, §58.3, §59.8).  
**Governs:** Prompt-pack identity, versioning, segments, variable placeholders, provider-agnostic vs provider-tuned fields, repair linkage, artifact references, redaction posture, and ineligibility rules.  
**Reference:** Master Specification §26.1–26.11, §10.6 Prompt Pack Object, §29.2 Raw Prompt Storage, §58.3 Prompt Pack Versioning; ai-provider-contract.md (normalized request/response); provider-secret-storage-contract.md (no secrets in packs).

---

## 1. Scope and principles

- **Controlled system definitions:** Prompt packs are versioned, reviewable system assets—not loose string blobs or ad hoc templates. They must be stored, versioned, and compared in a deterministic way (§26.1, §58.3).
- **Provider-agnostic at root:** The root schema is provider-agnostic (§25.1). Provider-tuned variants (e.g. model-specific notes, formatting hints) may exist as optional overrides without rewriting the root contract.
- **No secrets:** Prompt packs must not contain API keys, tokens, or credentials. User data and artifact content are injected at assembly time through controlled pathways; redaction applies before storage and export (§26.4, §29.2).
- **Repair linkage explicit:** Repair prompts used for schema-failure recovery are linked by reference (e.g. `repair_prompt_ref`), not improvised at runtime (§26, validation/repair flow).
- **Exportable and auditable:** Prompt-pack metadata and versioned content support export and run traceability (§26.11, §29.2). Raw prompt storage is subject to redaction policy.

---

## 2. Root prompt-pack schema

Every prompt pack is a single document with the following root structure. All fields are required unless marked optional.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `internal_key` | string | Yes | Stable internal identifier (e.g. `aio/build-plan-draft`). Used for storage, lookup, and run association. Must be unique per pack. |
| `name` | string | Yes | Human-readable name (e.g. "Build Plan Draft Prompt Pack"). |
| `version` | string | Yes | Semantic version (e.g. `1.2.0`). See §3. |
| `pack_type` | string | Yes | One of: `planning`, `repair`, `summary`, `other`. Determines usage context. |
| `status` | string | Yes | One of: `active`, `inactive`, `deprecated`. Inactive/deprecated packs are not used for new runs unless explicitly allowed. |
| `segments` | object | Yes | Prompt segment composition. See §4. |
| `schema_target_ref` | string | No | Plugin-owned schema reference for structured output (e.g. `aio/build-plan-draft-v1`). When set, the pack expects output validating against this schema. |
| `repair_prompt_ref` | string | No | Reference to another prompt pack (internal_key) or segment used for schema-repair attempts. Only meaningful for planning packs. |
| `placeholder_rules` | object | No | Declared placeholders and substitution rules. See §5. |
| `provider_compatibility` | object | No | Provider capability and compatibility metadata. See §6. |
| `artifact_refs` | object | No | Declared input-artifact families (profile, registry, crawl). See §7. |
| `redaction` | object | No | Redaction posture for storage and export. See §8. |
| `changelog` | array | No | List of change entries (version, date, notes). |
| `deprecation` | object | No | When status is deprecated: successor_ref, message, sunset_date. |

---

## 3. Versioning and deprecation

### 3.1 Semantic version

- **Format:** `MAJOR.MINOR.PATCH` (e.g. `1.0.0`, `2.1.3`). MAJOR = breaking change; MINOR = additive change; PATCH = fix or non-breaking tweak.
- **Comparability:** Versions must be comparable (e.g. for "which prompt pack produced which run" and regression analysis (§26.8, §58.3)).
- **Stability:** Same `internal_key` may have multiple versioned instances; storage/registry distinguishes by version.

### 3.2 Deprecation block (when status = deprecated)

| Field | Type | Description |
|-------|------|-------------|
| `successor_ref` | string | internal_key (and optionally version) of the recommended successor pack. |
| `message` | string | Short human-readable deprecation message. |
| `sunset_date` | string (ISO 8601) | Optional date after which the pack should not be used for new runs. |

### 3.3 Changelog entry

| Field | Type | Description |
|-------|------|-------------|
| `version` | string | Version this entry describes. |
| `date` | string (ISO 8601) | Optional. |
| `notes` | string | Change notes for audit and comparison. |

---

## 4. Prompt segments (composition)

Prompt content is composed of named segments. No single opaque string field; segments support assembly, testing, and provider-specific ordering where needed.

### 4.1 Segments object (required keys)

| Segment key | Type | Required | Description |
|-------------|------|----------|-------------|
| `system_base` | string | Yes | Core system prompt: role, task, constraints (§26.3). What the AI is asked to do, what inputs it receives, what outputs it must produce. |
| `role_framing` | string | No | Role and behavior framing (e.g. "You are a site planning assistant."). May be merged into system_base at assembly. |
| `planning_instructions` | string | No | Detailed planning instructions, template-registry interpretation, build-plan expectations (§26.2). |
| `schema_requirements` | string | No | Explicit schema/output requirements. Should align with `schema_target_ref`. |
| `site_analysis_instructions` | string | No | How to use crawl/site context (§26.6). |
| `safety_instructions` | string | No | Safety and boundary rules (§26.10): no redefinition of system contracts, no execution authority from prose. |
| `normalization_expectations` | string | No | How output should be normalized or structured. |
| `provider_notes` | string | No | Provider-agnostic or provider-tuned notes (e.g. "Prefer JSON mode for structured output."). |

Additional segment keys may be added in future; the contract requires at least `system_base`. Segments are concatenated or merged per assembly rules; order may be defined in a separate `segment_order` array if needed.

### 4.2 Segment metadata (per segment, optional)

Each segment value may be a string or an object `{ "body": string, "redact_before_export": bool }`. When object form is used, `redact_before_export` indicates whether the segment body must be redacted in user-facing export (§8).

---

## 5. Variable placeholders and substitution rules

- **Declared placeholders only:** Placeholders must be declared in `placeholder_rules`. Format is implementation-defined but must be unambiguous (e.g. `{{profile_summary}}`, `{{crawl_summary}}`). No arbitrary code execution; substitution is string/safe replacement only.
- **Placeholder rules object:** Map placeholder name → metadata.

| Placeholder rule field | Type | Description |
|------------------------|------|-------------|
| `source` | string | One of: `profile`, `registry`, `crawl`, `goal`, `custom`. Indicates which artifact family supplies the value. |
| `max_length` | int | Optional. Max character length for substituted value (truncation/summarization). |
| `required` | bool | Optional. If true, pack is ineligible when value is missing. |

- **Ineligibility:** A pack is ineligible for a run if a required placeholder has no supplied value or if substitution would violate safety (e.g. injecting secrets). See §9.

---

## 6. Provider compatibility block

| Field | Type | Description |
|-------|------|-------------|
| `min_structured_output` | bool | If true, pack requires a provider that supports structured output. |
| `supported_providers` | array of string | Optional. List of provider_id values that have been tested or are explicitly supported. Empty or absent = any provider. |
| `capability_notes` | string | Optional. Freeform notes (e.g. "Best with models that support long context."). |
| `provider_overrides` | object | Optional. Map provider_id → overrides (e.g. segment ordering, provider-specific instructions). Must not introduce secrets or unsafe behavior. |

Provider-tuned content stays in `provider_overrides` or `provider_notes`; the root segments remain the single source of truth for provider-agnostic behavior.

---

## 7. Artifact references (input families)

Declares which input-artifact families this pack expects. Used for assembly and eligibility checks.

| Field | Type | Description |
|-------|------|-------------|
| `profile` | bool | Whether profile data (brand/business) is injected (§26.4). |
| `registry` | bool | Whether template registry data is injected (§26.5). |
| `crawl` | bool | Whether crawl/site context is injected (§26.6). |
| `goal` | bool | Whether user goal/intent is injected. |

Optional: `artifact_refs` may include more granular keys (e.g. `profile_snapshot`, `registry_snapshot`) as the product evolves. Assembly must respect redaction; no secrets in injected content.

---

## 8. Redaction posture

Raw prompt storage is subject to redaction policy (§29.2). This block documents what may be stored raw vs masked.

| Field | Type | Description |
|-------|------|-------------|
| `store_assembled_prompt` | bool | Whether the assembled (post-substitution) prompt may be stored for the run. When true, storage must still apply redaction (no secrets, no prohibited data). |
| `redact_segments_in_export` | array of string | Optional. List of segment keys whose body must be redacted in user-facing or support export. |
| `mask_placeholder_values` | bool | Optional. When true, placeholder values in stored prompts are replaced with a mask (e.g. `[PROFILE_INJECTED]`) in exports. |

Default: assembled prompt may be stored for audit; placeholder values and user data must not expose secrets. Redaction rules align with provider-secret-storage-contract.md and §43.14.

---

## 9. Ineligibility rules

A prompt pack is **ineligible** for a given run if any of the following hold. Ineligible packs must not be used for that run.

- **Status:** `status` is `inactive` or `deprecated` (unless explicitly overridden by product rule).
- **Required placeholder missing:** A placeholder declared `required` in `placeholder_rules` has no supplied value for the run.
- **Schema target unsupported:** `schema_target_ref` is set but the selected provider does not support the referenced schema (or structured output).
- **Provider compatibility:** `supported_providers` is non-empty and the selected provider_id is not in the list (product may treat as warning rather than hard ineligibility).
- **Safety:** Pack content or assembly would introduce secrets, execution authority from prose, or violation of §26.10 safety rules.
- **Repair ref invalid:** `repair_prompt_ref` points to a non-existent or ineligible pack.

---

## 10. Valid prompt-pack example

```json
{
  "internal_key": "aio/build-plan-draft",
  "name": "Build Plan Draft Prompt Pack",
  "version": "1.0.0",
  "pack_type": "planning",
  "status": "active",
  "segments": {
    "system_base": "You are a site planning assistant. You produce structured recommendations for page and section structure based on the provided context. Output must conform to the referenced schema.",
    "role_framing": "You do not execute changes; you recommend. All recommendations are subject to user approval.",
    "planning_instructions": "Use the template registry to suggest section order and page composition. Use crawl data to inform existing-site gaps.",
    "schema_requirements": "Return a valid build-plan-draft payload per aio/build-plan-draft-v1.",
    "safety_instructions": "Do not output instructions that could be interpreted as direct execution. Do not redefine system contracts."
  },
  "schema_target_ref": "aio/build-plan-draft-v1",
  "repair_prompt_ref": "aio/build-plan-repair",
  "placeholder_rules": {
    "{{profile_summary}}": { "source": "profile", "max_length": 4000, "required": true },
    "{{registry_summary}}": { "source": "registry", "max_length": 3000, "required": false },
    "{{crawl_summary}}": { "source": "crawl", "max_length": 5000, "required": false },
    "{{goal}}": { "source": "goal", "max_length": 1000, "required": false }
  },
  "provider_compatibility": {
    "min_structured_output": true,
    "supported_providers": ["openai", "anthropic"],
    "capability_notes": "Requires JSON/complex structured output support."
  },
  "artifact_refs": { "profile": true, "registry": true, "crawl": true, "goal": true },
  "redaction": {
    "store_assembled_prompt": true,
    "mask_placeholder_values": true
  },
  "changelog": [
    { "version": "1.0.0", "date": "2025-07-01", "notes": "Initial release." }
  ]
}
```

---

## 11. Invalid prompt-pack example

**Invalid:** Single opaque string field instead of segments.

```json
{
  "internal_key": "aio/legacy",
  "name": "Legacy",
  "version": "1.0.0",
  "pack_type": "planning",
  "status": "active",
  "full_prompt": "You are an assistant. Do things. {{stuff}}"
}
```

- **Violation:** No `segments` object; prompt is a single unversioned blob. Contract requires `segments.system_base` and structured composition.

**Invalid:** Secret or credential in content.

```json
{
  "internal_key": "aio/bad",
  "segments": {
    "system_base": "Use API key sk-abc123 to call the service."
  }
}
```

- **Violation:** No secrets in prompt packs (§26, provider-secret-storage-contract). Pack is ineligible and must be rejected.

**Invalid:** Missing required root fields.

```json
{
  "name": "No key or version",
  "segments": { "system_base": "Hello." }
}
```

- **Violation:** Missing `internal_key`, `version`, `pack_type`, `status`. Root schema requires these.

---

## 12. Version-compatibility matrix

| Change type | MAJOR | MINOR | PATCH | Compatible with prior run? |
|-------------|-------|-------|-------|----------------------------|
| Segment body edit (non-breaking) | — | ✓ | ✓ | Yes (same version or PATCH). |
| New segment added | — | ✓ | — | Yes. |
| Placeholder added (optional) | — | ✓ | ✓ | Yes. |
| Placeholder removed or required added | ✓ | — | — | No (breaking). |
| schema_target_ref changed | ✓ | — | — | No. |
| repair_prompt_ref changed | — | ✓ | ✓ | Yes (if repair logic tolerates). |
| Status → deprecated | — | ✓ | ✓ | Yes (pack still exists); new runs may be blocked by product rule. |
| Segment key renamed or removed | ✓ | — | — | No. |

Run traceability: each AI run records the prompt pack `internal_key` and `version` that produced it. Comparison and regression analysis use these; no ad hoc version logic.

---

## 13. Exportability notes

- **Export may include:** Prompt-pack version metadata, segment keys (and optionally redacted segment bodies per §8), placeholder_rules, schema_target_ref, repair_prompt_ref, changelog (§26.11).
- **Export must not include:** Secrets, raw credentials, or unmasked user data that was injected at assembly time. Redaction posture (§8) applies.
- **Raw prompt storage (§29.2):** The assembled prompt sent to the provider may be stored for audit/support, subject to redaction policy. Stored prompts must not expose secrets or prohibited data classes.

---

## 14. Cross-references

- **Provider contract:** ai-provider-contract.md — normalized request carries `system_prompt` and `user_message`; these are assembled from prompt pack segments and artifacts. `structured_output_schema_ref` aligns with prompt pack `schema_target_ref`.
- **Secrets:** provider-secret-storage-contract.md — no secrets in packs; redaction rules apply to stored/exported prompts.
- **Input artifacts:** Spec §26.4–26.6, §27 — profile, registry, crawl injection; artifact preparation and snapshot packaging are separate; prompt pack declares artifact_refs only.
- **Build Plan / output schema:** schema_target_ref points to plugin-owned output schemas (e.g. build-plan-draft); validation and repair flow reference this schema.

---

## 15. Out of scope for this schema

- Prompt-pack registry implementation (CRUD, storage backend).
- Provider driver execution or AI run persistence.
- Input artifact assembly or snapshot packaging.
- Build Plan generation or execution.
- Admin UI for editing prompt packs.
- Runtime repair-prompt execution (this schema only defines the linkage; execution is a later prompt).
