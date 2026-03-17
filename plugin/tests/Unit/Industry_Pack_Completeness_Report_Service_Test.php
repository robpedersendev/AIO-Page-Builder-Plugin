<?php
/**
 * Unit tests for Industry_Pack_Completeness_Report_Service (Prompt 520). Report structure and scoring.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Pack_Completeness_Report_Service.php';

final class Industry_Pack_Completeness_Report_Service_Test extends TestCase {

	public function test_generate_report_returns_structure_when_pack_registry_null(): void {
		$service = new Industry_Pack_Completeness_Report_Service( null );
		$result  = $service->generate_report( true );
		$this->assertArrayHasKey( 'pack_results', $result );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertIsArray( $result['pack_results'] );
		$this->assertCount( 0, $result['pack_results'] );
		$this->assertSame( 0, $result['summary']['pack_count'] );
		$this->assertSame( 0, $result['summary']['subtype_count'] );
		$this->assertSame( 0, $result['summary']['release_grade_count'] );
	}

	public function test_generate_report_with_one_active_pack_produces_one_result(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY    => 'completeness_test',
				Industry_Pack_Schema::FIELD_NAME           => 'Completeness Test',
				Industry_Pack_Schema::FIELD_SUMMARY       => 'For report test',
				Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
			),
		) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$service = new Industry_Pack_Completeness_Report_Service( $pack_registry, $bundle_registry, null, null, null, null, null, null, null, null, null );
		$result  = $service->generate_report( false );

		$this->assertCount( 1, $result['pack_results'] );
		$row = $result['pack_results'][0];
		$this->assertSame( 'completeness_test', $row['pack_key'] );
		$this->assertSame( '', $row['subtype_key'] );
		$this->assertArrayHasKey( 'dimension_scores', $row );
		$this->assertArrayHasKey( 'total', $row );
		$this->assertArrayHasKey( 'band', $row );
		$this->assertArrayHasKey( 'missing_assets', $row );
		$this->assertArrayHasKey( 'blocker_flags', $row );
		$this->assertArrayHasKey( 'notes', $row );
		$this->assertContains( $row['band'], array( Industry_Pack_Completeness_Report_Service::BAND_BELOW_MINIMAL, Industry_Pack_Completeness_Report_Service::BAND_MINIMAL_VIABLE ) );
		$this->assertGreaterThanOrEqual( 0, $row['total'] );
		$this->assertSame( 1, $result['summary']['pack_count'] );
	}

	public function test_report_result_has_bounded_interpretable_fields(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY    => 'bounded_test',
				Industry_Pack_Schema::FIELD_NAME           => 'Bounded',
				Industry_Pack_Schema::FIELD_SUMMARY       => 'Test',
				Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
			),
		) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$service = new Industry_Pack_Completeness_Report_Service( $pack_registry, $bundle_registry );
		$result  = $service->generate_report( false );

		$row = $result['pack_results'][0];
		$dims = $row['dimension_scores'];
		$this->assertArrayHasKey( Industry_Pack_Completeness_Report_Service::DIMENSION_PACK, $dims );
		$this->assertArrayHasKey( Industry_Pack_Completeness_Report_Service::DIMENSION_BUNDLE, $dims );
		$this->assertArrayHasKey( Industry_Pack_Completeness_Report_Service::DIMENSION_OVERLAYS, $dims );
		$this->assertTrue( $dims[ Industry_Pack_Completeness_Report_Service::DIMENSION_PACK ] >= -1 && $dims[ Industry_Pack_Completeness_Report_Service::DIMENSION_PACK ] <= 3 );
		$this->assertIsArray( $row['missing_assets'] );
		$this->assertIsArray( $row['blocker_flags'] );
		$this->assertIsArray( $row['notes'] );
	}
}
