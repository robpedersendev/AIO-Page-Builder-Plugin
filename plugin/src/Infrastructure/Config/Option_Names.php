<?php
/**
 * Stable global option keys. Single namespaced root; do not rename (spec §9.4, §62.3).
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

	/** Install notification sent state (dedupe); internal, spec §46.2. */
	public const INSTALL_NOTICE_STATE = self::PREFIX . 'install_notice_state';

	/** Reporting log (attempts and delivery status); internal, spec §46.12. */
	public const REPORTING_LOG = self::PREFIX . 'reporting_log';

	/** Heartbeat state (last_successful_month, retry state); internal, spec §46.4. */
	public const HEARTBEAT_STATE = self::PREFIX . 'heartbeat_state';

	/** Error report state (sent dedupe keys, retry state); internal, spec §46.6, §46.12. */
	public const ERROR_REPORT_STATE = self::PREFIX . 'error_report_state';

	/** Dependency notice dismissals (internal, runtime). */
	public const DEPENDENCY_NOTICE_DISMISSALS = self::PREFIX . 'dependency_notices';

	/** Uninstall/restore preferences (user-configurable, exportable). */
	public const UNINSTALL_PREFS = self::PREFIX . 'uninstall_prefs';

	/** Provider config reference only; secrets live in separate storage (not in exportable blob). */
	public const PROVIDER_CONFIG_REF = self::PREFIX . 'provider_config';

	/** Current editable brand and business profile (single option; shape per profile-schema.md). */
	public const PROFILE_CURRENT = self::PREFIX . 'profile_current';

	/** Onboarding draft state (secret-free; shape per onboarding-state-machine.md §7). */
	public const ONBOARDING_DRAFT = self::PREFIX . 'onboarding_draft';

	/** @var array<string>|null */
	private static ?array $all = null;

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
			self::PROFILE_CURRENT,
			self::ONBOARDING_DRAFT,
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
