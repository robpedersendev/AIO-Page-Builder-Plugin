<?php
/**
 * Unit tests for Schema_Version_Tracker: no migration needed, pending detected, future schema, result recording.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Migrations\Migration_Contract;
use AIOPageBuilder\Domain\Storage\Migrations\Migration_Result;
use AIOPageBuilder\Domain\Storage\Migrations\Schema_Version_Tracker;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Migrations/Migration_Contract.php';
require_once $plugin_root . '/src/Domain/Storage/Migrations/Schema_Version_Tracker.php';

/**
 * Stub migration: applies when current is "0", moves to "1".
 */
final class Stub_Migration_Table_0_To_1 implements Migration_Contract {
	public function id(): string {
		return 'table_schema_0_to_1'; }
	public function version_key(): string {
		return 'table_schema'; }
	public function from_version(): string {
		return '0'; }
	public function to_version(): string {
		return '1'; }
	public function applies_to( string $current_installed_version ): bool {
		return $current_installed_version === '0';
	}
	public function run(): Migration_Result {
		return new Migration_Result( Migration_Result::STATUS_SUCCESS, 'OK', array(), true, $this->id() );
	}
}

/**
 * No migration needed, pending detected, future schema, record result.
 */
final class Schema_Version_Tracker_Test extends TestCase {

	private Settings_Service $settings;
	private Schema_Version_Tracker $tracker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->settings               = new Settings_Service();
		$this->tracker                = new Schema_Version_Tracker( $this->settings );
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	public function test_get_installed_versions_returns_zero_when_not_set(): void {
		$v = $this->tracker->get_installed_versions();
		$this->assertIsArray( $v );
		foreach ( Versions::version_keys() as $key ) {
			$this->assertArrayHasKey( $key, $v );
			$this->assertSame( '0', $v[ $key ], "Unset key {$key} should be 0" );
		}
	}

	public function test_set_installed_version_persists(): void {
		$this->tracker->set_installed_version( 'table_schema', '1' );
		$v = $this->tracker->get_installed_versions();
		$this->assertSame( '1', $v['table_schema'] );
	}

	public function test_no_migration_needed_when_already_at_version(): void {
		$this->tracker->set_installed_version( 'table_schema', '1' );
		$pending = $this->tracker->get_pending_migrations( array( new Stub_Migration_Table_0_To_1() ) );
		$this->assertCount( 0, $pending );
	}

	public function test_pending_migration_detected_when_at_from_version(): void {
		$this->tracker->set_installed_version( 'table_schema', '0' );
		$pending = $this->tracker->get_pending_migrations( array( new Stub_Migration_Table_0_To_1() ) );
		$this->assertCount( 1, $pending );
		$this->assertSame( 'table_schema_0_to_1', $pending[0]->id() );
	}

	public function test_unsupported_future_schema_detected(): void {
		$this->tracker->set_installed_version( 'table_schema', '99' );
		$this->assertTrue( $this->tracker->is_installed_version_future( 'table_schema' ) );
	}

	public function test_future_schema_false_when_installed_equals_code(): void {
		$code = Versions::table_schema();
		$this->tracker->set_installed_version( 'table_schema', $code );
		$this->assertFalse( $this->tracker->is_installed_version_future( 'table_schema' ) );
	}

	public function test_record_migration_result_stores_sanitized_data(): void {
		$result = new Migration_Result( Migration_Result::STATUS_FAILURE, 'Sanitized error', array(), false, 'mig_x' );
		$this->tracker->record_migration_result( 'mig_x', $result );
		$raw = $this->settings->get( Option_Names::VERSION_MARKERS );
		$this->assertArrayHasKey( '_migration_log', $raw );
		$this->assertArrayHasKey( 'mig_x', $raw['_migration_log'] );
		$this->assertSame( 'failure', $raw['_migration_log']['mig_x']['status'] );
		$this->assertSame( 'Sanitized error', $raw['_migration_log']['mig_x']['message'] );
		$this->assertFalse( $raw['_migration_log']['mig_x']['safe_retry'] );
	}
}
