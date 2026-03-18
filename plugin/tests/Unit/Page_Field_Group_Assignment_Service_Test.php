<?php
/**
 * Unit tests for Page_Field_Group_Assignment_Service: template-based assignment,
 * composition-based assignment, reassignment, sync_with_refinement, persistence (spec §20.10–20.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Pages/Visible_Group_Key_Query_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Field_Group_Derivation_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service.php';

/**
 * In-memory wpdb stub for assignment map tests. Persists rows in $GLOBALS['_aio_assign_store'].
 */
final class Assignment_Map_Wpdb_Stub {
	public string $prefix = 'wp_';
	public int $insert_id = 0;
	private int $next_id  = 1;

	public function prepare( string $query, ...$args ): string {
		$i     = 0;
		$query = preg_replace_callback(
			'/%[sd]/',
			function () use ( $args, &$i ) {
				$v = $args[ $i++ ] ?? null;
				return $v === null ? 'NULL' : "'" . addslashes( (string) $v ) . "'";
			},
			$query
		);
		return $query;
	}

	public function query( string $sql ): int|false {
		$store = &$GLOBALS['_aio_assign_store'];
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		if ( strpos( $sql, 'INSERT INTO' ) !== false ) {
			if ( preg_match( '/VALUES\s*\(\s*\'([^\']*)\'\s*,\s*\'([^\']*)\'\s*,\s*\'([^\']*)\'/s', $sql, $m ) ) {
				$map_type   = $m[1];
				$source_ref = $m[2];
				$target_ref = $m[3];
			} else {
				$map_type   = '';
				$source_ref = '';
				$target_ref = '';
			}
			++$this->next_id;
			$this->insert_id = $this->next_id - 1;
			$store[]         = array(
				'id'         => $this->insert_id,
				'map_type'   => $map_type,
				'source_ref' => $source_ref,
				'target_ref' => $target_ref,
			);
			return 1;
		}
		return 1;
	}

	public function get_results( string $query, $output = OBJECT ): array {
		$store = $GLOBALS['_aio_assign_store'] ?? array();
		if ( ! is_array( $store ) ) {
			return array();
		}
		$map_type   = null;
		$source_ref = null;
		if ( preg_match( '/map_type\s*=\s*[\'"]([^\'"]*)[\'"]/', $query, $m ) ) {
			$map_type = $m[1];
		}
		if ( preg_match( '/source_ref\s*=\s*[\'"]([^\'"]*)[\'"]/', $query, $m ) ) {
			$source_ref = $m[1];
		}
		$out = array();
		foreach ( $store as $row ) {
			if ( ( $map_type === null || $row['map_type'] === $map_type ) && ( $source_ref === null || $row['source_ref'] === (string) $source_ref ) ) {
				$out[] = ( $output === \ARRAY_A || $output === 'ARRAY_A' ) ? $row : (object) $row;
			}
		}
		if ( preg_match( '/LIMIT\s+(\d+)/', $query, $m ) ) {
			$out = array_slice( $out, 0, (int) $m[1] );
		}
		return $out;
	}

	/** Supports list_target_refs_by_source (SELECT target_ref). */
	public function get_col( string $query ): array {
		$rows = $this->get_results( $query, \ARRAY_A );
		$out  = array();
		foreach ( $rows as $row ) {
			$ref = $row['target_ref'] ?? '';
			if ( $ref !== '' ) {
				$out[] = $ref;
			}
		}
		return $out;
	}

	public function delete( string $table, array $where, $where_format = null ): int|false {
		$store      = &$GLOBALS['_aio_assign_store'];
		$map_type   = $where['map_type'] ?? '';
		$source_ref = $where['source_ref'] ?? '';
		$before     = is_array( $store ) ? count( $store ) : 0;
		if ( ! is_array( $store ) ) {
			$store = array();
			return 0;
		}
		$store = array_values(
			array_filter(
				$store,
				function ( $r ) use ( $map_type, $source_ref ) {
					return ! ( $r['map_type'] === $map_type && $r['source_ref'] === $source_ref );
				}
			)
		);
		return $before - count( $store );
	}
}

final class Page_Field_Group_Assignment_Service_Test extends TestCase {

	private Assignment_Map_Service $assignment_map;
	private Field_Group_Derivation_Service $derivation;
	private Page_Field_Group_Assignment_Service $service;
	private static int $seed_id = 9000;

