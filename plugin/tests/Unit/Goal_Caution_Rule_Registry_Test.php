<?php
/**
 * Unit tests for Goal_Caution_Rule_Registry (Prompt 510).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Goal_Caution_Rule_Registry.php';

final class Goal_Caution_Rule_Registry_Test extends TestCase {

	public function test_load_and_get_returns_rule(): void {
		$registry = new Goal_Caution_Rule_Registry();
		$registry->load( array(
			array(
				Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY  => 'goal_calls_urgency',
				Goal_Caution_Rule_Registry::FIELD_GOAL_KEY      => 'calls',
				Goal_Caution_Rule_Registry::FIELD_SEVERITY      => 'caution',
				Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Urgency language must be accurate.',
				Goal_Caution_Rule_Registry::FIELD_STATUS        => 'active',
			),
		) );
		$rule = $registry->get( 'goal_calls_urgency' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'goal_calls_urgency', $rule[ Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY ] );
		$this->assertSame( 'calls', $rule[ Goal_Caution_Rule_Registry::FIELD_GOAL_KEY ] );
	}

	public function test_get_for_goal_returns_overlays(): void {
		$registry = new Goal_Caution_Rule_Registry();
		$registry->load( Goal_Caution_Rule_Registry::get_builtin_definitions() );
		$for_calls = $registry->get_for_goal( 'calls' );
		$this->assertGreaterThanOrEqual( 1, count( $for_calls ) );
		foreach ( $for_calls as $rule ) {
			$this->assertSame( 'calls', $rule[ Goal_Caution_Rule_Registry::FIELD_GOAL_KEY ] );
		}
	}

	public function test_invalid_goal_key_skipped(): void {
		$registry = new Goal_Caution_Rule_Registry();
		$registry->load( array(
			array(
				Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY  => 'goal_unknown_rule',
				Goal_Caution_Rule_Registry::FIELD_GOAL_KEY      => 'invalid_goal_xyz',
				Goal_Caution_Rule_Registry::FIELD_SEVERITY      => 'caution',
				Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Summary.',
				Goal_Caution_Rule_Registry::FIELD_STATUS        => 'active',
			),
		) );
		$this->assertNull( $registry->get( 'goal_unknown_rule' ) );
		$this->assertSame( array(), $registry->get_all() );
	}

	public function test_fallback_empty_goal_returns_nothing(): void {
		$registry = new Goal_Caution_Rule_Registry();
		$registry->load( array(
			array(
				Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY    => 'goal_calls_one',
				Goal_Caution_Rule_Registry::FIELD_GOAL_KEY       => 'calls',
				Goal_Caution_Rule_Registry::FIELD_SEVERITY       => 'caution',
				Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Summary.',
				Goal_Caution_Rule_Registry::FIELD_STATUS         => 'active',
			),
		) );
		$this->assertSame( array(), $registry->get_for_goal( '' ) );
	}
}
