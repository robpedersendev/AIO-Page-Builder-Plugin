<?php
/**
 * Unit tests for Table_Schema_Definitions: manifest-aligned definitions (spec §11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use AIOPageBuilder\Domain\Storage\Tables\Table_Schema_Definitions;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Schema_Definitions.php';

final class Table_Schema_Definitions_Test extends TestCase {

	private object $wpdb_stub;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb_stub = new class() {
			public string $prefix = 'wp_';
			public function get_charset_collate(): string {
				return 'DEFAULT CHARSET utf8mb4';
			}
		};
	}

	public function test_get_definitions_returns_eight_tables(): void {
		$defs = Table_Schema_Definitions::get_definitions( $this->wpdb_stub );
		$this->assertCount( 8, $defs );
	}

	public function test_each_definition_has_name_and_sql(): void {
		$defs = Table_Schema_Definitions::get_definitions( $this->wpdb_stub );
		foreach ( $defs as $def ) {
			$this->assertArrayHasKey( 'name', $def );
			$this->assertArrayHasKey( 'sql', $def );
			$this->assertStringContainsString( 'CREATE TABLE', $def['sql'] );
			$this->assertStringContainsString( $def['name'], $def['sql'] );
		}
	}

	public function test_table_names_use_prefix(): void {
		$defs = Table_Schema_Definitions::get_definitions( $this->wpdb_stub );
		$names = array_column( $defs, 'name' );
		$this->assertContains( 'wp_' . Table_Names::CRAWL_SNAPSHOTS, $names );
		$this->assertContains( 'wp_' . Table_Names::REPORTING_RECORDS, $names );
	}

	public function test_sql_contains_primary_key_and_schema_version(): void {
		$defs = Table_Schema_Definitions::get_definitions( $this->wpdb_stub );
		foreach ( $defs as $def ) {
			$this->assertMatchesRegularExpression( '/PRIMARY\s+KEY/i', $def['sql'], "Definition for {$def['name']} must have PRIMARY KEY" );
			$this->assertStringContainsString( 'schema_version', $def['sql'], "Definition for {$def['name']} must have schema_version column" );
		}
	}
}
