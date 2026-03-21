<?php
/**
 * Unit tests for Profile_Snapshot_Repository (v2-scope-backlog.md §3).
 *
 * Uses an in-memory wpdb stub so no database is required.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\Storage\Profile;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 5 );
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Repository.php';

// ---------------------------------------------------------------------------
// WP stubs needed by repository.
// ---------------------------------------------------------------------------
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ): string {
		$r = json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}

// ---------------------------------------------------------------------------
// In-memory wpdb stub for testing.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'AIOPageBuilder\Tests\Unit\Domain\Storage\Profile\Fake_Wpdb' ) ) {
	class Fake_Wpdb {
		public string $prefix = 'wp_';
		/** @var array<string, array<int, array<string, mixed>>> */
		public array $tables = array();
		/** @var array<string, mixed>|null Last inserted row */
		public ?array $last_insert = null;
		public string $last_error  = '';

		public function insert( string $table, array $data ): int {
			if ( ! isset( $this->tables[ $table ] ) ) {
				$this->tables[ $table ] = array();
			}
			$this->tables[ $table ][] = $data;
			$this->last_insert        = $data;
			return 1;
		}

		public function delete( string $table, array $where ): int {
			if ( ! isset( $this->tables[ $table ] ) ) {
				return 0;
			}
			$key                    = array_key_first( $where );
			$val                    = $where[ $key ];
			$before                 = count( $this->tables[ $table ] );
			$this->tables[ $table ] = array_values(
				array_filter(
					$this->tables[ $table ],
					fn( $r ) => ( $r[ $key ] ?? null ) !== $val
				)
			);
			return $before - count( $this->tables[ $table ] );
		}

		public function get_results( string $sql, $output = OBJECT ): ?array {
			$table = $this->prefix . 'aio_profile_snapshots';
			return $this->tables[ $table ] ?? array();
		}

		public function get_row( string $sql, $output = OBJECT ): ?array {
			$table = $this->prefix . 'aio_profile_snapshots';
			$rows  = $this->tables[ $table ] ?? array();
			foreach ( $rows as $row ) {
				if ( isset( $row['snapshot_id'] ) && str_contains( $sql, "'{$row['snapshot_id']}'" ) ) {
					return $row;
				}
			}
			return null;
		}

		public function get_var( string $sql ): ?string {
			$table = $this->prefix . 'aio_profile_snapshots';
			return (string) count( $this->tables[ $table ] ?? array() );
		}

		public function prepare( string $query, ...$args ): string {
			// Minimal implementation: replace %s with quoted first arg, %d with int.
			foreach ( $args as $arg ) {
				if ( is_int( $arg ) ) {
					$query = preg_replace( '/%d/', (string) $arg, $query, 1 );
				} else {
					$query = preg_replace( '/%s/', "'{$arg}'", $query, 1 );
				}
			}
			return $query;
		}
	}
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

/**
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository
 */
final class Profile_Snapshot_Repository_Test extends TestCase {

	private Fake_Wpdb $wpdb;
	private Profile_Snapshot_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb = new Fake_Wpdb();
		$this->repo = new Profile_Snapshot_Repository( $this->wpdb );
	}

	private function snapshot( string $id = 'snap_test_001', string $source = 'manual' ): Profile_Snapshot_Data {
		return new Profile_Snapshot_Data(
			$id,
			'other',
			'',
			'2025-01-15 10:00:00',
			'1',
			array( 'name' => 'Acme' ),
			array( 'industry' => 'Tech' ),
			$source
		);
	}

	public function test_save_inserts_row(): void {
		$snap   = $this->snapshot();
		$result = $this->repo->save( $snap );
		$this->assertTrue( $result );
		$this->assertCount( 1, $this->wpdb->tables['wp_aio_profile_snapshots'] ?? array() );
	}

	public function test_save_duplicate_id_returns_false(): void {
		$snap = $this->snapshot();
		$this->repo->save( $snap );
		$result = $this->repo->save( $snap );
		$this->assertFalse( $result );
		$this->assertCount( 1, $this->wpdb->tables['wp_aio_profile_snapshots'] );
	}

	public function test_get_by_id_hydrates_snapshot(): void {
		$snap = $this->snapshot( 'snap_abc' );
		$this->repo->save( $snap );
		$loaded = $this->repo->get_by_id( 'snap_abc' );
		$this->assertInstanceOf( Profile_Snapshot_Data::class, $loaded );
		$this->assertSame( 'snap_abc', $loaded->snapshot_id );
		$this->assertSame( 'manual', $loaded->source );
	}

	public function test_get_by_id_returns_null_when_not_found(): void {
		$loaded = $this->repo->get_by_id( 'nonexistent_snap' );
		$this->assertNull( $loaded );
	}

	public function test_delete_removes_row(): void {
		$snap = $this->snapshot( 'snap_del' );
		$this->repo->save( $snap );
		$result = $this->repo->delete( 'snap_del' );
		$this->assertTrue( $result );
	}

	public function test_brand_and_business_profiles_survive_round_trip(): void {
		$brand    = array(
			'name'    => 'Acme Co.',
			'tagline' => 'Building Tomorrow',
		);
		$business = array(
			'industry' => 'SaaS',
			'personas' => array( array( 'role' => 'Buyer' ) ),
		);
		$snap     = new Profile_Snapshot_Data( 'snap_rt', 'other', '', '2025-06-01 00:00:00', '1', $brand, $business, 'brand_profile_merge' );
		$this->repo->save( $snap );
		$loaded = $this->repo->get_by_id( 'snap_rt' );
		$this->assertSame( 'Acme Co.', $loaded->brand_profile['name'] ?? null );
		$this->assertSame( 'SaaS', $loaded->business_profile['industry'] ?? null );
	}

	public function test_count_returns_correct_total(): void {
		$this->assertSame( 0, $this->repo->count() );
		$this->repo->save( $this->snapshot( 'snap_1' ) );
		$this->repo->save( $this->snapshot( 'snap_2' ) );
		$this->assertSame( 2, $this->repo->count() );
	}
}
