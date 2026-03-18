<?php
/**
 * Unit tests for ACF regeneration/repair (Prompt 222).
 * Covers: dry-run plan, mismatch discovery, full/selective scope, page-assignment candidates,
 * refusal of unsafe cleanup, structured result and plan payloads.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service_Interface;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar_Interface;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Plan;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Result;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Service;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
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
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Plan.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Repair/ACF_Regeneration_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/ACF/Debug/ACF_Local_JSON_Mirror_Service.php';

final class ACF_Regeneration_Service_Test extends TestCase {

	private function blueprint_st01(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
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

	public function test_build_plan_dry_run_returns_plan_with_refused_cleanup(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array( $this->blueprint_st01() ) );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc    = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$section_repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$page_repo         = $this->createMock( Page_Template_Repository_Interface::class );

		$service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$plan = $service->build_plan( true, ACF_Regeneration_Plan::SCOPE_FULL, array() );

		$this->assertTrue( $plan->is_dry_run() );
		$this->assertSame( ACF_Regeneration_Plan::SCOPE_FULL, $plan->get_scope() );
		$refused = $plan->get_refused_cleanup();
		$this->assertNotEmpty( $refused, 'Plan must include refusal of unsafe cleanup (spec §20.15)' );
		$this->assertStringContainsString( 'Destructive cleanup not supported', $refused[0] );
	}

	public function test_build_plan_full_scope_detects_mismatches_when_no_acf_groups(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array( $this->blueprint_st01() ) );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc    = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$section_repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$page_repo         = $this->createMock( Page_Template_Repository_Interface::class );

		$service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$plan = $service->build_plan( false, ACF_Regeneration_Plan::SCOPE_FULL, array() );

		$mismatches = $plan->get_field_group_mismatches();
		$this->assertCount( 1, $mismatches );
		$this->assertSame( 'st01_hero', $mismatches[0]['section_key'] );
		$this->assertSame( 'group_aio_st01_hero', $mismatches[0]['group_key'] );
		$this->assertSame( ACF_Regeneration_Plan::MISMATCH_STATUS_MISSING, $mismatches[0]['status'] );
	}

	public function test_execute_repair_dry_run_returns_zero_mutations_and_warning(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array( $this->blueprint_st01() ) );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc    = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$section_repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$page_repo         = $this->createMock( Page_Template_Repository_Interface::class );

		$service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$plan   = $service->build_plan( true, ACF_Regeneration_Plan::SCOPE_FULL, array() );
		$result = $service->execute_repair( $plan );

		$this->assertSame( 0, $result->get_groups_regenerated() );
		$this->assertSame( 0, $result->get_page_assignments_repaired() );
		$this->assertNotEmpty( $result->get_warnings() );
		$this->assertStringContainsString( 'Dry run', $result->get_warnings()[0] );
	}

	public function test_plan_to_array_has_required_keys(): void {
		$plan = new ACF_Regeneration_Plan(
			true,
			ACF_Regeneration_Plan::SCOPE_FULL,
			null,
			null,
			true,
			array(
				array(
					'section_key' => 'st01_hero',
					'group_key'   => 'group_aio_st01_hero',
					'status'      => 'missing',
				),
			),
			array(
				array(
					'page_id' => 42,
					'type'    => 'page_template',
					'key'     => 'pt_landing',
				),
			),
			array( 'Destructive cleanup not supported.' )
		);
		$arr  = $plan->to_array();
		$this->assertArrayHasKey( 'dry_run', $arr );
		$this->assertArrayHasKey( 'scope', $arr );
		$this->assertArrayHasKey( 'field_group_mismatches', $arr );
		$this->assertArrayHasKey( 'page_assignment_repair_candidates', $arr );
		$this->assertArrayHasKey( 'refused_cleanup', $arr );
		$this->assertArrayHasKey( 'missing_count', $arr );
		$this->assertArrayHasKey( 'version_stale_count', $arr );
		$this->assertArrayHasKey( 'candidate_count', $arr );
		$this->assertSame( 1, $arr['missing_count'] );
		$this->assertSame( 1, $arr['candidate_count'] );
	}

	public function test_result_to_array_has_required_keys(): void {
		$result = new ACF_Regeneration_Result(
			2,
			array(),
			1,
			0,
			array(),
			array(),
			array(
				'missing'       => 2,
				'version_stale' => 0,
				'repaired'      => 2,
			),
			array(
				'repaired' => 1,
				'failed'   => 0,
				'skipped'  => 0,
			)
		);
		$arr    = $result->to_array();
		$this->assertArrayHasKey( 'groups_regenerated', $arr );
		$this->assertArrayHasKey( 'groups_skipped', $arr );
		$this->assertArrayHasKey( 'page_assignments_repaired', $arr );
		$this->assertArrayHasKey( 'page_assignments_failed', $arr );
		$this->assertArrayHasKey( 'warnings', $arr );
		$this->assertArrayHasKey( 'errors', $arr );
		$this->assertArrayHasKey( 'field_group_mismatch_summary', $arr );
		$this->assertArrayHasKey( 'page_assignment_repair_summary', $arr );
		$this->assertSame( 2, $arr['groups_regenerated'] );
		$this->assertSame( 1, $arr['page_assignments_repaired'] );
		$this->assertSame(
			array(
				'missing'       => 2,
				'version_stale' => 0,
				'repaired'      => 2,
			),
			$arr['field_group_mismatch_summary']
		);
	}

	public function test_build_plan_includes_page_assignment_candidates_when_include_page_assignments(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array( $this->blueprint_st01() ) );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc    = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map    = $this->create_assignment_map_mock(
			array(
				array(
					'source_ref' => '100',
					'target_ref' => 'pt_landing',
				),
			),
			array()
		);
		$section_repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$page_repo         = $this->createMock( Page_Template_Repository_Interface::class );

		$service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$plan       = $service->build_plan( false, ACF_Regeneration_Plan::SCOPE_FULL, array( 'include_page_assignments' => true ) );
		$candidates = $plan->get_page_assignment_repair_candidates();
		$this->assertCount( 1, $candidates );
		$this->assertSame( 100, $candidates[0]['page_id'] );
		$this->assertSame( 'page_template', $candidates[0]['type'] );
		$this->assertSame( 'pt_landing', $candidates[0]['key'] );
	}

	public function test_build_plan_section_family_scope_filters_by_variation_family(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array() );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$assignment_svc    = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map    = $this->create_assignment_map_mock( array(), array() );
		$section_repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$section_repo->method( 'list_all_definitions' )->willReturn(
			array(
				array(
					'internal_key'         => 'st01_hero',
					'variation_family_key' => 'hero',
				),
				array(
					'internal_key'         => 'st02_hero_alt',
					'variation_family_key' => 'hero',
				),
				array(
					'internal_key'         => 'st_faq',
					'variation_family_key' => 'faq',
				),
			)
		);
		$page_repo = $this->createMock( Page_Template_Repository_Interface::class );

		$blueprint_service->method( 'get_blueprint_for_section' )->willReturn( $this->blueprint_st01() );

		$service = new ACF_Regeneration_Service(
			$blueprint_service,
			$registrar,
			$assignment_svc,
			$assignment_map,
			$section_repo,
			$page_repo
		);

		$plan       = $service->build_plan( false, ACF_Regeneration_Plan::SCOPE_SECTION_FAMILY, array( 'section_family_key' => 'hero' ) );
		$mismatches = $plan->get_field_group_mismatches();
		$this->assertCount( 2, $mismatches );
		$section_keys = array_column( $mismatches, 'section_key' );
		$this->assertContains( 'st01_hero', $section_keys );
		$this->assertContains( 'st02_hero_alt', $section_keys );
		$this->assertNotContains( 'st_faq', $section_keys );
	}

	/**
	 * When mirror service and path are provided and execute_repair regenerates groups, mirror is refreshed (Prompt 224).
	 */
	public function test_execute_repair_refreshes_mirror_when_provided_and_repair_did_work(): void {
		$blueprint_service = $this->create_blueprint_service_mock( array( $this->blueprint_st01() ) );
		$registrar         = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->method( 'register_blueprint' )->willReturn( true );
		$assignment_svc = $this->createMock( Page_Field_Group_Assignment_Service_Interface::class );
		$assignment_map = $this->create_assignment_map_mock( array(), array() );
		$section_repo   = $this->createMock( Section_Template_Repository_Interface::class );
		$page_repo      = $this->createMock( Page_Template_Repository_Interface::class );

		$group_builder       = new ACF_Group_Builder( new ACF_Field_Builder() );
		$mirror              = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$mirror_refresh_path = sys_get_temp_dir() . '/aio-regen-mirror-' . uniqid( '', true );
		$this->assertTrue( mkdir( $mirror_refresh_path, 0755, true ), 'Temp mirror dir must be created' );

		try {
			$service = new ACF_Regeneration_Service(
				$blueprint_service,
				$registrar,
				$assignment_svc,
				$assignment_map,
				$section_repo,
				$page_repo,
				$mirror,
				$mirror_refresh_path
			);

			$plan   = $service->build_plan( false, ACF_Regeneration_Plan::SCOPE_FULL, array() );
			$result = $service->execute_repair( $plan );

			$this->assertSame( 1, $result->get_groups_regenerated() );
			$this->assertFileExists( $mirror_refresh_path . '/group_aio_st01_hero.json', 'Mirror must be written after repair' );
		} finally {
			if ( is_dir( $mirror_refresh_path ) ) {
				$files = glob( $mirror_refresh_path . '/*' );
				foreach ( $files !== false ? $files : array() as $f ) {
					@unlink( $f );
				}
				@rmdir( $mirror_refresh_path );
			}
		}
	}
}
