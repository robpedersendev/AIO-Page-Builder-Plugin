<?php
/**
 * Unit tests for Industry_Scaffold_Completeness_Report_Service (Prompt 538).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Scaffold_Completeness_Report_Service.php';

final class Industry_Scaffold_Completeness_Report_Service_Test extends TestCase {

	public function test_generate_report_with_no_registries_returns_structure(): void {
		$service = new Industry_Scaffold_Completeness_Report_Service( null, null, null );
		$report = $service->generate_report( array( 'include_draft_packs' => false, 'include_draft_subtypes' => false ) );
		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertArrayHasKey( 'scaffold_results', $report );
		$this->assertArrayHasKey( 'readable_summary', $report );
		$this->assertArrayHasKey( 'warnings', $report );
		$this->assertIsArray( $report['scaffold_results'] );
		$this->assertSame( array(), $report['scaffold_results'] );
	}

	public function test_generate_report_with_explicit_industry_key_evaluates_scaffold(): void {
		$service = new Industry_Scaffold_Completeness_Report_Service( null, null, null );
		$report = $service->generate_report( array(
			'scaffold_industry_keys' => array( 'test_future_industry' ),
			'include_draft_packs'    => false,
		) );
		$this->assertCount( 1, $report['scaffold_results'] );
		$result = $report['scaffold_results'][0];
		$this->assertSame( 'industry', $result['scaffold_type'] );
		$this->assertSame( 'test_future_industry', $result['scaffold_key'] );
		$this->assertArrayHasKey( 'artifact_classes', $result );
		$this->assertSame( Industry_Scaffold_Completeness_Report_Service::STATE_MISSING, $result['artifact_classes'][ Industry_Scaffold_Completeness_Report_Service::ARTIFACT_PACK ] );
		$this->assertSame( Industry_Scaffold_Completeness_Report_Service::STATE_MISSING, $result['artifact_classes'][ Industry_Scaffold_Completeness_Report_Service::ARTIFACT_STARTER_BUNDLE ] );
		$this->assertArrayHasKey( 'summary', $result );
	}

	public function test_generate_report_with_subtype_key_evaluates_subtype_scaffold(): void {
		$service = new Industry_Scaffold_Completeness_Report_Service( null, null, null );
		$report = $service->generate_report( array(
			'scaffold_subtype_keys'  => array( 'test_future_subtype' ),
			'include_draft_subtypes' => false,
		) );
		$this->assertCount( 1, $report['scaffold_results'] );
		$result = $report['scaffold_results'][0];
		$this->assertSame( 'subtype', $result['scaffold_type'] );
		$this->assertSame( 'test_future_subtype', $result['scaffold_key'] );
		$this->assertSame( Industry_Scaffold_Completeness_Report_Service::STATE_MISSING, $result['artifact_classes'][ Industry_Scaffold_Completeness_Report_Service::ARTIFACT_STARTER_BUNDLE ] );
	}
}
