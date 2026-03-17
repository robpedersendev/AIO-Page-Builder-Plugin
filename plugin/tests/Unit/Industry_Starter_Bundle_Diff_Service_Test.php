<?php
/**
 * Unit tests for Industry_Starter_Bundle_Diff_Service: compare returns bundles and diff_rows (Prompt 450).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Starter_Bundle_Diff_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Starter_Bundle_Diff_Service.php';

final class Industry_Starter_Bundle_Diff_Service_Test extends TestCase {

	public function test_compare_returns_empty_when_fewer_than_two_bundles(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$service = new Industry_Starter_Bundle_Diff_Service( $registry );
		$result = $service->compare( array( 'plumber_starter' ) );
		$this->assertCount( 1, $result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ] );
		$this->assertSame( array(), $result[ Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS ] );
	}

	public function test_compare_returns_bundles_and_diff_rows_for_two_bundles(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$service = new Industry_Starter_Bundle_Diff_Service( $registry );
		$result = $service->compare( array( 'plumber_starter', 'plumber_residential_starter' ) );
		$this->assertCount( 2, $result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ] );
		$bundles = $result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ];
		$this->assertSame( 'plumber_starter', $bundles[0]['bundle_key'] );
		$this->assertSame( 'plumber_residential_starter', $bundles[1]['bundle_key'] );
		$rows = $result[ Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS ];
		$this->assertGreaterThanOrEqual( 5, count( $rows ) );
		foreach ( $rows as $row ) {
			$this->assertArrayHasKey( 'field', $row );
			$this->assertArrayHasKey( 'label', $row );
			$this->assertArrayHasKey( 'values', $row );
			$this->assertArrayHasKey( 'changed', $row );
			$this->assertCount( 2, $row['values'] );
		}
	}

	public function test_compare_skips_invalid_keys(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$service = new Industry_Starter_Bundle_Diff_Service( $registry );
		$result = $service->compare( array( 'nonexistent_bundle', 'plumber_starter' ) );
		$this->assertCount( 1, $result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ] );
		$this->assertSame( array(), $result[ Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS ] );
	}

	public function test_compare_with_null_registry_returns_empty(): void {
		$service = new Industry_Starter_Bundle_Diff_Service( null );
		$result = $service->compare( array( 'plumber_starter', 'realtor_starter' ) );
		$this->assertSame( array(), $result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ] );
		$this->assertSame( array(), $result[ Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS ] );
	}
}
