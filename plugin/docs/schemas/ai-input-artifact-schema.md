# AI Input Artifact Schema

**Document type:** Authoritative contract for the normalized AI input artifact that packages profile, crawl, registry, and prompt-pack context into a bounded provider-ready structure (spec §23, §26, §27, §29.1, §29.2, §29.5, §59.8).  
**Governs:** Artifact root structure, required/optional sections, source references, payload blocks, attachment manifest, redaction and exclusion rules, versioning, and reproducibility.  
**Reference:** Master Specification §27.1–27.10, §29.1 Artifact Categories, §29.5 File Attachment Storage; profile-schema.md, profile-snapshot-schema.md; version-snapshot-schema.md; prompt-pack-schema.md; provider-secret-storage-contract.md.

---

## 1. Scope and principles

- **Bounded and reproducible:** The input artifact is a single normalized package built from selected sources. It is not an unbounded dump of raw site data (§27.1, §27.7). Snapshot references and version metadata support reproducibility (§27.3).
- **Distinct from raw data:** Raw profile, crawl, and registry data stay in their own stores. The input artifact holds references, bounded summaries, or normalized payload sections—not unbounded raw dumps. Raw prompt (assembled from artifact + prompt pack) and raw provider response are stored separately (§29.1).
- **No secrets or prohibited data:** Credentials, tokens, passwords, and prohibited data classes must not appear in the artifact. Redaction applies before submission and before storage (§27.4, §29.2, provider-secret-storage-contract.md).
- **Explicit source references:** Profile snapshot, crawl run, and registry snapshot are referenced by stable identifiers. Partial omission is allowed when optional sources are absent; required sections must be present for the artifact to be valid for submission (§27.8).

---

## 2. Root artifact structure

Every normalized input artifact has the following root structure. Required fields must be present for the artifact to be valid for provider submission.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `artifact_id` | string | Yes | Unique identifier for this artifact instance (e.g. UUID or run-scoped id). |
| `schema_version` | string | Yes | Version of this input-artifact schema (e.g. `1.0`). For migration and compatibility. |
| `created_at` | string (ISO 8601) | Yes | When the artifact was built. |
| `prompt_pack_ref` | object | Yes | Reference to the prompt pack used. See §3. |
| `profile` | object | No | Profile payload or reference. See §4. Required for planning runs that use profile context. |
| `crawl` | object | No | Crawl summary or reference. See §5. Optional. |
| `registry` | object | No | Registry snapshot reference or summary. See §6. Optional. |
| `goal` | object | No | User goal or intent (bounded text). Optional. |
| `attachment_manifest` | array | No | List of file-attachment entries. See §7. |
| `redaction` | object | No | Redaction and exclusion metadata. See §8. |
| `inclusion_rationale` | object | No | Short rationale for what was included/omitted. Supports audit. |
| `compatibility` | object | No | Version and compatibility markers for reproducibility. See §9. |

---

## 3. Prompt pack reference (required)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `internal_key` | string | Yes | Prompt pack internal_key (e.g. `aio/build-plan-draft`). |
| `version` | string | Yes | Prompt pack version used (e.g. `1.0.0`). |

Ensures the run can be reproduced with the same prompt-pack version (§27.3, prompt-pack-schema.md).

---

## 4. Profile section

Either a **reference** to a stored profile snapshot or a **bounded payload** (summary) for inclusion in the artifact. No raw live profile blob; use snapshot or normalized summary per profile-schema.md.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `source` | string | Yes | One of: `snapshot_ref`, `payload`. |
| `snapshot_id` | string | No | When source = snapshot_ref: id of the profile snapshot (profile-snapshot-schema.md). |
| `payload` | object | No | When source = payload: bounded object with brand_profile and business_profile summaries (or subset). Must conform to profile-schema structure; may be summarized/truncated per §27.2. Must not contain secrets. |

When profile is required for the run and absent, the artifact is invalid for submission (§27.8).

---

## 5. Crawl section

Crawl context: reference to a crawl run and/or a bounded summary (page inventory, meaningful-page summaries, hierarchy). Not raw page dumps (§26.6, §27.1).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `source` | string | Yes | One of: `run_ref`, `summary`, `both`. |
| `crawl_run_id` | string | No | When run_ref or both: crawl run identifier. |
| `summary` | object | No | When summary or both: bounded crawl summary (e.g. page_count, page_summaries array with bounded entries, hierarchy_notes). Max sizes per product; no unbounded HTML or raw content. |

---

## 6. Registry section

Template registry context: reference to a version snapshot or a bounded registry summary (template keys, names, purpose summaries, section order). Not full registry dump (§26.5, §27.1).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `source` | string | Yes | One of: `snapshot_ref`, `summary`. |
| `snapshot_id` | string | No | When source = snapshot_ref: version snapshot id (version-snapshot-schema.md scope_type registry). |
| `summary` | object | No | When source = summary: bounded registry summary (section templates, page templates, optional compositions index). Structure per product; bounded size. |

