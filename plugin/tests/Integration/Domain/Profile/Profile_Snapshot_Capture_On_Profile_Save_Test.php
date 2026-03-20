<?php
/**
 * Integration tests — profile snapshot capture on profile save (v2-scope-backlog.md §3).
 *
 * Verifies that merge_brand_profile() and merge_business_profile() fire the WordPress actions
 * that the capture service hooks into, and that snapshots are persisted after the merge.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Domain\Profile;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/tests/bootstrap_i18n_stub.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Factory.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Capture_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';

// ---------------------------------------------------------------------------
// WordPress action/filter stubs.
// ---------------------------------------------------------------------------
namespace AIOPageBuilder\Tests\Integration\Domain\Profile;

/** @var array<string, array<callable>> */
$GLOBALS['_test_wp_actions'] = array();

function do_action( string $hook, ...$args ): void {
	$callbacks = $GLOBALS['_test_wp_actions'][ $hook ] ?? array();
	foreach ( $callbacks as $cb ) {
		$cb( ...$args );
	}
}

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['_test_wp_actions'][ $hook ][] = $callback;
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
// Minimal WordPress option store backed by memory.
// ---------------------------------------------------------------------------
/** @var array<string, mixed> */
$GLOBALS['_test_options'] = array();

namespace AIOPageBuilder\Infrastructure\Settings;

if ( ! class_exists( 'AIOPageBuilder\Infrastructure\Settings\Settings_Service' ) ) {
	class Settings_Service {
		public function get( string $key ): mixed {
			return $GLOBALS['_test_options'][ $key ] ?? null;
		}

		public function set( string $key, mixed $value ): void {
			$GLOBALS['_test_options'][ $key ] = $value;
		}
	}
}

namespace AIOPageBuilder\Domain\Profile;

if ( ! class_exists( 'AIOPageBuilder\Domain\Profile\Template_Preference_Profile' ) ) {
	class Template_Preference_Profile {
		public function to_array(): array { return array(); }
		public static function from_array( array $a ): self { return new self(); }
	}
}

namespace AIOPageBuilder\Infrastructure\Config;

if ( ! class_exists( 'AIOPageBuilder\Infrastructure\Config\Option_Names' ) ) {
	class Option_Names {
		public const PROFILE_CURRENT = 'aio_page_builder_profile_current';
	}
}

namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		\AIOPageBuilder\Tests\Integration\Domain\Profile\do_action( $hook, ...$args );
	}
}

namespace AIOPageBuilder\Tests\Integration\Domain\Profile;

require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store.php';

// ---------------------------------------------------------------------------
// In-memory snapshot repository for test assertions.
// ---------------------------------------------------------------------------
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Capture_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

final class In_Memory_Snapshot_Repo {
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
}

/**
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Capture_Service
 * @covers \AIOPageBuilder\Domain\Storage\Profile\Profile_Store
 */
final class Profile_Snapshot_Capture_On_Profile_Save_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_test_wp_actions'] = array();
		$GLOBALS['_test_options']    = array();
	}

	private function make_store(): Profile_Store {
		return new Profile_Store( new Settings_Service(), new Profile_Normalizer() );
	}

	private function make_capture_service( In_Memory_Snapshot_Repo $repo ): Profile_Snapshot_Capture_Service {
		$factory = new Profile_Snapshot_Factory( new Profile_Snapshot_Helper() );
		return new Profile_Snapshot_Capture_Service( $factory, $repo ); // @phpstan-ignore-line
	}

	public function test_merge_brand_profile_fires_action(): void {
		$fired = false;
		add_action( 'aio_pb_brand_profile_merged', function () use ( &$fired ): void {
			$fired = true;
		} );
		$store = $this->make_store();
		$store->merge_brand_profile( array( 'name' => 'Acme' ) );
		$this->assertTrue( $fired, 'aio_pb_brand_profile_merged action must be fired.' );
	}

	public function test_merge_business_profile_fires_action(): void {
		$fired = false;
		add_action( 'aio_pb_business_profile_merged', function () use ( &$fired ): void {
			$fired = true;
		} );
		$store = $this->make_store();
		$store->merge_business_profile( array( 'industry' => 'Tech' ) );
		$this->assertTrue( $fired, 'aio_pb_business_profile_merged action must be fired.' );
	}

	public function test_capture_service_saves_snapshot_on_brand_merge(): void {
		$repo    = new In_Memory_Snapshot_Repo();
		$capture = $this->make_capture_service( $repo );
		$capture->register_hooks();
		$store = $this->make_store();
		$store->merge_brand_profile( array( 'name' => 'Acme' ) );
		$this->assertCount( 1, $repo->saved );
		$this->assertSame( 'brand_profile_merge', $repo->saved[0]->source );
	}

	public function test_capture_service_saves_snapshot_on_business_merge(): void {
		$repo    = new In_Memory_Snapshot_Repo();
		$capture = $this->make_capture_service( $repo );
		$capture->register_hooks();
		$store = $this->make_store();
		$store->merge_business_profile( array( 'industry' => 'SaaS' ) );
		$this->assertCount( 1, $repo->saved );
		$this->assertSame( 'business_profile_merge', $repo->saved[0]->source );
	}

	public function test_snapshot_taken_after_sanitized_merge_state_is_established(): void {
		$repo    = new In_Memory_Snapshot_Repo();
		$capture = $this->make_capture_service( $repo );
		$capture->register_hooks();
		$store = $this->make_store();
		$store->merge_brand_profile( array( 'name' => 'Post-merge Brand' ) );
		$snap = $repo->saved[0] ?? null;
		$this->assertNotNull( $snap );
		// * The snapshot must reflect post-merge state, not the old state.
		$this->assertSame( 'Post-merge Brand', $snap->brand_profile['name'] ?? null );
	}
}
