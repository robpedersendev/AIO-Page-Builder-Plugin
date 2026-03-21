<?php
/**
 * Compares a profile snapshot against the current live profile (v2-scope-backlog.md §3).
 *
 * Display-only: produces a flat list of diff rows for admin history UI. Does not mutate data.
 * Changed fields are included; unchanged fields are omitted unless $include_unchanged is true.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Produces diff_rows comparing snapshot values to current profile values.
 * Each diff row: { field, section, snapshot_value, current_value, changed }.
 */
final class Profile_Snapshot_Diff_Service {

	/**
	 * Compares a snapshot against the current profile store. Returns diff rows.
	 *
	 * @param Profile_Snapshot_Data   $snapshot         Snapshot to compare.
	 * @param Profile_Store_Interface $current_store    Live profile store.
	 * @param bool                    $include_unchanged Include rows where values are equal.
	 * @return array<int, array{field: string, section: string, snapshot_value: string, current_value: string, changed: bool}>
	 */
	public function diff( Profile_Snapshot_Data $snapshot, Profile_Store_Interface $current_store, bool $include_unchanged = false ): array {
		$current_brand    = $current_store->get_brand_profile();
		$current_business = $current_store->get_business_profile();

		$rows = array_merge(
			$this->diff_section( 'brand_profile', $snapshot->brand_profile, $current_brand, $include_unchanged ),
			$this->diff_section( 'business_profile', $snapshot->business_profile, $current_business, $include_unchanged )
		);

		return $rows;
	}

	/**
	 * Compares two snapshots. Snapshot A is treated as the earlier state; B as the later.
	 *
	 * @param Profile_Snapshot_Data $snap_a Earlier snapshot.
	 * @param Profile_Snapshot_Data $snap_b Later snapshot.
	 * @param bool                  $include_unchanged
	 * @return array<int, array{field: string, section: string, snapshot_value: string, current_value: string, changed: bool}>
	 */
	public function diff_snapshots( Profile_Snapshot_Data $snap_a, Profile_Snapshot_Data $snap_b, bool $include_unchanged = false ): array {
		$rows = array_merge(
			$this->diff_section( 'brand_profile', $snap_a->brand_profile, $snap_b->brand_profile, $include_unchanged ),
			$this->diff_section( 'business_profile', $snap_a->business_profile, $snap_b->business_profile, $include_unchanged )
		);
		return $rows;
	}

	/**
	 * Returns a summary: total fields checked, changed count, changed field keys.
	 *
	 * @param Profile_Snapshot_Data   $snapshot
	 * @param Profile_Store_Interface $current_store
	 * @return array{total: int, changed: int, changed_fields: array<int, string>}
	 */
	public function summary( Profile_Snapshot_Data $snapshot, Profile_Store_Interface $current_store ): array {
		$all     = $this->diff( $snapshot, $current_store, true );
		$changed = array_filter( $all, fn( $r ) => $r['changed'] );
		return array(
			'total'          => count( $all ),
			'changed'        => count( $changed ),
			'changed_fields' => array_column( $changed, 'field' ),
		);
	}

	// -------------------------------------------------------------------------
	// Internals
	// -------------------------------------------------------------------------

	/**
	 * Produces diff rows for one top-level section (brand or business).
	 *
	 * @param string               $section_key
	 * @param array<string, mixed> $snapshot_section
	 * @param array<string, mixed> $current_section
	 * @param bool                 $include_unchanged
	 * @return array<int, array{field: string, section: string, snapshot_value: string, current_value: string, changed: bool}>
	 */
	private function diff_section(
		string $section_key,
		array $snapshot_section,
		array $current_section,
		bool $include_unchanged
	): array {
		$rows       = array();
		$all_fields = array_unique(
			array_merge( array_keys( $snapshot_section ), array_keys( $current_section ) )
		);
		sort( $all_fields );

		foreach ( $all_fields as $field ) {
			$snap_val    = $snapshot_section[ $field ] ?? null;
			$current_val = $current_section[ $field ] ?? null;
			$changed     = ! $this->values_equal( $snap_val, $current_val );
			if ( ! $changed && ! $include_unchanged ) {
				continue;
			}
			$rows[] = array(
				'field'          => $field,
				'section'        => $section_key,
				'snapshot_value' => $this->display_value( $snap_val ),
				'current_value'  => $this->display_value( $current_val ),
				'changed'        => $changed,
			);
		}
		return $rows;
	}

	/**
	 * Converts a field value to a display string (scalar inline, arrays as JSON).
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function display_value( $value ): string {
		if ( $value === null ) {
			return '—';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_array( $value ) ) {
			$encoded = \wp_json_encode( $value );
			return is_string( $encoded ) ? $encoded : '[]';
		}
		return (string) $value;
	}

	/**
	 * Compares two values for equality; arrays are JSON-encoded for comparison.
	 *
	 * @param mixed $a
	 * @param mixed $b
	 * @return bool
	 */
	private function values_equal( $a, $b ): bool {
		if ( is_array( $a ) && is_array( $b ) ) {
			return \wp_json_encode( $a ) === \wp_json_encode( $b );
		}
		return $a === $b;
	}
}