---

## 7. Attachment manifest

When files are part of the input package, each entry is described in the manifest (§27.5, §29.5).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file_id` | string | Yes | Stable identifier for this file entry. |
| `file_type` | string | Yes | MIME type or product type (e.g. `image/png`, `application/json`). |
| `source_category` | string | Yes | One of: `profile_asset`, `crawl_export`, `registry_export`, `user_upload`, `other`. |
| `purpose` | string | Yes | Short purpose of inclusion (e.g. "Logo for brand context"). |
| `redaction_status` | string | Yes | One of: `none`, `redacted`, `excluded`. |
| `attachment_status` | string | Yes | One of: `attached`, `reference_only`, `failed`. |
| `size_bytes` | int | No | Size when known. |
| `download_eligible` | bool | No | Whether the file may be included in downloadable artifact bundles (permissions and redaction apply). |

Attachment manifest must not expose secret values. Redaction state and download eligibility are recorded without storing credentials (§29.5).

---

## 8. Redaction and exclusion

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `redaction_applied` | bool | Yes | Whether a redaction pass was run before finalizing the artifact. |
| `excluded_categories` | array of string | No | Categories that were excluded (e.g. `secrets`, `internal_tokens`, `excess_payload`). |
| `placeholder_used` | bool | No | Whether any placeholder or mask was used in place of redacted content. |

Exclusion rules (§27.4): remove secrets, remove protected internal values, avoid transmitting irrelevant sensitive data, summarize or omit data that exceeds safety boundaries. Prohibited data classes (API keys, passwords, auth tokens) must never appear (§52.6, provider-secret-storage-contract.md).

---

## 9. Compatibility and versioning

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `artifact_schema_version` | string | Yes | Same as root `schema_version`; may be repeated for convenience. |
| `profile_schema_version` | string | No | Profile schema version when profile payload is present (profile-snapshot-schema). |
| `registry_schema_version` | string | No | Registry/snapshot schema version when registry section is present. |

Versioning supports reproducibility: the same prompt pack version + same snapshot refs + same artifact schema version should yield a comparable input package (§27.3, §29.1).

---

## 10. Required vs optional sections

- **Required for a valid planning artifact (typical):** `artifact_id`, `schema_version`, `created_at`, `prompt_pack_ref`, `redaction` (with `redaction_applied`). Profile section is required when the prompt pack declares `artifact_refs.profile` and the run uses profile context; otherwise optional.
- **Optional:** `crawl`, `registry`, `goal`, `attachment_manifest`, `inclusion_rationale`, `compatibility` (except where product requires compatibility block).
- **Partial omission:** When an optional source is absent (e.g. no crawl run yet), the corresponding section may be omitted or present with `source: none` / empty. The artifact remains valid if required sections are present and redaction has been applied.

---

## 11. Inclusion rationale (optional block)

Short audit-oriented notes on what was included or omitted.

| Field | Type | Description |
|-------|------|-------------|
| `profile_included` | bool | Whether profile section was included. |
| `crawl_included` | bool | Whether crawl section was included. |
| `registry_included` | bool | Whether registry section was included. |
| `notes` | string | Optional freeform note (e.g. "Crawl omitted: no run available"). |

Must not contain secrets or sensitive internal values.

---

## 12. Valid example: profile-heavy artifact

```json
{
  "artifact_id": "art_550e8400-e29b-41d4-a716-446655440000",
  "schema_version": "1.0",
  "created_at": "2025-07-15T14:00:00Z",
  "prompt_pack_ref": {
    "internal_key": "aio/build-plan-draft",
    "version": "1.0.0"
  },
  "profile": {
    "source": "snapshot_ref",
    "snapshot_id": "prof_snap_abc123"
  },
  "crawl": {
    "source": "run_ref",
    "crawl_run_id": "run_xyz789"
  },
  "registry": {
    "source": "summary",
    "summary": {
      "section_templates": [ { "key": "st01_hero", "name": "Hero" }, { "key": "st05_cta", "name": "CTA" } ],
      "page_templates": [ { "key": "pt_landing", "name": "Landing" } ]
    }
  },
  "goal": {
    "text": "Improve conversion on the main landing page.",
    "max_length_applied": 1000
  },
  "attachment_manifest": [],
  "redaction": {
    "redaction_applied": true,
    "excluded_categories": [ "secrets" ],
    "placeholder_used": false
  },
  "inclusion_rationale": {
    "profile_included": true,
    "crawl_included": true,
    "registry_included": true,
    "notes": "Full context included for planning."
  },
  "compatibility": {
    "artifact_schema_version": "1.0",
    "profile_schema_version": "1",
    "registry_schema_version": "1"
  }
}
```

---

## 13. Valid example: crawl-heavy artifact

```json
{
  "artifact_id": "art_crawl_001",
  "schema_version": "1.0",
  "created_at": "2025-07-15T15:30:00Z",
  "prompt_pack_ref": {
    "internal_key": "aio/build-plan-draft",
    "version": "1.0.0"
  },
  "profile": {
    "source": "payload",
    "payload": {
      "brand_profile": { "brand_positioning_summary": "B2B SaaS.", "brand_voice_summary": "Professional, clear." },
      "business_profile": { "business_name": "Acme Inc", "current_site_url": "https://acme.example.com", "target_audience_summary": "SMB decision makers." }
    }
  },
  "crawl": {
    "source": "both",
    "crawl_run_id": "run_crawl_20250715",
    "summary": {
      "page_count": 12,
      "page_summaries": [
        { "url": "/", "title": "Home", "classification": "meaningful", "purpose_notes": "Primary landing." },
        { "url": "/pricing", "title": "Pricing", "classification": "meaningful", "purpose_notes": "Pricing table." }
      ],
      "hierarchy_notes": "Flat structure; no deep nesting."
    }
  },
  "registry": { "source": "snapshot_ref", "snapshot_id": "reg_snap_001" },
  "attachment_manifest": [
    {
      "file_id": "att_1",
      "file_type": "image/png",
      "source_category": "profile_asset",
      "purpose": "Logo",
      "redaction_status": "none",
      "attachment_status": "attached",
      "size_bytes": 2048,
      "download_eligible": true
    }
  ],
  "redaction": {
    "redaction_applied": true,
    "excluded_categories": [],
    "placeholder_used": false
  },
  "compatibility": {
    "artifact_schema_version": "1.0",
    "profile_schema_version": "1"
  }
}
```

---

## 14. Invalid example: unbounded dump

**Invalid:** Artifact contains raw unbounded profile dump or secrets.

```json
{
  "artifact_id": "art_bad",
  "schema_version": "1.0",
  "created_at": "2025-07-15T12:00:00Z",
  "prompt_pack_ref": { "internal_key": "aio/build-plan-draft", "version": "1.0.0" },
  "profile": {
    "source": "payload",
    "payload": {
      "api_key": "sk-abc123",
      "business_profile": { "business_name": "Acme", "raw_database_dump": "..." }
    }
  },
  "redaction": { "redaction_applied": false }
}
```

- **Violations:** Secret (`api_key`) in payload; unbounded/raw dump; `redaction_applied` false despite sensitive content. Artifact must be rejected.

---

## 15. Invalid example: missing required fields

**Invalid:** Missing prompt_pack_ref and redaction.

```json
{
  "artifact_id": "art_incomplete",
  "schema_version": "1.0",
  "created_at": "2025-07-15T12:00:00Z",
  "profile": { "source": "snapshot_ref", "snapshot_id": "x" }
}
```

- **Violations:** Missing required `prompt_pack_ref` and `redaction`. Artifact is not valid for submission (§27.8).

---

## 16. Prohibited fields and exclusion rules

The following must **never** appear in the artifact (root or any section):

- `api_key`, `secret`, `token`, `password`, `authorization`, or any credential field (provider-secret-storage-contract.md).
- Unbounded raw HTML, full database dumps, or unsummarized payloads that exceed product bounds.
- Internal-only tokens or values that must not be transmitted to the provider.

Attachment manifest may record `redaction_status` and `download_eligible` but must not contain secret values. Redaction must be applied before the artifact is stored or sent (§27.4, §29.2).

---

## 17. Cross-references

- **Profile:** profile-schema.md (current profile); profile-snapshot-schema.md (snapshot identity and inclusion). Artifact profile section references snapshot or uses bounded payload derived from profile.
- **Registry:** version-snapshot-schema.md (registry snapshot); object-model-schema.md. Artifact registry section references snapshot or bounded summary.
- **Crawl:** Crawl run id and bounded summary; no raw crawl storage in artifact schema (crawl data lives in crawl snapshot service).
- **Prompt pack:** prompt-pack-schema.md. Artifact references prompt pack by internal_key and version; prompt pack declares artifact_refs (profile, registry, crawl, goal).
- **Categories (§29.1):** This schema defines the **normalized input artifact** (input snapshot / normalized prompt package input). Raw prompt, raw provider response, normalized output, and file manifest are separate categories.

---

## 18. Out of scope for this schema

- Runtime artifact builder implementation.
- Provider calls, AI run persistence, or Build Plan generation.
- Artifact download/export bundle implementation.
- Onboarding UI changes.
- Assembly of real artifacts from live data (this schema only defines the contract for the built artifact).
