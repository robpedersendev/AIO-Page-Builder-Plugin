<?php
/**
 * Unit tests for Industry_Compliance_Warning_Resolver: get_for_display returns active rules only,
 * empty for unknown industry, display shape (Prompt 407).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Compliance_Rule_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Subtype_Compliance_Rule_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Goal_Caution_Rule_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Compliance_Warning_Resolver.php';

final class Industry_Compliance_Warning_Resolver_Test extends TestCase {

	public function test_get_for_display_empty_industry_returns_empty(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( array(
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'test_rule',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => 'realtor',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY       => 'caution',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Test summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
			),
		) );
		$resolver = new Industry_Compliance_Warning_Resolver( $registry );
		$this->assertSame( array(), $resolver->get_for_display( '' ) );
	}

	public function test_get_for_display_returns_active_only(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( array(
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'active_rule',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => 'plumber',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY       => 'warning',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Active summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
			),
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'draft_rule',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => 'plumber',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY       => 'info',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Draft summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'draft',
			),
		) );
		$resolver = new Industry_Compliance_Warning_Resolver( $registry );
		$out = $resolver->get_for_display( 'plumber' );
		$this->assertCount( 1, $out );
		$this->assertSame( 'active_rule', $out[0]['rule_key'] );
		$this->assertSame( 'warning', $out[0]['severity'] );
		$this->assertSame( 'Active summary.', $out[0]['caution_summary'] );
	}

	public function test_get_for_display_unknown_industry_returns_empty(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( array(
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'r1',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => 'realtor',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY       => 'caution',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
			),
		) );
		$resolver = new Industry_Compliance_Warning_Resolver( $registry );
		$this->assertSame( array(), $resolver->get_for_display( 'unknown_industry_xyz' ) );
	}

	public function test_get_for_display_builtin_definitions(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( Industry_Compliance_Rule_Registry::get_builtin_definitions() );
		$resolver = new Industry_Compliance_Warning_Resolver( $registry );
		$realtor = $resolver->get_for_display( 'realtor' );
		$this->assertGreaterThanOrEqual( 2, count( $realtor ) );
		foreach ( $realtor as $item ) {
			$this->assertArrayHasKey( 'rule_key', $item );
			$this->assertArrayHasKey( 'severity', $item );
			$this->assertArrayHasKey( 'caution_summary', $item );
			$this->assertNotEmpty( $item['rule_key'] );
			$this->assertNotEmpty( $item['caution_summary'] );
		}
	}

	/** Prompt 447: with subtype registry and subtype_key, display includes parent + subtype rules; fallback without subtype. */
	public function test_get_for_display_with_subtype_merges_parent_and_subtype_rules(): void {
		$parent = new Industry_Compliance_Rule_Registry();
		$parent->load( array(
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'parent_rule',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => 'realtor',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY        => 'caution',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Parent summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
			),
		) );
		$subtype = new Subtype_Compliance_Rule_Registry();
		$subtype->load( array(
			array(
				Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_RULE_KEY    => 'subtype_rule',
				Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_KEY         => 'realtor_buyer_agent',
				Subtype_Compliance_Rule_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
				Subtype_Compliance_Rule_Registry::FIELD_SEVERITY            => 'info',
				Subtype_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY     => 'Subtype summary.',
				Subtype_Compliance_Rule_Registry::FIELD_STATUS             => 'active',
			),
		) );
		$resolver = new Industry_Compliance_Warning_Resolver( $parent, $subtype );
		$with_subtype = $resolver->get_for_display( 'realtor', 'realtor_buyer_agent' );
		$this->assertCount( 2, $with_subtype );
		$rule_keys = array_column( $with_subtype, 'rule_key' );
		$this->assertContains( 'parent_rule', $rule_keys );
		$this->assertContains( 'subtype_rule', $rule_keys );
		$without_subtype = $resolver->get_for_display( 'realtor', '' );
		$this->assertCount( 1, $without_subtype );
		$this->assertSame( 'parent_rule', $without_subtype[0]['rule_key'] );
	}

	/** Prompt 510: with goal registry and goal_key, display includes industry + goal rules; fallback without goal_key. */
	public function test_get_for_display_with_goal_appends_goal_rules(): void {
		$parent = new Industry_Compliance_Rule_Registry();
		$parent->load( array(
			array(
				Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => 'parent_rule',
				Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY     => 'realtor',
				Industry_Compliance_Rule_Registry::FIELD_SEVERITY        => 'caution',
				Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Parent summary.',
				Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
			),
		) );
		$goal_registry = new Goal_Caution_Rule_Registry();
		$goal_registry->load( array(
			array(
				Goal_Caution_Rule_Registry::FIELD_GOAL_RULE_KEY    => 'goal_valuations_valuation_posture',
				Goal_Caution_Rule_Registry::FIELD_GOAL_KEY         => 'valuations',
				Goal_Caution_Rule_Registry::FIELD_SEVERITY         => 'warning',
				Goal_Caution_Rule_Registry::FIELD_CAUTION_SUMMARY  => 'Valuation language must not imply formal appraisal.',
				Goal_Caution_Rule_Registry::FIELD_STATUS            => 'active',
			),
		) );
		$resolver = new Industry_Compliance_Warning_Resolver( $parent, null, $goal_registry, null );
		$with_goal = $resolver->get_for_display( 'realtor', '', 'valuations' );
		$this->assertGreaterThanOrEqual( 2, count( $with_goal ) );
		$rule_keys = array_column( $with_goal, 'rule_key' );
		$this->assertContains( 'parent_rule', $rule_keys );
		$this->assertContains( 'goal_valuations_valuation_posture', $rule_keys );
		$without_goal = $resolver->get_for_display( 'realtor', '', '' );
		$this->assertCount( 1, $without_goal );
		$this->assertSame( 'parent_rule', $without_goal[0]['rule_key'] );
	}
}
