<?php
/**
 * Detects divergence between live industry profile and approved snapshot (industry-change-impact-contract.md).
 * Non-destructive; returns change-impact result for warnings and rollback/reporting. No mutation of content.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Rollback;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Compares live profile to approved snapshot and returns bounded change-impact result.
 */
final class Industry_Profile_Change_Impact_Service {

	private const KEY_PRIMARY   = 'primary_industry_key';
	private const KEY_SECONDARY = 'secondary_industry_keys';
	private const KEY_STYLE     = 'style_preset_ref';

	public const SEVERITY_NONE    = 'none';
	public const SEVERITY_INFO    = 'info';
	public const SEVERITY_WARNING = 'warning';

	/**
	 * Evaluates whether live profile diverges from the approved snapshot.
	 * Safe when snapshot is null or malformed; returns result with snapshot_missing or has_divergence false.
	 *
	 * @param array<string, mixed>      $live_profile   Current industry profile (e.g. from Industry_Profile_Repository::get_profile()).
	 * @param array<string, mixed>|null $approved_snapshot Industry approval snapshot from plan (KEY_INDUSTRY_APPROVAL_SNAPSHOT), or null.
	 * @param array<int, string>        $artifact_refs  Optional plan_id or artifact refs this result applies to.
	 * @param string|null               $live_style_preset_ref Optional current applied style preset key (from get_applied_preset()) for comparison.
	 * @return array{has_divergence: bool, severity: string, explanation_summary: string, affected_artifact_refs: array, snapshot_missing: bool}
	 */
	public function evaluate(
		array $live_profile,
		?array $approved_snapshot,
		array $artifact_refs = array(),
		?string $live_style_preset_ref = null
	): array {
		$empty = array(
			'has_divergence'         => false,
			'severity'               => self::SEVERITY_NONE,
			'explanation_summary'    => '',
			'affected_artifact_refs' => $artifact_refs,
			'snapshot_missing'       => false,
		);

		if ( $approved_snapshot === null || ! is_array( $approved_snapshot ) ) {
			$empty['snapshot_missing']    = true;
			$empty['severity']            = self::SEVERITY_INFO;
			$empty['explanation_summary'] = __( 'Industry context at approval was not recorded.', 'aio-page-builder' );
			return $empty;
		}

		$live_primary   = $this->normalize_primary( $live_profile );
		$live_secondary = $this->normalize_secondary( $live_profile );
		$live_style     = $live_style_preset_ref !== null ? trim( $live_style_preset_ref ) : '';

		$snap_primary   = isset( $approved_snapshot[ self::KEY_PRIMARY ] ) && is_string( $approved_snapshot[ self::KEY_PRIMARY ] )
			? trim( $approved_snapshot[ self::KEY_PRIMARY ] )
			: '';
		$snap_secondary = isset( $approved_snapshot[ self::KEY_SECONDARY ] ) && is_array( $approved_snapshot[ self::KEY_SECONDARY ] )
			? array_values( array_filter( array_map( 'trim', array_map( 'strval', $approved_snapshot[ self::KEY_SECONDARY ] ) ) ) )
			: array();
		$snap_style = '';
		if ( isset( $approved_snapshot[ self::KEY_STYLE ] ) && is_string( $approved_snapshot[ self::KEY_STYLE ] ) ) {
			$snap_style = trim( $approved_snapshot[ self::KEY_STYLE ] );
		}

		$primary_changed   = $live_primary !== $snap_primary;
		$secondary_changed = $this->secondary_sets_differ( $live_secondary, $snap_secondary );
		$style_changed     = $live_style !== $snap_style;

		if ( $primary_changed || $secondary_changed ) {
			$parts = array();
			if ( $primary_changed ) {
				$parts[] = sprintf(
					/* translators: 1: previous primary industry key, 2: current primary industry key */
					__( 'Primary industry changed from %1$s to %2$s.', 'aio-page-builder' ),
					$snap_primary === '' ? __( 'none', 'aio-page-builder' ) : $snap_primary,
					$live_primary === '' ? __( 'none', 'aio-page-builder' ) : $live_primary
				);
			}
			if ( $secondary_changed ) {
				$parts[] = __( 'Secondary industries changed.', 'aio-page-builder' );
			}
			return array(
				'has_divergence'         => true,
				'severity'               => self::SEVERITY_WARNING,
				'explanation_summary'    => implode( ' ', $parts ),
				'affected_artifact_refs' => $artifact_refs,
				'snapshot_missing'       => false,
			);
		}

		if ( $style_changed ) {
			return array(
				'has_divergence'         => true,
				'severity'               => self::SEVERITY_INFO,
				'explanation_summary'    => __( 'Style preset at approval differs from current.', 'aio-page-builder' ),
				'affected_artifact_refs' => $artifact_refs,
				'snapshot_missing'       => false,
			);
		}

		return $empty;
	}

	private function normalize_primary( array $profile ): string {
		$v = $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '';
		return is_string( $v ) ? trim( $v ) : '';
	}

	private function normalize_secondary( array $profile ): array {
		$v = $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ?? array();
		if ( ! is_array( $v ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', array_map( 'strval', $v ) ) ) );
	}

	private function secondary_sets_differ( array $a, array $b ): bool {
		sort( $a );
		sort( $b );
		return $a !== $b;
	}
}
