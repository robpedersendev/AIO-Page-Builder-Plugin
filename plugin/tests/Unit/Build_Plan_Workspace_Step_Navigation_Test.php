<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Workspace_Step_Navigation;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Stepper_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Workspace_Step_Navigation.php';
require_once $plugin_root . '/src/Support/Logging/Named_Debug_Log.php';
require_once $plugin_root . '/src/Support/Logging/Named_Debug_Log_Event.php';

final class Build_Plan_Workspace_Step_Navigation_Test extends TestCase {

	private const BASE = 'https://example.test/wp-admin/admin.php?page=aio-page-builder-build-plans&aio_tab=build_plans&plan_id=p1';

	public function test_next_hidden_while_required_remaining(): void {
		$def     = $this->plan_with_nav_pending();
		$stepper = ( new Build_Plan_Stepper_Builder() )->build( $def );
		$ctx     = Build_Plan_Workspace_Step_Navigation::compute( 4, $stepper, self::BASE );
		$this->assertSame( 1, $ctx['required_remaining'] );
		$this->assertFalse( $ctx['show_next'] );
		$this->assertTrue( $ctx['back_enabled'] );
	}

	public function test_next_shows_when_navigation_complete(): void {
		$def     = $this->plan_with_nav_approved();
		$stepper = ( new Build_Plan_Stepper_Builder() )->build( $def );
		$ctx     = Build_Plan_Workspace_Step_Navigation::compute( 4, $stepper, self::BASE );
		$this->assertSame( 0, $ctx['required_remaining'] );
		$this->assertTrue( $ctx['show_next'] );
		$this->assertTrue( $ctx['next_enabled'] );
		$this->assertStringContainsString( 'step=5', $ctx['next_url'] );
	}

	public function test_back_disabled_on_first_step(): void {
		$def     = $this->plan_with_nav_approved();
		$stepper = ( new Build_Plan_Stepper_Builder() )->build( $def );
		$ctx     = Build_Plan_Workspace_Step_Navigation::compute( 0, $stepper, self::BASE );
		$this->assertTrue( $ctx['show_back'] );
		$this->assertFalse( $ctx['back_enabled'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function plan_with_nav_pending(): array {
		return array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => 'overview',
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NAVIGATION,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'n1',
							Build_Plan_Item_Schema::KEY_PAYLOAD   => array(),
						),
					),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function plan_with_nav_approved(): array {
		$p = $this->plan_with_nav_pending();
		$p[ Build_Plan_Schema::KEY_STEPS ][4][ Build_Plan_Item_Schema::KEY_ITEMS ][0][ Build_Plan_Item_Schema::KEY_STATUS ] = Build_Plan_Item_Statuses::APPROVED;
		return $p;
	}
}
