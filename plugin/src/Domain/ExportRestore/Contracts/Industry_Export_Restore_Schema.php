<?php
/**
 * Schema version and validation for industry export/restore payload (profiles/industry.json).
 * See docs/contracts/industry-export-restore-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Industry export payload schema version and supported versions for restore.
 */
final class Industry_Export_Restore_Schema {

	/** Current industry export schema version. */
	public const SCHEMA_VERSION = '1';

	/** Supported schema versions for restore (fail safely on unknown). */
	public const SUPPORTED_VERSIONS = array( '1' );

	/** Payload key: schema version. */
	public const KEY_SCHEMA_VERSION = 'schema_version';

	/** Payload key: industry profile. */
	public const KEY_INDUSTRY_PROFILE = 'industry_profile';

	/** Payload key: applied preset. */
	public const KEY_APPLIED_PRESET = 'applied_preset';

	/**
	 * Whether the given schema version is supported on restore.
	 *
	 * @param string $version Version string from payload.
	 * @return bool
	 */
	public static function is_supported_version( string $version ): bool {
		return in_array( $version, self::SUPPORTED_VERSIONS, true );
	}
}
