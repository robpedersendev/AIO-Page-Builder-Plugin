<?php
/**
 * Unit tests for Future_Industry_Scorecard_Executor (Prompt 472). execute(), report shape, recommendation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Future_Industry_Scorecard_Executor;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Future_Industry_Scorecard_Executor.php';

final class Future_Industry_Scorecard_Executor_Test extends TestCase {

	public function test_execute_with_empty_dossier_returns_expected_structure(): void {
		$executor = new Future_Industry_Scorecard_Executor();
		$result = $executor->execute( array() );
		$this->assertIsArray( $result );
		$this->assertSame( 'Unknown', $result['candidate_label'] );
		$this->assertSame( '', $result['proposed_industry_key'] );
		$this->assertArrayHasKey( 'evaluated_at', $result );
		$this->assertIsArray( $result['dimension_scores'] );
		$this->assertIsInt( $result['aggregate_sum'] );
		$this->assertIsArray( $result['major_risks'] );
		$this->assertContains( $result['recommendation'], array( 'go', 'review', 'no-go' ), true );
		$this->assertIsString( $result['summary_text'] );
		$this->assertSame( 10, count( $result['dimension_scores'] ) );
		$this->assertGreaterThanOrEqual( 10, $result['aggregate_sum'] );
		$this->assertLessThanOrEqual( 50, $result['aggregate_sum'] );
	}

	public function test_execute_with_identity_populates_label_and_key(): void {
		$executor = new Future_Industry_Scorecard_Executor();
		$dossier = array(
			'candidate_identity' => array(
				'proposed_industry_key' => 'test-industry',
				'candidate_label'       => 'Test Industry',
				'evaluator'            => 'Tester',
				'dossier_date'         => '2025-01-01',
			),
		);
		$result = $executor->execute( $dossier );
		$this->assertSame( 'Test Industry', $result['candidate_label'] );
		$this->assertSame( 'test-industry', $result['proposed_industry_key'] );
	}

	public function test_execute_with_prefilled_dimension_scores_uses_them(): void {
		$executor = new Future_Industry_Scorecard_Executor( 40, 25 );
		$scores = array(
			'content_model_fit'         => 4,
			'template_overlap'           => 4,
			'lpagery_posture'           => 4,
			'cta_complexity'            => 4,
			'documentation_burden'      => 4,
			'styling_needs'              => 4,
			'compliance_caution_burden' => 4,
			'starter_bundle_viability'  => 4,
			'subtype_complexity'         => 4,
			'long_term_maintenance_cost' => 4,
		);
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Strong', 'proposed_industry_key' => 'strong' ),
			'dimension_scores'   => $scores,
		);
		$result = $executor->execute( $dossier );
		$this->assertSame( 40, $result['aggregate_sum'] );
		$this->assertSame( $scores, $result['dimension_scores'] );
		$this->assertSame( 'go', $result['recommendation'] );
	}

	public function test_execute_with_low_template_overlap_and_maintenance_yields_no_go(): void {
		$executor = new Future_Industry_Scorecard_Executor( 40, 25 );
		$scores = array(
			'content_model_fit'         => 4,
			'template_overlap'           => 1,
			'lpagery_posture'           => 4,
			'cta_complexity'            => 4,
			'documentation_burden'      => 4,
			'styling_needs'              => 4,
			'compliance_caution_burden' => 4,
			'starter_bundle_viability'  => 4,
			'subtype_complexity'         => 4,
			'long_term_maintenance_cost' => 4,
		);
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Weak overlap', 'proposed_industry_key' => 'weak' ),
			'dimension_scores'   => $scores,
		);
		$result = $executor->execute( $dossier );
		$this->assertSame( 'no-go', $result['recommendation'] );
		$this->assertNotEmpty( $result['major_risks'] );
	}

	public function test_execute_with_template_overlap_section_derives_score(): void {
		$executor = new Future_Industry_Scorecard_Executor();
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Overlap', 'proposed_industry_key' => 'ov' ),
			'template_overlap'    => array(
				'overlap_score' => 0.9,
			),
		);
		$result = $executor->execute( $dossier );
		$this->assertGreaterThanOrEqual( 4, $result['dimension_scores']['template_overlap'] );
	}

	public function test_execute_with_compliance_heavy_derives_lower_compliance_score(): void {
		$executor = new Future_Industry_Scorecard_Executor();
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Heavy compliance', 'proposed_industry_key' => 'hc' ),
			'compliance_caution' => 'Heavy regulatory and legal liability burden.',
		);
		$result = $executor->execute( $dossier );
		$this->assertSame( 2, $result['dimension_scores']['compliance_caution_burden'] );
	}

	public function test_execute_aggregate_below_no_go_threshold_yields_no_go(): void {
		$executor = new Future_Industry_Scorecard_Executor( 40, 25 );
		$scores = array(
			'content_model_fit'         => 2,
			'template_overlap'           => 2,
			'lpagery_posture'           => 2,
			'cta_complexity'            => 2,
			'documentation_burden'      => 2,
			'styling_needs'              => 2,
			'compliance_caution_burden' => 2,
			'starter_bundle_viability'  => 2,
			'subtype_complexity'         => 2,
			'long_term_maintenance_cost' => 2,
		);
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Low', 'proposed_industry_key' => 'low' ),
			'dimension_scores'   => $scores,
		);
		$result = $executor->execute( $dossier );
		$this->assertSame( 20, $result['aggregate_sum'] );
		$this->assertSame( 'no-go', $result['recommendation'] );
	}

	public function test_execute_summary_text_includes_label_and_recommendation(): void {
		$executor = new Future_Industry_Scorecard_Executor();
		$dossier = array(
			'candidate_identity' => array( 'candidate_label' => 'Summary Test', 'proposed_industry_key' => 'st' ),
			'dimension_scores'   => array_fill_keys( array(
				'content_model_fit', 'template_overlap', 'lpagery_posture', 'cta_complexity',
				'documentation_burden', 'styling_needs', 'compliance_caution_burden',
				'starter_bundle_viability', 'subtype_complexity', 'long_term_maintenance_cost',
			), 3 ),
		);
		$result = $executor->execute( $dossier );
		$this->assertStringContainsString( 'Summary Test', $result['summary_text'] );
		$this->assertStringContainsString( $result['recommendation'], $result['summary_text'] );
	}
}
