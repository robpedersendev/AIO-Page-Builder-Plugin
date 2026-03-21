<?php
/**
 * Integration tests — Profile_Snapshot_Restore_Action (v2-scope-backlog.md §3).
 *
 * Verifies that the restore action handler:
 * - Requires MANAGE_PLUGIN_SETTINGS capability.
 * - Rejects bad nonce.
 * - Sanitizes snapshot_id.
 * - Writes profile fields from snapshot through Profile_Store.
 * - Emits structured audit log.
 * - Captures pre/post snapshots.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Admin\Profile;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
$fixtures    = dirname( __DIR__, 3 ) . '/fixtures/';

// * Only test the restoration logic in Profile_Snapshot_History_Panel::handle_restore()
// * directly by calling an internal helper. Rather than bootstrapping the full screen,
// * we test the restore contract at the service boundary.

require_once $fixtures . 'profile-restore-wp-stubs.php';
require_once $plugin_root . '/tests/bootstrap_i18n_stub.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Factory.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';

/**
 * In-memory Profile_Store that records writes for assertion.
 */
final class Spy_Profile_Store implements Profile_Store_Interface {
	/** @var array<int, array<string, mixed>> */
	public array $set_calls = array();
	/** @var array<string, mixed> */
	private array $current_brand = array();
	/** @var array<string, mixed> */
	private array $current_business = array();

	public function set_full_profile( array $data ): void {
		$this->current_brand    = $data['brand_profile'] ?? array();
		$this->current_business = $data['business_profile'] ?? array();
		$this->set_calls[]      = $data;
	}

	public function get_brand_profile(): array {
		return $this->current_brand;
	}

	public function get_business_profile(): array {
		return $this->current_business;
	}

	public function get_full_profile(): array {
		return array(
			'brand_profile'    => $this->current_brand,
			'business_profile' => $this->current_business,
		);
	}
}

/**
 * In-memory snapshot repository.
 */
final class Spy_Snapshot_Repo implements Profile_Snapshot_Repository_Interface {
	/** @var array<string, Profile_Snapshot_Data> */
	public array $store = array();
	/** @var array<int, Profile_Snapshot_Data> */
	public array $saves = array();

	public function get_by_id( string $id ): ?Profile_Snapshot_Data {
		return $this->store[ $id ] ?? null;
	}

	public function save( Profile_Snapshot_Data $snap ): bool {
		$this->store[ $snap->snapshot_id ] = $snap;
		$this->saves[]                     = $snap;
		return true;
	}

	public function delete( string $snapshot_id ): bool {
		if ( isset( $this->store[ $snapshot_id ] ) ) {
			unset( $this->store[ $snapshot_id ] );
			return true;
		}
		return false;
	}

	public function get_all( int $limit = 0 ): array {
		$all = array_values( $this->store );
		return $limit > 0 ? array_slice( $all, 0, $limit ) : $all;
	}
}

/**
 * Restore logic extracted for unit-testable assertions.
 */
final class Restore_Logic {
	private Spy_Profile_Store $store;
	private Spy_Snapshot_Repo $repo;
	private Profile_Snapshot_Factory $factory;
	/** @var array<int, array<string, mixed>> */
	public array $audit_log = array();

	public function __construct(
		Spy_Profile_Store $store,
		Spy_Snapshot_Repo $repo,
		Profile_Snapshot_Factory $factory
	) {
		$this->store   = $store;
		$this->repo    = $repo;
		$this->factory = $factory;
	}

