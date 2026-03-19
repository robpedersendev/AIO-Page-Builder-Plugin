<?php
/**
 * Unit tests for Composition Registry: create, validation, duplication, section mappings, snapshot ref, status (Prompt 029).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Duplicator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Registry_Result;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Registry_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Codes;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Result;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Statuses.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Codes.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Duplicator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';

final class Composition_Registry_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Section_Registry_Service $section_registry;
	private Page_Template_Repository $page_repo;
	private Page_Template_Registry_Service $page_template_registry;
	private Composition_Repository $composition_repo;
	private Assignment_Map_Service $assignment_map;
	private Composition_Validator $validator;
	private Composition_Registry_Service $registry;
	private Composition_Duplicator $duplicator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']        = array();
		$GLOBALS['_aio_assign_insert_id'] = 0;

		$this->section_repo     = new Section_Template_Repository();
		$section_normalizer     = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_validator      = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_normalizer, $this->section_repo );
		$this->section_registry = new Section_Registry_Service( $section_validator, $this->section_repo );

		$this->page_repo              = new Page_Template_Repository();
		$page_normalizer              = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer();
		$page_validator               = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator( $page_normalizer, $this->page_repo, $this->section_registry );
		$this->page_template_registry = new Page_Template_Registry_Service( $page_validator, $this->page_repo );

		$this->composition_repo = new Composition_Repository();
		$this->assignment_map   = new Assignment_Map_Service( $this->create_wpdb_stub() );
		$this->validator        = new Composition_Validator( $this->section_registry, $this->page_template_registry );
		$this->registry         = new Composition_Registry_Service( $this->validator, $this->composition_repo, $this->assignment_map );
		$this->duplicator       = new Composition_Duplicator( $this->registry );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_wp_update_post_return'],
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_assign_results'],
			$GLOBALS['_aio_assign_insert_id']
		);
		parent::tearDown();
	}

	private function create_wpdb_stub(): object {
		$prefix = 'wp_';
		$table  = $prefix . Table_Names::ASSIGNMENT_MAPS;
		return new class( $table ) {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			private string $table;

			public function __construct( string $table ) {
				$this->table = $table;
			}

			public function prepare( string $query, ...$args ): string {
				return $query;
			}

			public function query( string $sql ): int|false {
				if ( strpos( $sql, 'INSERT' ) !== false ) {
					$GLOBALS['_aio_assign_insert_id'] = ( $GLOBALS['_aio_assign_insert_id'] ?? 0 ) + 1;
					$this->insert_id                  = $GLOBALS['_aio_assign_insert_id'];
					return 1;
				}
				return 1;
			}

			public function get_results( string $query, $output = OBJECT ): array {
				return $GLOBALS['_aio_assign_results'] ?? array();
			}

			public function delete( string $table, array $where, $where_format = null ): int|false {
				return 1;
			}
		};
	}

	private function valid_minimal_section( string $key = 'st01_hero', string $name = 'Hero' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Section purpose.',
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st01',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'acf_st01',
			Section_Schema::FIELD_HELPER_REF               => 'helper_st01',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_st01',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY            => array(),
			Section_Schema::FIELD_VERSION                  => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
		);
	}

	private function valid_minimal_composition( string $comp_id = 'comp_test01' ): array {
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => $comp_id,
			Composition_Schema::FIELD_NAME                 => 'Test Composition',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st01_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st05_cta',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => 'snap_v1',
		);
	}

	public function test_valid_composition_creation(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7000;

		$input  = $this->valid_minimal_composition();
		$result = $this->registry->create( $input );

		$this->assertInstanceOf( Composition_Registry_Result::class, $result );
		$this->assertTrue( $result->success );
		$this->assertSame( 7000, $result->post_id );
		$this->assertNotNull( $result->definition );
		$this->assertSame( 'comp_test01', $result->definition[ Composition_Schema::FIELD_COMPOSITION_ID ] );
		$this->assertSame( 'Test Composition', $result->definition[ Composition_Schema::FIELD_NAME ] );
		$this->assertArrayHasKey( Composition_Schema::FIELD_VALIDATION_STATUS, $result->definition );
		$this->assertArrayHasKey( Composition_Schema::FIELD_ORDERED_SECTION_LIST, $result->definition );
		$this->assertCount( 2, $result->definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] );
	}

	public function test_validation_failure_capture(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7001;

		$input = $this->valid_minimal_composition( 'comp_invalid' );
		$input[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ][] = array(
			Composition_Schema::SECTION_ITEM_KEY      => 'st99_nonexistent',
			Composition_Schema::SECTION_ITEM_POSITION => 2,
		);

		$result = $this->registry->create( $input );

		$this->assertTrue( $result->success );
		$this->assertContains( Composition_Validation_Codes::SECTION_MISSING, $result->definition[ Composition_Schema::FIELD_VALIDATION_CODES ] ?? array() );
		$this->assertSame( Composition_Validation_Result::VALIDATION_FAILED, $result->definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] );
	}

	public function test_duplication_new_id_and_provenance(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7002;

		$create_result = $this->registry->create( $this->valid_minimal_composition( 'comp_original' ) );
		$this->assertTrue( $create_result->success );

		$comp_post                             = new \WP_Post(
			array(
				'ID'          => 7002,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Test Composition',
				'post_status' => 'publish',
				'post_name'   => 'comp_original',
			)
		);
		$section_posts                         = $GLOBALS['_aio_wp_query_posts'] ?? array();
		$GLOBALS['_aio_get_post_return']       = $comp_post;
		$GLOBALS['_aio_wp_query_posts']        = array_merge( $section_posts, array( $comp_post ) );
		$GLOBALS['_aio_post_meta']['7002']     = array(
			'_aio_internal_key'           => 'comp_original',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $create_result->definition ),
		);
		$GLOBALS['_aio_wp_insert_post_return'] = 7003;

		$dup_result = $this->duplicator->duplicate( 7002, 'Cloned Copy' );

		$this->assertTrue( $dup_result->success );
		$this->assertNotSame( 'comp_original', $dup_result->definition[ Composition_Schema::FIELD_COMPOSITION_ID ] );
		$this->assertSame( 'comp_original', $dup_result->definition[ Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID ] );
		$this->assertSame( 'Cloned Copy', $dup_result->definition[ Composition_Schema::FIELD_NAME ] );
		$this->assertSame( 'draft', $dup_result->definition[ Composition_Schema::FIELD_STATUS ] );
		$this->assertTrue(
			Composition_Validation_Result::is_valid( $dup_result->definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? '' ),
			'Duplicate is revalidated; validation_status must be a valid result constant'
		);
	}

	public function test_normalized_composition_to_section_mapping_persistence(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7004;

		$input = $this->valid_minimal_composition( 'comp_mapping' );
		$input[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY      => 'st01_hero',
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY      => 'st05_cta',
				Composition_Schema::SECTION_ITEM_POSITION => 1,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'compact',
			),
		);
		$result = $this->registry->create( $input );
		$this->assertTrue( $result->success );

		$GLOBALS['_aio_assign_results'] = array(
			array(
				'target_ref' => 'st01_hero',
				'payload'    => wp_json_encode(
					array(
						'position' => 0,
						'variant'  => 'default',
					)
				),
			),
			array(
				'target_ref' => 'st05_cta',
				'payload'    => wp_json_encode(
					array(
						'position' => 1,
						'variant'  => 'compact',
					)
				),
			),
		);

		$mappings = $this->registry->get_section_mappings( 'comp_mapping' );

		$this->assertCount( 2, $mappings );
		$this->assertSame( 'st01_hero', $mappings[0]['section_key'] );
		$this->assertSame( 0, $mappings[0]['position'] );
		$this->assertSame( 'default', $mappings[0]['variant'] );
		$this->assertSame( 'st05_cta', $mappings[1]['section_key'] );
		$this->assertSame( 1, $mappings[1]['position'] );
		$this->assertSame( 'compact', $mappings[1]['variant'] );
	}

	public function test_attach_registry_snapshot_ref(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7010;

		$create_result = $this->registry->create( $this->valid_minimal_composition( 'comp_attach_snap' ) );
		$this->assertTrue( $create_result->success );

		$comp_post                         = new \WP_Post(
			array(
				'ID'          => 7010,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => 'comp_attach_snap',
			)
		);
		$GLOBALS['_aio_get_post_return']   = $comp_post;
		$GLOBALS['_aio_post_meta']['7010'] = array(
			'_aio_internal_key'           => 'comp_attach_snap',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $create_result->definition ),
		);

		$attach_result = $this->registry->attach_registry_snapshot_ref( $create_result->post_id, 'snap_manual_ref' );

		$this->assertTrue( $attach_result->success );
		$this->assertSame( 'snap_manual_ref', $attach_result->definition[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' );
	}

	public function test_snapshot_reference_field_persistence(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7005;

		$input = $this->valid_minimal_composition( 'comp_snapshot' );
		$input[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = 'snap_registry_20250110';

		$result = $this->registry->create( $input );
		$this->assertTrue( $result->success );

		$GLOBALS['_aio_wp_query_posts']    = array(
			new \WP_Post(
				array(
					'ID'          => 7005,
					'post_type'   => Object_Type_Keys::COMPOSITION,
					'post_title'  => 'Test',
					'post_status' => 'publish',
					'post_name'   => 'comp_snapshot',
				)
			),
		);
		$GLOBALS['_aio_post_meta']['7005'] = array(
			'_aio_internal_key'           => 'comp_snapshot',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $result->definition ),
		);

		$read = $this->registry->get_by_id_string( 'comp_snapshot' );
		$this->assertNotNull( $read );
		$this->assertSame( 'snap_registry_20250110', $read[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' );
	}

	public function test_status_transition_enforcement_draft_to_active_blocked_when_invalid(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7006;

		$input = $this->valid_minimal_composition( 'comp_blocked' );
		$input[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ][] = array(
			Composition_Schema::SECTION_ITEM_KEY      => 'st99_nonexistent',
			Composition_Schema::SECTION_ITEM_POSITION => 2,
		);
		$create_result = $this->registry->create( $input );
		$this->assertTrue( $create_result->success );
		$post_id = $create_result->post_id;

		$comp_post                                      = new \WP_Post(
			array(
				'ID'          => $post_id,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => 'comp_blocked',
			)
		);
		$GLOBALS['_aio_get_post_return']                = $comp_post;
		$GLOBALS['_aio_wp_query_posts']                 = array( $comp_post );
		$GLOBALS['_aio_post_meta'][ (string) $post_id ] = array(
			'_aio_internal_key'           => 'comp_blocked',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $create_result->definition ),
		);

		$status_result = $this->registry->set_status( $post_id, 'active' );

		$this->assertFalse( $status_result->success );
		$this->assertStringContainsString( 'Cannot activate', implode( ' ', $status_result->errors ) );
	}

	public function test_status_transition_draft_to_active_allowed_when_valid(): void {
		$this->seed_sections_st01_and_st05();
		$GLOBALS['_aio_wp_insert_post_return'] = 7007;

		$input = $this->valid_minimal_composition( 'comp_activatable' );
		$input[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = 'snap_ok';
		$create_result = $this->registry->create( $input );
		$this->assertTrue( $create_result->success );

		$def = $create_result->definition;
		$def[ Composition_Schema::FIELD_VALIDATION_STATUS ] = Composition_Validation_Result::VALID;
		$post_id = $create_result->post_id;

		$comp_post                                      = new \WP_Post(
			array(
				'ID'          => $post_id,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => 'comp_activatable',
			)
		);
		$GLOBALS['_aio_get_post_return']                = $comp_post;
		$GLOBALS['_aio_wp_query_posts']                 = array( $comp_post );
		$GLOBALS['_aio_post_meta'][ (string) $post_id ] = array(
			'_aio_internal_key'           => 'comp_activatable',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_update_post_return']          = $post_id;

		$status_result = $this->registry->set_status( $post_id, 'active' );

		$this->assertTrue( $status_result->success );
		$this->assertSame( 'active', $status_result->definition[ Composition_Schema::FIELD_STATUS ] );
	}

	public function test_empty_section_list_validation_failure(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 7008;

		$input  = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_empty',
			Composition_Schema::FIELD_NAME                 => 'Empty',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(),
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => 'snap',
		);
		$result = $this->registry->create( $input );

		$this->assertTrue( $result->success );
		$this->assertContains( Composition_Validation_Codes::EMPTY_SECTION_LIST, $result->definition[ Composition_Schema::FIELD_VALIDATION_CODES ] ?? array() );
		$this->assertSame( Composition_Validation_Result::VALIDATION_FAILED, $result->definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] );
	}

	private function seed_sections_st01_and_st05(): void {
		$GLOBALS['_aio_wp_query_posts']    = array(
			new \WP_Post(
				array(
					'ID'          => 9001,
					'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
					'post_title'  => 'Hero',
					'post_status' => 'publish',
					'post_name'   => 'st01_hero',
				)
			),
			new \WP_Post(
				array(
					'ID'          => 9002,
					'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
					'post_title'  => 'CTA',
					'post_status' => 'publish',
					'post_name'   => 'st05_cta',
				)
			),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array(
			'_aio_internal_key'       => 'st01_hero',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $this->valid_minimal_section( 'st01_hero', 'Hero' ) ),
		);
		$GLOBALS['_aio_post_meta']['9002'] = array(
			'_aio_internal_key'       => 'st05_cta',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $this->valid_minimal_section( 'st05_cta', 'CTA' ) ),
		);
	}
}
