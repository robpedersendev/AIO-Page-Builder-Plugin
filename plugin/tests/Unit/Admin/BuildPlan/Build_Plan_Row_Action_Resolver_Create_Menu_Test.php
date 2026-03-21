<?php
/**
 * Unit tests for Build_Plan_Row_Action_Resolver — create_menu (menu_new) item affordances.
 *
 * Verifies that:
 * - ITEM_TYPE_MENU_NEW items expose execute/retry affordances (distinct from approve/deny-only MENU_CHANGE).
 * - ITEM_TYPE_MENU_CHANGE items do NOT expose execute/retry (approve/deny only in v1).
 * - State transitions (denied, completed, failed, rejected) map truthfully for menu_new items.
 * - Existing ITEM_TYPE_DESIGN_TOKEN execute behavior is unaffected.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin\BuildPlan;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/tests/bootstrap_i18n_stub.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';

/**
 * @covers \AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver
 */
final class Build_Plan_Row_Action_Resolver_Create_Menu_Test extends TestCase {

	private Build_Plan_Row_Action_Resolver $resolver;

	protected function setUp(): void {
		$this->resolver = new Build_Plan_Row_Action_Resolver();
	}

	private function make_item( string $item_type, string $status ): array {
		return array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => 'test-item-' . $item_type . '-' . $status,
			Build_Plan_Item_Schema::KEY_ITEM_TYPE => $item_type,
			'status'                              => $status,
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(),
		);
	}

	private function find_action( array $actions, string $action_id ): ?array {
		foreach ( $actions as $action ) {
			if ( ( $action['action_id'] ?? '' ) === $action_id ) {
				return $action;
			}
		}
		return null;
	}

	private function caps( bool $can_approve = true, bool $can_execute = true ): array {
		return array(
			'can_approve' => $can_approve,
			'can_execute' => $can_execute,
		);
	}

	// ----------------------------------------------------------------
	// ITEM_TYPE_MENU_NEW — execute/retry affordances.
	// ----------------------------------------------------------------

	public function test_menu_new_approved_exposes_enabled_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute, 'execute action must be present for approved menu_new items.' );
		$this->assertTrue( $execute['enabled'], 'execute must be enabled for approved menu_new items.' );
	}

	public function test_menu_new_failed_exposes_enabled_retry(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, Build_Plan_Item_Statuses::FAILED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$retry   = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_RETRY );
		$this->assertNotNull( $retry, 'retry action must be present for failed menu_new items.' );
		$this->assertTrue( $retry['enabled'], 'retry must be enabled for failed menu_new items.' );
	}

	public function test_menu_new_completed_has_execute_disabled(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, Build_Plan_Item_Statuses::COMPLETED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		if ( $execute !== null ) {
			$this->assertFalse( $execute['enabled'], 'execute must be disabled for completed menu_new items.' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function test_menu_new_denied_has_execute_disabled(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, Build_Plan_Item_Statuses::REJECTED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		if ( $execute !== null ) {
			$this->assertFalse( $execute['enabled'], 'execute must be disabled for denied menu_new items.' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function test_menu_new_execute_disabled_without_can_execute_capability(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, $this->caps( can_approve: true, can_execute: false ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		if ( $execute !== null ) {
			$this->assertFalse( $execute['enabled'], 'execute must be disabled when can_execute is false.' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	// ----------------------------------------------------------------
	// ITEM_TYPE_MENU_CHANGE — approve/deny only (NO execute/retry in v1).
	// ----------------------------------------------------------------

	public function test_menu_change_approved_has_no_enabled_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		if ( $execute !== null ) {
			$this->assertFalse( $execute['enabled'], 'menu_change items must not have enabled execute (approve/deny only).' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function test_menu_change_and_menu_new_are_distinct(): void {
		$this->assertNotSame(
			Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE,
			Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW
		);
	}

	// ----------------------------------------------------------------
	// ITEM_TYPE_DESIGN_TOKEN — existing execute behavior is unaffected.
	// ----------------------------------------------------------------

	public function test_design_token_approved_still_exposes_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, $this->caps() );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute, 'execute action must still exist for approved design_token items.' );
		$this->assertTrue( $execute['enabled'] );
	}
}
