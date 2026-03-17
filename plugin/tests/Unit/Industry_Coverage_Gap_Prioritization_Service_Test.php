<?php
/**
 * Unit tests for Industry_Coverage_Gap_Prioritization_Service (Prompt 524). Ranked report from gap sets.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Prioritization_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Coverage_Gap_Analyzer.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Coverage_Gap_Prioritization_Service.php';

final class Industry_Coverage_Gap_Prioritization_Service_Test extends TestCase {

	public function test_generate_report_empty_gaps_returns_structure(): void {
		$service = new Industry_Coverage_Gap_Prioritization_Service( null );
		$result  = $service->generate_report( array( 'gaps' => array(), 'by_scope' => array() ) );
		$this->assertArrayHasKey( 'ranked', $result );
		$this->assertArrayHasKey( 'by_tier', $result );
		$this->assertArrayHasKey( 'release_blocker_cues', $result );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertIsArray( $result['ranked'] );
		$this->assertCount( 0, $result['ranked'] );
		$this->assertSame( 0, $result['summary']['urgent'] );
		$this->assertSame( 0, $result['summary']['important'] );
		$this->assertSame( 0, $result['summary']['optional'] );
	}

	public function test_generate_report_ranks_and_groups_by_tier(): void {
		$gaps = array(
			array(
				'scope'                 => 'realtor',
				'missing_artifact_class' => Industry_Coverage_Gap_Analyzer::GAP_STARTER_BUNDLE,
				'priority'              => Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH,
				'explanation'            => 'No bundle for realtor.',
			),
			array(
				'scope'                 => 'realtor',
				'missing_artifact_class' => Industry_Coverage_Gap_Analyzer::GAP_QUESTION_PACK,
				'priority'              => Industry_Coverage_Gap_Analyzer::PRIORITY_LOW,
				'explanation'            => 'No question pack.',
			),
		);
		$service = new Industry_Coverage_Gap_Prioritization_Service( null );
		$result  = $service->generate_report( array( 'gaps' => $gaps, 'by_scope' => array() ) );

		$this->assertCount( 2, $result['ranked'] );
		$this->assertSame( Industry_Coverage_Gap_Prioritization_Service::TIER_URGENT, $result['ranked'][0]['tier'] );
		$this->assertSame( Industry_Coverage_Gap_Prioritization_Service::TIER_OPTIONAL, $result['ranked'][1]['tier'] );
		$this->assertSame( 1, $result['summary']['urgent'] );
		$this->assertSame( 0, $result['summary']['important'] );
		$this->assertSame( 1, $result['summary']['optional'] );
		$this->assertArrayHasKey( 'rationale', $result['ranked'][0] );
		$this->assertArrayHasKey( 'priority_score', $result['ranked'][0] );
		$this->assertCount( 1, $result['release_blocker_cues'] );
	}

	public function test_run_with_null_analyzer_returns_empty_ranked(): void {
		$service = new Industry_Coverage_Gap_Prioritization_Service( null );
		$result  = $service->run( true );
		$this->assertArrayHasKey( 'ranked', $result );
		$this->assertIsArray( $result['ranked'] );
		$this->assertCount( 0, $result['ranked'] );
	}
}
