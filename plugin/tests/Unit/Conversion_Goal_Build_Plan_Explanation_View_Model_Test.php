<?php
/**
 * Unit tests for Conversion_Goal_Build_Plan_Explanation_View_Model (Prompt 499).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\ViewModels\BuildPlan\Conversion_Goal_Build_Plan_Explanation_View_Model;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once dirname( __DIR__ ) . '/bootstrap_i18n_stub.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Admin/ViewModels/BuildPlan/Conversion_Goal_Build_Plan_Explanation_View_Model.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';

final class Conversion_Goal_Build_Plan_Explanation_View_Model_Test extends TestCase {

	public function test_from_plan_definition_empty_returns_no_goal_context(): void {
		$vm = Conversion_Goal_Build_Plan_Explanation_View_Model::from_plan_definition( array() );
		$this->assertFalse( $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_HAS_GOAL_CONTEXT ] );
		$this->assertNull( $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_CONVERSION_GOAL_KEY ] );
		$this->assertSame( '', $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_GOAL_RATIONALE_LINE ] );
	}

	public function test_from_plan_definition_with_goal_source_returns_goal_context(): void {
		$plan = array(
			Build_Plan_Schema::KEY_GOAL_OVERLAY_SOURCE => array(
				'conversion_goal_key' => 'calls',
				'applied'             => true,
			),
		);
		$vm   = Conversion_Goal_Build_Plan_Explanation_View_Model::from_plan_definition( $plan );
		$this->assertTrue( $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_HAS_GOAL_CONTEXT ] );
		$this->assertSame( 'calls', $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_CONVERSION_GOAL_KEY ] );
		$this->assertTrue( $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_GOAL_OVERLAY_APPLIED ] );
		$this->assertNotSame( '', $vm[ Conversion_Goal_Build_Plan_Explanation_View_Model::KEY_GOAL_RATIONALE_LINE ] );
	}
}
