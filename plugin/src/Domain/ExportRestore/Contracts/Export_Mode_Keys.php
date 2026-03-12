<?php
/**
 * Export mode keys (spec §52.1, export-bundle-structure-contract.md).
 *
 * Stable identifiers for manifest export_type. Each mode defines included/excluded categories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Export mode constants. Must align with export-bundle-structure-contract.md §2.
 */
final class Export_Mode_Keys {

	/** Full operational backup; all included + optional as configured. */
	public const FULL_OPERATIONAL_BACKUP = 'full_operational_backup';

	/** Pre-uninstall backup; same as full backup. */
	public const PRE_UNINSTALL_BACKUP = 'pre_uninstall_backup';

	/** Support bundle; redacted settings/profile, registries, plans, tokens; optional logs/reporting. */
	public const SUPPORT_BUNDLE = 'support_bundle';

	/** Template-only; registries and compositions only. */
	public const TEMPLATE_ONLY_EXPORT = 'template_only_export';

	/** Plan/artifact export; plans, token sets, optional normalized AI outputs. */
	public const PLAN_ARTIFACT_EXPORT = 'plan_artifact_export';

	/** Uninstall settings/profile only; only settings, profiles, uninstall_restore_metadata (spec §52.11). */
	public const UNINSTALL_SETTINGS_PROFILE_ONLY = 'uninstall_settings_profile_only';

	/** @var list<string>|null */
	private static ?array $all = null;

	/**
	 * Returns all export mode values.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::FULL_OPERATIONAL_BACKUP,
			self::PRE_UNINSTALL_BACKUP,
			self::SUPPORT_BUNDLE,
			self::TEMPLATE_ONLY_EXPORT,
			self::PLAN_ARTIFACT_EXPORT,
			self::UNINSTALL_SETTINGS_PROFILE_ONLY,
		);
		return self::$all;
	}

	/**
	 * Returns whether the value is a valid export mode.
	 *
	 * @param string $mode Export mode value.
	 * @return bool
	 */
	public static function is_valid( string $mode ): bool {
		return in_array( $mode, self::all(), true );
	}
}
