<?php
/**
 * Unit tests for Industry_Compliance_Warning_Resolver: get_for_display returns active rules only,
 * empty for unknown industry, display shape (Prompt 407).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Compliance_Rule_Registry.php';
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
}
