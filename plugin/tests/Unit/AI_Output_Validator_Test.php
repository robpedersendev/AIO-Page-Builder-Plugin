<?php
/**
 * Unit tests for AI output validation pipeline (spec §28.11–28.14, ai-output-validation-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\AI\Validation\Dropped_Record_Report;
use AIOPageBuilder\Domain\AI\Validation\Normalized_Output_Builder;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Dropped_Record_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Validation_Report.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Normalized_Output_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Validation/AI_Output_Validator.php';

final class AI_Output_Validator_Test extends TestCase {

	private function validator(): AI_Output_Validator {
		return new AI_Output_Validator( new Normalized_Output_Builder() );
	}

	/** @return array<string, mixed> */
	private function minimal_valid_payload(): array {
		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => '1',
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY    => array(
				'summary_text'       => 'Draft plan.',
				'planning_mode'      => 'mixed',
				'overall_confidence' => 'medium',
			),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array(),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array( 'recommended_top_level_pages' => array(), 'hierarchy_map' => array(), 'navigation_summary' => '' ),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE    => array(),
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN      => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS   => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => array(),
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array(),
		);
	}

	public function test_fully_valid_output_produces_passed_state_and_normalized_output(): void {
		$payload = $this->minimal_valid_payload();
		$report  = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_PASSED, $report->get_final_validation_state() );
		$this->assertTrue( $report->is_top_level_valid() );
		$this->assertSame( Validation_Report::PARSE_OK, $report->get_parse_status() );
		$this->assertSame( Validation_Report::RAW_CAPTURE_OK, $report->get_raw_capture_status() );
		$normalized = $report->get_normalized_output();
		$this->assertNotNull( $normalized );
		$this->assertArrayHasKey( Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION, $normalized );
		$this->assertArrayHasKey( Build_Plan_Draft_Schema::KEY_RUN_SUMMARY, $normalized );
		$this->assertSame( array(), $report->get_dropped_records() );
		$this->assertTrue( $report->allows_build_plan_handoff() );
	}

	public function test_parse_failure_produces_failed_state_no_normalized_output(): void {
		$report = $this->validator()->validate( 'not json {', Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_FAILED, $report->get_final_validation_state() );
		$this->assertSame( Validation_Report::PARSE_FAILED, $report->get_parse_status() );
		$this->assertNull( $report->get_normalized_output() );
		$this->assertSame( 'parse', $report->get_blocking_failure_stage() );
		$this->assertFalse( $report->allows_build_plan_handoff() );
	}

	public function test_empty_string_produces_raw_capture_empty_and_failed(): void {
		$report = $this->validator()->validate( '', Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::RAW_CAPTURE_EMPTY, $report->get_raw_capture_status() );
		$this->assertSame( Validation_Report::STATE_FAILED, $report->get_final_validation_state() );
		$this->assertNull( $report->get_normalized_output() );
	}

	public function test_top_level_schema_failure_produces_failed_state(): void {
		$payload = $this->minimal_valid_payload();
		unset( $payload[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] );
		$report = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_FAILED, $report->get_final_validation_state() );
		$this->assertFalse( $report->is_top_level_valid() );
		$this->assertNull( $report->get_normalized_output() );
		$this->assertSame( 'top_level', $report->get_blocking_failure_stage() );
	}

	public function test_invalid_run_summary_enum_produces_failed_state(): void {
		$payload = $this->minimal_valid_payload();
		$payload[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ]['planning_mode'] = 'invalid_mode';
		$report = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_FAILED, $report->get_final_validation_state() );
		$this->assertNull( $report->get_normalized_output() );
	}

	public function test_item_level_partial_failure_produces_partial_state_and_dropped_records(): void {
		$payload = $this->minimal_valid_payload();
		$valid_item = array(
			'current_page_url'   => '/',
			'current_page_title' => 'Home',
			'action'             => 'keep',
			'reason'             => 'Keep as is.',
			'risk_level'         => 'low',
			'confidence'         => 'high',
		);
		$invalid_item = array(
			'current_page_url'   => '/about',
			'current_page_title' => 'About',
			'action'             => 'invalid_action',
			'reason'             => 'Change.',
			'risk_level'         => 'low',
			'confidence'         => 'high',
		);
		$payload[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] = array( $valid_item, $invalid_item );
		$report = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_PARTIAL, $report->get_final_validation_state() );
		$dropped = $report->get_dropped_records();
		$this->assertCount( 1, $dropped );
		$this->assertInstanceOf( Dropped_Record_Report::class, $dropped[0] );
		$this->assertSame( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $dropped[0]->get_section() );
		$this->assertSame( 1, $dropped[0]->get_index() );
		$this->assertStringContainsString( 'invalid_enum', $dropped[0]->get_reason() );
		$normalized = $report->get_normalized_output();
		$this->assertNotNull( $normalized );
		$this->assertCount( 1, $normalized[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] );
		$this->assertTrue( $report->allows_build_plan_handoff() );
	}

	public function test_invalid_enum_in_existing_page_changes_drops_record(): void {
		$payload = $this->minimal_valid_payload();
		$payload[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] = array(
			array(
				'current_page_url'   => '/',
				'current_page_title' => 'Home',
				'action'             => 'keep',
				'reason'             => 'Keep.',
				'risk_level'         => 'low',
				'confidence'         => 'high',
			),
			array(
				'current_page_url'   => '/x',
				'current_page_title' => 'X',
				'action'             => 'keep',
				'reason'             => 'Keep.',
				'risk_level'         => 'invalid_risk',
				'confidence'         => 'high',
			),
		);
		$report = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_PARTIAL, $report->get_final_validation_state() );
		$this->assertCount( 1, $report->get_dropped_records() );
		$this->assertNotNull( $report->get_normalized_output() );
	}

	public function test_internal_reference_validation_failure_drops_record(): void {
		$payload = $this->minimal_valid_payload();
		$payload[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] = array(
			array(
				'current_page_url'   => '/about',
				'current_page_title' => 'About',
				'action'             => 'replace_with_new_page',
				'reason'             => 'Replace.',
				'risk_level'         => 'medium',
				'confidence'         => 'high',
				'target_page_title'  => '',
				'target_slug'         => '',
				'target_template_key' => '',
			),
		);
		$report = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertSame( Validation_Report::STATE_PARTIAL, $report->get_final_validation_state() );
		$dropped = $report->get_dropped_records();
		$this->assertCount( 1, $dropped );
		$errors = $dropped[0]->get_errors();
		$this->assertNotEmpty( array_filter( $errors, function ( $e ) {
			return strpos( $e, 'internal_ref' ) !== false;
		} ) );
	}

	public function test_blocking_failure_prevents_build_plan_handoff(): void {
		$report = $this->validator()->validate( 'invalid json', Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertFalse( $report->allows_build_plan_handoff() );
		$this->assertNull( $report->get_normalized_output() );

		$payload = $this->minimal_valid_payload();
		unset( $payload[ Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION ] );
		$report2 = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$this->assertFalse( $report2->allows_build_plan_handoff() );
		$this->assertNull( $report2->get_normalized_output() );
	}

	public function test_unsupported_schema_ref_produces_failed_state(): void {
		$payload = $this->minimal_valid_payload();
		$report  = $this->validator()->validate( $payload, 'unknown/schema-v99', false );
		$this->assertSame( Validation_Report::STATE_FAILED, $report->get_final_validation_state() );
		$this->assertNull( $report->get_normalized_output() );
	}

	public function test_repair_attempt_flag_set_when_invoked_after_repair(): void {
		$payload = $this->minimal_valid_payload();
		$report  = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, true );
		$this->assertTrue( $report->is_repair_attempted() );
		$this->assertTrue( $report->is_repair_succeeded() );
	}

	public function test_repair_attempted_but_failed_when_validation_fails(): void {
		$report = $this->validator()->validate( 'not json', Build_Plan_Draft_Schema::SCHEMA_REF, true );
		$this->assertTrue( $report->is_repair_attempted() );
		$this->assertFalse( $report->is_repair_succeeded() );
	}

	public function test_validation_report_to_array_has_expected_shape(): void {
		$payload = $this->minimal_valid_payload();
		$report  = $this->validator()->validate( $payload, Build_Plan_Draft_Schema::SCHEMA_REF, false );
		$arr     = $report->to_array();
		$this->assertArrayHasKey( 'raw_capture_status', $arr );
		$this->assertArrayHasKey( 'parse_status', $arr );
		$this->assertArrayHasKey( 'top_level_valid', $arr );
		$this->assertArrayHasKey( 'schema_ref', $arr );
		$this->assertArrayHasKey( 'record_validation_results', $arr );
		$this->assertArrayHasKey( 'dropped_records', $arr );
		$this->assertArrayHasKey( 'normalized_output', $arr );
		$this->assertArrayHasKey( 'final_validation_state', $arr );
		$this->assertArrayHasKey( 'blocking_failure_stage', $arr );
		$this->assertArrayHasKey( 'repair_attempted', $arr );
		$this->assertArrayHasKey( 'repair_succeeded', $arr );
		$this->assertSame( Validation_Report::STATE_PASSED, $arr['final_validation_state'] );
	}

	public function test_dropped_record_report_to_array(): void {
		$d = new Dropped_Record_Report( 'existing_page_changes', 1, 'invalid_enum', array( 'action' ) );
		$arr = $d->to_array();
		$this->assertSame( 'existing_page_changes', $arr['section'] );
		$this->assertSame( 1, $arr['index'] );
		$this->assertSame( 'invalid_enum', $arr['reason'] );
		$this->assertSame( array( 'action' ), $arr['errors'] );
	}
}
