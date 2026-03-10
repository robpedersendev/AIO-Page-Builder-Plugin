<?php
/**
 * Unit tests for Table_Names: stable suffixes, full_name (spec §11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';

final class Table_Names_Test extends TestCase {

	public function test_all_returns_eight_manifest_tables(): void {
		$all = Table_Names::all();
		$this->assertCount( 8, $all );
		$this->assertContains( Table_Names::CRAWL_SNAPSHOTS, $all );
		$this->assertContains( Table_Names::AI_ARTIFACTS, $all );
		$this->assertContains( Table_Names::JOB_QUEUE, $all );
		$this->assertContains( Table_Names::EXECUTION_LOG, $all );
		$this->assertContains( Table_Names::ROLLBACK_RECORDS, $all );
		$this->assertContains( Table_Names::TOKEN_SETS, $all );
		$this->assertContains( Table_Names::ASSIGNMENT_MAPS, $all );
		$this->assertContains( Table_Names::REPORTING_RECORDS, $all );
	}

	public function test_suffixes_match_manifest(): void {
		$this->assertSame( 'aio_crawl_snapshots', Table_Names::CRAWL_SNAPSHOTS );
		$this->assertSame( 'aio_ai_artifacts', Table_Names::AI_ARTIFACTS );
		$this->assertSame( 'aio_job_queue', Table_Names::JOB_QUEUE );
		$this->assertSame( 'aio_execution_log', Table_Names::EXECUTION_LOG );
		$this->assertSame( 'aio_rollback_records', Table_Names::ROLLBACK_RECORDS );
		$this->assertSame( 'aio_token_sets', Table_Names::TOKEN_SETS );
		$this->assertSame( 'aio_assignment_maps', Table_Names::ASSIGNMENT_MAPS );
		$this->assertSame( 'aio_reporting_records', Table_Names::REPORTING_RECORDS );
	}

	public function test_full_name_uses_prefix(): void {
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$this->assertSame( 'wp_' . Table_Names::CRAWL_SNAPSHOTS, Table_Names::full_name( $wpdb, Table_Names::CRAWL_SNAPSHOTS ) );
		$this->assertSame( 'wp_aio_job_queue', Table_Names::full_name( $wpdb, Table_Names::JOB_QUEUE ) );
	}
}
