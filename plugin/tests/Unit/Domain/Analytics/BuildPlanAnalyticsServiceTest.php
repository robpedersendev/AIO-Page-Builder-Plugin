<?php
/**
 * Unit tests for Build_Plan_Analytics_Service rollback metrics.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace {
	defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

	$GLOBALS['__aio_opts'] = array();

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( string $key, $default = false ) {
			return $GLOBALS['__aio_opts'][ $key ] ?? $default;
		}
	}
	if ( ! function_exists( 'update_option' ) ) {
		function update_option( string $key, $value, bool $autoload = false ): bool {
			$GLOBALS['__aio_opts'][ $key ] = $value;
			return true;
		}
	}
}

namespace AIOPageBuilder\Tests\Unit\Domain\Analytics {

	use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
	use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface;
	use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
	use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository;
	use PHPUnit\Framework\TestCase;

	$plugin_root = dirname( __DIR__, 4 );
	require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
	require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_Analytics_Service.php';
	require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
	require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
	require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';
	require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository.php';

	final class StubPlanListProvider implements Build_Plan_List_Provider_Interface {
		public function list_recent( int $limit = 50, int $offset = 0 ): array {
			return array();
		}
	}

	final class BuildPlanAnalyticsServiceTest extends TestCase {

		protected function setUp(): void {
			parent::setUp();
			$GLOBALS['__aio_opts'] = array();
		}

		public function test_rollback_rate_is_zero_when_denominator_is_zero(): void {
			$svc = new Build_Plan_Analytics_Service( new StubPlanListProvider(), new Operational_Snapshot_Repository() );
			$summary = $svc->get_rollback_frequency_summary( '2026-01-01', '2026-01-31' );
			$this->assertSame( 0, $summary['completed_rollbacks'] );
			$this->assertSame( 0, $summary['rollback_eligible_completed_executions'] );
			$this->assertSame( 0.0, $summary['rollback_rate'] );
		}

		public function test_rollback_rate_counts_rollbacks_over_eligible_executions(): void {
			$repo = new Operational_Snapshot_Repository();
			$svc  = new Build_Plan_Analytics_Service( new StubPlanListProvider(), $repo );

			update_option(
				Operational_Snapshot_Repository::OPTION_KEY,
				array(
					'snap-1' => array(
						'snapshot_id'       => 'snap-1',
						'snapshot_type'     => 'post_change',
						'object_family'     => 'page',
						'target_ref'        => 'post:1',
						'created_at'        => '2026-01-05T00:00:00+00:00',
						'schema_version'    => '1',
						'action_type'       => 'replace_page',
						'rollback_eligible' => true,
					),
					'snap-2' => array(
						'snapshot_id'       => 'snap-2',
						'snapshot_type'     => 'post_change',
						'object_family'     => 'page',
						'target_ref'        => 'post:1',
						'created_at'        => '2026-01-06T00:00:00+00:00',
						'schema_version'    => '1',
						'action_type'       => Execution_Action_Types::ROLLBACK_ACTION,
						'rollback_eligible' => false,
					),
				)
			);

			$summary = $svc->get_rollback_frequency_summary( '2026-01-01', '2026-01-31' );
			$this->assertSame( 1, $summary['completed_rollbacks'] );
			$this->assertSame( 1, $summary['rollback_eligible_completed_executions'] );
			$this->assertSame( 1.0, $summary['rollback_rate'] );
		}
	}
}

