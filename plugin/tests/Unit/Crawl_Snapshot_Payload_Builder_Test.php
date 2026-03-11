<?php
/**
 * Unit tests for Crawl_Snapshot_Payload_Builder: session and page payload structure, status normalization (spec §11.1, §24.15, Prompt 050).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';

final class Crawl_Snapshot_Payload_Builder_Test extends TestCase {

	public function test_build_session_payload_contains_required_keys(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_session_payload(
			'run-1',
			'example.com',
			'2025-01-15T10:00:00Z',
			null,
			array(),
			0,
			0,
			0,
			0,
			Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING
		);
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::SESSION_CRAWL_RUN_ID, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::SESSION_SITE_HOST, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::SESSION_STARTED_AT, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::SESSION_FINAL_STATUS, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::SESSION_SCHEMA_VERSION, $payload );
		$this->assertSame( '1', $payload[ Crawl_Snapshot_Payload_Builder::SESSION_SCHEMA_VERSION ] );
		$this->assertSame( 'run-1', $payload[ Crawl_Snapshot_Payload_Builder::SESSION_CRAWL_RUN_ID ] );
		$this->assertSame( 'example.com', $payload[ Crawl_Snapshot_Payload_Builder::SESSION_SITE_HOST ] );
	}

	public function test_build_session_payload_normalizes_invalid_final_status_to_running(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_session_payload(
			'run-2',
			'example.com',
			null,
			null,
			array(),
			0,
			0,
			0,
			0,
			'invalid_status'
		);
		$this->assertSame( Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING, $payload[ Crawl_Snapshot_Payload_Builder::SESSION_FINAL_STATUS ] );
	}

	public function test_build_page_payload_contains_required_keys(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload( 'run-1', 'https://example.com/page' );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_RUN_ID, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::PAGE_URL, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS, $payload );
		$this->assertArrayHasKey( Crawl_Snapshot_Payload_Builder::PAGE_SCHEMA_VERSION, $payload );
		$this->assertSame( 'run-1', $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_RUN_ID ] );
		$this->assertSame( 'https://example.com/page', $payload[ Crawl_Snapshot_Payload_Builder::PAGE_URL ] );
		$this->assertSame( Crawl_Snapshot_Payload_Builder::STATUS_PENDING, $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS ] );
		$this->assertSame( '1', $payload[ Crawl_Snapshot_Payload_Builder::PAGE_SCHEMA_VERSION ] );
	}

	public function test_build_page_payload_returns_empty_for_empty_run_id(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload( '', 'https://example.com/' );
		$this->assertEmpty( $payload );
	}

	public function test_build_page_payload_returns_empty_for_empty_url(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload( 'run-1', '' );
		$this->assertEmpty( $payload );
	}

	public function test_build_page_payload_applies_overrides(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload(
			'run-1',
			'https://example.com/',
			array(
				Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT => 'Home',
				Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS   => Crawl_Snapshot_Payload_Builder::STATUS_COMPLETED,
				Crawl_Snapshot_Payload_Builder::PAGE_ERROR_STATE    => null,
			)
		);
		$this->assertSame( 'Home', $payload[ Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT ] );
		$this->assertSame( Crawl_Snapshot_Payload_Builder::STATUS_COMPLETED, $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS ] );
	}

	public function test_build_page_payload_normalizes_invalid_status_to_pending(): void {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload(
			'run-1',
			'https://example.com/',
			array( Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS => 'invalid' )
		);
		$this->assertSame( Crawl_Snapshot_Payload_Builder::STATUS_PENDING, $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS ] );
	}

	public function test_get_allowed_page_statuses(): void {
		$statuses = Crawl_Snapshot_Payload_Builder::get_allowed_page_statuses();
		$this->assertContains( Crawl_Snapshot_Payload_Builder::STATUS_PENDING, $statuses );
		$this->assertContains( Crawl_Snapshot_Payload_Builder::STATUS_COMPLETED, $statuses );
		$this->assertContains( Crawl_Snapshot_Payload_Builder::STATUS_ERROR, $statuses );
	}

	public function test_get_allowed_session_statuses(): void {
		$statuses = Crawl_Snapshot_Payload_Builder::get_allowed_session_statuses();
		$this->assertContains( Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING, $statuses );
		$this->assertContains( Crawl_Snapshot_Payload_Builder::SESSION_STATUS_COMPLETED, $statuses );
		$this->assertContains( Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED, $statuses );
	}
}
