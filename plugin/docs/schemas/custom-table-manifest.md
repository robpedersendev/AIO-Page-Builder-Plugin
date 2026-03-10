# Custom Table Manifest

**Document type:** Authoritative contract for plugin custom tables (spec §9.5, §11).  
**Governs:** Table names, columns, types, indexes, retention, export, and versioning before any installer or repository code.  
**Related:** storage-strategy-matrix.md; object-model-schema.md (CPT objects that reference these tables).

---

## 1. Global naming convention

- **Physical table name:** `{wpdb->prefix}aio_{logical_suffix}`. Example: `wp_aio_crawl_snapshots`. The prefix is the WordPress database prefix (e.g. `wp_`); the suffix is stable and defined in this manifest. No runtime-derived suffixes; no dynamic table creation.
- **Column names:** Lowercase `snake_case`. No reserved words as column names.
- **Foreign-reference columns:** Named `{referenced_entity}_id` or `{referenced_entity}_ref` (e.g. `run_id`, `job_ref`). Type: `BIGINT UNSIGNED` for same-DB IDs, or `VARCHAR(64)` for cross-table/cross-system refs (e.g. plan_id, run_id from CPT).
- **Version key:** Each table has a `schema_version` column (VARCHAR(16)) or is covered by a single schema version stored in options; version is used for upgrade path and migration.

---

## 2. Table list (Section 11 coverage)

| Logical name | Physical suffix | Spec |
|--------------|-----------------|------|
| Crawl snapshots | `aio_crawl_snapshots` | §11.1 |
| AI artifacts | `aio_ai_artifacts` | §11.2 |
| Job queue | `aio_job_queue` | §11.3 |
| Execution log | `aio_execution_log` | §11.4 |
| Diff / rollback | `aio_rollback_records` | §11.5 |
| Token sets | `aio_token_sets` | §11.6 |
| Assignment maps | `aio_assignment_maps` | §11.7 |
| Reporting / telemetry | `aio_reporting_records` | §11.8 |

No other custom tables exist for this plugin unless added by a formal schema revision. Tables may be split later for volume (e.g. crawl_snapshots by run_id) only with a documented migration; the core manifest is fixed.

---

## 3. Per-table definitions

### 3.1 Crawl snapshots (§11.1)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Crawl snapshots |
| **Physical name** | `{prefix}aio_crawl_snapshots` |
| **Purpose** | Structured records of public-site discovery and analysis; one row per URL per crawl run (or equivalent grain). |
| **Retention class** | Medium-lived operational; retention-managed. |
| **Sensitivity** | Internal operational. |
| **Export** | Optional; include only if export mode allows; no secrets. |
| **Uninstall** | Remove by policy or retain by choice; document in uninstall prefs. |
| **Schema version** | Stored in option or column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| crawl_run_id | VARCHAR(64) | No | — | Crawl run identifier (stable ref). |
| url | VARCHAR(2048) | No | — | Discovered URL. |
| canonical_url | VARCHAR(2048) | Yes | NULL | Canonical URL if different. |
| title_snapshot | VARCHAR(512) | Yes | NULL | Title at crawl time. |
| meta_snapshot | TEXT | Yes | NULL | Meta description / tags snapshot. |
| indexability_flags | VARCHAR(255) | Yes | NULL | Index/noindex etc. |
| page_classification | VARCHAR(64) | Yes | NULL | Page type or classification. |
| hierarchy_clues | TEXT | Yes | NULL | Hierarchy/parent clues (JSON or delimited). |
| navigation_participation | TINYINT UNSIGNED | Yes | 0 | Boolean or flags. |
| summary_data | TEXT | Yes | NULL | Summary or excerpt. |
| content_hash | VARCHAR(64) | Yes | NULL | Hash or change marker. |
| crawl_status | VARCHAR(32) | No | 'pending' | pending \| completed \| error. |
| error_state | VARCHAR(255) | Yes | NULL | Error code or message if failed. |
| crawled_at | DATETIME | Yes | NULL | When this URL was crawled. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| schema_version | VARCHAR(16) | No | '1' | Schema version for migrations. |

