<?php
/**
 * Reporting delivery status constants (spec §46.12, reporting-payload-contract.md §7).
 *
 * Used in delivery_metadata.delivery_status for internal tracking only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Delivery status for reporting attempts. Internal use; not sent outbound.
 */
final class Reporting_Delivery_Status {

	public const PENDING = 'pending';
	public const SENT    = 'sent';
	public const FAILED  = 'failed';
	public const SKIPPED = 'skipped';

	/** @var array<int, string> */
	private static ?array $all = null;

	/**
	 * Returns all delivery status values.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array( self::PENDING, self::SENT, self::FAILED, self::SKIPPED );
		return self::$all;
	}

	/**
	 * Returns whether the value is a valid delivery status.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::all(), true );
	}
}
