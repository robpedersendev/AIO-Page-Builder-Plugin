<?php
/**
 * Unit tests for Build Plan state machine: root and item transitions, terminal states, scenario coverage (build-plan-state-machine.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';

final class Build_Plan_State_Machine_Test extends TestCase {

	// ---------- Root status transitions ----------

	public function test_root_pending_review_can_transition_to_approved_or_rejected(): void {
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_PENDING_REVIEW, Build_Plan_Statuses::ROOT_APPROVED ) );
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_PENDING_REVIEW, Build_Plan_Statuses::ROOT_REJECTED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_PENDING_REVIEW, Build_Plan_Statuses::ROOT_IN_PROGRESS ) );
	}

	public function test_root_approved_can_transition_to_in_progress_or_superseded(): void {
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_APPROVED, Build_Plan_Statuses::ROOT_IN_PROGRESS ) );
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_APPROVED, Build_Plan_Statuses::ROOT_SUPERSEDED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_APPROVED, Build_Plan_Statuses::ROOT_REJECTED ) );
	}

	public function test_root_in_progress_can_transition_to_completed_or_superseded(): void {
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_IN_PROGRESS, Build_Plan_Statuses::ROOT_COMPLETED ) );
		$this->assertTrue( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_IN_PROGRESS, Build_Plan_Statuses::ROOT_SUPERSEDED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_IN_PROGRESS, Build_Plan_Statuses::ROOT_PENDING_REVIEW ) );
	}

	public function test_root_rejected_has_no_outgoing_transitions(): void {
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_REJECTED, Build_Plan_Statuses::ROOT_APPROVED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_REJECTED, Build_Plan_Statuses::ROOT_PENDING_REVIEW ) );
	}

	public function test_root_completed_and_superseded_are_terminal(): void {
		$this->assertTrue( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_REJECTED ) );
		$this->assertTrue( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_COMPLETED ) );
		$this->assertTrue( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_SUPERSEDED ) );
		$this->assertFalse( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_PENDING_REVIEW ) );
		$this->assertFalse( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_APPROVED ) );
		$this->assertFalse( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_IN_PROGRESS ) );
	}

	public function test_invalid_root_status_transition_rejected(): void {
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( 'invalid', Build_Plan_Statuses::ROOT_APPROVED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_PENDING_REVIEW, 'invalid' ) );
	}

	// ---------- Item status: review phase ----------

	public function test_item_review_pending_to_approved_rejected_skipped_allowed(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::PENDING, Build_Plan_Item_Statuses::APPROVED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::PENDING, Build_Plan_Item_Statuses::REJECTED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::PENDING, Build_Plan_Item_Statuses::SKIPPED ) );
	}

	public function test_item_review_revert_approved_to_pending_allowed(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::PENDING ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::REJECTED, Build_Plan_Item_Statuses::PENDING ) );
	}

	public function test_item_review_completed_not_from_pending(): void {
		$this->assertFalse( Build_Plan_Item_Statuses::can_transition_review( Build_Plan_Item_Statuses::PENDING, Build_Plan_Item_Statuses::COMPLETED ) );
	}

	// ---------- Item status: execution phase ----------

	public function test_item_execution_approved_to_in_progress_allowed(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::IN_PROGRESS ) );
	}

	public function test_item_execution_in_progress_to_completed_failed_skipped_allowed(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::IN_PROGRESS, Build_Plan_Item_Statuses::COMPLETED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::IN_PROGRESS, Build_Plan_Item_Statuses::FAILED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::IN_PROGRESS, Build_Plan_Item_Statuses::SKIPPED ) );
	}

	public function test_item_execution_failed_to_in_progress_or_skipped_allowed(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::FAILED, Build_Plan_Item_Statuses::IN_PROGRESS ) );
		$this->assertTrue( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::FAILED, Build_Plan_Item_Statuses::SKIPPED ) );
	}

	public function test_item_execution_rejected_item_has_no_execution_transition(): void {
		$this->assertFalse( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::REJECTED, Build_Plan_Item_Statuses::IN_PROGRESS ) );
	}

	public function test_item_terminal_statuses(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::REJECTED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::SKIPPED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::COMPLETED ) );
		$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::FAILED ) );
		$this->assertFalse( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::PENDING ) );
		$this->assertFalse( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::APPROVED ) );
		$this->assertFalse( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::IN_PROGRESS ) );
	}

	// ---------- Step status enum ----------

	public function test_step_statuses_include_blocked_and_reviewed(): void {
		$this->assertTrue( Build_Plan_Statuses::is_valid_step_status( Build_Plan_Statuses::STEP_BLOCKED ) );
		$this->assertTrue( Build_Plan_Statuses::is_valid_step_status( Build_Plan_Statuses::STEP_REVIEWED ) );
	}

	// ---------- Scenario: denial is not failure ----------

	public function test_rejected_root_is_terminal_denial_not_failure(): void {
		$this->assertTrue( Build_Plan_Statuses::is_root_terminal( Build_Plan_Statuses::ROOT_REJECTED ) );
		$this->assertFalse( Build_Plan_Statuses::can_transition_root( Build_Plan_Statuses::ROOT_REJECTED, Build_Plan_Statuses::ROOT_APPROVED ) );
	}

	// ---------- Scenario: partial item denial ----------

	public function test_item_rejected_is_terminal_execution_does_not_apply(): void {
		$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( Build_Plan_Item_Statuses::REJECTED ) );
		$this->assertFalse( Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::REJECTED, Build_Plan_Item_Statuses::COMPLETED ) );
	}
}
