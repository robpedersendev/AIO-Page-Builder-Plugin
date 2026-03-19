<?php
/**
 * Restore scope keys for Import / Restore (spec §52, §52.8). Scope limits which plugin-owned categories are written.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Restore scope identifiers selected by user before confirm restore.
 */
final class Restore_Scope_Keys {

	/** Restore only settings + profile data (and closely related plugin-owned metadata). */
	public const SETTINGS_PROFILE_ONLY = 'settings_profile_only';

	/** Restore full plugin-owned backup (all included categories in package). */
	public const FULL_AIO_BACKUP = 'full_aio_backup';

	/** @var array<int, string>|null */
	private static ?array $all = null;

	/**
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::SETTINGS_PROFILE_ONLY,
			self::FULL_AIO_BACKUP,
		);
		return self::$all;
	}

	public static function is_valid( string $scope ): bool {
		return in_array( $scope, self::all(), true );
	}
}
