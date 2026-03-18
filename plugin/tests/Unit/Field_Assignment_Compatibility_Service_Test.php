<?php
/**
 * Unit tests for Field_Assignment_Compatibility_Service: retention helpers, deprecated detection,
 * would_break_content, compatibility notes (spec §20.12, §20.15).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\ACF\Compatibility\Field_Assignment_Compatibility_Service;
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
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Field_Group_Derivation_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Compatibility/Field_Assignment_Compatibility_Service.php';

/**
 * In-memory wpdb stub for compatibility tests.
 */
final class Compatibility_Service_Wpdb_Stub {
	public string $prefix = 'wp_';
	public int $insert_id = 0;
	private int $next_id  = 1;

	public function prepare( string $query, ...$args ): string {
		$i = 0;
		return preg_replace_callback(
			'/%[sd]/',
			function () use ( $args, &$i ) {
				$v = $args[ $i++ ] ?? null;
				return $v === null ? 'NULL' : "'" . addslashes( (string) $v ) . "'";
			},
			$query
		);
	}

	public function query( string $sql ): int|false {
		$store = &$GLOBALS['_aio_assign_store'];
		if ( ! is_array( $store ) ) {
			$store = array(); }
		if ( strpos( $sql, 'INSERT INTO' ) !== false ) {
			$map_type   = '';
			$source_ref = '';
			$target_ref = '';
			if ( preg_match( '/VALUES\s*\(\s*\'([^\']*)\'\s*,\s*\'([^\']*)\'\s*,\s*\'([^\']*)\'/s', $sql, $m ) ) {
				$map_type   = $m[1];
				$source_ref = $m[2];
				$target_ref = $m[3];
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
			return array(); }
		$map_type   = null;
		$source_ref = null;
		if ( preg_match( '/map_type\s*=\s*[\'"]([^\'"]*)[\'"]/', $query, $m ) ) {
			$map_type = $m[1]; }
		if ( preg_match( '/source_ref\s*=\s*[\'"]([^\'"]*)[\'"]/', $query, $m ) ) {
			$source_ref = $m[1]; }
		$out = array();
		foreach ( $store as $row ) {
			if ( ( $map_type === null || $row['map_type'] === $map_type ) && ( $source_ref === null || $row['source_ref'] === (string) $source_ref ) ) {
				$out[] = ( $output === \ARRAY_A || $output === 'ARRAY_A' ) ? $row : (object) $row;
			}
		}
		if ( preg_match( '/LIMIT\s+(\d+)/', $query, $m ) ) {
			$out = array_slice( $out, 0, (int) $m[1] ); }
		return $out;
	}

	public function delete( string $table, array $where, $where_format = null ): int|false {
		$store      = &$GLOBALS['_aio_assign_store'];
		$map_type   = $where['map_type'] ?? '';
		$source_ref = $where['source_ref'] ?? '';
		$before     = is_array( $store ) ? count( $store ) : 0;
		if ( ! is_array( $store ) ) {
			$store = array();
			return 0; }
		$store = array_values( array_filter( $store, fn( $r ) => ! ( $r['map_type'] === $map_type && $r['source_ref'] === $source_ref ) ) );
		return $before - count( $store );
	}
}

final class Field_Assignment_Compatibility_Service_Test extends TestCase {

	private Field_Assignment_Compatibility_Service $service;
	private Page_Field_Group_Assignment_Service $assignment_svc;
	private static int $seed_id = 9000;

	protected function setUp(): void {
		parent::setUp();
		self::$seed_id                  = 9000;
		$GLOBALS['_aio_assign_store']   = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_post_meta']      = array();

		$wpdb                 = new Compatibility_Service_Wpdb_Stub();
		$assignment_map       = new Assignment_Map_Service( $wpdb );
		$page_repo            = new Page_Template_Repository();
		$comp_repo            = new Composition_Repository();
		$section_repo         = new Section_Template_Repository();
		$derivation           = new Field_Group_Derivation_Service( $page_repo, $comp_repo, $section_repo );
		$this->assignment_svc = new Page_Field_Group_Assignment_Service( $assignment_map, $derivation );
		$this->service        = new Field_Assignment_Compatibility_Service( $this->assignment_svc, $section_repo );
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

	public function test_get_groups_to_retain_during_refinement_unions_derived_and_existing(): void {
		$page_id = 40;
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

		$this->assignment_svc->assign_from_template( $page_id, 'pt_landing', true );
		$derived = array( 'group_aio_st01_hero', 'group_aio_st05_faq', 'group_aio_st07_new' );

		$retain = $this->service->get_groups_to_retain_during_refinement( $page_id, $derived );

		$this->assertContains( 'group_aio_st01_hero', $retain );
		$this->assertContains( 'group_aio_st05_faq', $retain );
		$this->assertContains( 'group_aio_st07_new', $retain );
	}

	public function test_would_break_content_returns_true_when_assigned(): void {
		$page_id = 41;
		$this->seed_template(
			'pt_landing',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
				),
			)
		);
		$this->seed_section( 'st01_hero' );
		$this->assignment_svc->assign_from_template( $page_id, 'pt_landing', true );

		$this->assertTrue( $this->service->would_break_content( 'group_aio_st01_hero', $page_id ) );
	}

	public function test_would_break_content_returns_false_when_not_assigned(): void {
		$this->assertFalse( $this->service->would_break_content( 'group_aio_st01_hero', 99999 ) );
	}

	public function test_is_deprecated_group_returns_true_for_deprecated_section(): void {
		$this->seed_section(
			'st_old_deprecated',
			array(
				'status'      => 'deprecated',
				'deprecation' => array( 'deprecated' => true ),
			)
		);
		$this->assertTrue( $this->service->is_deprecated_group( 'group_aio_st_old_deprecated' ) );
	}

	public function test_is_deprecated_group_returns_false_for_active_section(): void {
		$this->seed_section( 'st01_hero' );
		$this->assertFalse( $this->service->is_deprecated_group( 'group_aio_st01_hero' ) );
	}

	public function test_get_compatibility_notes_for_page_includes_deprecated_count(): void {
		$page_id = 42;
		$this->seed_template(
			'pt_legacy',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_old_deprecated' ),
				),
			)
		);
		$this->seed_section(
			'st_old_deprecated',
			array(
				'status'      => 'deprecated',
				'deprecation' => array( 'deprecated' => true ),
			)
		);
		$this->assignment_svc->assign_from_template( $page_id, 'pt_legacy', true );
		$store   = &$GLOBALS['_aio_assign_store'];
		$store[] = array(
			'id'         => 998,
			'map_type'   => 'page_field_group',
			'source_ref' => (string) $page_id,
			'target_ref' => 'group_aio_st_old_deprecated',
		);

		$notes = $this->service->get_compatibility_notes_for_page( $page_id );

		$this->assertNotEmpty( $notes );
		$this->assertTrue( (bool) preg_match( '/deprecated/', implode( ' ', $notes ) ) );
	}
}
