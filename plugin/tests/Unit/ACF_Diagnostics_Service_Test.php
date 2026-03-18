<?php
/**
 * Unit tests for ACF_Diagnostics_Service: payload generation, blueprint summary,
 * stale-state reporting, compatibility warnings (spec §20, §45, §56, Prompt 040).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Compatibility\Field_Cleanup_Advisor;
use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Diagnostics_Service;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar;
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
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Field_Group_Derivation_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Page_Field_Group_Assignment_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Compatibility/Cleanup_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Compatibility/Field_Cleanup_Advisor.php';
require_once $plugin_root . '/src/Domain/ACF/Diagnostics/ACF_Diagnostics_Service.php';

/**
 * Wpdb stub for assignment map (matches Field_Cleanup_Advisor_Test).
 */
final class Diagnostics_Wpdb_Stub {
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
			$map_type = $source_ref = $target_ref = '';
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
		$map_type = $source_ref = null;
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

final class ACF_Diagnostics_Service_Test extends TestCase {

	private ACF_Diagnostics_Service $diagnostics;
	private static int $seed_id = 8000;

	protected function setUp(): void {
		parent::setUp();
		self::$seed_id                  = 8000;
		$GLOBALS['_aio_assign_store']   = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_post_meta']      = array();

		$wpdb            = new Diagnostics_Wpdb_Stub();
		$assignment_map  = new Assignment_Map_Service( $wpdb );
		$page_repo       = new Page_Template_Repository();
		$comp_repo       = new Composition_Repository();
		$section_repo    = new Section_Template_Repository();
		$derivation      = new Field_Group_Derivation_Service( $page_repo, $comp_repo, $section_repo );
		$assignment_svc  = new Page_Field_Group_Assignment_Service( $assignment_map, $derivation );
		$validator       = new \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator();
		$normalizer      = new \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer( $validator );
		$blueprint_svc   = new Section_Field_Blueprint_Service( $section_repo, $validator, $normalizer );
		$group_builder   = new ACF_Group_Builder( new ACF_Field_Builder() );
		$group_registrar = new ACF_Group_Registrar( $blueprint_svc, $group_builder );
		$cleanup_advisor = new Field_Cleanup_Advisor( $assignment_svc, $derivation, $assignment_map, $section_repo );

		$this->diagnostics = new ACF_Diagnostics_Service(
			$blueprint_svc,
			$group_registrar,
			$assignment_svc,
			$assignment_map,
			$cleanup_advisor,
			null
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_assign_store'], $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function seed_section( string $key, array $definition ): void {
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

	public function test_get_full_payload_returns_stable_keys(): void {
		$payload = $this->diagnostics->get_full_payload();

		$this->assertArrayHasKey( 'blueprints', $payload );
		$this->assertArrayHasKey( 'registered_groups', $payload );
		$this->assertArrayHasKey( 'page_assignments', $payload );
		$this->assertArrayHasKey( 'compatibility_warnings', $payload );
		$this->assertArrayHasKey( 'stale_items', $payload );
	}

	public function test_blueprint_summary_counts_valid_blueprints(): void {
		$this->seed_section(
			'st_diag_hero',
			array(
				'field_blueprint' => array(
					'blueprint_id'    => 'acf_st_diag_hero',
					'section_key'     => 'st_diag_hero',
					'section_version' => '1',
					'label'           => 'Diag Hero',
					'fields'          => array(
						array(
							'name'  => 'headline',
							'type'  => 'text',
							'label' => 'Headline',
						),
					),
				),
			)
		);
		$this->seed_section(
			'st_diag_faq',
			array(
				'field_blueprint' => array(
					'blueprint_id'    => 'acf_st_diag_faq',
					'section_key'     => 'st_diag_faq',
					'section_version' => '1',
					'label'           => 'Diag FAQ',
					'fields'          => array(
						array(
							'name'  => 'title',
							'type'  => 'text',
							'label' => 'Title',
						),
					),
				),
			)
		);

		$summary = $this->diagnostics->get_blueprint_summary();

		$this->assertSame( 2, $summary['valid_count'] );
		$this->assertCount( 2, $summary['valid'] );
		$this->assertArrayHasKey( 'section_key', $summary['valid'][0] );
		$this->assertArrayHasKey( 'group_key', $summary['valid'][0] );
	}

	public function test_registered_groups_summary_when_acf_unavailable(): void {
		$this->seed_section(
			'st_diag_one',
			array(
				'field_blueprint' => array(
					'blueprint_id'    => 'acf_st_diag_one',
					'section_key'     => 'st_diag_one',
					'section_version' => '1',
					'label'           => 'One',
					'fields'          => array(
						array(
							'name'  => 'f1',
							'type'  => 'text',
							'label' => 'F1',
						),
					),
				),
			)
		);

		$summary = $this->diagnostics->get_registered_groups_summary();

		$this->assertFalse( $summary['acf_available'] );
		$this->assertSame( 1, $summary['expected_count'] );
	}

	public function test_stale_items_aggregates_from_cleanup_advisor(): void {
		$this->seed_template(
			'pt_diag_landing',
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
					array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_diag_hero' ),
				),
			)
		);
		$this->seed_section( 'st_diag_hero', array() );
		$this->seed_section( 'st_diag_old', array() );
		$this->diagnostics->get_page_assignments_summary();

		$page_id = 7001;
		$this->diagnostics->get_page_assignments_summary();
		$store   = &$GLOBALS['_aio_assign_store'];
		$store[] = array(
			'id'         => 1,
			'map_type'   => 'page_template',
			'source_ref' => (string) $page_id,
			'target_ref' => 'pt_diag_landing',
		);
		$store[] = array(
			'id'         => 2,
			'map_type'   => 'page_field_group',
			'source_ref' => (string) $page_id,
			'target_ref' => 'group_aio_st_diag_old',
		);

		foreach ( $GLOBALS['_aio_post_meta'] ?? array() as $id => $meta ) {
			if ( ! empty( $meta['_aio_page_template_definition'] ) ) {
				$def = json_decode( $meta['_aio_page_template_definition'], true );
				if ( is_array( $def ) && ( $def['internal_key'] ?? '' ) === 'pt_diag_landing' ) {
					$def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ]               = array(
						array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_diag_hero' ),
					);
					$GLOBALS['_aio_post_meta'][ $id ]['_aio_page_template_definition'] = wp_json_encode( $def );
					break;
				}
			}
		}

