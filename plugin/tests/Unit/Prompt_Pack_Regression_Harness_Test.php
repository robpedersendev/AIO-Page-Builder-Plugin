<?php
/**
 * Unit tests for prompt-pack regression harness (spec §26, §28.11–28.13, §56.2, Prompt 120).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\PromptPacks\Regression\Prompt_Pack_Regression_Harness;
use AIOPageBuilder\Domain\AI\PromptPacks\Regression\Regression_Result;
use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Normalized_Output_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Dropped_Record_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Validation_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/AI_Output_Validator.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Regression/Regression_Result.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Regression/Prompt_Pack_Regression_Harness.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Service.php';

final class Prompt_Pack_Regression_Harness_Test extends TestCase {

	private function harness( string $fixtures_base = '' ): Prompt_Pack_Regression_Harness {
		$validator = new AI_Output_Validator();
		$base      = $fixtures_base !== '' ? $fixtures_base : dirname( __DIR__, 2 ) . '/tests/fixtures';
		return new Prompt_Pack_Regression_Harness( $validator, $base );
	}

	private function golden_fixture_passed(): array {
		return array(
			'prompt_pack_ref' => array(
				'internal_key' => 'aio/build-plan-draft',
				'version'      => '1.0.0',
			),
			'schema_ref'      => 'aio/build-plan-draft-v1',
			'input'           => array(
				'schema_version'               => 'aio/build-plan-draft-v1',
				'run_summary'                  => array(
					'summary_text'       => 'Minimal regression fixture.',
					'planning_mode'      => 'new_site',
					'overall_confidence' => 'high',
				),
				'site_purpose'                 => array(),
				'site_structure'               => array(),
				'existing_page_changes'        => array(),
				'new_pages_to_create'          => array(),
				'menu_change_plan'             => array(),
				'design_token_recommendations' => array(),
				'seo_recommendations'          => array(),
				'warnings'                     => array(),
				'assumptions'                  => array(),
				'confidence'                   => array(),
			),
			'expected'        => array(
				'final_validation_state' => 'passed',
				'normalized_output'      => array(
					'schema_version'               => 'aio/build-plan-draft-v1',
					'run_summary'                  => array(
						'summary_text'       => 'Minimal regression fixture.',
						'planning_mode'      => 'new_site',
						'overall_confidence' => 'high',
					),
					'site_purpose'                 => array(),
					'site_structure'               => array(),
					'existing_page_changes'        => array(),
					'new_pages_to_create'          => array(),
					'menu_change_plan'             => array(),
					'design_token_recommendations' => array(),
					'seo_recommendations'          => array(),
					'warnings'                     => array(),
					'assumptions'                  => array(),
					'confidence'                   => array(),
				),
				'dropped_records'        => array(),
			),
		);
	}

	public function test_exact_match_pass(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture_passed() );
		$this->assertInstanceOf( Regression_Result::class, $result );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertSame( Regression_Result::OUTCOME_PASS, $result->get_outcome() );
		$run = $result->get_regression_run();
		$this->assertArrayHasKey( 'run_id', $run );
		$this->assertSame( 'aio/build-plan-draft', $run['prompt_pack_ref']['internal_key'] ?? '' );
		$this->assertArrayHasKey( 'validator_regression_summary', $result->to_array() );
		$this->assertTrue( $result->get_validator_regression_summary()['final_validation_state_match'] );
	}

	public function test_validation_failed_fixture_matches(): void {
		$fixture             = $this->golden_fixture_passed();
		$fixture['input']    = '';
		$fixture['expected'] = array(
			'final_validation_state' => 'failed',
			'blocking_failure_stage' => 'raw_capture',
		);
		$harness             = $this->harness();
		$result              = $harness->run_from_array( $fixture );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertTrue( $result->get_validator_regression_summary()['final_validation_state_match'] );
	}

	public function test_fixture_version_mismatch_handling(): void {
		$harness = $this->harness();
		$invalid = array(
			'prompt_pack_ref' => array(
				'internal_key' => 'aio/build-plan-draft',
				'version'      => '1.0.0',
			),
			'schema_ref'      => '',
			'input'           => array(),
			'expected'        => array( 'final_validation_state' => 'passed' ),
		);
		$result  = $harness->run_from_array( $invalid );
		$this->assertSame( Regression_Result::OUTCOME_FAIL, $result->get_outcome() );
		$this->assertStringContainsString( 'schema_ref', $result->get_message() );
	}

	public function test_normalized_output_regression(): void {
		$fixture = $this->golden_fixture_passed();
		$fixture['expected']['normalized_output']['run_summary']['summary_text'] = 'Different expected text';
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Regression_Result::OUTCOME_REGRESSION, $result->get_outcome() );
		$diff = $result->get_normalized_output_diff_summary();
		$this->assertNotNull( $diff );
		$this->assertFalse( $diff['match'] );
		$this->assertNotEmpty( $diff['value_diffs'] );
	}

	public function test_run_from_file_golden(): void {
		$base    = dirname( __DIR__, 2 ) . '/tests/fixtures';
		$harness = $this->harness( $base );
		$result  = $harness->run( 'prompt-packs/aio-build-plan-draft-1.0.0-golden.json' );
		$this->assertInstanceOf( Regression_Result::class, $result );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
	}

	public function test_run_from_file_validation_failed(): void {
		$base    = dirname( __DIR__, 2 ) . '/tests/fixtures';
		$harness = $this->harness( $base );
		$result  = $harness->run( 'prompt-packs/aio-build-plan-draft-1.0.0-validation-failed.json' );
		$this->assertTrue( $result->is_pass(), $result->get_message() );
		$this->assertSame( Regression_Result::OUTCOME_PASS, $result->get_outcome() );
	}

	public function test_registry_suggested_fixture_basename(): void {
		$base = Prompt_Pack_Registry_Service::get_suggested_fixture_basename( 'aio/build-plan-draft', '1.0.0' );
		$this->assertSame( 'aio-build-plan-draft-1.0.0', $base );
	}

	public function test_partial_output_dropped_record_comparison(): void {
		$fixture                                       = $this->golden_fixture_passed();
		$fixture['input']['existing_page_changes']     = array(
			array(
				'current_page_url'   => '/valid/',
				'current_page_title' => 'Valid',
				'action'             => 'keep',
				'reason'             => 'OK',
				'risk_level'         => 'low',
				'confidence'         => 'high',
			),
			array(
				'current_page_url'   => '/bad/',
				'current_page_title' => 'Bad',
				'action'             => 'invalid_action_value',
				'reason'             => 'x',
				'risk_level'         => 'low',
				'confidence'         => 'high',
			),
		);
		$fixture['expected']['final_validation_state'] = 'partial';
		$fixture['expected']['normalized_output']['existing_page_changes'] = array(
			array(
				'current_page_url'   => '/valid/',
				'current_page_title' => 'Valid',
				'action'             => 'keep',
				'reason'             => 'OK',
				'risk_level'         => 'low',
				'confidence'         => 'high',
			),
		);
		$fixture['expected']['dropped_records']                            = array(
			array(
				'section' => 'existing_page_changes',
				'index'   => 1,
				'reason'  => 'invalid_enum',
				'errors'  => array(),
			),
		);
		$harness = $this->harness();
		$result  = $harness->run_from_array( $fixture );
		$this->assertSame( Regression_Result::OUTCOME_PASS, $result->get_outcome(), $result->get_message() );
		$this->assertTrue( $result->get_validator_regression_summary()['dropped_count_match'] );
	}

	public function test_example_regression_result_payload(): void {
		$harness = $this->harness();
		$result  = $harness->run_from_array( $this->golden_fixture_passed() );
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'outcome', $payload );
		$this->assertArrayHasKey( 'regression_run', $payload );
		$this->assertArrayHasKey( 'normalized_output_diff_summary', $payload );
		$this->assertArrayHasKey( 'validator_regression_summary', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'run_id', $payload['regression_run'] );
		$this->assertArrayHasKey( 'ran_at', $payload['regression_run'] );
	}
}
