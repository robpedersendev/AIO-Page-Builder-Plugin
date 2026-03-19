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
	 * Returns snapshot_id => created_at (unix timestamp) for snapshots with the given target_ref.
	 *
	 * @param string $target_ref
	 * @return array<string, int>
	 */
	public function list_snapshot_created_times_for_target( string $target_ref ): array {
		$target_ref = trim( $target_ref );
		if ( $target_ref === '' ) {
			return array();
		}
		$store = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$out = array();
		foreach ( $store as $id => $snap ) {
			if ( ! is_array( $snap ) ) {
				continue;
			}
			$ref = isset( $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) ? trim( (string) $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) : '';
			if ( $ref !== $target_ref ) {
				continue;
			}
			$ts         = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				? strtotime( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				: 0;
			$out[ $id ] = $ts;
		}
		return $out;
	}

	/**
	 * Returns rollback-capable history entries for a build plan (v1: page replacement + token only).
	 *
	 * @param string $plan_id Build plan internal key.
	 * @return array<int, array{post_snapshot_id: string, pre_snapshot_id: string, action_type: string, target_ref: string, created_at: string}>
	 */
	public function list_rollback_entries_for_plan( string $plan_id ): array {
		$plan_id = trim( $plan_id );
		if ( $plan_id === '' ) {
			return array();
		}
		$store = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$entries = array();
		foreach ( $store as $id => $snap ) {
			if ( ! is_array( $snap ) ) {
				continue;
			}
			$type = isset( $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] )
				? $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ]
				: '';
			if ( $type !== Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE ) {
				continue;
			}
			$plan_ref = isset( $snap[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ) ? trim( (string) $snap[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ) : '';
			if ( $plan_ref !== $plan_id ) {
				continue;
			}
			$pre_id = isset( $snap['pre_snapshot_id'] ) && is_string( $snap['pre_snapshot_id'] ) ? trim( $snap['pre_snapshot_id'] ) : '';
			if ( $pre_id === '' ) {
				continue;
			}
			$action_type = isset( $snap[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ] )
				? $snap[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ]
				: '';
			$target_ref  = isset( $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) ? trim( (string) $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) : '';
			$created_at  = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				? $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ]
				: '';
			$entries[]   = array(
				'post_snapshot_id' => $id,
				'pre_snapshot_id'  => $pre_id,
				'action_type'      => $action_type,
				'target_ref'       => $target_ref,
				'created_at'       => $created_at,
			);
		}
		usort(
			$entries,
			function ( array $a, array $b ): int {
				$ta = strtotime( $a['created_at'] );
				$tb = strtotime( $b['created_at'] );
				return $tb <=> $ta;
			}
		);
		return array_values( $entries );
	}

	/**
	 * Lists post-change snapshots in optional date range for analytics.
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m-d.
	 * @return list<array<string, mixed>>
	 */
	public function list_post_change_snapshots_for_period( ?string $date_from = null, ?string $date_to = null ): array {
		$store = \get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$from_ts = $date_from !== null && $date_from !== '' ? strtotime( $date_from . ' 00:00:00' ) : false;
		$to_ts   = $date_to !== null && $date_to !== '' ? strtotime( $date_to . ' 23:59:59' ) : false;

		$out = array();
		foreach ( $store as $snap ) {
			if ( ! is_array( $snap ) ) {
				continue;
			}
			$type = isset( $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] )
				? $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ]
				: '';
			if ( $type !== Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE ) {
				continue;
			}
			$created_at = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				? $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ]
				: '';
			$ts         = $created_at !== '' ? strtotime( $created_at ) : false;
			if ( $ts === false ) {
				$out[] = $snap;
				continue;
			}
			if ( $from_ts !== false && $ts < $from_ts ) {
				continue;
			}
			if ( $to_ts !== false && $ts > $to_ts ) {
				continue;
			}
			$out[] = $snap;
		}
		return $out;
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
			$ts             = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
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
