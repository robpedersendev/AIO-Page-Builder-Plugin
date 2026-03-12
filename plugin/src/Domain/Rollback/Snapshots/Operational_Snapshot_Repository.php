<?php
/**
 * Option-based persistence for operational snapshots (spec §41.2, §11.5).
 *
 * Stores snapshot records in a single option; caps size to avoid unbounded growth.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Saves and retrieves operational snapshots via options.
 */
final class Operational_Snapshot_Repository implements Operational_Snapshot_Repository_Interface {

	/** Option key for snapshot store (array keyed by snapshot_id). */
	public const OPTION_KEY = 'aio_operational_snapshots';

	/** Max number of snapshots to retain; oldest by created_at evicted when exceeded. */
	private const MAX_SNAPSHOTS = 1000;

	/**
	 * Saves a full snapshot record.
	 *
	 * @param array<string, mixed> $snapshot Full snapshot root.
	 * @return bool
	 */
	public function save( array $snapshot ): bool {
		$id = isset( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) && is_string( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] )
			? trim( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] )
			: '';
		if ( $id === '' ) {
			return false;
		}
		$store = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$store[ $id ] = $snapshot;
		if ( count( $store ) > self::MAX_SNAPSHOTS ) {
			$store = $this->evict_oldest( $store );
		}
		return \update_option( self::OPTION_KEY, $store, false );
	}

	/**
	 * Retrieves a snapshot by snapshot_id.
	 *
	 * @param string $snapshot_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( string $snapshot_id ): ?array {
		$snapshot_id = trim( $snapshot_id );
		if ( $snapshot_id === '' ) {
			return null;
		}
		$store = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $store ) || ! isset( $store[ $snapshot_id ] ) ) {
			return null;
		}
		$record = $store[ $snapshot_id ];
		return is_array( $record ) ? $record : null;
	}

	/**
	 * Keeps at most MAX_SNAPSHOTS entries; evicts oldest by created_at.
	 *
	 * @param array<string, array<string, mixed>> $store
	 * @return array<string, array<string, mixed>>
	 */
	private function evict_oldest( array $store ): array {
		$with_ts = array();
		foreach ( $store as $id => $snap ) {
			$ts = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				? strtotime( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				: 0;
			$with_ts[ $id ] = $ts;
		}
		asort( $with_ts, SORT_NUMERIC );
		$to_remove = array_slice( array_keys( $with_ts ), 0, count( $store ) - self::MAX_SNAPSHOTS, true );
		foreach ( $to_remove as $id ) {
			unset( $store[ $id ] );
		}
		return $store;
	}
}
