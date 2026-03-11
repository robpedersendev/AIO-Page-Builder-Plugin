<?php
/**
 * Unit tests for onboarding state machine contract: step keys and status constants (onboarding-state-machine.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Step_Keys.php';

final class Onboarding_State_Machine_Contract_Test extends TestCase {

	public function test_ordered_steps_match_contract(): void {
		$ordered = Onboarding_Step_Keys::ordered();
		$this->assertCount( 11, $ordered, 'Contract defines 11 steps' );
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $ordered[0] );
		$this->assertSame( Onboarding_Step_Keys::SUBMISSION, $ordered[10] );
		$this->assertSame( Onboarding_Step_Keys::REVIEW, $ordered[9] );
		$this->assertSame( Onboarding_Step_Keys::PROVIDER_SETUP, $ordered[8] );
	}

	public function test_overall_statuses_include_required(): void {
		$statuses = Onboarding_Statuses::overall_statuses();
		$this->assertContains( Onboarding_Statuses::NOT_STARTED, $statuses );
		$this->assertContains( Onboarding_Statuses::IN_PROGRESS, $statuses );
		$this->assertContains( Onboarding_Statuses::DRAFT_SAVED, $statuses );
		$this->assertContains( Onboarding_Statuses::BLOCKED, $statuses );
		$this->assertContains( Onboarding_Statuses::READY_FOR_SUBMISSION, $statuses );
		$this->assertContains( Onboarding_Statuses::SUBMITTED, $statuses );
		$this->assertCount( 6, $statuses );
	}

	public function test_step_statuses_include_required(): void {
		$step = Onboarding_Statuses::step_statuses();
		$this->assertContains( Onboarding_Statuses::STEP_NOT_STARTED, $step );
		$this->assertContains( Onboarding_Statuses::STEP_IN_PROGRESS, $step );
		$this->assertContains( Onboarding_Statuses::STEP_COMPLETED, $step );
		$this->assertContains( Onboarding_Statuses::STEP_SKIPPED, $step );
		$this->assertContains( Onboarding_Statuses::STEP_BLOCKED, $step );
		$this->assertCount( 5, $step );
	}
}
