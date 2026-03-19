<?php
/**
 * Bounded audit trail for major industry profile changes (Prompt 465, industry-profile-audit-trail-contract).
 * Records primary/secondary industry, subtype, starter bundle changes for support and change-impact analysis.
 * Admin/support-only; no public exposure. Safe fallback when data missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Appends and retrieves bounded profile change events. Does not mutate profile or approval snapshots.
 */
final class Industry_Profile_Audit_Trail_Service {

	public const EVENT_PRIMARY_INDUSTRY_CHANGED     = 'primary_industry_changed';
	public const EVENT_SECONDARY_INDUSTRIES_CHANGED = 'secondary_industries_changed';
	public const EVENT_SUBTYPE_CHANGED              = 'subtype_changed';
	public const EVENT_STARTER_BUNDLE_CHANGED       = 'starter_bundle_changed';
	public const EVENT_PROFILE_REPLACED             = 'profile_replaced';

	private const OPTION_KEY  = Option_Names::INDUSTRY_PROFILE_AUDIT_TRAIL;
	private const MAX_EVENTS  = 100;
	private const SUMMARY_MAX = 256;

	/**
	 * Records profile change by diffing old and new; appends one or more events. Call after successful profile write.
	 *
	 * @param array<string, mixed> $old_profile Profile before write.
	 * @param array<string, mixed> $new_profile Profile after write (normalized).
	 * @return void
	 */
	public function record_profile_change( array $old_profile, array $new_profile ): void {
		$events = array();

		$old_primary = trim( (string) ( $old_profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '' ) );
		$new_primary = trim( (string) ( $new_profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '' ) );
		if ( $old_primary !== $new_primary ) {
			$events[] = $this->event(
				self::EVENT_PRIMARY_INDUSTRY_CHANGED,
				$old_primary !== '' ? 'primary: ' . $old_primary : 'primary: (none)',
				$new_primary !== '' ? 'primary: ' . $new_primary : 'primary: (none)',
				array_filter( array( $old_primary, $new_primary ) )
			);
		}

		$old_secondary = isset( $old_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $old_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $old_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: array();
		$new_secondary = isset( $new_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $new_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $new_profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: array();
		if ( $this->array_refs_differ( $old_secondary, $new_secondary ) ) {
			$events[] = $this->event(
				self::EVENT_SECONDARY_INDUSTRIES_CHANGED,
				'secondary: ' . ( count( $old_secondary ) > 0 ? implode( ', ', array_slice( $old_secondary, 0, 5 ) ) : '(none)' ),
				'secondary: ' . ( count( $new_secondary ) > 0 ? implode( ', ', array_slice( $new_secondary, 0, 5 ) ) : '(none)' ),
				array_merge( array_slice( $old_secondary, 0, 10 ), array_slice( $new_secondary, 0, 10 ) )
			);
		}

		$old_subtype = trim( (string) ( $old_profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ?? $old_profile[ Industry_Profile_Schema::FIELD_SUBTYPE ] ?? '' ) );
		$new_subtype = trim( (string) ( $new_profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ?? $new_profile[ Industry_Profile_Schema::FIELD_SUBTYPE ] ?? '' ) );
		if ( $old_subtype !== $new_subtype ) {
			$events[] = $this->event(
				self::EVENT_SUBTYPE_CHANGED,
				$old_subtype !== '' ? 'subtype: ' . $old_subtype : 'subtype: (none)',
				$new_subtype !== '' ? 'subtype: ' . $new_subtype : 'subtype: (none)',
				array_filter( array( $old_subtype, $new_subtype ) )
			);
		}

		$old_bundle = trim( (string) ( $old_profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ?? '' ) );
		$new_bundle = trim( (string) ( $new_profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ?? '' ) );
		if ( $old_bundle !== $new_bundle ) {
			$events[] = $this->event(
				self::EVENT_STARTER_BUNDLE_CHANGED,
				$old_bundle !== '' ? 'bundle: ' . $old_bundle : 'bundle: (none)',
				$new_bundle !== '' ? 'bundle: ' . $new_bundle : 'bundle: (none)',
				array_filter( array( $old_bundle, $new_bundle ) )
			);
		}

		if ( empty( $events ) ) {
			return;
		}

		$existing = $this->get_raw_events();
		foreach ( array_reverse( $events ) as $ev ) {
			array_unshift( $existing, $ev );
		}
		$existing = array_slice( $existing, 0, self::MAX_EVENTS );
		\update_option( self::OPTION_KEY, $existing, true );
	}

	/**
	 * Returns timeline of events (newest first). Bounded by limit.
	 *
	 * @param int $limit Max events to return (default 50).
	 * @return array<int, array{event_type: string, timestamp: string, old_summary: string, new_summary: string, related_refs: array, actor?: string}>
	 */
	public function get_timeline( int $limit = 50 ): array {
		$events = $this->get_raw_events();
		$limit  = $limit > 0 ? min( $limit, self::MAX_EVENTS ) : 50;
		return array_slice( $events, 0, $limit );
	}

	/**
	 * Builds a single event array.
	 *
	 * @param string   $event_type
	 * @param string   $old_summary
	 * @param string   $new_summary
	 * @param string[] $related_refs
	 * @return array<string, mixed>
	 */
	private function event( string $event_type, string $old_summary, string $new_summary, array $related_refs ): array {
		$old_summary  = substr( $old_summary, 0, self::SUMMARY_MAX );
		$new_summary  = substr( $new_summary, 0, self::SUMMARY_MAX );
		$related_refs = array_values( array_unique( array_slice( array_map( 'strval', $related_refs ), 0, 20 ) ) );
		return array(
			'event_type'   => $event_type,
			'timestamp'    => gmdate( 'c' ),
			'old_summary'  => $old_summary,
			'new_summary'  => $new_summary,
			'related_refs' => $related_refs,
		);
	}

	private function array_refs_differ( array $a, array $b ): bool {
		$a = array_values( array_filter( array_map( 'trim', array_map( 'strval', $a ) ) ) );
		$b = array_values( array_filter( array_map( 'trim', array_map( 'strval', $b ) ) ) );
		if ( count( $a ) !== count( $b ) ) {
			return true;
		}
		sort( $a );
		sort( $b );
		return $a !== $b;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_raw_events(): array {
		$raw = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $e ) {
			if ( is_array( $e ) && isset( $e['event_type'], $e['timestamp'] ) ) {
				$out[] = $e;
			}
		}
		return $out;
	}
}
