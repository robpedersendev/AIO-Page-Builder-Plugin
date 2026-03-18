<?php
/**
 * Unit tests for Industry_Scaffold_Promotion_Readiness_Report_Service (Prompt 565).
 * Report structure, tier grouping, scaffold vs release distinction.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Provider_Interface;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Scaffold_Completeness_Report_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Scaffold_Completeness_Report_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Scaffold_Promotion_Readiness_Report_Service.php';

final class Industry_Scaffold_Promotion_Readiness_Report_Service_Test extends TestCase {

	public function test_generate_report_with_null_service_returns_required_structure(): void {
		$service = new Industry_Scaffold_Promotion_Readiness_Report_Service( null );
		$result  = $service->generate_report();
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'by_tier', $result );
		$this->assertArrayHasKey( 'generated_at', $result );
		$this->assertSame( 0, $result['summary']['total'] );
		$this->assertSame( 0, $result['summary']['scaffold_complete'] );
		$this->assertSame( 0, $result['summary']['authored_near_ready'] );
		$this->assertSame( 0, $result['summary']['not_near_ready'] );
		$this->assertSame( array(), $result['items'] );
	}

	public function test_each_item_has_required_keys(): void {
		$stub    = $this->create_stub_completeness_report();
		$service = new Industry_Scaffold_Promotion_Readiness_Report_Service( $stub );
		$result  = $service->generate_report();
		$keys    = array( 'scaffold_ref', 'scaffold_type', 'readiness_score', 'readiness_tier', 'blockers', 'missing_evidence', 'notes' );
		foreach ( $result['items'] as $item ) {
			foreach ( $keys as $key ) {
				$this->assertArrayHasKey( $key, $item );
			}
			$this->assertContains(
				$item['readiness_tier'],
				array(
					Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_SCAFFOLD_COMPLETE,
					Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_AUTHORED_NEAR_READY,
					Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_NOT_NEAR_READY,
				)
			);
		}
	}

	public function test_tier_grouping_consistent_with_summary(): void {
		$stub     = $this->create_stub_completeness_report();
		$service  = new Industry_Scaffold_Promotion_Readiness_Report_Service( $stub );
		$result   = $service->generate_report();
		$by_tier  = $result['by_tier'];
		$complete = count( $by_tier[ Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_SCAFFOLD_COMPLETE ] ?? array() );
		$near     = count( $by_tier[ Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_AUTHORED_NEAR_READY ] ?? array() );
		$not      = count( $by_tier[ Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_NOT_NEAR_READY ] ?? array() );
		$this->assertSame( $result['summary']['scaffold_complete'], $complete );
		$this->assertSame( $result['summary']['authored_near_ready'], $near );
		$this->assertSame( $result['summary']['not_near_ready'], $not );
		$this->assertSame( count( $result['items'] ), $result['summary']['total'] );
	}

	public function test_scaffold_complete_vs_release_distinction_tiers_are_advisory(): void {
		$service = new Industry_Scaffold_Promotion_Readiness_Report_Service( null );
		$result  = $service->generate_report();
		$this->assertArrayHasKey( Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_SCAFFOLD_COMPLETE, $result['by_tier'] );
		$this->assertArrayHasKey( Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_AUTHORED_NEAR_READY, $result['by_tier'] );
		$this->assertArrayHasKey( Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_NOT_NEAR_READY, $result['by_tier'] );
		$this->assertNotEquals( 'release_ready', Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_AUTHORED_NEAR_READY );
	}

	/**
	 * Stub that returns two scaffold results: one with missing (not_near_ready), one all authored (authored_near_ready).
	 *
	 * @return Industry_Scaffold_Completeness_Report_Provider_Interface
	 */
	private function create_stub_completeness_report(): Industry_Scaffold_Completeness_Report_Provider_Interface {
		return new class() implements Industry_Scaffold_Completeness_Report_Provider_Interface {
			public function generate_report( array $options = array() ): array {
				return array(
					'scaffold_results' => array(
						array(
							'scaffold_type'    => 'industry',
							'scaffold_key'     => 'test_industry',
							'artifact_classes' => array(
								'pack'           => Industry_Scaffold_Completeness_Report_Service::STATE_MISSING,
								'starter_bundle' => Industry_Scaffold_Completeness_Report_Service::STATE_SCAFFOLDED,
							),
							'summary'          => 'Incomplete',
						),
						array(
							'scaffold_type'    => 'subtype',
							'scaffold_key'     => 'test_subtype',
							'artifact_classes' => array(
								'pack'           => Industry_Scaffold_Completeness_Report_Service::STATE_AUTHORED,
								'starter_bundle' => Industry_Scaffold_Completeness_Report_Service::STATE_AUTHORED,
							),
							'summary'          => 'Authored',
						),
					),
					'readable_summary' => '',
					'warnings'         => array(),
					'generated_at'     => gmdate( 'Y-m-d\TH:i:s\Z' ),
				);
			}
		};
	}
}
