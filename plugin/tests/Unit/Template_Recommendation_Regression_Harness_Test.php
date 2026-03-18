<?php
/**
 * Golden regression tests for template-recommendation harness (spec §58.3, §60.5, Prompt 211).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Regression\Template_Recommendation_Regression_Harness;
use AIOPageBuilder\Domain\AI\Regression\Template_Recommendation_Regression_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Regression/Template_Recommendation_Regression_Result.php';
require_once $plugin_root . '/src/Domain/AI/Regression/Template_Recommendation_Regression_Harness.php';

final class Template_Recommendation_Regression_Harness_Test extends TestCase {

	private function harness( string $fixtures_base = '' ): Template_Recommendation_Regression_Harness {
		$base = $fixtures_base !== '' ? $fixtures_base : dirname( __DIR__, 2 ) . '/tests/fixtures';
		return new Template_Recommendation_Regression_Harness( $base );
	}

	private function golden_fixture(): array {
		return array(
			'case_id'           => 'golden-top-level',
			'scenario'          => 'top_level',
			'fixture_version'   => '1',
			'recommendation'    => array(
				'template_key'              => 'pt_home_marketing_01',
				'template_category_class'   => 'top_level',
				'template_family'           => 'home',
				'template_selection_reason' => 'Home page for main landing.',
			),
			'expected'          => array(
				'template_category_class'   => 'top_level',
				'allowed_template_families' => array( 'home', 'about', 'contact' ),
				'cta_law_aligned'           => true,
				'require_explanation'       => true,
			),
			'template_metadata' => array(
				'min_cta'          => 3,
				'last_section_cta' => true,
			),
		);
	}

	public function test_class_fit_pass(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture() );
		$this->assertInstanceOf( Template_Recommendation_Regression_Result::class, $result );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertTrue( $result->get_class_fit() );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_PASS, $result->get_outcome() );
	}

	public function test_class_fit_fail(): void {
		$fixture = $this->golden_fixture();
		$fixture['recommendation']['template_category_class'] = 'hub';
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_REGRESSION, $result->get_outcome() );
		$this->assertFalse( $result->get_class_fit() );
		$this->assertArrayHasKey( 'class_mismatch', $result->get_details() );
	}

	public function test_family_fit_pass(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture() );
		$this->assertTrue( $result->get_family_fit() );
	}

	public function test_family_fit_fail(): void {
		$fixture                                      = $this->golden_fixture();
		$fixture['recommendation']['template_family'] = 'unknown_family';
		$harness                                      = $this->harness();
		$result                                       = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_REGRESSION, $result->get_outcome() );
		$this->assertFalse( $result->get_family_fit() );
		$this->assertArrayHasKey( 'family_mismatch', $result->get_details() );
	}

	public function test_cta_law_aligned_with_metadata_compliant(): void {
		$fixture                      = $this->golden_fixture();
		$fixture['template_metadata'] = array(
			'min_cta'          => 4,
			'last_section_cta' => true,
		);
		$harness                      = $this->harness();
		$result                       = $harness->run_from_array( $fixture );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertTrue( $result->get_cta_law_aligned() );
	}

	public function test_cta_law_aligned_with_metadata_non_compliant(): void {
		$fixture                                = $this->golden_fixture();
		$fixture['expected']['cta_law_aligned'] = true;
		$fixture['template_metadata']           = array(
			'min_cta'          => 2,
			'last_section_cta' => true,
		);
		$harness                                = $this->harness();
		$result                                 = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_REGRESSION, $result->get_outcome() );
		$this->assertFalse( $result->get_cta_law_aligned() );
		$this->assertArrayHasKey( 'cta_law', $result->get_details() );
	}

	public function test_cta_law_not_checked_when_no_metadata(): void {
		$fixture = $this->golden_fixture();
		unset( $fixture['template_metadata'] );
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertNull( $result->get_cta_law_aligned() );
	}

	public function test_explanation_required_and_present(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture() );
		$this->assertTrue( $result->get_explanation_fit() );
	}

	public function test_explanation_required_and_missing(): void {
		$fixture = $this->golden_fixture();
		$fixture['recommendation']['template_selection_reason'] = '';
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_REGRESSION, $result->get_outcome() );
		$this->assertFalse( $result->get_explanation_fit() );
		$this->assertArrayHasKey( 'explanation_missing', $result->get_details() );
	}

	public function test_recommendation_normalized_from_proposed_template_summary(): void {
		$fixture                   = $this->golden_fixture();
		$fixture['recommendation'] = array(
			'template_selection_reason' => 'Reason at top level.',
			'proposed_template_summary' => array(
				'template_key'            => 'pt_home_marketing_01',
				'template_category_class' => 'top_level',
				'template_family'         => 'home',
			),
		);
		$harness                   = $this->harness();
		$result                    = $harness->run_from_array( $fixture );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertTrue( $result->get_class_fit() );
		$this->assertTrue( $result->get_family_fit() );
	}

	public function test_fixture_missing_expected(): void {
		$fixture = $this->golden_fixture();
		unset( $fixture['expected'] );
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_FAIL, $result->get_outcome() );
		$this->assertStringContainsString( 'expected', $result->get_message() );
		$this->assertArrayHasKey( 'fixture_invalid', $result->get_details() );
	}

	public function test_fixture_missing_recommendation(): void {
		$fixture = $this->golden_fixture();
		unset( $fixture['recommendation'] );
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_FAIL, $result->get_outcome() );
		$this->assertArrayHasKey( 'fixture_invalid', $result->get_details() );
	}

	public function test_run_with_non_array_fixture_returns_fail(): void {
		$harness = $this->harness();
		$result  = $harness->run( 123 );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_FAIL, $result->get_outcome() );
		$this->assertStringContainsString( 'array', strtolower( $result->get_message() ) );
	}

	public function test_run_from_file_golden(): void {
		$base    = dirname( __DIR__, 2 ) . '/tests/fixtures';
		$harness = $this->harness( $base );
		$result  = $harness->run( 'template-recommendations/golden-top-level.json' );
		$this->assertInstanceOf( Template_Recommendation_Regression_Result::class, $result );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertSame( 'golden-top-level', $result->get_regression_run()['case_id'] );
	}

	public function test_run_from_file_invalid_path_returns_fail(): void {
		$base    = dirname( __DIR__, 2 ) . '/tests/fixtures';
		$harness = $this->harness( $base );
		$result  = $harness->run( 'template-recommendations/nonexistent.json' );
		$this->assertSame( Template_Recommendation_Regression_Result::OUTCOME_FAIL, $result->get_outcome() );
		$this->assertStringContainsString( 'array', strtolower( $result->get_message() ) );
	}

	public function test_example_regression_result_payload(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture() );
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'outcome', $payload );
		$this->assertArrayHasKey( 'regression_run', $payload );
		$this->assertArrayHasKey( 'class_fit', $payload );
		$this->assertArrayHasKey( 'family_fit', $payload );
		$this->assertArrayHasKey( 'cta_law_aligned', $payload );
		$this->assertArrayHasKey( 'explanation_fit', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'details', $payload );
		$this->assertArrayHasKey( 'case_id', $payload['regression_run'] );
		$this->assertArrayHasKey( 'scenario', $payload['regression_run'] );
		$this->assertArrayHasKey( 'fixture_version', $payload['regression_run'] );
		$this->assertArrayHasKey( 'ran_at', $payload['regression_run'] );
	}
}
