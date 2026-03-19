<?php
/**
 * Unit tests for Build Plan analytics: trend aggregation, date-range filter, blocker grouping, redacted outputs (spec §30, §45, §49.11; Prompt 129).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_Analytics_Service.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';

class Build_Plan_Analytics_Stub_Snapshot_Repository implements \AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface {

	/** @var array<string, array<int, array{post_snapshot_id: string, pre_snapshot_id: string, action_type: string, target_ref: string, created_at: string}>> */
	private $by_plan_id;

	public function __construct( array $by_plan_id ) {
		$this->by_plan_id = $by_plan_id;
	}

	public function save( array $snapshot ): bool {
		return false;
	}

	public function get_by_id( string $snapshot_id ): ?array {
		return null;
	}

	public function list_snapshot_created_times_for_target( string $target_ref ): array {
		return array();
	}

	public function list_rollback_entries_for_plan( string $plan_id ): array {
		return $this->by_plan_id[ $plan_id ] ?? array();
	}

	public function list_post_change_snapshots_for_period( ?string $date_from = null, ?string $date_to = null ): array {
		return array();
	}
}

/**
 * Stub list provider that returns a fixed list of plans for analytics tests.
 */
class Build_Plan_Analytics_Stub_Repository implements \AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface {

	/** @var array<int, array<string, mixed>> */
	private $plans;

	public function __construct( array $plans ) {
		$this->plans = $plans;
	}

	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		return array_slice( $this->plans, $offset, $limit > 0 ? $limit : count( $this->plans ) );
	}
}

final class Build_Plan_Analytics_Service_Test extends TestCase {

	private function make_plan( string $post_date, string $root_status, array $items_with_status ): array {
		$steps = array(
			array(
				Build_Plan_Item_Schema::KEY_STEP_ID   => 'step1',
				Build_Plan_Item_Schema::KEY_STEP_TYPE => 'existing_page_changes',
				Build_Plan_Item_Schema::KEY_TITLE     => 'Step 1',
				Build_Plan_Item_Schema::KEY_ORDER     => 1,
				Build_Plan_Item_Schema::KEY_ITEMS     => array_map(
					function ( $s ) {
						return array(
							Build_Plan_Item_Schema::KEY_ITEM_ID => 'item-' . uniqid( '', true ),
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => 'existing_page_change',
							Build_Plan_Item_Schema::KEY_STATUS => $s,
							Build_Plan_Item_Schema::KEY_PAYLOAD => array(),
						);
					},
					$items_with_status
				),
			),
		);
		return array(
			'id'                           => 1,
			'post_date'                    => $post_date,
			Build_Plan_Schema::KEY_PLAN_ID => 'plan-' . uniqid( '', true ),
			Build_Plan_Schema::KEY_STATUS  => $root_status,
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	public function test_plan_review_trends_aggregates_by_root_status(): void {
		$plans  = array(
			$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_APPROVED, array( 'approved' ) ),
			$this->make_plan( '2025-03-02 10:00:00', Build_Plan_Statuses::ROOT_REJECTED, array( 'rejected' ) ),
			$this->make_plan( '2025-03-03 10:00:00', Build_Plan_Statuses::ROOT_PENDING_REVIEW, array( 'pending' ) ),
		);
		$repo   = new Build_Plan_Analytics_Stub_Repository( $plans );
		$svc    = new Build_Plan_Analytics_Service( $repo );
		$trends = $svc->get_plan_review_trends( null, null );
		$this->assertSame( 3, $trends['total_plans'] );
		$this->assertSame( 1, $trends['approval_count'] );
		$this->assertSame( 1, $trends['rejection_count'] );
		$this->assertSame( 0.5, $trends['approval_rate'] );
		$this->assertSame( 0.5, $trends['denial_rate'] );
		$this->assertArrayHasKey( 'by_status', $trends );
		$this->assertSame( 1, $trends['by_status'][ Build_Plan_Statuses::ROOT_APPROVED ] ?? 0 );
		$this->assertSame( 1, $trends['by_status'][ Build_Plan_Statuses::ROOT_REJECTED ] ?? 0 );
	}

	public function test_date_range_filtering_limits_plans(): void {
		$plans  = array(
			$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_APPROVED, array() ),
			$this->make_plan( '2025-03-15 10:00:00', Build_Plan_Statuses::ROOT_APPROVED, array() ),
			$this->make_plan( '2025-03-30 10:00:00', Build_Plan_Statuses::ROOT_PENDING_REVIEW, array() ),
		);
		$repo   = new Build_Plan_Analytics_Stub_Repository( $plans );
		$svc    = new Build_Plan_Analytics_Service( $repo );
		$trends = $svc->get_plan_review_trends( '2025-03-10', '2025-03-20' );
		$this->assertSame( 1, $trends['total_plans'] );
		$this->assertSame( '2025-03-10', $trends['date_from'] );
		$this->assertSame( '2025-03-20', $trends['date_to'] );
	}