**Indexes:** PRIMARY (id); UNIQUE (crawl_run_id, url); INDEX (crawl_run_id); INDEX (crawl_status); INDEX (crawled_at); INDEX (created_at).

**Related objects:** Referenced by crawl-run context; may be referenced by AI run (crawl snapshot ref). No secrets.

---

### 3.2 AI artifacts (§11.2)

| Attribute | Value |
|-----------|--------|
| **Logical name** | AI artifacts |
| **Physical name** | `{prefix}aio_ai_artifacts` |
| **Purpose** | Input/output artifacts for AI runs; raw and normalized refs; redaction status. |
| **Retention class** | Long-lived operational; retention policy. |
| **Sensitivity** | **Restricted:** Admin-visible; redaction required for export and sensitive views. No secrets in rows; payloads may contain redacted content. |
| **Export** | Optional; redaction mandatory before export. |
| **Uninstall** | Preserve by choice or remove by policy. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| artifact_ref | VARCHAR(64) | No | — | Stable public artifact id (e.g. UUID). UNIQUE. |
| run_id | VARCHAR(64) | No | — | AI run reference (CPT or run table). |
| artifact_type | VARCHAR(32) | No | — | input \| raw_prompt \| raw_response \| normalized_output \| file. |
| file_ref | VARCHAR(512) | Yes | NULL | File or storage location reference. |
| raw_prompt_ref | VARCHAR(64) | Yes | NULL | Self-ref or ref to another artifact row. |
| raw_response_ref | VARCHAR(64) | Yes | NULL | As above. |
| normalized_output_ref | VARCHAR(64) | Yes | NULL | As above. |
| validation_status | VARCHAR(32) | No | 'pending' | pending \| valid \| failed. |
| redaction_status | VARCHAR(32) | No | 'pending' | pending \| redacted \| not_applicable. |
| usage_metadata | TEXT | Yes | NULL | JSON or text; no secrets. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| updated_at | DATETIME | Yes | NULL | Last update. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (artifact_ref); INDEX (run_id); INDEX (artifact_type); INDEX (validation_status); INDEX (created_at).

**Related objects:** Referenced by AI Run object (CPT). **Redaction required** for logs and export; no raw provider secrets stored.

---

### 3.3 Job queue (§11.3)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Job queue |
| **Physical name** | `{prefix}aio_job_queue` |
| **Purpose** | Queued, running, retrying, completed, failed, or cancelled tasks. |
| **Retention class** | Short-lived operational; routine cleanup for completed/failed/cancelled. |
| **Sensitivity** | Internal operational; payload reference may point to sensitive data—no secrets in payload. |
| **Export** | Excluded by default. |
| **Uninstall** | Remove on uninstall or after retention window. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| job_ref | VARCHAR(64) | No | — | Stable job id (e.g. UUID). UNIQUE. |
| job_type | VARCHAR(64) | No | — | Type of job (e.g. crawl, ai_run, execution). |
| queue_status | VARCHAR(32) | No | 'queued' | queued \| running \| retrying \| completed \| failed \| cancelled. |
| priority | SMALLINT UNSIGNED | No | 0 | Higher = higher priority. |
| payload_ref | VARCHAR(512) | Yes | NULL | Reference to payload (e.g. option key or serialized id); no inline secrets. |
| actor_ref | VARCHAR(64) | Yes | NULL | User or system actor. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | When job was enqueued. |
| started_at | DATETIME | Yes | NULL | When job started. |
| completed_at | DATETIME | Yes | NULL | When job finished. |
| retry_count | SMALLINT UNSIGNED | No | 0 | Number of retries. |
| lock_token | VARCHAR(64) | Yes | NULL | Lock state for concurrent processing. |
| failure_reason | VARCHAR(512) | Yes | NULL | Error message or code; no secrets. |
| related_object_refs | TEXT | Yes | NULL | JSON array of related ids (plan, run, etc.). |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (job_ref); INDEX (queue_status); INDEX (job_type); INDEX (created_at); INDEX (priority, queue_status) for queue processing.

