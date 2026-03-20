<?php
/**
 * Unit tests for Profile_Snapshot_Diff_Service (v2-scope-backlog.md §3).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\Storage\Profile;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Diff_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 5 );
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Diff_Service.php';

// Stub wp_json_encode.
namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\wp_json_encode' ) ) {
	function wp_json_encode( $data ): string {
		$r = \json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}

namespace AIOPageBuilder\Tests\Unit\Domain\Storage\Profile;

/**
 * Fake Profile_Store that returns controlled profile data for diff tests.
 */
final class Fake_Profile_Store_For_Diff {
	private array $brand;
	private array $business;

	public function __construct( array $brand = array(), array $business = array() ) {
		$this->brand    = $brand;
		$this->business = $business;
	}

	public function get_brand_profile(): array {
		return $this->brand;
	}

	public function get_business_profile(): array {
		return $this->business;
	}
}

/**
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Diff_Service
 */
final class Profile_Snapshot_Diff_Service_Test extends TestCase {

	private Profile_Snapshot_Diff_Service $diff_svc;

	protected function setUp(): void {
		parent::setUp();
		$this->diff_svc = new Profile_Snapshot_Diff_Service();
	}

	private function snapshot( array $brand = array(), array $business = array() ): Profile_Snapshot_Data {
		return new Profile_Snapshot_Data( 'snap_test', 'other', '', '2025-01-01 00:00:00', '1', $brand, $business, 'manual' );
	}

	public function test_no_diff_when_profiles_are_identical(): void {
		$brand    = array( 'name' => 'Acme', 'tagline' => 'Great' );
		$business = array( 'industry' => 'Tech' );
		$snap     = $this->snapshot( $brand, $business );
		$store    = new Fake_Profile_Store_For_Diff( $brand, $business ); // @phpstan-ignore-line
		$rows     = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$this->assertEmpty( $rows, 'No diff rows expected when snapshot matches current profile.' );
	}

	public function test_changed_field_is_included(): void {
		$snap  = $this->snapshot( array( 'name' => 'Old Name' ), array() );
		$store = new Fake_Profile_Store_For_Diff( array( 'name' => 'New Name' ), array() ); // @phpstan-ignore-line
		$rows  = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$this->assertCount( 1, $rows );
		$this->assertSame( 'name', $rows[0]['field'] );
		$this->assertTrue( $rows[0]['changed'] );
		$this->assertSame( 'Old Name', $rows[0]['snapshot_value'] );
		$this->assertSame( 'New Name', $rows[0]['current_value'] );
	}

	public function test_unchanged_field_omitted_by_default(): void {
		$brand = array( 'name' => 'Same', 'tagline' => 'changed' );
		$snap  = $this->snapshot( $brand, array() );
		$store = new Fake_Profile_Store_For_Diff( array( 'name' => 'Same', 'tagline' => 'different' ), array() ); // @phpstan-ignore-line
		$rows  = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$this->assertCount( 1, $rows );
		$this->assertSame( 'tagline', $rows[0]['field'] );
	}

	public function test_unchanged_field_included_when_flag_set(): void {
		$brand = array( 'name' => 'Same' );
		$snap  = $this->snapshot( $brand, array() );
		$store = new Fake_Profile_Store_For_Diff( $brand, array() ); // @phpstan-ignore-line
		$rows  = $this->diff_svc->diff( $snap, $store, true ); // @phpstan-ignore-line
		$this->assertCount( 1, $rows );
		$this->assertFalse( $rows[0]['changed'] );
	}

	public function test_array_field_compared_by_json_equality(): void {
		$personas = array( array( 'role' => 'Buyer' ) );
		$snap     = $this->snapshot( array(), array( 'personas' => $personas ) );
		$store    = new Fake_Profile_Store_For_Diff( array(), array( 'personas' => $personas ) ); // @phpstan-ignore-line
		$rows     = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$this->assertEmpty( $rows );
	}

	public function test_added_field_in_current_shows_as_changed(): void {
		$snap  = $this->snapshot( array(), array() );
		$store = new Fake_Profile_Store_For_Diff( array( 'tagline' => 'New!' ), array() ); // @phpstan-ignore-line
		$rows  = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$this->assertCount( 1, $rows );
		$this->assertSame( '—', $rows[0]['snapshot_value'] );
	}

	public function test_section_labels_are_correct(): void {
		$snap  = $this->snapshot( array( 'name' => 'A' ), array( 'industry' => 'X' ) );
		$store = new Fake_Profile_Store_For_Diff( array( 'name' => 'B' ), array( 'industry' => 'Y' ) ); // @phpstan-ignore-line
		$rows  = $this->diff_svc->diff( $snap, $store ); // @phpstan-ignore-line
		$sections = array_column( $rows, 'section' );
		$this->assertContains( 'brand_profile', $sections );
		$this->assertContains( 'business_profile', $sections );
	}

	public function test_summary_returns_counts(): void {
		$snap  = $this->snapshot( array( 'a' => '1', 'b' => '2' ), array() );
		$store = new Fake_Profile_Store_For_Diff( array( 'a' => '1', 'b' => '9' ), array() ); // @phpstan-ignore-line
		$sum   = $this->diff_svc->summary( $snap, $store ); // @phpstan-ignore-line
		$this->assertSame( 2, $sum['total'] );
		$this->assertSame( 1, $sum['changed'] );
		$this->assertContains( 'b', $sum['changed_fields'] );
	}
}
