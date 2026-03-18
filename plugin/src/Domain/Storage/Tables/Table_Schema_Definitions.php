<?php
/**
 * Deterministic SQL definitions for custom tables (spec §11, custom-table-manifest.md). dbDelta-compliant.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Builds CREATE TABLE statements from the manifest. One source of truth; no ad hoc SQL elsewhere.
 * Each definition is keyed by Table_Names suffix and includes full table name and SQL body.
 */
final class Table_Schema_Definitions {

	/**
	 * Returns definitions for all manifest tables. SQL is dbDelta-compliant (backticks, two spaces before PRIMARY KEY).
	 *
	 * @param \wpdb|object $wpdb WordPress database abstraction (must have prefix and get_charset_collate()).
	 * @return array<int, array{name: string, sql: string}> List of name => full table name, sql => CREATE TABLE statement.
	 */
	public static function get_definitions( $wpdb ): array {
		$prefix  = $wpdb->prefix;
		$charset = $wpdb->get_charset_collate();
		$defs    = array();

		$defs[] = array(
			'name' => $prefix . Table_Names::CRAWL_SNAPSHOTS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::CRAWL_SNAPSHOTS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `crawl_run_id` varchar(64) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `canonical_url` varchar(2048) DEFAULT NULL,
  `title_snapshot` varchar(512) DEFAULT NULL,
  `meta_snapshot` text DEFAULT NULL,
  `indexability_flags` varchar(255) DEFAULT NULL,
  `page_classification` varchar(64) DEFAULT NULL,
  `hierarchy_clues` text DEFAULT NULL,
  `navigation_participation` tinyint(3) UNSIGNED DEFAULT 0,
  `summary_data` text DEFAULT NULL,
  `content_hash` varchar(64) DEFAULT NULL,
  `crawl_status` varchar(32) NOT NULL DEFAULT 'pending',
  `error_state` varchar(255) DEFAULT NULL,
  `crawled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `crawl_run_id_url` (`crawl_run_id`,`url`(191)),
  KEY `crawl_run_id` (`crawl_run_id`),
  KEY `crawl_status` (`crawl_status`),
  KEY `crawled_at` (`crawled_at`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::AI_ARTIFACTS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::AI_ARTIFACTS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artifact_ref` varchar(64) NOT NULL,
  `run_id` varchar(64) NOT NULL,
  `artifact_type` varchar(32) NOT NULL,
  `file_ref` varchar(512) DEFAULT NULL,
  `raw_prompt_ref` varchar(64) DEFAULT NULL,
  `raw_response_ref` varchar(64) DEFAULT NULL,
  `normalized_output_ref` varchar(64) DEFAULT NULL,
  `validation_status` varchar(32) NOT NULL DEFAULT 'pending',
  `redaction_status` varchar(32) NOT NULL DEFAULT 'pending',
  `usage_metadata` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `artifact_ref` (`artifact_ref`),
  KEY `run_id` (`run_id`),
  KEY `artifact_type` (`artifact_type`),
  KEY `validation_status` (`validation_status`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::JOB_QUEUE,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::JOB_QUEUE . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_ref` varchar(64) NOT NULL,
  `job_type` varchar(64) NOT NULL,
  `queue_status` varchar(32) NOT NULL DEFAULT 'queued',
  `priority` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `payload_ref` varchar(512) DEFAULT NULL,
  `actor_ref` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `retry_count` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `lock_token` varchar(64) DEFAULT NULL,
  `failure_reason` varchar(512) DEFAULT NULL,
  `related_object_refs` text DEFAULT NULL,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `job_ref` (`job_ref`),
  KEY `queue_status` (`queue_status`),
  KEY `job_type` (`job_type`),
  KEY `created_at` (`created_at`),
  KEY `priority_queue_status` (`priority`,`queue_status`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::EXECUTION_LOG,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::EXECUTION_LOG . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_ref` varchar(64) NOT NULL,
  `action_type` varchar(64) NOT NULL,
  `job_ref` varchar(64) DEFAULT NULL,
  `affected_object_refs` text DEFAULT NULL,
  `actor_ref` varchar(64) DEFAULT NULL,
  `pre_change_snapshot_ref` varchar(64) DEFAULT NULL,
  `result_summary` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `warning_flags` varchar(255) DEFAULT NULL,
  `error_details_ref` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `log_ref` (`log_ref`),
  KEY `action_type` (`action_type`),
  KEY `job_ref` (`job_ref`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::ROLLBACK_RECORDS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::ROLLBACK_RECORDS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `diff_ref` varchar(64) NOT NULL,
  `rollback_ref` varchar(64) DEFAULT NULL,
  `execution_log_ref` varchar(64) DEFAULT NULL,
  `snapshot_refs` text DEFAULT NULL,
  `object_scope` varchar(64) NOT NULL,
  `object_ref` varchar(64) DEFAULT NULL,
  `diff_type` varchar(32) NOT NULL,
  `rollback_eligible` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `rollback_status` varchar(32) NOT NULL DEFAULT 'none',
  `failure_notes` varchar(512) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `diff_ref` (`diff_ref`),
  KEY `execution_log_ref` (`execution_log_ref`),
  KEY `rollback_ref` (`rollback_ref`),
  KEY `rollback_status` (`rollback_status`),
  KEY `object_scope_object_ref` (`object_scope`,`object_ref`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::TOKEN_SETS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::TOKEN_SETS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_set_ref` varchar(64) NOT NULL,
  `source_type` varchar(32) NOT NULL,
  `state` varchar(32) NOT NULL DEFAULT 'proposed',
  `plan_ref` varchar(64) DEFAULT NULL,
  `scope_ref` varchar(64) DEFAULT NULL,
  `value_payload` longtext DEFAULT NULL,
  `acceptance_status` varchar(32) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_at` datetime DEFAULT NULL,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `token_set_ref` (`token_set_ref`),
  KEY `plan_ref` (`plan_ref`),
  KEY `scope_ref` (`scope_ref`),
  KEY `state` (`state`),
  KEY `acceptance_status` (`acceptance_status`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::ASSIGNMENT_MAPS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::ASSIGNMENT_MAPS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `map_type` varchar(64) NOT NULL,
  `source_ref` varchar(64) NOT NULL,
  `target_ref` varchar(64) NOT NULL,
  `scope_ref` varchar(64) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  KEY `map_type` (`map_type`),
  KEY `source_ref` (`source_ref`),
  KEY `target_ref` (`target_ref`),
  KEY `map_type_source_ref` (`map_type`,`source_ref`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		$defs[] = array(
			'name' => $prefix . Table_Names::REPORTING_RECORDS,
			'sql'  => "CREATE TABLE `{$prefix}" . Table_Names::REPORTING_RECORDS . "` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_ref` varchar(64) NOT NULL,
  `report_type` varchar(32) NOT NULL,
  `destination_category` varchar(32) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `payload_summary` text DEFAULT NULL,
  `redaction_state` varchar(32) NOT NULL DEFAULT 'pending',
  `send_attempt_count` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `response_summary` varchar(512) DEFAULT NULL,
  `failure_reason` varchar(512) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `schema_version` varchar(16) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `report_ref` (`report_ref`),
  KEY `report_type` (`report_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) $charset;",
		);

		return $defs;
	}
}
