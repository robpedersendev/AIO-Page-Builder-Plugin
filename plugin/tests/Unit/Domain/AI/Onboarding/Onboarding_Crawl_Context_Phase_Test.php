<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Onboarding;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Crawl_Context_Phase;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use PHPUnit\Framework\TestCase;

final class Onboarding_Crawl_Context_Phase_Test extends TestCase {

	public function test_none_when_no_run_id(): void {
		$out = Onboarding_Crawl_Context_Phase::summarize( array( 'latest_crawl_run_id' => null ), null );
		$this->assertSame( Onboarding_Crawl_Context_Phase::PHASE_NONE, $out['phase'] );
	}

	public function test_running_when_started_without_end(): void {
		$out = Onboarding_Crawl_Context_Phase::summarize(
			array(
				'latest_crawl_run_id'       => 'run-1',
				'latest_crawl_started_at'   => '2025-01-01T00:00:00Z',
				'latest_crawl_ended_at'     => '',
				'latest_crawl_final_status' => Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING,
			),
			null
		);
		$this->assertSame( Onboarding_Crawl_Context_Phase::PHASE_RUNNING, $out['phase'] );
	}

	public function test_completed_phase(): void {
		$out = Onboarding_Crawl_Context_Phase::summarize(
			array(
				'latest_crawl_run_id'           => 'run-2',
				'latest_crawl_started_at'       => '2025-01-01T00:00:00Z',
				'latest_crawl_ended_at'         => '2025-01-01T01:00:00Z',
				'latest_crawl_final_status'     => Crawl_Snapshot_Payload_Builder::SESSION_STATUS_COMPLETED,
				'latest_crawl_total_discovered' => 10,
				'latest_crawl_failed_count'     => 0,
			),
			null
		);
		$this->assertSame( Onboarding_Crawl_Context_Phase::PHASE_COMPLETED, $out['phase'] );
		$this->assertFalse( $out['is_stale'] );
	}
}
