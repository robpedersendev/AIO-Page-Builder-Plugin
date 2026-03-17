<?php
/**
 * Unit tests for Conversion_Goal_Starter_Bundle_To_Build_Plan_Service (Prompt 498).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Starter_Bundle_To_Build_Plan_Service;
use AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Plan_Generation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Conversion_Goal_Starter_Bundle_To_Build_Plan_Service.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Starter_Bundle_To_Build_Plan_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';

final class Conversion_Goal_Starter_Bundle_To_Build_Plan_Service_Test extends TestCase {

	/**
	 * Empty bundle key is rejected by Conversion_Goal_Starter_Bundle_To_Build_Plan_Service before delegating to base.
	 * Uses container when available (bootstrap); otherwise skipped because base service is final and cannot be mocked.
	 */
	public function test_convert_to_draft_empty_bundle_key_returns_failure(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array() );
		$base = null;
		if ( class_exists( \AIOPageBuilder\Infrastructure\Container\Service_Container::class, false ) ) {
			$container = \AIOPageBuilder\Infrastructure\Container\Service_Container::get_instance();
			if ( $container !== null && $container->has( 'industry_starter_bundle_to_build_plan_service' ) ) {
				$base = $container->get( 'industry_starter_bundle_to_build_plan_service' );
			}
		}
		if ( $base === null || ! $base instanceof Industry_Starter_Bundle_To_Build_Plan_Service ) {
			$this->markTestSkipped( 'industry_starter_bundle_to_build_plan_service not available (base is final); run with full bootstrap for integration test.' );
		}
		$service = new Conversion_Goal_Starter_Bundle_To_Build_Plan_Service( $base, $registry );
		$result  = $service->convert_to_draft( '', array() );
		$this->assertInstanceOf( Plan_Generation_Result::class, $result );
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}
}
