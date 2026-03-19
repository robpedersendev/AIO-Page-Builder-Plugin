<?php
/**
 * Stable global option keys. Single namespaced root; do not rename (spec ?9.4, ?62.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical option name constants. Future prompts may add fields within approved structures; roots require migration.
 */
final class Option_Names {

	private const PREFIX = 'aio_page_builder_';

	/** Main plugin settings (exportable, user-configurable). */
	public const MAIN_SETTINGS = self::PREFIX . 'settings';

	/** Version and migration markers (internal, runtime). */
	public const VERSION_MARKERS = self::PREFIX . 'version_markers';

	/** Reporting settings placeholder; no secrets (exportable). */
	public const REPORTING_SETTINGS = self::PREFIX . 'reporting';

	/** Install notification sent state (dedupe); internal, spec ?46.2. */
	public const INSTALL_NOTICE_STATE = self::PREFIX . 'install_notice_state';

	/** Reporting log (attempts and delivery status); internal, spec ?46.12. */
	public const REPORTING_LOG = self::PREFIX . 'reporting_log';

	/** Heartbeat state (last_successful_month, retry state); internal, spec ?46.4. */
	public const HEARTBEAT_STATE = self::PREFIX . 'heartbeat_state';

	/** Error report state (sent dedupe keys, retry state); internal, spec ?46.6, ?46.12. */
	public const ERROR_REPORT_STATE = self::PREFIX . 'error_report_state';

	/** Dependency notice dismissals (internal, runtime). */
	public const DEPENDENCY_NOTICE_DISMISSALS = self::PREFIX . 'dependency_notices';

	/** Uninstall/restore preferences (user-configurable, exportable). */
	public const UNINSTALL_PREFS = self::PREFIX . 'uninstall_prefs';

	/** Provider config reference only; secrets live in separate storage (not in exportable blob). */
	public const PROVIDER_CONFIG_REF = self::PREFIX . 'provider_config';

	/** Provider health: connection_test_result and last_successful_use per provider (no secrets; spec ?49.9). */
	public const PROVIDER_HEALTH_STATE = self::PREFIX . 'provider_health_state';

	/** Current editable brand and business profile (single option; shape per profile-schema.md). */
	public const PROFILE_CURRENT = self::PREFIX . 'profile_current';

	/** Site-level industry profile (primary/secondary industry, subtype, service/geo model; shape per industry-profile-schema.md). */
	public const INDUSTRY_PROFILE = self::PREFIX . 'industry_profile';

	/** Onboarding draft state (secret-free; shape per onboarding-state-machine.md ?7). */
	public const ONBOARDING_DRAFT = self::PREFIX . 'onboarding_draft';

	/** Prompt experiment definitions (Prompt 121; no secrets). */
	public const PROMPT_EXPERIMENTS = self::PREFIX . 'prompt_experiments';

	/** Global styling settings (tokens, component overrides); plugin-owned, removed on uninstall (spec ?17.10, styling contract ?8). */
	public const GLOBAL_STYLE_SETTINGS = 'aio_global_style_settings';

	/** Applied design tokens (execution-applied; merged on read by Global_Style_Settings_Repository). */
	public const APPLIED_DESIGN_TOKENS = self::PREFIX . 'applied_design_tokens';

	/** Per-entity style payloads (section/page template overrides); plugin-owned, removed on uninstall. */
	public const ENTITY_STYLE_PAYLOADS = 'aio_entity_style_payloads';

	/** Style cache version marker; plugin-owned, removed on uninstall. */
	public const STYLE_CACHE_VERSION = 'aio_style_cache_version';

	/** Applied industry style preset (preset_key, label?, applied_at); for revert/display (industry-style-preset-application-contract). */
	public const APPLIED_INDUSTRY_PRESET = self::PREFIX . 'applied_industry_preset';

	/** Industry section overrides (section_key => override record per industry-override-contract; Prompts 367���369). */
	public const INDUSTRY_SECTION_OVERRIDES = self::PREFIX . 'industry_section_overrides';

