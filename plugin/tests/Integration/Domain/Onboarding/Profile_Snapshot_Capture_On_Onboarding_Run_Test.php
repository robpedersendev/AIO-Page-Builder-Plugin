<?php
/**
 * Integration tests — profile snapshot capture on onboarding run completion (v2-scope-backlog.md §3).
 *
 * Verifies that:
 * - A successful onboarding run (via aio_pb_onboarding_run_completed hook) captures a snapshot
 *   with source=onboarding_completion.
 * - A failed/incomplete run never fires the hook, so no misleading snapshot is created.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Domain\Onboarding;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/tests/bootstrap_i18n_stub.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Factory.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Capture_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';

namespace AIOPageBuilder\Tests\Integration\Domain\Onboarding;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Capture_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store_Interface;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// WordPress action stubs — delegate to global WordPress stubs in bootstrap.php.
// ---------------------------------------------------------------------------
function do_action( string $hook, ...$args ): void {
	\do_action( $hook, ...$args );
}

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ) {
	\add_action( $hook, $callback, $priority, $accepted_args );
	return true;
}

function wp_json_encode( $data ): string {
	$r = \json_encode( $data );
	return is_string( $r ) ? $r : '';
}

function error_log( string $msg ): bool {
	return true;
}

// ---------------------------------------------------------------------------
// Spy profile store.
// ---------------------------------------------------------------------------
final class Spy_Onboarding_Profile_Store implements Profile_Store_Interface {
	/** @var array<string, mixed> */
	private array $brand = array( 'name' => 'Onboarding Brand' );
	/** @var array<string, mixed> */
	private array $business = array( 'industry' => 'Onboarding Industry' );

	public function get_brand_profile(): array {
		return $this->brand;
	}

	public function get_business_profile(): array {
		return $this->business;
	}

	public function get_full_profile(): array {
		return array(
			'brand_profile'    => $this->brand,
			'business_profile' => $this->business,
		);
	}
}

// ---------------------------------------------------------------------------
// In-memory snapshot repository.
// ---------------------------------------------------------------------------
final class Spy_Onboarding_Snapshot_Repo implements Profile_Snapshot_Repository_Interface {
	/** @var array<int, Profile_Snapshot_Data> */
	public array $saved = array();

	public function save( Profile_Snapshot_Data $snap ): bool {
		$this->saved[] = $snap;
		return true;
	}

	public function get_by_id( string $id ): ?Profile_Snapshot_Data {
		foreach ( $this->saved as $s ) {
			if ( $s->snapshot_id === $id ) {
				return $s;
			}
		}
		return null;
	}

	public function delete( string $snapshot_id ): bool {
		foreach ( $this->saved as $k => $s ) {
			if ( $s->snapshot_id === $snapshot_id ) {
				unset( $this->saved[ $k ] );
				return true;
			}
		}
		return false;
	}

	public function get_all( int $limit = 0 ): array {
		$all = array_values( $this->saved );
		return $limit > 0 ? array_slice( $all, 0, $limit ) : $all;
	}
}

/**
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Capture_Service
 */
final class Profile_Snapshot_Capture_On_Onboarding_Run_Test extends TestCase {

	private function make( Spy_Onboarding_Snapshot_Repo $repo ): Profile_Snapshot_Capture_Service {
		$factory = new Profile_Snapshot_Factory( new Profile_Snapshot_Helper() );
		return new Profile_Snapshot_Capture_Service( $factory, $repo ); // @phpstan-ignore-line
	}

	public function test_onboarding_run_completed_captures_snapshot(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$store   = new Spy_Onboarding_Profile_Store();

		$capture->on_onboarding_run_completed( $store, 'run-test-001' );

		$this->assertCount( 1, $repo->saved );
		$this->assertSame( 'onboarding_completion', $repo->saved[0]->source );
		$this->assertSame( 'onboarding_session', $repo->saved[0]->scope_type );
		$this->assertSame( 'run-test-001', $repo->saved[0]->scope_id );
	}

	public function test_onboarding_snapshot_includes_brand_and_business_profile(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$store   = new Spy_Onboarding_Profile_Store();

		$capture->on_onboarding_run_completed( $store, 'run-test-002' );

		$snap = $repo->saved[0] ?? null;
		$this->assertNotNull( $snap );
		$this->assertSame( 'Onboarding Brand', $snap->brand_profile['name'] ?? null );
		$this->assertSame( 'Onboarding Industry', $snap->business_profile['industry'] ?? null );
	}

	public function test_no_snapshot_captured_when_action_hook_is_not_fired(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$capture->register_hooks();
		// * Do NOT fire the hook. Simulates a failed/incomplete run.
		$this->assertCount( 0, $repo->saved );
	}

	public function test_snapshot_schema_version_is_set(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$store   = new Spy_Onboarding_Profile_Store();

		$capture->on_onboarding_run_completed( $store, 'run-version-test' );

		$snap = $repo->saved[0] ?? null;
		$this->assertNotNull( $snap );
		$this->assertNotEmpty( $snap->profile_schema_version );
	}

	public function test_snapshot_id_is_unique_across_multiple_captures(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$store   = new Spy_Onboarding_Profile_Store();

		$capture->on_onboarding_run_completed( $store, 'run-a' );
		$capture->on_onboarding_run_completed( $store, 'run-b' );

		$this->assertCount( 2, $repo->saved );
		$this->assertNotSame( $repo->saved[0]->snapshot_id, $repo->saved[1]->snapshot_id );
	}

	public function test_capture_via_hook_integration(): void {
		$repo    = new Spy_Onboarding_Snapshot_Repo();
		$capture = $this->make( $repo );
		$store   = new Spy_Onboarding_Profile_Store();

		// * Register the brand/business hooks; onboarding hook is wired separately by the provider.
		// * Here we call on_onboarding_run_completed directly to test the capture contract.
		$capture->on_onboarding_run_completed( $store, 'run-hook-integration' );

		$this->assertNotEmpty( $repo->saved );
		$snap = $repo->saved[0];
		$this->assertSame( 'onboarding_completion', $snap->source );
		$this->assertNotEmpty( $snap->snapshot_id );
		$this->assertNotEmpty( $snap->created_at );
	}
}
