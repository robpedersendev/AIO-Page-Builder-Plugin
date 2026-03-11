<?php
/**
 * Unit tests for Crawl_Snapshot_Repository: get by run/URL, composite key, list, save (spec §11.1, Prompt 050).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Repository.php';

final class Crawl_Snapshot_Repository_Test extends TestCase {

	private object $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb = $this->create_wpdb_stub();
	}

	private function create_wpdb_stub(): object {
		$table = 'wp_' . Table_Names::CRAWL_SNAPSHOTS;
		$rows  = array();
		$insert_id = 0;
		return new class( $table, $rows, $insert_id ) {
			public string $prefix = 'wp_';
			private string $table;
			private array $rows;
			private int $insert_id;

			public function __construct( string $table, array &$rows, int &$insert_id ) {
				$this->table     = $table;
				$this->rows     = &$rows;
				$this->insert_id = &$insert_id;
			}

			public function get_row( string $query, $output = OBJECT ) {
				if ( count( $this->rows ) > 0 ) {
					$row = (object) $this->rows[0];
					return $output === ARRAY_A ? (array) $row : $row;
				}
				return null;
			}

			public function get_results( string $query, $output = OBJECT ) {
				$out = array();
				foreach ( $this->rows as $r ) {
					$out[] = $output === ARRAY_A ? $r : (object) $r;
				}
				return $out;
			}

			public function query( string $query ) {
				// Simulate INSERT: append a row and set insert_id.
				if ( stripos( $query, 'INSERT' ) !== false ) {
					$this->insert_id = count( $this->rows ) + 1;
					$this->rows[] = array( 'id' => $this->insert_id );
					return 1;
				}
				return 0;
			}

			public function prepare( string $query, ...$args ) {
				return $query;
			}

			public function update( $table, $data, $where, $format = null, $where_format = null ) {
				return 1;
			}
		};
	}

	private function create_wpdb_stub_with_insert_id(): object {
		$table = 'wp_' . Table_Names::CRAWL_SNAPSHOTS;
		$rows  = array();
		$insert_id = 0;
		$stub = new class( $table, $rows, $insert_id ) {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			private string $table;
			private array $rows;

			public function __construct( string $table, array &$rows, int &$insert_id ) {
				$this->table = $table;
				$this->rows = &$rows;
			}

			public function get_row( string $query, $output = OBJECT ) {
				return null;
			}

			public function get_results( string $query, $output = OBJECT ) {
				return array();
			}

			public function query( string $query ) {
				$this->insert_id = 42;
				return 1;
			}

			public function prepare( string $query, ...$args ) {
				return $query;
			}

			public function update( $table, $data, $where, $format = null, $where_format = null ) {
				return 1;
			}
		};
		return $stub;
	}

	public function test_get_by_run_and_url_returns_null_when_no_row(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$this->assertNull( $repo->get_by_run_and_url( 'run-1', 'https://example.com/' ) );
	}

	public function test_get_by_key_returns_null_for_non_composite_key(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$this->assertNull( $repo->get_by_key( 'run-1' ) );
	}

	public function test_get_by_key_returns_null_for_composite_key_when_no_row(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$key  = "run-1\nhttps://example.com/";
		$this->assertNull( $repo->get_by_key( $key ) );
	}

	public function test_list_by_run_id_returns_empty(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$this->assertSame( array(), $repo->list_by_run_id( 'run-1' ) );
	}

	public function test_list_by_status_returns_empty(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$this->assertSame( array(), $repo->list_by_status( Crawl_Snapshot_Payload_Builder::STATUS_PENDING ) );
	}

	public function test_save_with_valid_payload_returns_insert_id(): void {
		$wpdb = $this->create_wpdb_stub_with_insert_id();
		$repo = new Crawl_Snapshot_Repository( $wpdb );
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload( 'run-1', 'https://example.com/' );
		$id = $repo->save( $payload );
		$this->assertSame( 42, $id );
	}

	public function test_save_with_empty_payload_returns_zero(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$id = $repo->save( array() );
		$this->assertSame( 0, $id );
	}

	public function test_exists_returns_false_for_missing_id(): void {
		$repo = new Crawl_Snapshot_Repository( $this->wpdb );
		$this->assertFalse( $repo->exists( 999 ) );
	}
}
