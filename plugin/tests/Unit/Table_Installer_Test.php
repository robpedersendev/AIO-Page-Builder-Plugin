<?php
/**
 * Unit tests for Table_Installer: install_or_upgrade, schema version recording, failure reporting (spec §11, §53.1).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Tables\DbDelta_Runner;
use AIOPageBuilder\Domain\Storage\Tables\Table_Installer;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use AIOPageBuilder\Domain\Storage\Migrations\Schema_Version_Tracker;
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
require_once $plugin_root . '/src/Domain/Storage/Migrations/Schema_Version_Tracker.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Schema_Definitions.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/DbDelta_Runner.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Installer.php';

final class Table_Installer_Test extends TestCase {

	private object $wpdb_stub;
	private Settings_Service $settings;
	private Schema_Version_Tracker $tracker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->wpdb_stub = $this->create_wpdb_stub( true );
		$this->settings  = new Settings_Service();
		$this->tracker   = new Schema_Version_Tracker( $this->settings );
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	private function create_wpdb_stub( bool $table_exists ): object {
		$prefix = 'wp_';
		return new class( $prefix, $table_exists ) {
			public string $prefix;
			private bool $table_exists;

			public function __construct( string $prefix, bool $table_exists ) {
				$this->prefix       = $prefix;
				$this->table_exists = $table_exists;
			}

			public function get_charset_collate(): string {
				return '';
			}

			public function prepare( string $query, ...$args ): string {
				return $query;
			}

			public function get_var( string $query ): ?string {
				return $this->table_exists ? $this->prefix . 'aio_crawl_snapshots' : null;
			}

			public function suppress_errors( bool $suppress = true ): void {
			}

			public string $last_error = '';
		};
	}

	public function test_install_or_upgrade_records_schema_version_on_success(): void {
		$runner   = new DbDelta_Runner();
		$installer = new Table_Installer( $this->wpdb_stub, $runner, $this->tracker );
		$result   = $installer->install_or_upgrade();
		$this->assertTrue( $result['success'], $result['message'] );
		$this->assertNull( $result['failed_table'] );
		$versions = $this->tracker->get_installed_versions();
		$this->assertSame( Versions::table_schema(), $versions['table_schema'] );
	}

	public function test_install_or_upgrade_returns_failure_when_runner_fails(): void {
		$this->wpdb_stub->last_error = 'Simulated DB error.';
		$runner   = new DbDelta_Runner();
		$installer = new Table_Installer( $this->wpdb_stub, $runner, $this->tracker );
		$result   = $installer->install_or_upgrade();
		$this->assertFalse( $result['success'] );
		$this->assertNotEmpty( $result['failed_table'] );
		$this->assertStringContainsString( 'Simulated', $result['message'] );
		$versions = $this->tracker->get_installed_versions();
		$this->assertSame( '0', $versions['table_schema'] );
	}

	public function test_get_missing_tables_returns_suffixes_when_tables_absent(): void {
		$this->wpdb_stub = $this->create_wpdb_stub( false );
		$runner   = new DbDelta_Runner();
		$installer = new Table_Installer( $this->wpdb_stub, $runner, $this->tracker );
		$missing  = $installer->get_missing_tables();
		$this->assertCount( 8, $missing );
		$this->assertContains( Table_Names::CRAWL_SNAPSHOTS, $missing );
	}

	public function test_table_exists_returns_false_when_get_var_null(): void {
		$this->wpdb_stub = $this->create_wpdb_stub( false );
		$installer = new Table_Installer( $this->wpdb_stub, new DbDelta_Runner(), $this->tracker );
		$this->assertFalse( $installer->table_exists( Table_Names::JOB_QUEUE ) );
	}

	public function test_table_exists_returns_true_when_get_var_returns_table_name(): void {
		$this->wpdb_stub = $this->create_wpdb_stub( true );
		$installer = new Table_Installer( $this->wpdb_stub, new DbDelta_Runner(), $this->tracker );
		$this->assertTrue( $installer->table_exists( Table_Names::CRAWL_SNAPSHOTS ) );
	}
}
