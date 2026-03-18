<?php
/**
 * Report of recommendations omitted from a Build Plan (spec §30.3, build-plan-schema.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

/**
 * Machine-readable report of records that were not converted into actionable plan items.
 * Preserves traceability: section, index, reason, optional record snapshot (no secrets).
 */
final class Omitted_Recommendation_Report {

	public const KEY_SECTION         = 'section';
	public const KEY_INDEX           = 'index';
	public const KEY_REASON          = 'reason';
	public const KEY_MESSAGE         = 'message';
	public const KEY_RECORD_SNAPSHOT = 'record_snapshot';

	/**
	 * Builds a single omitted-entry array.
	 *
	 * @param string     $section  Normalized output section key.
	 * @param int        $index    Index in section array.
	 * @param string     $reason   Reason code (e.g. insufficient_data, invalid_reference).
	 * @param string     $message  Optional message.
	 * @param array|null $snapshot Optional record snapshot (redacted).
	 * @return array<string, mixed>
	 */
	public static function entry( string $section, int $index, string $reason, string $message = '', ?array $snapshot = null ): array {
		$out = array(
			self::KEY_SECTION => $section,
			self::KEY_INDEX   => $index,
			self::KEY_REASON  => $reason,
		);
		if ( $message !== '' ) {
			$out[ self::KEY_MESSAGE ] = $message;
		}
		if ( $snapshot !== null ) {
			$out[ self::KEY_RECORD_SNAPSHOT ] = $snapshot;
		}
		return $out;
	}

	/**
	 * Returns a full report payload.
	 *
	 * @param array<int, array<string, mixed>> $entries List of entry arrays.
	 * @return array{omitted: array<int, array<string, mixed>>, count: int}
	 */
	public static function report( array $entries ): array {
		return array(
			'omitted' => array_values( $entries ),
			'count'   => count( $entries ),
		);
	}
}
