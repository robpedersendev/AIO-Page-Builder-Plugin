<?php
/**
 * Reporting event type and delivery status constants (spec §46, reporting-payload-contract.md).
 *
 * Stable identifiers for envelope.event_type and delivery_metadata.delivery_status.
 * Do not add event types without contract revision.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Event types for outbound reporting. Must align with reporting-payload-contract.md §2, §8.
 */
final class Reporting_Event_Types {

	/** Installation notification (one per site per install lifecycle). */
	public const INSTALL_NOTIFICATION = 'install_notification';

	/** Monthly heartbeat (one per site per calendar month). */
	public const HEARTBEAT = 'heartbeat';

	/** Developer error report (severity/trigger-based). */
	public const DEVELOPER_ERROR_REPORT = 'developer_error_report';

	/** @var list<string> */
	private static ?array $all = null;

	/**
	 * Returns all event type values.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::INSTALL_NOTIFICATION,
			self::HEARTBEAT,
			self::DEVELOPER_ERROR_REPORT,
		);
		return self::$all;
	}

	/**
	 * Returns whether the value is a valid event type.
	 *
	 * @param string $event_type Event type value.
	 * @return bool
	 */
	public static function is_valid( string $event_type ): bool {
		return in_array( $event_type, self::all(), true );
	}
}