**Related objects:** Referenced by execution log (job_ref). No secrets in payload_ref or failure_reason.

---

### 3.4 Execution log (§11.4)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Execution log |
| **Physical name** | `{prefix}aio_execution_log` |
| **Purpose** | Structured records of site-impacting actions and operational steps; support and rollback reasoning. |
| **Retention class** | Medium-lived operational; retention-managed. |
| **Sensitivity** | Internal operational; **redaction required**—no secrets, tokens, or raw payloads in result_summary or error_details. |
| **Export** | Optional; redaction mandatory. |
| **Uninstall** | Retention-managed; remove by policy. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| log_ref | VARCHAR(64) | No | — | Stable log entry id. UNIQUE. |
| action_type | VARCHAR(64) | No | — | Type of action (e.g. page_build, rollback, token_apply). |
| job_ref | VARCHAR(64) | Yes | NULL | Related job_queue.job_ref. |
| affected_object_refs | TEXT | Yes | NULL | JSON array of affected object ids. |
| actor_ref | VARCHAR(64) | Yes | NULL | User or system. |
| pre_change_snapshot_ref | VARCHAR(64) | Yes | NULL | Ref to snapshot or rollback row if applicable. |
| result_summary | TEXT | Yes | NULL | Sanitized summary; no secrets. |
| status | VARCHAR(32) | No | 'pending' | pending \| success \| partial \| failed. |
| warning_flags | VARCHAR(255) | Yes | NULL | Warning codes or flags. |
| error_details_ref | VARCHAR(64) | Yes | NULL | Ref to error detail (or inline if redacted); no secrets. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | When action was logged. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (log_ref); INDEX (action_type); INDEX (job_ref); INDEX (status); INDEX (created_at).

**Related objects:** References job queue; may reference rollback_records. **Secrets must not be stored in this table.**

---

### 3.5 Diff / rollback (§11.5)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Rollback records |
| **Physical name** | `{prefix}aio_rollback_records` |
| **Purpose** | Before/after refs and change data for inspection and reversal. |
| **Retention class** | Long-lived operational; preserve by choice / retention. |
| **Sensitivity** | Admin-visible restricted. |
| **Export** | Optional. |
| **Uninstall** | Preserve by choice or remove by policy. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| diff_ref | VARCHAR(64) | No | — | Stable diff id. UNIQUE. |
| rollback_ref | VARCHAR(64) | Yes | NULL | Parent rollback group id if applicable. |
| execution_log_ref | VARCHAR(64) | Yes | NULL | Related execution_log.log_ref. |
| snapshot_refs | TEXT | Yes | NULL | JSON array of snapshot refs (before/after). |
| object_scope | VARCHAR(64) | No | — | Page, plan, token_set, etc. |
| object_ref | VARCHAR(64) | Yes | NULL | Affected object id. |
| diff_type | VARCHAR(32) | No | — | Type of change. |
| rollback_eligible | TINYINT UNSIGNED | No | 1 | 0 or 1. |
| rollback_status | VARCHAR(32) | No | 'none' | none \| pending \| applied \| failed. |
| failure_notes | VARCHAR(512) | Yes | NULL | No secrets. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (diff_ref); INDEX (execution_log_ref); INDEX (rollback_ref); INDEX (rollback_status); INDEX (object_scope, object_ref); INDEX (created_at).

**Related objects:** Referenced by execution log. No secrets.

---

