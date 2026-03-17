<?php
/**
 * Unit tests for Future_Industry_Comparison_Matrix_Service (Prompt 473). build_matrix(), structure, suggested_order.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Future_Industry_Comparison_Matrix_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Future_Industry_Comparison_Matrix_Service.php';

final class Future_Industry_Comparison_Matrix_Service_Test extends TestCase {

	private function make_scorecard_result( string $label, string $key, int $aggregate, string $recommendation, array $dimension_scores ): array {
		return array(
			'candidate_label'       => $label,
			'proposed_industry_key' => $key,
			'evaluated_at'         => gmdate( 'c' ),
			'dimension_scores'     => $dimension_scores,
			'aggregate_sum'        => $aggregate,
			'major_risks'          => array(),
			'recommendation'       => $recommendation,
			'summary_text'         => '',
		);
	}

	private function default_dimension_scores( int $override = 3 ): array {
		$dims = array(
			'content_model_fit', 'template_overlap', 'lpagery_posture', 'cta_complexity',
			'documentation_burden', 'styling_needs', 'compliance_caution_burden',
			'starter_bundle_viability', 'subtype_complexity', 'long_term_maintenance_cost',
		);
		return array_fill_keys( $dims, $override );
	}

	public function test_build_matrix_with_empty_array_returns_expected_structure(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$matrix = $service->build_matrix( array() );
		$this->assertIsArray( $matrix );
		$this->assertSame( array(), $matrix['candidates'] );
		$this->assertIsArray( $matrix['dimension_comparison'] );
		$this->assertSame( array(), $matrix['per_candidate_summary'] );
		$this->assertSame( array(), $matrix['reuse_vs_new_build'] );
		$this->assertSame( array(), $matrix['subtype_caution_highlight'] );
		$this->assertSame( array(), $matrix['suggested_order'] );
	}

	public function test_build_matrix_with_one_scorecard_returns_that_candidate(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$scores = $this->default_dimension_scores( 4 );
		$result = $this->make_scorecard_result( 'Industry A', 'industry-a', 40, 'go', $scores );
		$matrix = $service->build_matrix( array( $result ) );
		$this->assertCount( 1, $matrix['candidates'] );
		$this->assertSame( 'Industry A', $matrix['candidates'][0]['label'] );
		$this->assertSame( 'industry-a', $matrix['candidates'][0]['proposed_industry_key'] );
		$this->assertArrayHasKey( 'Industry A', $matrix['per_candidate_summary'] );
		$this->assertSame( 40, $matrix['per_candidate_summary']['Industry A']['aggregate_sum'] );
		$this->assertSame( 'go', $matrix['per_candidate_summary']['Industry A']['recommendation'] );
		$this->assertSame( array( 'Industry A' ), $matrix['suggested_order'] );
		$this->assertArrayHasKey( 'Industry A', $matrix['reuse_vs_new_build'] );
		$this->assertArrayHasKey( 'Industry A', $matrix['subtype_caution_highlight'] );
	}

	public function test_build_matrix_with_two_candidates_orders_go_before_review(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$go_scores   = $this->default_dimension_scores( 4 );
		$review_scores = $this->default_dimension_scores( 3 );
		$results = array(
			$this->make_scorecard_result( 'Review First', 'review', 30, 'review', $review_scores ),
			$this->make_scorecard_result( 'Go First', 'go', 40, 'go', $go_scores ),
		);
		$matrix = $service->build_matrix( $results );
		$this->assertCount( 2, $matrix['candidates'] );
		$this->assertSame( 'Go First', $matrix['suggested_order'][0] );
		$this->assertSame( 'Review First', $matrix['suggested_order'][1] );
	}

	public function test_build_matrix_reuse_vs_new_build_high_for_high_scores(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$scores = $this->default_dimension_scores( 3 );
		$scores['template_overlap'] = 5;
		$scores['content_model_fit'] = 5;
		$result = $this->make_scorecard_result( 'High Reuse', 'hr', 42, 'go', $scores );
		$matrix = $service->build_matrix( array( $result ) );
		$this->assertSame( 'High reuse', $matrix['reuse_vs_new_build']['High Reuse'] );
	}

	public function test_build_matrix_subtype_caution_high_burden_when_both_low(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$scores = $this->default_dimension_scores( 4 );
		$scores['subtype_complexity'] = 1;
		$scores['compliance_caution_burden'] = 1;
		$result = $this->make_scorecard_result( 'Heavy Burden', 'hb', 28, 'review', $scores );
		$matrix = $service->build_matrix( array( $result ) );
		$this->assertSame( 'High burden', $matrix['subtype_caution_highlight']['Heavy Burden']['burden_note'] );
	}

	public function test_build_matrix_dimension_comparison_has_all_dimensions(): void {
		$service = new Future_Industry_Comparison_Matrix_Service();
		$scores = $this->default_dimension_scores( 3 );
		$result = $this->make_scorecard_result( 'One', 'one', 30, 'review', $scores );
		$matrix = $service->build_matrix( array( $result ) );
		$this->assertArrayHasKey( 'template_overlap', $matrix['dimension_comparison'] );
		$this->assertArrayHasKey( 'One', $matrix['dimension_comparison']['template_overlap'] );
		$this->assertSame( 3, $matrix['dimension_comparison']['template_overlap']['One'] );
	}
}