		$stale = $this->diagnostics->get_stale_items();

		$this->assertGreaterThanOrEqual( 1, $stale['count'] );
		$this->assertNotEmpty( $stale['items'] );
	}

	public function test_page_assignments_summary_counts_rows_and_pages(): void {
		$store   = &$GLOBALS['_aio_assign_store'];
		$store[] = array(
			'id'         => 10,
			'map_type'   => 'page_field_group',
			'source_ref' => '1001',
			'target_ref' => 'group_aio_st01',
		);
		$store[] = array(
			'id'         => 11,
			'map_type'   => 'page_field_group',
			'source_ref' => '1001',
			'target_ref' => 'group_aio_st05',
		);
		$store[] = array(
			'id'         => 12,
			'map_type'   => 'page_field_group',
			'source_ref' => '1002',
			'target_ref' => 'group_aio_st01',
		);
		$store[] = array(
			'id'         => 13,
			'map_type'   => 'page_template',
			'source_ref' => '1001',
			'target_ref' => 'pt_landing',
		);

		$summary = $this->diagnostics->get_page_assignments_summary();

		$this->assertSame( 3, $summary['total_assignment_rows'] );
		$this->assertSame( 2, $summary['pages_with_assignments'] );
		$this->assertArrayHasKey( 'by_page', $summary );
	}
}
