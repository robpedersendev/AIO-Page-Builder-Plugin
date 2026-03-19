<?php
/**
 * Persists and retrieves industry Build Plan item overrides (industry-override-contract.md, Prompt 369).
 * Option-backed; reviewer/admin-only mutations via Save_Industry_Build_Plan_Override_Action.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Overrides;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Build Plan item override persistence: record, get, list per plan. Safe when option missing or invalid.
 */
final class Industry_Build_Plan_Item_Override_Service {

	/**
	 * Records an override for a Build Plan item.
	 *
	 * @param string $plan_id  Plan ID (internal_key or UUID).
	 * @param string $item_id  Plan item item_id.
	 * @param string $state    Industry_Override_Schema::STATE_ACCEPTED or STATE_REJECTED.
	 * @param string $reason   Optional review note (sanitized by caller or use Industry_Override_Schema::sanitize_reason).
	 * @return bool True when saved; false when validation failed or save failed.
	 */
	public function record_override( string $plan_id, string $item_id, string $state, string $reason = '' ): bool {
		$plan_id = trim( $plan_id );
		$item_id = trim( $item_id );
		if ( $plan_id === '' || $item_id === '' || strlen( $item_id ) > Industry_Override_Schema::TARGET_KEY_MAX_LENGTH ) {
			return false;
		}
		$reason   = Industry_Override_Schema::sanitize_reason( $reason );
		$override = array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
			Industry_Override_Schema::FIELD_TARGET_KEY  => $item_id,
			Industry_Override_Schema::FIELD_PLAN_ID     => $plan_id,
			Industry_Override_Schema::FIELD_STATE       => $state,
			Industry_Override_Schema::FIELD_REASON      => $reason,
			Industry_Override_Schema::FIELD_CREATED_AT  => time(),
			Industry_Override_Schema::FIELD_UPDATED_AT  => time(),
		);
		if ( Industry_Override_Schema::validate( $override ) !== array() ) {
			return false;
		}
		$all = $this->get_all();
		if ( ! isset( $all[ $plan_id ] ) || ! is_array( $all[ $plan_id ] ) ) {
			$all[ $plan_id ] = array();
		}
		$all[ $plan_id ][ $item_id ] = $override;
		return \update_option( Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES, $all, true ) !== false;
	}

	/**
	 * Returns override record for a plan item, or null.
	 *
	 * @param string $plan_id
	 * @param string $item_id
	 * @return array<string, mixed>|null
	 */
	public function get_override( string $plan_id, string $item_id ): ?array {
		$plan_id = trim( $plan_id );
		$item_id = trim( $item_id );
		if ( $plan_id === '' || $item_id === '' ) {
			return null;
		}
		$all            = $this->get_all();
		$plan_overrides = $all[ $plan_id ] ?? array();
		if ( ! is_array( $plan_overrides ) ) {
			return null;
		}
		return isset( $plan_overrides[ $item_id ] ) && is_array( $plan_overrides[ $item_id ] ) ? $plan_overrides[ $item_id ] : null;
	}

	/**
	 * Returns all item overrides for a plan (item_id => override record).
	 *
	 * @param string $plan_id
	 * @return array<string, array<string, mixed>>
	 */
	public function list_for_plan( string $plan_id ): array {
		$plan_id = trim( $plan_id );
		if ( $plan_id === '' ) {
			return array();
		}
		$all            = $this->get_all();
		$plan_overrides = $all[ $plan_id ] ?? array();
		if ( ! is_array( $plan_overrides ) ) {
			return array();
		}
		$out = array();
		foreach ( $plan_overrides as $id => $record ) {
			if ( is_string( $id ) && $id !== '' && is_array( $record ) ) {
				$out[ $id ] = $record;
			}
		}
		return $out;
	}

	/**
	 * Returns all Build Plan item overrides across all plans (for listing/audit).
	 * Each entry has plan_id, item_id, and override record.
	 *
	 * @return array<int, array{plan_id: string, item_id: string, override: array<string, mixed>}>
	 */
	public function list_all_overrides(): array {
		$all = $this->get_all();
		$out = array();
		foreach ( $all as $plan_id => $items ) {
			if ( ! is_string( $plan_id ) || $plan_id === '' || ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item_id => $record ) {
				if ( is_string( $item_id ) && $item_id !== '' && is_array( $record ) ) {
					$out[] = array(
						'plan_id'  => $plan_id,
						'item_id'  => $item_id,
						'override' => $record,
					);
				}
			}
		}
		return $out;
	}

	/**
	 * Removes the override for a plan item. Bounded single-item removal for audit safety.
	 *
	 * @param string $plan_id Plan ID (internal_key or UUID).
	 * @param string $item_id Plan item item_id.
	 * @return bool True when removed or entry was absent; false when save failed.
	 */
	public function remove_override( string $plan_id, string $item_id ): bool {
		$plan_id = trim( $plan_id );
		$item_id = trim( $item_id );
		if ( $plan_id === '' || $item_id === '' ) {
			return true;
		}
		$all            = $this->get_all();
		$plan_overrides = $all[ $plan_id ] ?? array();
		if ( ! is_array( $plan_overrides ) || ! isset( $plan_overrides[ $item_id ] ) ) {
			return true;
		}
		unset( $plan_overrides[ $item_id ] );
		if ( $plan_overrides === array() ) {
			unset( $all[ $plan_id ] );
		} else {
			$all[ $plan_id ] = $plan_overrides;
		}
		return \update_option( Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES, $all, true ) !== false;
	}

	/**
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	private function get_all(): array {
		$raw = \get_option( Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $plan_key => $items ) {
			if ( ! is_string( $plan_key ) || $plan_key === '' || ! is_array( $items ) ) {
				continue;
			}
			$out[ $plan_key ] = $items;
		}
		return $out;
	}
}