### 3.6 Token sets (§11.6)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Token sets |
| **Physical name** | `{prefix}aio_token_sets` |
| **Purpose** | Design-token collections and states; versioned, comparable, approvable. |
| **Retention class** | Permanent until user deletion / long-lived. |
| **Sensitivity** | Admin-visible restricted. |
| **Export** | Exportable. |
| **Uninstall** | Preserve by choice. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| token_set_ref | VARCHAR(64) | No | — | Stable token set id. UNIQUE. |
| source_type | VARCHAR(32) | No | — | ai_recommendation \| manual \| import. |
| state | VARCHAR(32) | No | 'proposed' | current \| proposed. |
| plan_ref | VARCHAR(64) | Yes | NULL | Associated Build Plan id if applicable. |
| scope_ref | VARCHAR(64) | Yes | NULL | Site or settings scope. |
| value_payload | LONGTEXT | Yes | NULL | JSON token values; no secrets. |
| acceptance_status | VARCHAR(32) | No | 'pending' | pending \| accepted \| rejected. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| applied_at | DATETIME | Yes | NULL | When applied if applicable. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (token_set_ref); INDEX (plan_ref); INDEX (scope_ref); INDEX (state); INDEX (acceptance_status); INDEX (created_at).

**Related objects:** May reference Build Plan. No secrets in value_payload (tokens only).

---

### 3.7 Assignment maps (§11.7)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Assignment maps |
| **Physical name** | `{prefix}aio_assignment_maps` |
| **Purpose** | Mappings for page-to-field-group, page-to-template, plan-to-object, template-to-dependency, composition-to-section; queryable and normalized. |
| **Retention class** | Long-lived operational. |
| **Sensitivity** | Admin-visible restricted. |
| **Export** | Exportable. |
| **Uninstall** | Preserve by choice. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| map_type | VARCHAR(64) | No | — | page_field_group \| page_template \| plan_object \| template_dependency \| composition_section. |
| source_ref | VARCHAR(64) | No | — | Left side of mapping (e.g. page id, plan id). |
| target_ref | VARCHAR(64) | No | — | Right side (e.g. field group key, template id). |
| scope_ref | VARCHAR(64) | Yes | NULL | Optional scope (e.g. composition id). |
| payload | TEXT | Yes | NULL | JSON for extra mapping data. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); INDEX (map_type); INDEX (source_ref); INDEX (target_ref); INDEX (map_type, source_ref); INDEX (created_at).

**Related objects:** References pages, plans, templates, compositions by ref. No secrets.

---

### 3.8 Reporting / telemetry (§11.8)

| Attribute | Value |
|-----------|--------|
| **Logical name** | Reporting records |
| **Physical name** | `{prefix}aio_reporting_records` |
| **Purpose** | Operational communication records for private-distribution reporting; audit and failure visibility. |
| **Retention class** | Long-lived operational; retention-managed. |
| **Sensitivity** | **Externally derived; redaction-controlled.** No secrets in payload_summary or response_summary. |
| **Export** | Excluded by default; audit export under permissions with redaction. |
| **Uninstall** | Retention-managed; remove by policy. |
| **Schema version** | Column `schema_version` VARCHAR(16) default `1`. |

**Columns:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| report_ref | VARCHAR(64) | No | — | Stable report id. UNIQUE. |
| report_type | VARCHAR(32) | No | — | install \| heartbeat \| diagnostics \| error. |
| destination_category | VARCHAR(32) | Yes | NULL | Destination identifier (no secrets). |
| status | VARCHAR(32) | No | 'pending' | pending \| sent \| failed. |
| payload_summary | TEXT | Yes | NULL | Redacted summary only; no secrets. |
| redaction_state | VARCHAR(32) | No | 'pending' | pending \| redacted \| not_applicable. |
| send_attempt_count | SMALLINT UNSIGNED | No | 0 | Retry count. |
| response_summary | VARCHAR(512) | Yes | NULL | Sanitized response info. |
| failure_reason | VARCHAR(512) | Yes | NULL | No secrets. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Row creation. |
| sent_at | DATETIME | Yes | NULL | When successfully sent. |
| schema_version | VARCHAR(16) | No | '1' | Schema version. |

