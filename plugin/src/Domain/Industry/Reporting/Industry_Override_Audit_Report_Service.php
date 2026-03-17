<?php
/**
 * Builds a bounded override audit summary for support/diagnostics (Prompt 437, industry-override-audit-report-contract).
 * Grouped by type and optional industry_context_ref; artifact refs only; admin/support use.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;

/**
 * Generates override audit report for support packets and diagnostics. Read-only; bounded.
 */
final class Industry_Override_Audit_Report_Service {

	/** @var Industry_Override_Read_Model_Builder */
	private $read_model_builder;

	public function __construct( ?Industry_Override_Read_Model_Builder $read_model_builder = null ) {
		$this->read_model_builder = $read_model_builder ?? new Industry_Override_Read_Model_Builder();
	}

	/**
	 * Returns a bounded summary of overrides: by target_type, counts, and artifact refs. Safe for support bundle inclusion.
	 *
	 * @return array{
	 *   generated_at: string,
	 *   total_count: int,
	 *   by_type: array<string, array{count: int, items: list<array{target_key: string, plan_id: string|null, state: string, reason_length: int}>}>,
	 *   by_industry_context: array<string, int>
	 * }
	 */
	public function build_report(): array {
		$rows = $this->read_model_builder->build( array() );

		$by_type = array(
			Industry_Override_Schema::TARGET_TYPE_SECTION         => array( 'count' => 0, 'items' => array() ),
			Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE   => array( 'count' => 0, 'items' => array() ),
			Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM => array( 'count' => 0, 'items' => array() ),
		);
		$by_industry_context = array();

		foreach ( $rows as $row ) {
			$target_type = $row['target_type'] ?? '';
			$target_key  = $row['target_key'] ?? '';
			$plan_id     = $row['plan_id'] ?? null;
			$state       = $row['state'] ?? '';
			$reason      = $row['reason'] ?? '';
			$industry_ref = $row['industry_context_ref'] ?? '';

			if ( ! isset( $by_type[ $target_type ] ) ) {
				$by_type[ $target_type ] = array( 'count' => 0, 'items' => array() );
			}
			$by_type[ $target_type ]['count']++;
			$by_type[ $target_type ]['items'][] = array(
				'target_key'    => $target_key,
				'plan_id'       => $plan_id,
				'state'         => $state,
				'reason_length' => strlen( $reason ),
			);

			$ctx_key = $industry_ref !== '' ? $industry_ref : '_unknown';
			$by_industry_context[ $ctx_key ] = ( $by_industry_context[ $ctx_key ] ?? 0 ) + 1;
		}

		return array(
			'generated_at'         => gmdate( 'c' ),
			'total_count'          => count( $rows ),
			'by_type'              => $by_type,
			'by_industry_context'  => $by_industry_context,
		);
	}
}
