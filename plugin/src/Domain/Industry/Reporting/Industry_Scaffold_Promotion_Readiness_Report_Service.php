<?php
/**
 * Internal promotion-readiness report generator for scaffolded assets (Prompt 565).
 * Scores scaffolded packs/subtypes by readiness tier, blockers, and missing evidence. Advisory only.
 * See industry-scaffold-promotion-readiness-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Produces a bounded promotion-readiness report from scaffold completeness data.
 */
final class Industry_Scaffold_Promotion_Readiness_Report_Service {

	/** Readiness tier: all artifact classes at least scaffolded; no missing. */
	public const TIER_SCAFFOLD_COMPLETE = 'scaffold_complete';

	/** Readiness tier: content authored; suitable for promotion check (lint/health pass not verified here). */
	public const TIER_AUTHORED_NEAR_READY = 'authored_near_ready';

	/** Readiness tier: missing or not yet ready for promotion check. */
	public const TIER_NOT_NEAR_READY = 'not_near_ready';

	/** @var Industry_Scaffold_Completeness_Report_Provider_Interface|null */
	private $scaffold_completeness_service;

	public function __construct( ?Industry_Scaffold_Completeness_Report_Provider_Interface $scaffold_completeness_service = null ) {
		$this->scaffold_completeness_service = $scaffold_completeness_service;
	}

	/**
	 * Generates the promotion-readiness report. Groups by tier.
	 *
	 * @return array{
	 *   summary: array{total: int, scaffold_complete: int, authored_near_ready: int, not_near_ready: int},
	 *   items: list<array{scaffold_ref: string, scaffold_type: string, readiness_score: int, readiness_tier: string, blockers: list<string>, missing_evidence: list<string>, notes: string}>,
	 *   by_tier: array<string, list<array>>,
	 *   generated_at: string
	 * }
	 */
	public function generate_report(): array {
		$items = array();

		if ( $this->scaffold_completeness_service instanceof Industry_Scaffold_Completeness_Report_Provider_Interface ) {
			$report = $this->scaffold_completeness_service->generate_report( array() );
			$results = isset( $report['scaffold_results'] ) && is_array( $report['scaffold_results'] ) ? $report['scaffold_results'] : array();
			foreach ( $results as $r ) {
				$scaffold_type = isset( $r['scaffold_type'] ) && is_string( $r['scaffold_type'] ) ? $r['scaffold_type'] : '';
				$scaffold_key = isset( $r['scaffold_key'] ) && is_string( $r['scaffold_key'] ) ? $r['scaffold_key'] : '';
				$scaffold_ref = $scaffold_type === 'subtype' ? 'subtype:' . $scaffold_key : $scaffold_key;
				$classes = isset( $r['artifact_classes'] ) && is_array( $r['artifact_classes'] ) ? $r['artifact_classes'] : array();
				$blockers = array();
				$missing_evidence = array();
				$authored_count = 0;
				$missing_count = 0;
				foreach ( $classes as $artifact => $state ) {
					if ( $state === Industry_Scaffold_Completeness_Report_Service::STATE_MISSING ) {
						$blockers[] = $artifact;
						$missing_evidence[] = $artifact;
						++$missing_count;
					} elseif ( $state === Industry_Scaffold_Completeness_Report_Service::STATE_AUTHORED ) {
						++$authored_count;
					}
				}
				$total_classes = count( $classes );
				$readiness_score = $total_classes > 0 ? (int) round( ( $authored_count / $total_classes ) * 100 ) : 0;
				if ( $missing_count > 0 ) {
					$tier = self::TIER_NOT_NEAR_READY;
				} elseif ( $authored_count === $total_classes && $total_classes > 0 ) {
					$tier = self::TIER_AUTHORED_NEAR_READY;
				} else {
					$tier = self::TIER_SCAFFOLD_COMPLETE;
				}
				$notes = isset( $r['summary'] ) && is_string( $r['summary'] ) ? $r['summary'] : '';
				$items[] = array(
					'scaffold_ref'      => $scaffold_ref,
					'scaffold_type'     => $scaffold_type,
					'readiness_score'   => $readiness_score,
					'readiness_tier'   => $tier,
					'blockers'          => $blockers,
					'missing_evidence'  => $missing_evidence,
					'notes'             => $notes,
				);
			}
		}

		$by_tier = $this->group_by( $items, 'readiness_tier' );
		$by_tier = array(
			self::TIER_SCAFFOLD_COMPLETE   => $by_tier[ self::TIER_SCAFFOLD_COMPLETE ] ?? array(),
			self::TIER_AUTHORED_NEAR_READY => $by_tier[ self::TIER_AUTHORED_NEAR_READY ] ?? array(),
			self::TIER_NOT_NEAR_READY      => $by_tier[ self::TIER_NOT_NEAR_READY ] ?? array(),
		);
		$summary = array(
			'total'               => count( $items ),
			'scaffold_complete'    => count( $by_tier[ self::TIER_SCAFFOLD_COMPLETE ] ),
			'authored_near_ready'  => count( $by_tier[ self::TIER_AUTHORED_NEAR_READY ] ),
			'not_near_ready'       => count( $by_tier[ self::TIER_NOT_NEAR_READY ] ),
		);

		return array(
			'summary'      => $summary,
			'items'        => $items,
			'by_tier'      => $by_tier,
			'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * @param list<array<string, mixed>> $items
	 * @param string $key
	 * @return array<string, list<array<string, mixed>>>
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