**Indexes:** PRIMARY (id); UNIQUE (report_ref); INDEX (report_type); INDEX (status); INDEX (created_at).

**Related objects:** None. **Secrets must not be stored in this table; redaction before write or on read.**

---

## 4. Indexing strategy (§11.10)

- **Primary keys:** All tables use `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY.
- **Stable refs:** Unique index on logical id column (e.g. `artifact_ref`, `job_ref`) for cross-reference.
- **Foreign-reference columns:** Index on every column that references another table or object (run_id, job_ref, execution_log_ref, plan_ref, etc.).
- **Status columns:** Index on queue_status, crawl_status, validation_status, rollback_status, acceptance_status, status (reporting) for filtering.
- **Timestamp columns:** Index on created_at (and sent_at, crawled_at where used for range queries).
- **Composite:** (priority, queue_status) for job queue processing; (crawl_run_id, url) for crawl uniqueness.

---

## 5. Cleanup and retention (§11.11)

| Table | Ephemeral / stale cleanup | Uninstall | Export-protected |
|-------|----------------------------|-----------|------------------|
| Crawl snapshots | Retention window by policy | Remove or retain by prefs | No |
| AI artifacts | Retention by policy | Preserve or remove by prefs | Redaction required |
| Job queue | Completed/failed/cancelled after TTL | Remove | Excluded |
| Execution log | Retention window | Remove by policy | Redaction required |
| Rollback records | Optional TTL | Preserve or remove by prefs | No |
| Token sets | No ephemeral | Preserve by choice | No |
| Assignment maps | No ephemeral | Preserve by choice | No |
| Reporting records | Retention window | Remove by policy | Redaction; excluded by default |

No table may grow without policy. Cleanup jobs (out of scope for this prompt) will use these retention classes.

---

## 6. Security and redaction

- **AI artifacts:** Redaction required for export and sensitive views; redaction_status column; no raw provider secrets in rows.
- **Execution log:** result_summary and error_details must be sanitized; no secrets, tokens, or raw payloads.
- **Reporting records:** payload_summary and response_summary redaction-controlled; secrets must not be stored.
- **All tables:** No column may store API keys, passwords, auth tokens, or session secrets. Mark reporting and artifact tables as restricted; require capability checks for read/write in implementation.

---

## 7. Manifest validation checklist

- [ ] §11.1 Crawl snapshot table(s) — §3.1 aio_crawl_snapshots; columns, indexes, retention, export, version.
- [ ] §11.2 AI artifact table(s) — §3.2 aio_ai_artifacts; columns, indexes, retention, export, redaction, version.
- [ ] §11.3 Job queue table(s) — §3.3 aio_job_queue; columns, indexes, retention, export, version.
- [ ] §11.4 Execution log table(s) — §3.4 aio_execution_log; columns, indexes, retention, redaction, version.
- [ ] §11.5 Diff/rollback table(s) — §3.5 aio_rollback_records; columns, indexes, retention, version.
- [ ] §11.6 Token set table(s) — §3.6 aio_token_sets; columns, indexes, retention, export, version.
- [ ] §11.7 Assignment map table(s) — §3.7 aio_assignment_maps; columns, indexes, retention, export, version.
- [ ] §11.8 Reporting/telemetry table(s) — §3.8 aio_reporting_records; columns, indexes, retention, redaction, version.
- [ ] Every table has primary key and indexes defined (§4).
- [ ] Every table has retention/export notes (§3, §5).
- [ ] Every table has schema_version or versioning key (§3).
- [ ] No data types left vague; no “whatever fields needed later”; no dynamic table creation.

All Section 11 table classes are accounted for. No later installer prompt should guess table structure.
