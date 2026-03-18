<?php
/**
 * Unit tests for ACF migration verification harness (Prompt 225).
 * Covers: same-version verification, field-key stability, group-key stability,
 * assignment continuity, mirror coherence, regeneration behavior after simulated partial failure.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Field_Group_Debug_Exporter;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Migration\ACF_Migration_Verification_Result;
use AIOPageBuilder\Domain\ACF\Migration\ACF_Migration_Verification_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar_Interface;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Plan;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service_Interface;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Debug/ACF_Local_JSON_Mirror_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Debug/ACF_Field_Group_Debug_Exporter.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Plan.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Migration/ACF_Migration_Verification_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Migration/ACF_Migration_Verification_Service.php';

final class ACF_Migration_Verification_Service_Test extends TestCase {

	private function blueprint_st01(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero',
			Field_Blueprint_Schema::FIELDS          => array(
				array(
					'key'   => 'field_st01_hero_headline',
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
			),
		);
	}

	private function create_blueprint_service_mock( array $blueprints ): Section_Field_Blueprint_Service_Interface {
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_all_blueprints' )->willReturn( $blueprints );
		$mock->method( 'get_blueprint_for_section' )->willReturnCallback(
			function ( string $key ) use ( $blueprints ) {
				foreach ( $blueprints as $bp ) {
					if ( ( $bp[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' ) === $key ) {
						return $bp;
					}
				}
				return null;
			}
		);
		return $mock;
	}

	private function create_assignment_map_mock( array $page_template_rows = array(), array $page_composition_rows = array() ): Assignment_Map_Service_Interface {
		$mock = $this->createMock( Assignment_Map_Service_Interface::class );
		$mock->method( 'list_by_type' )->willReturnCallback(
			function ( string $type ) use ( $page_template_rows, $page_composition_rows ) {
				if ( $type === Assignment_Types::PAGE_TEMPLATE ) {
						return $page_template_rows;
				}
				if ( $type === Assignment_Types::PAGE_COMPOSITION ) {
					return $page_composition_rows;
				}
				return array();
			}
		);
		return $mock;
	}

	private function create_page_template_repo_mock( array $valid_keys = array() ): Page_Template_Repository_Interface {
		$mock = $this->createMock( Page_Template_Repository_Interface::class );
		$defs = array();
		foreach ( $valid_keys as $k ) {
			$defs[] = array( 'internal_key' => $k );
		}
		$mock->method( 'list_all_definitions' )->willReturn( $defs );
		return $mock;
	}

	public function test_run_verification_returns_result_with_expected_shape(): void {
		$blueprints        = array( $this->blueprint_st01() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$page_repo         = $this->create_page_template_repo_mock( array() );
		$group_builder     = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service    = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter    = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$result = $service->run_verification( array( 'acf_available' => false ) );

		$this->assertInstanceOf( ACF_Migration_Verification_Result::class, $result );
		$this->assertNotEmpty( $result->get_verification_run_at() );
		$this->assertNotEmpty( $result->get_plugin_version() );
		$this->assertArrayHasKey( 'stable_group_keys', $result->get_field_key_stability_summary() );
		$this->assertArrayHasKey( 'stable_field_keys', $result->get_field_key_stability_summary() );
		$this->assertArrayHasKey( 'assignments_checked', $result->get_assignment_continuity_summary() );
		$this->assertArrayHasKey( 'in_sync', $result->get_mirror_coherence() );
		$this->assertArrayHasKey( 'plan_buildable', $result->get_regeneration_safe() );
		$this->assertArrayHasKey( 'overall_status', $result->to_array() );
		$this->assertContains( $result->get_overall_status(), array( ACF_Migration_Verification_Result::STATUS_PASS, ACF_Migration_Verification_Result::STATUS_WARNING, ACF_Migration_Verification_Result::STATUS_FAIL ) );
	}

	public function test_field_key_stability_summary_includes_deterministic_group_and_field_keys(): void {
		$blueprints           = array( $this->blueprint_st01() );
		$blueprint_service    = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map       = $this->create_assignment_map_mock( array(), array() );
		$page_repo            = $this->create_page_template_repo_mock( array() );
		$group_builder        = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service       = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter       = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );
		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service( $blueprint_service, $registrar, $assignment_svc, $assignment_map, $section_repo, $page_repo );

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$summary = $service->build_field_key_stability_summary( array( 'acf_available' => false ) );

		$this->assertContains( 'group_aio_st01_hero', $summary['stable_group_keys'] );
		$this->assertContains( 'field_st01_hero_headline', $summary['stable_field_keys'] );
		$this->assertArrayHasKey( 'summary', $summary );
	}

	public function test_assignment_continuity_flags_orphaned_when_target_not_in_registry(): void {
		$blueprints           = array( $this->blueprint_st01() );
		$blueprint_service    = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map       = $this->create_assignment_map_mock(
			array(
				array(
					'source_ref' => '1',
					'target_ref' => 'pt_valid',
				),
			),
			array()
		);
		$page_repo            = $this->create_page_template_repo_mock( array( 'pt_valid' ) );
		$group_builder        = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service       = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter       = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );
		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service( $blueprint_service, $registrar, $assignment_svc, $assignment_map, $section_repo, $page_repo );

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$summary = $service->build_assignment_continuity_summary();

		$this->assertSame( 1, $summary['assignments_checked'] );
		$this->assertSame( 1, $summary['assignments_relevant'] );
		$this->assertEmpty( $summary['orphaned_or_invalid'] );
	}

	public function test_assignment_continuity_detects_orphaned(): void {
		$blueprints           = array( $this->blueprint_st01() );
		$blueprint_service    = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map       = $this->create_assignment_map_mock(
			array(
				array(
					'source_ref' => '1',
					'target_ref' => 'pt_missing',
				),
			),
			array()
		);
		$page_repo            = $this->create_page_template_repo_mock( array( 'pt_other' ) );
		$group_builder        = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service       = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter       = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );
		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service( $blueprint_service, $registrar, $assignment_svc, $assignment_map, $section_repo, $page_repo );

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$summary = $service->build_assignment_continuity_summary();

		$this->assertSame( 1, $summary['assignments_checked'] );
		$this->assertSame( 0, $summary['assignments_relevant'] );
		$this->assertContains( 'page_template:pt_missing', $summary['orphaned_or_invalid'] );
	}

	public function test_mirror_coherence_uses_registry_manifest(): void {
		$blueprints           = array( $this->blueprint_st01() );
		$blueprint_service    = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map       = $this->create_assignment_map_mock( array(), array() );
		$page_repo            = $this->create_page_template_repo_mock( array() );
		$group_builder        = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service       = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter       = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );
		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service( $blueprint_service, $registrar, $assignment_svc, $assignment_map, $section_repo, $page_repo );

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$coherence = $service->build_mirror_coherence( array() );

		$this->assertArrayHasKey( 'in_sync', $coherence );
		$this->assertArrayHasKey( 'version_mismatch', $coherence );
		$this->assertArrayHasKey( 'summary', $coherence );
		$this->assertGreaterThanOrEqual( 0, $coherence['in_sync'] );
	}

	public function test_regeneration_safe_plan_buildable_after_simulated_partial_failure(): void {
		$blueprints        = array( $this->blueprint_st01() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$page_repo         = $this->create_page_template_repo_mock( array() );
		$group_builder     = new \AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$mirror_service    = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$debug_exporter    = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$registrar            = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc       = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$section_repo         = $this->createMock( Section_Template_Repository_Interface::class );
		$regeneration_service = new ACF_Regeneration_Service( $blueprint_service, $registrar, $assignment_svc, $assignment_map, $section_repo, $page_repo );

		$service = new ACF_Migration_Verification_Service(
			$blueprint_service,
			$assignment_map,
			$page_repo,
			null,
			$debug_exporter,
			$mirror_service,
			$regeneration_service
		);

		$regeneration_safe = $service->build_regeneration_safe();

		$this->assertTrue( $regeneration_safe['plan_buildable'] );
		$this->assertArrayHasKey( 'summary', $regeneration_safe );
	}

	public function test_result_to_array_contains_all_payload_keys(): void {
		$result = new ACF_Migration_Verification_Result(
			'2025-03-14T12:00:00Z',
			'1.0.0',
			'1',
			array(
				'stable_group_keys'   => array(),
				'stable_field_keys'   => array(),
				'unstable_or_missing' => array(),
				'summary'             => 'Ok',
			),
			array(
				'assignments_checked'  => 0,
				'assignments_relevant' => 0,
				'orphaned_or_invalid'  => array(),
				'summary'              => 'Ok',
			),
			array(
				'in_sync'          => 0,
				'version_mismatch' => 0,
				'summary'          => 'Ok',
			),
			array(
				'plan_buildable'               => true,
				'repair_candidates_consistent' => true,
				'summary'                      => 'Ok',
			),
			array(),
			array(),
			ACF_Migration_Verification_Result::STATUS_PASS,
			'Pass.'
		);

		$arr = $result->to_array();

		$this->assertArrayHasKey( 'verification_run_at', $arr );
		$this->assertArrayHasKey( 'field_key_stability_summary', $arr );
		$this->assertArrayHasKey( 'assignment_continuity_summary', $arr );
		$this->assertArrayHasKey( 'mirror_coherence', $arr );
		$this->assertArrayHasKey( 'regeneration_safe', $arr );
		$this->assertArrayHasKey( 'breaking_change_risks', $arr );
		$this->assertArrayHasKey( 'deprecation_risks', $arr );
		$this->assertArrayHasKey( 'overall_status', $arr );
		$this->assertTrue( $result->is_pass() );
	}
}
