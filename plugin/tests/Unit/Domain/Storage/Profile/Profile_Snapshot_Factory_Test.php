<?php
/**
 * Unit tests for Profile_Snapshot_Factory (v2-scope-backlog.md §3).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\Storage\Profile;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 5 );
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Factory.php';

// ---------------------------------------------------------------------------
// Minimal stubs: gmdate, random_bytes, bin2hex
// ---------------------------------------------------------------------------
namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\gmdate' ) ) {
	function gmdate( string $format, ?int $timestamp = null ): string {
		return \gmdate( $format, $timestamp );
	}
}

namespace AIOPageBuilder\Tests\Unit\Domain\Storage\Profile;

/**
 * Fake Profile_Store that returns controlled profile data.
 */
final class Fake_Profile_Store_For_Factory {
	public function get_full_profile(): array {
		return array(
			Profile_Schema::ROOT_BRAND    => array( 'name' => 'Test Brand', 'tagline' => 'Great tag' ),
			Profile_Schema::ROOT_BUSINESS => array( 'industry' => 'Tech', 'founded' => '2010' ),
			Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE => array(),
		);
	}
}

/**
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory
 */
final class Profile_Snapshot_Factory_Test extends TestCase {

	private function factory(): Profile_Snapshot_Factory {
		return new Profile_Snapshot_Factory( new Profile_Snapshot_Helper() );
	}

	private function fake_store(): Fake_Profile_Store_For_Factory {
		return new Fake_Profile_Store_For_Factory();
	}

	public function test_build_returns_profile_snapshot_data(): void {
		$store   = $this->fake_store();
		$factory = $this->factory();
		// * PHP type system doesn't enforce Profile_Store; we pass a duck-type compatible stub.
		$snap = $factory->build( $store, 'manual' ); // @phpstan-ignore-line
		$this->assertInstanceOf( Profile_Snapshot_Data::class, $snap );
	}

	public function test_snapshot_id_is_non_empty_string(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertNotEmpty( $snap->snapshot_id );
	}

	public function test_snapshot_id_starts_with_snap_prefix(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertStringStartsWith( 'snap_', $snap->snapshot_id );
	}

	public function test_two_builds_produce_distinct_ids(): void {
		$factory = $this->factory();
		$snap_a  = $factory->build( $this->fake_store() ); // @phpstan-ignore-line
		$snap_b  = $factory->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertNotSame( $snap_a->snapshot_id, $snap_b->snapshot_id );
	}

	public function test_brand_profile_contains_store_data(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertSame( 'Test Brand', $snap->brand_profile['name'] ?? null );
	}

	public function test_business_profile_contains_store_data(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertSame( 'Tech', $snap->business_profile['industry'] ?? null );
	}

	public function test_source_is_propagated(): void {
		$snap = $this->factory()->build( $this->fake_store(), 'onboarding_completion' ); // @phpstan-ignore-line
		$this->assertSame( 'onboarding_completion', $snap->source );
	}

	public function test_scope_type_and_id_are_propagated(): void {
		$snap = $this->factory()->build( $this->fake_store(), 'manual', 'ai_run', 'run-ref-001' ); // @phpstan-ignore-line
		$this->assertSame( 'ai_run', $snap->scope_type );
		$this->assertSame( 'run-ref-001', $snap->scope_id );
	}

	public function test_profile_schema_version_is_set(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$this->assertNotEmpty( $snap->profile_schema_version );
	}

	public function test_snapshot_does_not_include_prohibited_keys(): void {
		$snap = $this->factory()->build( $this->fake_store() ); // @phpstan-ignore-line
		$prohibited = array( 'password', 'api_key', 'bearer_token', 'secret' );
		foreach ( $prohibited as $key ) {
			$this->assertArrayNotHasKey( $key, $snap->brand_profile );
			$this->assertArrayNotHasKey( $key, $snap->business_profile );
		}
	}
}