	protected function setUp(): void {
		parent::setUp();
		self::$seed_id                  = 9000;
		$GLOBALS['_aio_assign_store']   = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_post_meta']      = array();

		$wpdb                 = new Assignment_Map_Wpdb_Stub();
		$this->assignment_map = new Assignment_Map_Service( $wpdb );
		$this->derivation     = new Field_Group_Derivation_Service(
			new Page_Template_Repository(),
			new Composition_Repository(),
			new Section_Template_Repository()
		);
		$this->service        = new Page_Field_Group_Assignment_Service(
			$this->assignment_map,
			$this->derivation
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_assign_store'], $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function seed_template( string $key, array $definition ): void {
		$id                                        = self::$seed_id++;
		$post                                      = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => $key,
			)
		);
		$GLOBALS['_aio_wp_query_posts'][]          = $post;
		$def                                       = array_merge( array( 'internal_key' => $key ), $definition );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'             => $key,
			'_aio_status'                   => 'active',
			'_aio_page_template_definition' => wp_json_encode( $def ),
		);
	}

	private function seed_composition( string $comp_id, array $definition ): void {
		$id                                        = self::$seed_id++;
		$post                                      = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::COMPOSITION,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => $comp_id,
			)
		);
		$GLOBALS['_aio_wp_query_posts'][]          = $post;
		$def                                       = array_merge( array( 'composition_id' => $comp_id ), $definition );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'           => $comp_id,
			'_aio_status'                 => 'active',
			'_aio_composition_definition' => wp_json_encode( $def ),
		);
	}

	private function seed_section( string $key, array $definition = array() ): void {
		$id                                        = self::$seed_id++;
		$post                                      = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => 'Test',
				'post_status' => 'publish',
				'post_name'   => $key,
			)
		);
		$GLOBALS['_aio_wp_query_posts'][]          = $post;
		$def                                       = array_merge(
			array(
				'internal_key' => $key,
				'status'       => 'active',
			),
			$definition
		);
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'       => $key,
			'_aio_status'             => $def['status'] ?? 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
	}

	public function test_assign_from_template_full_replace_persists_groups_and_source(): void {
		$page_id = 42;
		$this->seed_template(
			'pt_landing_contact',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st05_faq' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->seed_section( 'st05_faq' );

		$result = $this->service->assign_from_template( $page_id, 'pt_landing_contact', true );

		$this->assertSame( 2, $result['assigned'] );
		$this->assertSame( array(), $result['errors'] );

		$visible = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertContains( 'group_aio_st01_hero', $visible );
		$this->assertContains( 'group_aio_st05_faq', $visible );
		$this->assertCount( 2, $visible );

		$source = $this->service->get_structural_source_for_page( $page_id );
		$this->assertNotNull( $source );
		$this->assertSame( 'page_template', $source['type'] );
		$this->assertSame( 'pt_landing_contact', $source['key'] );
	}

	public function test_assign_from_composition_full_replace_persists_groups_and_source(): void {
		$page_id = 100;
		$this->seed_composition(
			'comp-custom-001',
			array(
				Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
					array( Composition_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
					array( Composition_Schema::SECTION_ITEM_KEY => 'st03_cta' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->seed_section( 'st03_cta' );

		$result = $this->service->assign_from_composition( $page_id, 'comp-custom-001', true );

		$this->assertSame( 2, $result['assigned'] );
		$visible = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertContains( 'group_aio_st01_hero', $visible );
		$this->assertContains( 'group_aio_st03_cta', $visible );

		$source = $this->service->get_structural_source_for_page( $page_id );
		$this->assertNotNull( $source );
		$this->assertSame( 'page_composition', $source['type'] );
		$this->assertSame( 'comp-custom-001', $source['key'] );
	}

	public function test_reassign_from_template_to_composition_clears_template(): void {
		$page_id = 50;
		$this->seed_template(
			'pt_old',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
				),
			)
		);
		$this->seed_composition(
			'comp-new',
			array(
				Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
					array( Composition_Schema::SECTION_ITEM_KEY => 'st03_cta' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->seed_section( 'st03_cta' );

		$this->service->assign_from_template( $page_id, 'pt_old', true );
		$this->service->assign_from_composition( $page_id, 'comp-new', true );

		$source = $this->service->get_structural_source_for_page( $page_id );
		$this->assertSame( 'page_composition', $source['type'] );
		$this->assertSame( 'comp-new', $source['key'] );
		$visible = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertContains( 'group_aio_st03_cta', $visible );
		$this->assertNotContains( 'group_aio_st01_hero', $visible );
	}

	public function test_reassign_from_stored_source_template(): void {
		$page_id = 60;
		$this->seed_template(
			'pt_landing_contact',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st05_faq' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->seed_section( 'st05_faq' );

		$this->service->assign_from_template( $page_id, 'pt_landing_contact', true );
		$before = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertCount( 2, $before );

		// Update template definition in place to add a section.
		foreach ( $GLOBALS['_aio_post_meta'] ?? array() as $id => $meta ) {
			if ( ! empty( $meta['_aio_page_template_definition'] ) ) {
				$def = json_decode( $meta['_aio_page_template_definition'], true );
				if ( is_array( $def ) && ( $def['internal_key'] ?? '' ) === 'pt_landing_contact' ) {
					$def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ][]             = array( Page_Template_Schema::SECTION_ITEM_KEY => 'st07_testimonials' );
					$GLOBALS['_aio_post_meta'][ $id ]['_aio_page_template_definition'] = wp_json_encode( $def );
					break;
				}
			}
		}
		$this->seed_section( 'st07_testimonials' );

		$result = $this->service->reassign_from_stored_source( $page_id );

		$this->assertSame( 3, $result['assigned'] );
		$after = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertContains( 'group_aio_st07_testimonials', $after );
	}

	public function test_reassign_from_stored_source_returns_error_when_no_source(): void {
		$page_id = 999;
		$result  = $this->service->reassign_from_stored_source( $page_id );
		$this->assertSame( 0, $result['assigned'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	public function test_sync_with_refinement_keeps_existing_and_adds_new(): void {
		$page_id = 70;
		$this->seed_template(
			'pt_landing',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st05_faq' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->seed_section( 'st05_faq' );

		$this->service->assign_from_template( $page_id, 'pt_landing', true );
		$visible_before = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertCount( 2, $visible_before );

		// Add new section to template definition in place.
		foreach ( $GLOBALS['_aio_post_meta'] ?? array() as $id => $meta ) {
			if ( ! empty( $meta['_aio_page_template_definition'] ) ) {
				$def = json_decode( $meta['_aio_page_template_definition'], true );
				if ( is_array( $def ) && ( $def['internal_key'] ?? '' ) === 'pt_landing' ) {
					$def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ][]             = array( Page_Template_Schema::SECTION_ITEM_KEY => 'st07_new' );
					$GLOBALS['_aio_post_meta'][ $id ]['_aio_page_template_definition'] = wp_json_encode( $def );
					break;
				}
			}
		}
		$this->seed_section( 'st07_new' );

		$result = $this->service->sync_with_refinement( $page_id );

		$this->assertSame( 3, $result['assigned'] );
		$visible_after = $this->service->get_visible_groups_for_page( $page_id );
		$this->assertContains( 'group_aio_st07_new', $visible_after );
	}

	public function test_get_visible_groups_for_page_returns_empty_when_no_assignment(): void {
		$result = $this->service->get_visible_groups_for_page( 9999 );
		$this->assertSame( array(), $result );
	}

	public function test_get_visible_groups_result_for_page_matches_get_visible_groups_for_page(): void {
		$page_id = 42;
		$this->seed_page_template( 'pt_landing' );
		$this->seed_page( $page_id );
		$assign = $this->service->assign_from_template( $page_id, 'pt_landing', true );
		$this->assertGreaterThan( 0, $assign['assigned'] );
		$list   = $this->service->get_visible_groups_for_page( $page_id );
		$result = $this->service->get_visible_groups_result_for_page( $page_id );
		$this->assertSame( $list, $result->get_group_keys() );
	}

	public function test_get_visible_groups_result_for_page_returns_empty_for_invalid_page_id(): void {
		$result = $this->service->get_visible_groups_result_for_page( 0 );
		$this->assertSame( array(), $result->get_group_keys() );
	}

	public function test_get_structural_source_for_page_returns_null_when_no_source(): void {
		$this->assertNull( $this->service->get_structural_source_for_page( 9999 ) );
	}
}