	public function test_common_blockers_groups_rejected_and_failed_by_item_type(): void {
		$plans    = array(
			$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_COMPLETED, array( 'rejected', 'rejected', 'failed' ) ),
		);
		$repo     = new Build_Plan_Analytics_Stub_Repository( $plans );
		$svc      = new Build_Plan_Analytics_Service( $repo );
		$blockers = $svc->get_common_blockers( null, null, 10 );
		$this->assertArrayHasKey( 'blockers', $blockers );
		$this->assertSame( 2, $blockers['total_rejected'] );
		$this->assertSame( 1, $blockers['total_failed'] );
		$this->assertNotEmpty( $blockers['blockers'] );
		$this->assertSame( 'existing_page_change', $blockers['blockers'][0]['category'] );
		$this->assertSame( 3, $blockers['blockers'][0]['count'] );
	}

	public function test_execution_failure_trends_counts_failed_items_by_type(): void {
		$plans  = array(
			$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_COMPLETED, array( 'failed', 'completed', 'failed' ) ),
		);
		$repo   = new Build_Plan_Analytics_Stub_Repository( $plans );
		$svc    = new Build_Plan_Analytics_Service( $repo );
		$trends = $svc->get_execution_failure_trends( null, null );
		$this->assertSame( 2, $trends['total_failed_items'] );
		$this->assertArrayHasKey( 'failures_by_item_type', $trends );
		$this->assertSame( 2, $trends['failures_by_item_type']['existing_page_change'] ?? 0 );
	}

	public function test_rollback_frequency_summary_returns_stable_structure(): void {
		$repo    = new Build_Plan_Analytics_Stub_Repository( array() );
		$svc     = new Build_Plan_Analytics_Service( $repo );
		$summary = $svc->get_rollback_frequency_summary( null, null );
		$this->assertArrayHasKey( 'total_rollbacks', $summary );
		$this->assertArrayHasKey( 'by_month', $summary );
		$this->assertArrayHasKey( 'source', $summary );
		$this->assertSame( 0, $summary['total_rollbacks'] );
		$this->assertSame( 'plan_analytics_only', $summary['source'] );
	}

	public function test_rollback_frequency_summary_uses_operational_snapshots_when_available(): void {
		$plan   = $this->make_plan( '2025-03-15 10:00:00', Build_Plan_Statuses::ROOT_COMPLETED, array() );
		$repo   = new Build_Plan_Analytics_Stub_Repository( array( $plan ) );
		$snaps  = new Build_Plan_Analytics_Stub_Snapshot_Repository(
			array(
				$plan[ Build_Plan_Schema::KEY_PLAN_ID ] => array(
					array(
						'post_snapshot_id' => 'post_1',
						'pre_snapshot_id'  => 'pre_1',
						'action_type'      => 'replace_page',
						'target_ref'       => 'post:123',
						'created_at'       => '2025-03-16 12:00:00',
					),
					array(
						'post_snapshot_id' => 'post_2',
						'pre_snapshot_id'  => 'pre_2',
						'action_type'      => 'token_change',
						'target_ref'       => 'tokens:global',
						'created_at'       => '2025-03-20 12:00:00',
					),
				),
			)
		);
		$svc    = new Build_Plan_Analytics_Service( $repo, $snaps );
		$out    = $svc->get_rollback_frequency_summary( '2025-03-01', '2025-03-31' );
		$this->assertSame( 'operational_snapshots', $out['source'] );
		$this->assertSame( 2, $out['total_rollbacks'] );
		$this->assertNotEmpty( $out['by_month'] );
		$this->assertSame( '2025-03', $out['by_month'][0]['month'] );
		$this->assertSame( 2, $out['by_month'][0]['count'] );
	}

	public function test_analytics_summary_returns_redacted_payloads_no_raw_secrets(): void {
		$repo    = new Build_Plan_Analytics_Stub_Repository(
			array(
				$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_APPROVED, array( 'approved' ) ),
			)
		);
		$svc     = new Build_Plan_Analytics_Service( $repo );
		$summary = $svc->get_analytics_summary( null, null );
		$this->assertArrayHasKey( 'plan_review_trends', $summary );
		$this->assertArrayHasKey( 'common_blockers', $summary );
		$this->assertArrayHasKey( 'execution_failure_trends', $summary );
		$this->assertArrayHasKey( 'rollback_frequency_summary', $summary );
		foreach ( $summary as $key => $payload ) {
			$this->assertIsArray( $payload, "Payload $key must be array" );
			$this->assertArrayNotHasKey( 'api_key', $payload );
			$this->assertArrayNotHasKey( 'secret', $payload );
		}
	}

	/**
	 * Example analytics summary payload (spec §45, §49.11; Prompt 129). No pseudocode.
	 */
	public function test_example_analytics_summary_payload(): void {
		$repo    = new Build_Plan_Analytics_Stub_Repository(
			array(
				$this->make_plan( '2025-03-01 10:00:00', Build_Plan_Statuses::ROOT_APPROVED, array( 'approved', 'completed' ) ),
				$this->make_plan( '2025-03-02 10:00:00', Build_Plan_Statuses::ROOT_REJECTED, array( 'rejected' ) ),
			)
		);
		$svc     = new Build_Plan_Analytics_Service( $repo );
		$summary = $svc->get_analytics_summary( '2025-03-01', '2025-03-31' );
		$example = array(
			'plan_review_trends'         => array(
				'total_plans'     => 2,
				'by_status'       => array(
					'pending_review' => 0,
					'approved'       => 1,
					'rejected'       => 1,
					'in_progress'    => 0,
					'completed'      => 0,
					'superseded'     => 0,
				),
				'approval_count'  => 1,
				'rejection_count' => 1,
				'approval_rate'   => 0.5,
				'denial_rate'     => 0.5,
				'date_from'       => '2025-03-01',
				'date_to'         => '2025-03-31',
			),
			'common_blockers'            => array(
				'blockers'       => array(
					array(
						'category' => 'existing_page_change',
						'count'    => 1,
					),
				),
				'total_rejected' => 1,
				'total_failed'   => 0,
				'date_from'      => '2025-03-01',
				'date_to'        => '2025-03-31',
			),
			'execution_failure_trends'   => array(
				'failures_by_item_type' => array(),
				'total_failed_items'    => 0,
				'date_from'             => '2025-03-01',
				'date_to'               => '2025-03-31',
			),
			'rollback_frequency_summary' => array(
				'total_rollbacks' => 0,
				'by_month'        => array(),
				'date_from'       => '2025-03-01',
				'date_to'         => '2025-03-31',
				'source'          => 'plan_analytics_only',
			),
		);
		$this->assertSame( $example['plan_review_trends']['total_plans'], $summary['plan_review_trends']['total_plans'] );
		$this->assertSame( $example['plan_review_trends']['approval_count'], $summary['plan_review_trends']['approval_count'] );
		$this->assertSame( $example['plan_review_trends']['rejection_count'], $summary['plan_review_trends']['rejection_count'] );
		$this->assertSame( $example['rollback_frequency_summary']['source'], $summary['rollback_frequency_summary']['source'] );
	}
}
