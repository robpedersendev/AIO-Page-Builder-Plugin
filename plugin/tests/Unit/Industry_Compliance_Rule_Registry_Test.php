<?php
/**
 * Unit tests for Industry_Compliance_Rule_Registry: valid/invalid rules, severity validation,
 * get_for_industry (Prompt 405).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Compliance_Rule_Registry.php';

final class Industry_Compliance_Rule_Registry_Test extends TestCase {

	private function valid_rule( string $rule_key = 'test_rule_01', string $industry_key = 'realtor' ): array {
		return array(
			Industry_Compliance_Rule_Registry::FIELD_RULE_KEY        => $rule_key,
			Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY    => $industry_key,
			Industry_Compliance_Rule_Registry::FIELD_SEVERITY        => Industry_Compliance_Rule_Registry::SEVERITY_CAUTION,
			Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY => 'Test caution summary.',
			Industry_Compliance_Rule_Registry::FIELD_STATUS          => 'active',
		);
	}

	public function test_load_and_get_valid_rule(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'realtor_mls_01', 'realtor' ) ) );
		$rule = $registry->get( 'realtor_mls_01' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'realtor_mls_01', $rule[ Industry_Compliance_Rule_Registry::FIELD_RULE_KEY ] );
		$this->assertSame( 'realtor', $rule[ Industry_Compliance_Rule_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( 'caution', $rule[ Industry_Compliance_Rule_Registry::FIELD_SEVERITY ] );
	}

	public function test_load_skips_invalid_severity(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$rule = $this->valid_rule();
		$rule[ Industry_Compliance_Rule_Registry::FIELD_SEVERITY ] = 'invalid';
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'test_rule_01' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_skips_empty_caution_summary(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$rule = $this->valid_rule();
		$rule[ Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY ] = '';
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'test_rule_01' ) );
	}

	public function test_get_for_industry_returns_only_that_industry(): void {
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( array(
			$this->valid_rule( 'r1', 'plumber' ),
			$this->valid_rule( 'r2', 'cosmetology_nail' ),
			$this->valid_rule( 'r3', 'plumber' ),
		) );
		$plumber = $registry->get_for_industry( 'plumber' );
		$this->assertCount( 2, $plumber );
	}

	public function test_builtin_definitions_load(): void {
		$defs = Industry_Compliance_Rule_Registry::get_builtin_definitions();
		$this->assertGreaterThanOrEqual( 1, count( $defs ), 'Expected at least one built-in compliance rule' );
		$registry = new Industry_Compliance_Rule_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 1, count( $registry->get_all() ) );
		$realtor = $registry->get_for_industry( 'realtor' );
		$this->assertGreaterThanOrEqual( 1, count( $realtor ) );
		$rule = $registry->get( 'realtor_mls_board' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'warning', $rule[ Industry_Compliance_Rule_Registry::FIELD_SEVERITY ] );
		$this->assertArrayHasKey( Industry_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY, $rule );
	}
}
