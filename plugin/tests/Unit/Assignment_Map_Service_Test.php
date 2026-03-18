<?php
/**
 * Unit tests for Assignment_Map_Service: CRUD basics, assignment type validation (spec §11.7, Prompt 020).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Map_Service.php';

final class Assignment_Map_Service_Test extends TestCase {

	private object $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb = $this->create_wpdb_stub();
	}

	private function create_wpdb_stub(): object {
		$prefix    = 'wp_';
		$table     = $prefix . Table_Names::ASSIGNMENT_MAPS;
		$insert_id = 0;
		$rows      = array();
		$list      = array();
		return new class( $table, $insert_id, $rows, $list ) {
			public string $prefix = 'wp_';
			private string $table;
			private int $insert_id;
			private array $rows;
			private array $list;

			public function __construct( string $table, int &$insert_id, array &$rows, array &$list ) {
				$this->table     = $table;
				$this->insert_id = &$insert_id;
				$this->rows      = &$rows;
				$this->list      = &$list;
			}

			public function prepare( string $query, ...$args ): string {
				return $query;
			}

			public function query( string $sql ): int|false {
				if ( strpos( $sql, 'INSERT' ) !== false ) {
					$GLOBALS['_aio_assign_insert_id'] = ( $GLOBALS['_aio_assign_insert_id'] ?? 0 ) + 1;
					return 1;
				}
				return 1;
			}

			public function get_insert_id(): int {
				return $GLOBALS['_aio_assign_insert_id'] ?? 99;
			}

			public function get_row( string $query, $output = OBJECT ) {
				$id  = isset( $GLOBALS['_aio_assign_get_row_id'] ) ? (int) $GLOBALS['_aio_assign_get_row_id'] : 0;
				$row = isset( $GLOBALS['_aio_assign_row'] ) ? $GLOBALS['_aio_assign_row'] : null;
				if ( $output === ARRAY_A && $row !== null ) {
					return $row;
				}
				return null;
			}

			public function get_results( string $query, $output = OBJECT ) {
				return $GLOBALS['_aio_assign_results'] ?? array();
			}

			public function update( string $table, array $data, array $where, $format = null, $where_format = null ): int|false {
				return 1;
			}

			public function delete( string $table, array $where, $where_format = null ): int|false {
				return 1;
			}
		};
	}

	/** Stub that exposes insert_id as property for Assignment_Map_Service. */
	private function create_wpdb_stub_with_insert_id(): object {
		$table = $this->wpdb->prefix . Table_Names::ASSIGNMENT_MAPS;
		$stub  = new class( $table ) {
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
					$this->insert_id = 42;
					return 1;
				}
				return 1;
			}

			public function get_row( string $query, $output = OBJECT ) {
				return isset( $GLOBALS['_aio_assign_row'] ) ? $GLOBALS['_aio_assign_row'] : null;
			}

			public function get_results( string $query, $output = OBJECT ) {
				return $GLOBALS['_aio_assign_results'] ?? array();
			}

			public function update( string $table, array $data, array $where, $format = null, $where_format = null ): int|false {
				return 1;
			}

			public function delete( string $table, array $where, $where_format = null ): int|false {
				return 1;
			}
		};
		return $stub;
	}

	public function test_create_returns_id_on_success(): void {
		$wpdb = $this->create_wpdb_stub_with_insert_id();
		$svc  = new Assignment_Map_Service( $wpdb );
		$id   = $svc->create( Assignment_Types::PAGE_TEMPLATE, 'page-1', 'tpl-landing', '', null );
		$this->assertSame( 42, $id );
	}

	public function test_create_returns_zero_for_invalid_map_type(): void {
		$wpdb = $this->create_wpdb_stub_with_insert_id();
		$svc  = new Assignment_Map_Service( $wpdb );
		$id   = $svc->create( 'invalid_type', 'source', 'target', '', null );
		$this->assertSame( 0, $id );
	}

	public function test_create_returns_zero_for_empty_source_ref(): void {
		$wpdb = $this->create_wpdb_stub_with_insert_id();
		$svc  = new Assignment_Map_Service( $wpdb );
		$id   = $svc->create( Assignment_Types::PAGE_TEMPLATE, '', 'target', '', null );
		$this->assertSame( 0, $id );
	}

	public function test_get_by_id_returns_null_for_zero_id(): void {
		$svc = new Assignment_Map_Service( $this->create_wpdb_stub_with_insert_id() );
		$this->assertNull( $svc->get_by_id( 0 ) );
	}

	public function test_get_by_id_returns_row_when_stub_provides_it(): void {
		$GLOBALS['_aio_assign_row'] = array(
			'id'             => '1',
			'map_type'       => Assignment_Types::COMPOSITION_SECTION,
			'source_ref'     => 'comp-1',
			'target_ref'     => 'section-1',
			'scope_ref'      => null,
			'payload'        => null,
			'created_at'     => '2025-01-01 00:00:00',
			'schema_version' => '1',
		);
		$wpdb                       = $this->create_wpdb_stub_with_insert_id();
		$svc                        = new Assignment_Map_Service( $wpdb );
		$row                        = $svc->get_by_id( 1 );
		$this->assertIsArray( $row );
		$this->assertSame( Assignment_Types::COMPOSITION_SECTION, $row['map_type'] );
		unset( $GLOBALS['_aio_assign_row'] );
	}

	public function test_list_by_type_returns_empty_for_invalid_type(): void {
		$svc  = new Assignment_Map_Service( $this->create_wpdb_stub_with_insert_id() );
		$list = $svc->list_by_type( 'invalid', 10, 0 );
		$this->assertSame( array(), $list );
	}

	public function test_list_by_type_returns_stub_results(): void {
		$GLOBALS['_aio_assign_results'] = array(
			array(
				'id'             => '1',
				'map_type'       => Assignment_Types::PLAN_OBJECT,
				'source_ref'     => 'plan-1',
				'target_ref'     => 'obj-1',
				'scope_ref'      => null,
				'payload'        => null,
				'created_at'     => '2025-01-01 00:00:00',
				'schema_version' => '1',
			),
		);
		$svc                            = new Assignment_Map_Service( $this->create_wpdb_stub_with_insert_id() );
		$list                           = $svc->list_by_type( Assignment_Types::PLAN_OBJECT, 10, 0 );
		$this->assertCount( 1, $list );
		$this->assertSame( 'plan-1', $list[0]['source_ref'] );
		unset( $GLOBALS['_aio_assign_results'] );
	}

	public function test_delete_returns_false_for_zero_id(): void {
		$svc = new Assignment_Map_Service( $this->create_wpdb_stub_with_insert_id() );
		$this->assertFalse( $svc->delete( 0 ) );
	}

	public function test_update_returns_false_for_zero_id(): void {
		$svc = new Assignment_Map_Service( $this->create_wpdb_stub_with_insert_id() );
		$this->assertFalse( $svc->update( 0, 'page_template', null, null, null, null ) );
	}
}
