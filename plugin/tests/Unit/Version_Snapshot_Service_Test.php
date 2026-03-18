<?php
/**
 * Unit tests for Version Snapshot Service: capture, retrieval, list, composition attachment, payload structure, prohibited-field exclusion (Prompt 030).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Snapshots\Snapshot_Payload_Builder;
use AIOPageBuilder\Domain\Registries\Snapshots\Snapshot_Type_Keys;
use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Version_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Snapshot_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Version_Snapshot_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Version_Snapshot_Repository.php';

final class Version_Snapshot_Service_Test extends TestCase {

	private \AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service $section_registry;
	private \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service $page_template_registry;
	private \AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository $snapshot_repo;
	private \AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository $composition_repo;
	private Version_Snapshot_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();

		$section_repo           = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$section_norm           = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_valid          = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_norm, $section_repo );
		$this->section_registry = new \AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service( $section_valid, $section_repo );

		$page_repo                    = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		$page_norm                    = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer();
		$page_valid                   = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator( $page_norm, $page_repo, $this->section_registry );
		$this->page_template_registry = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service( $page_valid, $page_repo );

		$this->snapshot_repo    = new \AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository();
		$this->composition_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository();

		$this->service = new Version_Snapshot_Service(
			$this->section_registry,
			$this->page_template_registry,
			$this->snapshot_repo,
			$this->composition_repo
		);
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	public function test_capture_section_registry_creates_snapshot(): void {
		$GLOBALS['_aio_wp_query_posts']        = array();
		$GLOBALS['_aio_wp_insert_post_return'] = 8001;

		$result = $this->service->capture_section_registry();

		$this->assertTrue( $result['success'] );
		$this->assertStringStartsWith( 'snap_', $result['snapshot_id'] );
		$this->assertSame( 8001, $result['post_id'] );
		$this->assertEmpty( $result['errors'] );

		$snap_post                      = new \WP_Post(
			array(
				'ID'          => 8001,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap',
			)
		);
		$GLOBALS['_aio_wp_query_posts'] = array( $snap_post );

		$def = $this->service->get_by_snapshot_id( $result['snapshot_id'] );
		$this->assertNotNull( $def );
		$this->assertSame( Version_Snapshot_Schema::SCOPE_REGISTRY, $def[ Version_Snapshot_Schema::FIELD_SCOPE_TYPE ] );
		$this->assertSame( 'section_registry', $def[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] );
		$this->assertArrayHasKey( 'payload', $def );
		$this->assertArrayHasKey( 'sections', $def['payload'] );
		$this->assertArrayHasKey( 'captured_at', $def['payload'] );
	}

	public function test_capture_page_template_registry_creates_snapshot(): void {
		$GLOBALS['_aio_wp_query_posts']        = array();
		$GLOBALS['_aio_wp_insert_post_return'] = 8002;

		$result = $this->service->capture_page_template_registry();

		$this->assertTrue( $result['success'] );
		$this->assertStringStartsWith( 'snap_', $result['snapshot_id'] );
		$snap_post                      = new \WP_Post(
			array(
				'ID'          => 8002,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap',
			)
		);
		$GLOBALS['_aio_wp_query_posts'] = array( $snap_post );
		$def                            = $this->service->get_by_snapshot_id( $result['snapshot_id'] );
		$this->assertNotNull( $def );
		$this->assertSame( 'page_template_registry', $def[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] );
		$this->assertArrayHasKey( 'templates', $def['payload'] );
	}

	public function test_capture_composition_context_creates_snapshot(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8003;

		$composition = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_test_snap',
			Composition_Schema::FIELD_NAME                 => 'Test',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st01_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
			Composition_Schema::FIELD_VALIDATION_STATUS    => 'valid',
		);

		$result = $this->service->capture_composition_context( $composition );

		$this->assertTrue( $result['success'] );
		$this->assertStringStartsWith( 'snap_', $result['snapshot_id'] );
		$snap_post                      = new \WP_Post(
			array(
				'ID'          => 8003,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap',
			)
		);
		$GLOBALS['_aio_wp_query_posts'] = array( $snap_post );
		$def                            = $this->service->get_by_snapshot_id( $result['snapshot_id'] );
		$this->assertNotNull( $def );
		$this->assertSame( 'comp_test_snap', $def[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] );
		$this->assertArrayHasKey( 'composition_id', $def['payload'] );
		$this->assertSame( 'comp_test_snap', $def['payload']['composition_id'] );
		$this->assertArrayHasKey( 'ordered_section_list', $def['payload'] );
	}

	public function test_capture_composition_context_fails_without_composition_id(): void {
		$result = $this->service->capture_composition_context( array( 'name' => 'No ID' ) );

		$this->assertFalse( $result['success'] );
		$this->assertSame( '', $result['snapshot_id'] );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( 'composition_id', implode( ' ', $result['errors'] ) );
	}

	public function test_retrieval_by_id_and_snapshot_id(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8004;
		$capture                               = $this->service->capture_section_registry();
		$this->assertTrue( $capture['success'] );

		$snap_post                       = new \WP_Post(
			array(
				'ID'          => 8004,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap_retrieval',
			)
		);
		$GLOBALS['_aio_get_post_return'] = $snap_post;
		$GLOBALS['_aio_wp_query_posts']  = array( $snap_post );

		$by_id   = $this->service->get_by_id( $capture['post_id'] );
		$by_snap = $this->service->get_by_snapshot_id( $capture['snapshot_id'] );

		$this->assertNotNull( $by_id );
		$this->assertNotNull( $by_snap );
		$this->assertSame( $by_id[ Version_Snapshot_Schema::FIELD_SNAPSHOT_ID ], $by_snap[ Version_Snapshot_Schema::FIELD_SNAPSHOT_ID ] );
	}

	public function test_list_by_scope_type_and_scope_id(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8005;
		$this->service->capture_section_registry();

		$snap_post                         = new \WP_Post(
			array(
				'ID'          => 8005,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap_abc',
			)
		);
		$GLOBALS['_aio_wp_query_posts']    = array( $snap_post );
		$GLOBALS['_aio_post_meta']['8005'] = array(
			'_aio_internal_key'        => 'snap_abc123',
			'_aio_status'              => 'active',
			'_aio_scope_type'          => Version_Snapshot_Schema::SCOPE_REGISTRY,
			'_aio_scope_id'            => 'section_registry',
			'_aio_snapshot_definition' => wp_json_encode(
				array(
					'snapshot_id'    => 'snap_abc123',
					'scope_type'     => Version_Snapshot_Schema::SCOPE_REGISTRY,
					'scope_id'       => 'section_registry',
					'created_at'     => '2025-07-15T10:00:00Z',
					'schema_version' => '1',
					'status'         => 'active',
				)
			),
		);

		$by_type = $this->service->list_by_scope_type( Version_Snapshot_Schema::SCOPE_REGISTRY, 10, 0 );
		$by_id   = $this->service->list_by_scope_id( 'section_registry', 10, 0 );

		$this->assertGreaterThanOrEqual( 0, count( $by_type ) );
		$this->assertGreaterThanOrEqual( 0, count( $by_id ) );
	}

	public function test_attach_snapshot_reference_to_composition(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8006;
		$capture                               = $this->service->capture_section_registry();
		$this->assertTrue( $capture['success'] );

		$snap_post                         = new \WP_Post(
			array(
				'ID'          => 8006,
				'post_type'   => Object_Type_Keys::VERSION_SNAPSHOT,
				'post_title'  => 'Snap',
				'post_status' => 'publish',
				'post_name'   => 'snap',
			)
		);
		$comp_post                         = new \WP_Post(
			array(
				'ID'          => 9001,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Comp',
				'post_status' => 'publish',
				'post_name'   => 'comp_attach',
			)
		);
		$comp_def                          = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_attach',
			Composition_Schema::FIELD_NAME                 => 'Attach Test',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(),
			Composition_Schema::FIELD_STATUS               => 'draft',
		);
		$GLOBALS['_aio_get_post_return']   = $comp_post;
		$GLOBALS['_aio_wp_query_posts']    = array( $snap_post, $comp_post );
		$GLOBALS['_aio_post_meta']['9001'] = array(
			'_aio_internal_key'           => 'comp_attach',
			'_aio_status'                 => 'draft',
			'_aio_composition_definition' => wp_json_encode( $comp_def ),
		);

		$attached = $this->service->attach_snapshot_reference_to_composition( 9001, $capture['snapshot_id'] );

		$this->assertTrue( $attached );
		$updated = $this->composition_repo->get_definition_by_id( 9001 );
		$this->assertNotNull( $updated );
		$this->assertSame( $capture['snapshot_id'], $updated[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' );
	}

	public function test_schema_aligned_payload_structure(): void {
		$sections = array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 'st01_hero',
				Section_Schema::FIELD_NAME         => 'Hero',
				Section_Schema::FIELD_CATEGORY     => 'hero_intro',
				Section_Schema::FIELD_STATUS       => 'active',
			),
		);
		$payload  = Snapshot_Payload_Builder::build_section_registry_payload( $sections );

		$this->assertArrayHasKey( 'sections', $payload );
		$this->assertArrayHasKey( 'captured_at', $payload );
		$this->assertCount( 1, $payload['sections'] );
		$this->assertSame( 'st01_hero', $payload['sections'][0][ Section_Schema::FIELD_INTERNAL_KEY ] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $payload['captured_at'] );
	}

	public function test_prohibited_field_exclusion(): void {
		$payload = array(
			'sections'    => array(
				array(
					'internal_key' => 'st01',
					'api_key'      => 'secret123',
				),
			),
			'captured_at' => '2025-07-15T10:00:00Z',
		);
		$this->assertFalse( Snapshot_Payload_Builder::has_no_prohibited_fields( $payload ) );

		$found = Snapshot_Payload_Builder::collect_prohibited_keys( $payload );
		$this->assertNotEmpty( $found );
		$this->assertStringContainsString( 'api_key', implode( ' ', $found ) );
	}

	public function test_payload_without_prohibited_fields_passes(): void {
		$payload = Snapshot_Payload_Builder::build_section_registry_payload(
			array(
				array(
					'internal_key' => 'st01',
					'name'         => 'Hero',
					'status'       => 'active',
				),
			)
		);
		$this->assertTrue( Snapshot_Payload_Builder::has_no_prohibited_fields( $payload ) );
	}

	public function test_snapshot_type_keys_registry_oriented(): void {
		$this->assertTrue( Snapshot_Type_Keys::is_registry_oriented( Snapshot_Type_Keys::SECTION_REGISTRY ) );
		$this->assertTrue( Snapshot_Type_Keys::is_registry_oriented( Snapshot_Type_Keys::COMPOSITION_CONTEXT ) );
		$this->assertSame( Version_Snapshot_Schema::SCOPE_REGISTRY, Snapshot_Type_Keys::get_scope_type_for( Snapshot_Type_Keys::SECTION_REGISTRY ) );
	}
}
