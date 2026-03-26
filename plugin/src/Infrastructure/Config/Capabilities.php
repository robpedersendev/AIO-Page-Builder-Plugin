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

	/** Virtual menu gates: resolved via map_meta_cap (Hub_Menu_Capabilities) for merged admin hubs. */
	public const ACCESS_SETTINGS_HUB         = 'aio_access_settings_hub';
	public const ACCESS_IMPORT_EXPORT_TAB    = 'aio_access_import_export_tab';
	public const ACCESS_AI_WORKSPACE         = 'aio_access_ai_workspace';
	public const ACCESS_ONBOARDING_WORKSPACE = 'aio_access_onboarding_workspace';
	public const ACCESS_PLANS_WORKSPACE      = 'aio_access_plans_workspace';
	public const ACCESS_TEMPLATE_LIBRARY     = 'aio_access_template_library';
	public const ACCESS_INDUSTRY_WORKSPACE   = 'aio_access_industry_workspace';

	/** @var array<string>|null Full list cached for get_all(). */
	private static ?array $all = null;

	/**
	 * Returns all plugin capability names in stable order. For registration and diagnostics.
	 *
	 * @return array<string>
	 */
	public static function get_all(): array {
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
			self::ACCESS_SETTINGS_HUB,
			self::ACCESS_IMPORT_EXPORT_TAB,
			self::ACCESS_AI_WORKSPACE,
			self::ACCESS_ONBOARDING_WORKSPACE,
			self::ACCESS_PLANS_WORKSPACE,
			self::ACCESS_TEMPLATE_LIBRARY,
			self::ACCESS_INDUSTRY_WORKSPACE,
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
		return in_array( $cap, self::get_all(), true );
	}

	/**
	 * Site administrators (manage_options) and multisite super admins get full template registry access regardless of aio_* role grants.
	 * The primitive fallback uses current_user_can_for_route() so activation gaps align with the elevated-admin policy.
	 *
	 * @param string $registry_cap Capabilities::MANAGE_* for the relevant registry surface.
	 * @return bool
	 */
	public static function current_user_can_or_site_admin( string $registry_cap ): bool {
		if ( \current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( \function_exists( 'is_multisite' ) && \is_multisite() && \function_exists( 'is_super_admin' ) && \is_super_admin() ) {
			return true;
		}
		return self::current_user_can_for_route( $registry_cap );
	}

	/**
	 * Whether the capability is a WordPress meta cap that must be checked with a post/page ID (WP 6.1+).
	 *
	 * @param string $cap Capability name.
	 * @return bool
	 */
	public static function is_meta_post_or_page_cap_without_object( string $cap ): bool {
		return in_array(
			$cap,
			array( 'delete_post', 'delete_page', 'edit_post', 'edit_page', 'read_post', 'read_page' ),
			true
		);
	}

	/**
	 * current_user_can() for admin routes and tab gates that only use primitive or plugin caps.
	 *
	 * * Never passes bare meta post/page caps to core — that triggers map_meta_cap() _doing_it_wrong (WP 6.1+).
	 * * If a dynamic cap is ever miswired to a meta cap without an object ID, access is denied instead of logging.
	 * * Site administrators (manage_options) and network super admins pass every plugin capability here so hubs/tabs stay aligned with full admin access (activation gaps, map_meta_cap ordering).
	 *
	 * @param string $cap Capability string (from route registry or screen get_capability()).
	 * @return bool
	 */
	public static function current_user_can_for_route( string $cap ): bool {
		if ( self::is_meta_post_or_page_cap_without_object( $cap ) ) {
			return false;
		}
		if ( self::is_plugin_capability( $cap ) && self::current_user_is_elevated_site_admin() ) {
			return true;
		}
		return \current_user_can( $cap );
	}

	/**
	 * Whether the current user is a site admin (manage_options) or a multisite super admin.
	 *
	 * @return bool
	 */
	private static function current_user_is_elevated_site_admin(): bool {
		if ( ! \is_user_logged_in() ) {
			return false;
		}
		if ( \current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( \function_exists( 'is_multisite' ) && \is_multisite() && \function_exists( 'is_super_admin' ) && \is_super_admin() ) {
			return true;
		}
		return false;
	}
}