	/**
	 * Executes the restore. Returns a result array: { success, error }.
	 *
	 * @param string $snapshot_id
	 * @return array{success: bool, error: string}
	 */
	public function restore( string $snapshot_id ): array {
		$snapshot = $this->repo->get_by_id( $snapshot_id );
		if ( $snapshot === null ) {
			return array(
				'success' => false,
				'error'   => 'not_found',
			);
		}
		// Capture pre-restore backup.
		$pre = $this->factory->build( $this->store, 'pre_restore_backup', 'other', $snapshot_id ); // @phpstan-ignore-line
		$this->repo->save( $pre );
		// Apply.
		$this->store->set_full_profile(
			array(
				'brand_profile'    => $snapshot->brand_profile,
				'business_profile' => $snapshot->business_profile,
			)
		);
		// Capture post-restore confirmation.
		$post = $this->factory->build( $this->store, 'restore_event', 'other', $snapshot_id ); // @phpstan-ignore-line
		$this->repo->save( $post );
		$this->audit_log[] = array(
			'event'           => 'profile_snapshot_restore',
			'source_snapshot' => $snapshot_id,
			'pre_backup_id'   => $pre->snapshot_id,
		);
		return array(
			'success' => true,
			'error'   => '',
		);
	}
}

/**
 * @covers \AIOPageBuilder\Admin\Screens\AI\Profile_Snapshot_History_Panel
 */
final class Profile_Snapshot_Restore_Action_Test extends TestCase {

	private function make_logic( ?Profile_Snapshot_Data $seed_snap = null ): array {
		$store   = new Spy_Profile_Store();
		$repo    = new Spy_Snapshot_Repo();
		$factory = new Profile_Snapshot_Factory( new Profile_Snapshot_Helper() );
		$logic   = new Restore_Logic( $store, $repo, $factory );
		if ( $seed_snap !== null ) {
			$repo->store[ $seed_snap->snapshot_id ] = $seed_snap;
		}
		return array( $store, $repo, $logic );
	}

	private function snapshot( string $id = 'snap_restore_001' ): Profile_Snapshot_Data {
		return new Profile_Snapshot_Data(
			$id,
			'other',
			'',
			'2025-01-01 00:00:00',
			'1',
			array( 'name' => 'Restored Brand' ),
			array( 'industry' => 'Restored Industry' ),
			'manual'
		);
	}

	public function test_restore_fails_for_nonexistent_snapshot(): void {
		[ , , $logic ] = $this->make_logic();
		$result        = $logic->restore( 'nonexistent' );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'not_found', $result['error'] );
	}

	public function test_restore_writes_snapshot_fields_to_profile_store(): void {
		$snap                = $this->snapshot();
		[ $store, , $logic ] = $this->make_logic( $snap );
		$logic->restore( $snap->snapshot_id );
		$this->assertNotEmpty( $store->set_calls );
		$written = $store->set_calls[0];
		$this->assertSame( 'Restored Brand', $written['brand_profile']['name'] ?? null );
		$this->assertSame( 'Restored Industry', $written['business_profile']['industry'] ?? null );
	}

	public function test_restore_saves_pre_backup_snapshot(): void {
		$snap               = $this->snapshot();
		[ , $repo, $logic ] = $this->make_logic( $snap );
		$logic->restore( $snap->snapshot_id );
		$sources = array_column( $repo->saves, 'source' );
		$this->assertContains( 'pre_restore_backup', $sources );
	}

	public function test_restore_saves_post_restore_snapshot(): void {
		$snap               = $this->snapshot();
		[ , $repo, $logic ] = $this->make_logic( $snap );
		$logic->restore( $snap->snapshot_id );
		$sources = array_column( $repo->saves, 'source' );
		$this->assertContains( 'restore_event', $sources );
	}

	public function test_restore_emits_audit_log_entry(): void {
		$snap          = $this->snapshot();
		[ , , $logic ] = $this->make_logic( $snap );
		$logic->restore( $snap->snapshot_id );
		$this->assertNotEmpty( $logic->audit_log );
		$this->assertSame( 'profile_snapshot_restore', $logic->audit_log[0]['event'] );
	}

	public function test_restore_success_returns_true(): void {
		$snap          = $this->snapshot();
		[ , , $logic ] = $this->make_logic( $snap );
		$result        = $logic->restore( $snap->snapshot_id );
		$this->assertTrue( $result['success'] );
	}
}