	/** Industry page template overrides (template_key => override record). */
	public const INDUSTRY_PAGE_TEMPLATE_OVERRIDES = self::PREFIX . 'industry_page_template_overrides';

	/** Industry Build Plan item overrides (plan_id => item_id => override record). */
	public const INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES = self::PREFIX . 'industry_build_plan_item_overrides';

	/** Disabled industry pack keys (list of industry_key; admin toggle; industry-pack-activation-contract). */
	public const DISABLED_INDUSTRY_PACKS = self::PREFIX . 'disabled_industry_packs';

	/** Industry read-model cache version; bump invalidates caches (industry-cache-contract; removed on uninstall). */
	public const INDUSTRY_CACHE_VERSION = 'aio_industry_cache_version';

	/** Bounded audit trail for industry profile changes (Prompt 465; industry-profile-audit-trail-contract). */
	public const INDUSTRY_PROFILE_AUDIT_TRAIL = self::PREFIX . 'industry_profile_audit_trail';

	/** @var array<string>|null */
	private static ?array $all = null;

	/**
	 * Production readiness / lifecycle options (explicit keys; do not prefix-change).
	 * These are referenced by external operational docs and lifecycle hooks.
	 */
	public const PB_DO_FIRST_RUN_REDIRECT      = 'aio_pb_do_first_run_redirect';
	public const PB_INSTALLATION_ID            = 'aio_pb_installation_id';
	public const PB_UNINSTALL_CLEANUP_MODE     = 'aio_pb_uninstall_cleanup_mode';
	public const PB_LAST_DEACTIVATION_AT       = 'aio_pb_last_deactivation_at';
	public const PB_VERSION_STATE              = 'aio_pb_version_state';
	public const PB_ENVIRONMENT_DIAGNOSTICS    = 'aio_pb_environment_diagnostics';

	/**
	 * Returns all known option keys in stable order.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::MAIN_SETTINGS,
			self::VERSION_MARKERS,
			self::REPORTING_SETTINGS,
			self::INSTALL_NOTICE_STATE,
			self::REPORTING_LOG,
			self::HEARTBEAT_STATE,
			self::ERROR_REPORT_STATE,
			self::DEPENDENCY_NOTICE_DISMISSALS,
			self::UNINSTALL_PREFS,
			self::PROVIDER_CONFIG_REF,
			self::PROVIDER_HEALTH_STATE,
			self::PROFILE_CURRENT,
			self::INDUSTRY_PROFILE,
			self::ONBOARDING_DRAFT,
			self::PROMPT_EXPERIMENTS,
			self::GLOBAL_STYLE_SETTINGS,
			self::APPLIED_DESIGN_TOKENS,
			self::ENTITY_STYLE_PAYLOADS,
			self::STYLE_CACHE_VERSION,
			self::APPLIED_INDUSTRY_PRESET,
			self::INDUSTRY_SECTION_OVERRIDES,
			self::INDUSTRY_PAGE_TEMPLATE_OVERRIDES,
			self::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES,
			self::DISABLED_INDUSTRY_PACKS,
			self::INDUSTRY_CACHE_VERSION,
			self::INDUSTRY_PROFILE_AUDIT_TRAIL,
			// Production readiness / lifecycle state (non-exportable unless explicitly included in export manifest).
			self::PB_DO_FIRST_RUN_REDIRECT,
			self::PB_INSTALLATION_ID,
			self::PB_UNINSTALL_CLEANUP_MODE,
			self::PB_LAST_DEACTIVATION_AT,
			self::PB_VERSION_STATE,
			self::PB_ENVIRONMENT_DIAGNOSTICS,
		);
		return self::$all;
	}

	/**
	 * Returns whether the key is a known option name.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public static function is_valid( string $key ): bool {
		return in_array( $key, self::all(), true );
	}
}
