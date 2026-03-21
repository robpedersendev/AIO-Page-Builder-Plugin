<?php
/**
 * Unit tests for Build_Plan_Row_Action_Resolver — hierarchy item execute/retry affordances.
 *
 * Verifies that:
 * - ITEM_TYPE_HIERARCHY_ASSIGNMENT items expose execute/retry when eligible.
 * - ITEM_TYPE_HIERARCHY_NOTE items never expose execute/retry (advisory-only).
 * - State transitions (denied, completed, failed) map truthfully.
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
final class Build_Plan_Row_Action_Resolver_Hierarchy_Test extends TestCase {

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

	// --- ITEM_TYPE_HIERARCHY_ASSIGNMENT: eligible execute ---

	public function test_hierarchy_assignment_approved_exposes_enabled_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute, 'Execute action must be present for approved hierarchy_assignment item.' );
		$this->assertTrue( $execute['enabled'], 'Execute must be enabled when item is approved and user can_execute.' );
	}

	public function test_hierarchy_assignment_failed_exposes_enabled_retry(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, Build_Plan_Item_Statuses::FAILED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$retry   = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_RETRY );
		$this->assertNotNull( $retry, 'Retry action must be present for failed hierarchy_assignment item.' );
		$this->assertTrue( $retry['enabled'], 'Retry must be enabled when item is failed and user can_execute.' );
	}

	// --- ITEM_TYPE_HIERARCHY_ASSIGNMENT: capability gate ---

	public function test_hierarchy_assignment_approved_execute_disabled_without_can_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => false ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute );
		$this->assertFalse( $execute['enabled'], 'Execute must be disabled when can_execute is false.' );
	}

	// --- ITEM_TYPE_HIERARCHY_ASSIGNMENT: terminal states ---

	public function test_hierarchy_assignment_completed_execute_is_disabled(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, Build_Plan_Item_Statuses::COMPLETED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute );
		$this->assertFalse( $execute['enabled'], 'Execute must be disabled for completed items.' );
	}

	public function test_hierarchy_assignment_rejected_execute_is_disabled(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, Build_Plan_Item_Statuses::REJECTED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute );
		$this->assertFalse( $execute['enabled'], 'Execute must be disabled for rejected items.' );
	}

	// --- ITEM_TYPE_HIERARCHY_NOTE: advisory-only, never executes ---

	public function test_hierarchy_note_does_not_expose_execute_action(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNull( $execute, 'Execute action must NOT appear for advisory hierarchy_note items.' );
	}

	public function test_hierarchy_note_does_not_expose_retry_action(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE, Build_Plan_Item_Statuses::FAILED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$retry   = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_RETRY );
		$this->assertNull( $retry, 'Retry action must NOT appear for advisory hierarchy_note items.' );
	}

	// --- Existing ITEM_TYPE_DESIGN_TOKEN: must still work ---

	public function test_design_token_still_exposes_execute(): void {
		$item    = $this->make_item( Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN, Build_Plan_Item_Statuses::APPROVED );
		$actions = $this->resolver->resolve( $item, array( 'can_execute' => true ) );
		$execute = $this->find_action( $actions, Build_Plan_Row_Action_Resolver::ACTION_EXECUTE );
		$this->assertNotNull( $execute );
		$this->assertTrue( $execute['enabled'], 'Existing design_token execute behavior must be unaffected.' );
	}
}
