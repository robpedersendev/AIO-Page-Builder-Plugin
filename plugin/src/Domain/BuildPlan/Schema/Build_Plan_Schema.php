<?php
/**
 * Build Plan object schema constants (spec §10.4, §30.1–30.12, build-plan-schema.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Root Build Plan field names, status enum, and step type enum. Machine-readable; stable.
 */
final class Build_Plan_Schema {

	public const SCHEMA_VERSION_DEFAULT = '1';

	/** Required root fields (build-plan-schema.md §4.1). */
	public const KEY_PLAN_ID           = 'plan_id';
	public const KEY_STATUS           = 'status';
	public const KEY_AI_RUN_REF       = 'ai_run_ref';
	public const KEY_NORMALIZED_OUTPUT_REF = 'normalized_output_ref';
	public const KEY_PLAN_TITLE        = 'plan_title';
	public const KEY_PLAN_SUMMARY      = 'plan_summary';
	public const KEY_SITE_PURPOSE_SUMMARY = 'site_purpose_summary';
	public const KEY_SITE_FLOW_SUMMARY = 'site_flow_summary';
	public const KEY_STEPS             = 'steps';
	public const KEY_CREATED_AT        = 'created_at';

	/** Optional root fields. */
	public const KEY_PROFILE_CONTEXT_REF   = 'profile_context_ref';
	public const KEY_CRAWL_SNAPSHOT_REF    = 'crawl_snapshot_ref';
	public const KEY_REGISTRY_SNAPSHOT_REF = 'registry_snapshot_ref';
	public const KEY_AFFECTED_PAGE_REFS    = 'affected_page_refs';
	public const KEY_COMPLETED_AT          = 'completed_at';
	public const KEY_ACTOR_REFS            = 'actor_refs';
	public const KEY_APPROVAL_DENIAL_STATE = 'approval_denial_state';
	public const KEY_REMAINING_WORK_STATUS = 'remaining_work_status';
	public const KEY_EXECUTION_STATUS      = 'execution_status';
	public const KEY_WARNINGS              = 'warnings';
	public const KEY_ASSUMPTIONS           = 'assumptions';
	public const KEY_CONFIDENCE            = 'confidence';
	public const KEY_EXECUTION_HISTORY_ANCHOR = 'execution_history_anchor';
	public const KEY_HISTORY_RETENTION     = 'history_retention';
	public const KEY_SCHEMA_VERSION        = 'schema_version';
	/** Industry context at approval/execution-request time (industry-approval-snapshot-contract.md). */
	public const KEY_INDUSTRY_APPROVAL_SNAPSHOT = 'industry_approval_snapshot';

	/** Status enum (spec §30.4, object-model §3.4). */
	public const STATUS_PENDING_REVIEW = 'pending_review';
	public const STATUS_APPROVED       = 'approved';
	public const STATUS_REJECTED      = 'rejected';
	public const STATUS_IN_PROGRESS   = 'in_progress';
	public const STATUS_COMPLETED     = 'completed';
	public const STATUS_SUPERSEDED    = 'superseded';

	public const STATUS_ENUM = array(
		self::STATUS_PENDING_REVIEW,
		self::STATUS_APPROVED,
		self::STATUS_REJECTED,
		self::STATUS_IN_PROGRESS,
		self::STATUS_COMPLETED,
		self::STATUS_SUPERSEDED,
	);

	/** Step type enum (seven-step UI + confirmation, build-plan-schema.md §3.1). */
	public const STEP_TYPE_OVERVIEW             = 'overview';
	public const STEP_TYPE_EXISTING_PAGE_CHANGES = 'existing_page_changes';
	public const STEP_TYPE_NEW_PAGES            = 'new_pages';
	public const STEP_TYPE_HIERARCHY_FLOW       = 'hierarchy_flow';
	public const STEP_TYPE_NAVIGATION           = 'navigation';
	public const STEP_TYPE_DESIGN_TOKENS        = 'design_tokens';
	public const STEP_TYPE_SEO                  = 'seo';
	public const STEP_TYPE_CONFIRMATION         = 'confirmation';
	public const STEP_TYPE_LOGS_ROLLBACK        = 'logs_rollback';

	public const STEP_TYPES = array(
		self::STEP_TYPE_OVERVIEW,
		self::STEP_TYPE_EXISTING_PAGE_CHANGES,
		self::STEP_TYPE_NEW_PAGES,
		self::STEP_TYPE_HIERARCHY_FLOW,
		self::STEP_TYPE_NAVIGATION,
		self::STEP_TYPE_DESIGN_TOKENS,
		self::STEP_TYPE_SEO,
		self::STEP_TYPE_CONFIRMATION,
		self::STEP_TYPE_LOGS_ROLLBACK,
	);

	/** Required root field keys for plan eligibility. */
	public const REQUIRED_ROOT_KEYS = array(
		self::KEY_PLAN_ID,
		self::KEY_STATUS,
		self::KEY_AI_RUN_REF,
		self::KEY_NORMALIZED_OUTPUT_REF,
		self::KEY_PLAN_TITLE,
		self::KEY_PLAN_SUMMARY,
		self::KEY_SITE_PURPOSE_SUMMARY,
		self::KEY_SITE_FLOW_SUMMARY,
		self::KEY_STEPS,
		self::KEY_CREATED_AT,
	);

	/**
	 * Returns required root keys.
	 *
	 * @return array<int, string>
	 */
	public static function get_required_root_keys(): array {
		return self::REQUIRED_ROOT_KEYS;
	}

	/**
	 * Returns whether status is valid.
	 */
	public static function is_valid_status( string $status ): bool {
		return in_array( $status, self::STATUS_ENUM, true );
	}

	/**
	 * Returns whether the plan is eligible for execution (status = approved).
	 */
	public static function is_eligible_for_execution( string $status ): bool {
		return $status === self::STATUS_APPROVED;
	}

	/**
	 * Returns whether step_type is valid.
	 */
	public static function is_valid_step_type( string $step_type ): bool {
		return in_array( $step_type, self::STEP_TYPES, true );
	}
}
