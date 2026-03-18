<?php
/**
 * Custom capability definitions. Source of truth for all plugin capability names (spec §44.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Stable capability name constants and accessors. Do not rename; gate all privileged actions by these.
 */
final class Capabilities {

	public const MANAGE_SETTINGS              = 'aio_manage_settings';
	public const MANAGE_SECTION_TEMPLATES     = 'aio_manage_section_templates';
	public const MANAGE_PAGE_TEMPLATES        = 'aio_manage_page_templates';
	public const MANAGE_COMPOSITIONS          = 'aio_manage_compositions';
	public const MANAGE_BRAND_PROFILE         = 'aio_manage_brand_profile';
	public const RUN_ONBOARDING               = 'aio_run_onboarding';
	public const MANAGE_AI_PROVIDERS          = 'aio_manage_ai_providers';
	public const RUN_AI_PLANS                 = 'aio_run_ai_plans';
	public const VIEW_BUILD_PLANS             = 'aio_view_build_plans';
	public const APPROVE_BUILD_PLANS          = 'aio_approve_build_plans';
	public const EXECUTE_BUILD_PLANS          = 'aio_execute_build_plans';
	public const EXECUTE_PAGE_REPLACEMENTS    = 'aio_execute_page_replacements';
	public const MANAGE_NAVIGATION_CHANGES    = 'aio_manage_navigation_changes';
	public const MANAGE_TOKEN_CHANGES         = 'aio_manage_token_changes';
	public const FINALIZE_PLAN_ACTIONS        = 'aio_finalize_plan_actions';
	public const VIEW_LOGS                    = 'aio_view_logs';
	public const MANAGE_QUEUE_RECOVERY        = 'aio_manage_queue_recovery';
	public const VIEW_SENSITIVE_DIAGNOSTICS   = 'aio_view_sensitive_diagnostics';
	public const DOWNLOAD_ARTIFACTS           = 'aio_download_artifacts';
	public const EXPORT_DATA                  = 'aio_export_data';
	public const IMPORT_DATA                  = 'aio_import_data';
	public const EXECUTE_ROLLBACKS            = 'aio_execute_rollbacks';
	public const MANAGE_REPORTING_AND_PRIVACY = 'aio_manage_reporting_and_privacy';

	/** CPT-level: Prompt Pack, Documentation, AI Run metadata, Version Snapshot (spec §10, §44.3). */
	public const MANAGE_PROMPT_PACKS    = 'aio_manage_prompt_packs';
	public const MANAGE_DOCUMENTATION   = 'aio_manage_documentation';
	public const VIEW_AI_RUNS           = 'aio_view_ai_runs';
	public const VIEW_VERSION_SNAPSHOTS = 'aio_view_version_snapshots';

	/** @var array<string>|null Full list cached for getAll(). */
	private static ?array $all = null;

	/**
	 * Returns all plugin capability names in stable order. For registration and diagnostics.
	 *
	 * @return array<string>
	 */
	public static function getAll(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::MANAGE_SETTINGS,
			self::MANAGE_SECTION_TEMPLATES,
			self::MANAGE_PAGE_TEMPLATES,
			self::MANAGE_COMPOSITIONS,
			self::MANAGE_BRAND_PROFILE,
			self::RUN_ONBOARDING,
			self::MANAGE_AI_PROVIDERS,
			self::RUN_AI_PLANS,
			self::VIEW_BUILD_PLANS,
			self::APPROVE_BUILD_PLANS,
			self::EXECUTE_BUILD_PLANS,
			self::EXECUTE_PAGE_REPLACEMENTS,
			self::MANAGE_NAVIGATION_CHANGES,
			self::MANAGE_TOKEN_CHANGES,
			self::FINALIZE_PLAN_ACTIONS,
			self::VIEW_LOGS,
			self::MANAGE_QUEUE_RECOVERY,
			self::VIEW_SENSITIVE_DIAGNOSTICS,
			self::DOWNLOAD_ARTIFACTS,
			self::EXPORT_DATA,
			self::IMPORT_DATA,
			self::EXECUTE_ROLLBACKS,
			self::MANAGE_REPORTING_AND_PRIVACY,
			self::MANAGE_PROMPT_PACKS,
			self::MANAGE_DOCUMENTATION,
			self::VIEW_AI_RUNS,
			self::VIEW_VERSION_SNAPSHOTS,
		);
		return self::$all;
	}

	/**
	 * Capabilities granted to Editor by default (spec §44.2). Subset only; no provider/reporting/export/execution.
	 *
	 * @return array<string>
	 */
	public static function get_editor_defaults(): array {
		return array(
			self::VIEW_BUILD_PLANS,
			self::APPROVE_BUILD_PLANS,
			self::VIEW_LOGS,
		);
	}

	/**
	 * Returns whether the given capability is in the plugin set. For validation only.
	 *
	 * @param string $cap Capability name.
	 * @return bool
	 */
	public static function is_plugin_capability( string $cap ): bool {
		return in_array( $cap, self::getAll(), true );
	}
}
