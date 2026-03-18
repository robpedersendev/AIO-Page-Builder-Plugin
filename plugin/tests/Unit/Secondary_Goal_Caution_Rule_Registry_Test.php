<?php
/**
 * Unit tests for Secondary_Goal_Caution_Rule_Registry (Prompt 547, 548).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Caution_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Secondary_Goal_Caution_Rule_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Goal_Caution_Rule_Registry.php';

final class Secondary_Goal_Caution_Rule_Registry_Test extends TestCase {

	public function test_load_and_get_returns_rule(): void {
		$registry = new Secondary_Goal_Caution_Rule_Registry();
		$registry->load(
			array(
				array(
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_RULE_KEY => 'sec_test_rule',
					Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY    => 'lead_capture',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SEVERITY               => Secondary_Goal_Caution_Rule_Registry::SEVERITY_CAUTION,
					Secondary_Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY        => 'Test mixed-funnel caution.',
					Secondary_Goal_Caution_Rule_Registry::FIELD_STATUS                 => Secondary_Goal_Caution_Rule_Registry::STATUS_ACTIVE,
				),
			)
		);
		$rule = $registry->get( 'sec_test_rule' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'calls', $rule[ Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY ] );
		$this->assertSame( 'lead_capture', $rule[ Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY ] );
	}

	public function test_get_for_primary_secondary_returns_rules(): void {
		$registry = new Secondary_Goal_Caution_Rule_Registry();
		$registry->load( Secondary_Goal_Caution_Rule_Registry::get_builtin_definitions() );
		$for_pair = $registry->get_for_primary_secondary( 'calls', 'lead_capture' );
		$this->assertGreaterThanOrEqual( 1, count( $for_pair ) );
		foreach ( $for_pair as $rule ) {
			$this->assertSame( 'calls', $rule[ Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY ] );
			$this->assertSame( 'lead_capture', $rule[ Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY ] );
		}
	}

	public function test_invalid_rule_primary_equals_secondary_skipped_at_load(): void {
		$registry = new Secondary_Goal_Caution_Rule_Registry();
		$registry->load(
			array(
				array(
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_RULE_KEY => 'sec_bad_same',
					Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY     => 'calls',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SEVERITY               => Secondary_Goal_Caution_Rule_Registry::SEVERITY_INFO,
					Secondary_Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY      => 'Same goal.',
					Secondary_Goal_Caution_Rule_Registry::FIELD_STATUS                 => Secondary_Goal_Caution_Rule_Registry::STATUS_ACTIVE,
				),
			)
		);
		$this->assertEmpty( $registry->get_all() );
	}

	/** Primary-goal-only fallback: secondary registry returns empty when primary equals secondary. */
	public function test_get_for_primary_secondary_returns_empty_when_primary_equals_secondary(): void {
		$registry = new Secondary_Goal_Caution_Rule_Registry();
		$registry->load(
			array(
				array(
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_RULE_KEY => 'sec_other',
					Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY     => 'lead_capture',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SEVERITY               => Secondary_Goal_Caution_Rule_Registry::SEVERITY_INFO,
					Secondary_Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY       => 'Other rule.',
					Secondary_Goal_Caution_Rule_Registry::FIELD_STATUS                 => Secondary_Goal_Caution_Rule_Registry::STATUS_ACTIVE,
				),
			)
		);
		$this->assertSame( array(), $registry->get_for_primary_secondary( 'calls', 'calls' ) );
	}

	/** Composition order: primary-goal rules then secondary-goal rules retrievable. */
	public function test_composition_order_primary_then_secondary_retrievable(): void {
		$primary_registry = new Goal_Caution_Rule_Registry();
		$primary_registry->load(
			array(
				array(
					Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY => 'goal_calls_test',
					Goal_Caution_Rule_Registry::FIELD_GOAL_KEY => 'calls',
					Goal_Caution_Rule_Registry::FIELD_SEVERITY => Goal_Caution_Rule_Registry::SEVERITY_INFO,
					Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Primary goal caution.',
					Goal_Caution_Rule_Registry::FIELD_STATUS   => Goal_Caution_Rule_Registry::STATUS_ACTIVE,
				),
			)
		);
		$secondary_registry = new Secondary_Goal_Caution_Rule_Registry();
		$secondary_registry->load(
			array(
				array(
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_RULE_KEY => 'sec_calls_lead',
					Secondary_Goal_Caution_Rule_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SECONDARY_GOAL_KEY    => 'lead_capture',
					Secondary_Goal_Caution_Rule_Registry::FIELD_SEVERITY               => Secondary_Goal_Caution_Rule_Registry::SEVERITY_CAUTION,
					Secondary_Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY        => 'Secondary goal caution.',
					Secondary_Goal_Caution_Rule_Registry::FIELD_STATUS                 => Secondary_Goal_Caution_Rule_Registry::STATUS_ACTIVE,
				),
			)
		);
		$primary_rules   = $primary_registry->get_for_goal( 'calls' );
		$secondary_rules = $secondary_registry->get_for_primary_secondary( 'calls', 'lead_capture' );
		$this->assertCount( 1, $primary_rules );
		$this->assertCount( 1, $secondary_rules );
	}
}
