<?php
/**
 * Internal drift report generator (Prompt 562). Flags likely drift across contracts, schemas,
 * docs, and seeded asset conventions. Advisory only; no auto-fix. See industry-subsystem-drift-detection-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;

/**
 * Generates a bounded drift report for contract/schema/convention consistency.
 */
final class Industry_Drift_Report_Service {

	/** Drift type: schema or data shape diverges from documented schema. */
	public const DRIFT_TYPE_SCHEMA = 'schema_drift';

	/** Drift type: seeded assets diverge from each other (convention). */
	public const DRIFT_TYPE_CONVENTION = 'convention_drift';

	/** Severity: blocking or high-risk; immediate review. */
	public const SEVERITY_SEVERE = 'severe';

	/** Severity: low-impact; next maintenance window. */
	public const SEVERITY_MINOR = 'minor';

	/** Required pack fields per schema (minimal set for drift check). */
	private const PACK_REQUIRED_FIELDS = array(
		Industry_Pack_Schema::FIELD_INDUSTRY_KEY,
		Industry_Pack_Schema::FIELD_NAME,
		Industry_Pack_Schema::FIELD_STATUS,
	);

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	public function __construct( ?Industry_Pack_Registry $pack_registry = null ) {
		$this->pack_registry = $pack_registry;
	}

	/**
	 * Generates the drift report. Groups by severity and drift type.
	 *
	 * @return array{
	 *   summary: array{total: int, severe: int, minor: int, by_type: array<string, int>},
	 *   items: array<int, array{drift_type: string, severity: string, evidence_refs: array<int, string>, explanation: string, suggested_review_path: string}>,
	 *   by_severity: array<string, array<int, array>>,
	 *   by_type: array<string, array<int, array>>,
	 *   generated_at: string
	 * }
	 */
	public function generate_report(): array {
		$items = array();

		if ( $this->pack_registry instanceof Industry_Pack_Registry ) {
			$packs     = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
			$packs     = array_merge( $packs, $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_DRAFT ) );
			$packs     = array_merge( $packs, $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_DEPRECATED ) );
			$seen_keys = array();
			foreach ( $packs as $pack ) {
				$industry_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
				foreach ( self::PACK_REQUIRED_FIELDS as $field ) {
					$val   = isset( $pack[ $field ] ) ? $pack[ $field ] : null;
					$empty = $val === null || ( is_string( $val ) && trim( (string) $val ) === '' );
					if ( $empty ) {
						$items[] = array(
							'drift_type'            => self::DRIFT_TYPE_SCHEMA,
							'severity'              => self::SEVERITY_SEVERE,
							'evidence_refs'         => array( $industry_key !== '' ? $industry_key : 'unknown', $field ),
							'explanation'           => sprintf( 'Pack definition missing required schema field: %s', $field ),
							'suggested_review_path' => 'Update pack definition to include required field per industry-pack-schema; re-run health check.',
						);
					}
				}
				if ( $industry_key !== '' ) {
					$seen_keys[ $industry_key ] = $pack;
				}
			}
			$this->check_convention_drift( $seen_keys, $items );
		}

		$by_severity = $this->group_by( $items, 'severity' );
		$by_type     = $this->group_by( $items, 'drift_type' );
		$summary     = array(
			'total'   => count( $items ),
			'severe'  => count( $by_severity[ self::SEVERITY_SEVERE ] ?? array() ),
			'minor'   => count( $by_severity[ self::SEVERITY_MINOR ] ?? array() ),
			'by_type' => array_map( 'count', $by_type ),
		);

		return array(
			'summary'      => $summary,
			'items'        => $items,
			'by_severity'  => $by_severity,
			'by_type'      => $by_type,
			'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * Checks convention consistency across packs (e.g. version_marker presence).
	 *
	 * @param array<string, array<string, mixed>> $packs Keyed by industry_key.
	 * @param array<int, array<string, mixed>>          $items Append findings here.
	 */
	private function check_convention_drift( array $packs, array &$items ): void {
		if ( count( $packs ) < 2 ) {
			return;
		}
		$with_version    = 0;
		$without_version = 0;
		foreach ( $packs as $pack ) {
			$v = isset( $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) ? $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] : null;
			if ( $v !== null && $v !== '' ) {
				++$with_version;
			} else {
				++$without_version;
			}
		}
		if ( $with_version > 0 && $without_version > 0 ) {
			$items[] = array(
				'drift_type'            => self::DRIFT_TYPE_CONVENTION,
				'severity'              => self::SEVERITY_MINOR,
				'evidence_refs'         => array_keys( $packs ),
				'explanation'           => 'Mixed use of version_marker across pack definitions; convention recommends consistent use.',
				'suggested_review_path' => 'Add version_marker to all pack definitions or document exception in authoring guide.',
			);
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @param string                     $key
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function group_by( array $items, string $key ): array {
		$out = array();
		foreach ( $items as $item ) {
			$v = isset( $item[ $key ] ) && is_string( $item[ $key ] ) ? $item[ $key ] : '';
			if ( $v === '' ) {
				continue;
			}
			if ( ! isset( $out[ $v ] ) ) {
				$out[ $v ] = array();
			}
			$out[ $v ][] = $item;
		}
		return $out;
	}
}
