<?php
/**
 * Unit tests for Subtype_Compliance_Rule_Registry: valid/invalid subtype rules,
 * get_for_subtype, builtin definitions (Prompt 446, 447).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Subtype_Compliance_Rule_Registry.php';

final class Subtype_Compliance_Rule_Registry_Test extends TestCase {

	private function valid_subtype_rule(
		string $subtype_rule_key = 'test_subtype_rule_01',
		string $subtype_key = 'realtor_buyer_agent',
		string $parent_industry_key = 'realtor'
	): array {
		return array(
			Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_RULE_KEY    => $subtype_rule_key,
			Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_KEY        => $subtype_key,
			Subtype_Compliance_Rule_Registry::FIELD_PARENT_INDUSTRY_KEY => $parent_industry_key,
			Subtype_Compliance_Rule_Registry::FIELD_SEVERITY            => Subtype_Compliance_Rule_Registry::SEVERITY_CAUTION,
			Subtype_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY     => 'Test subtype caution summary.',
			Subtype_Compliance_Rule_Registry::FIELD_STATUS              => 'active',
		);
	}

	public function test_load_and_get_valid_subtype_rule(): void {
		$registry = new Subtype_Compliance_Rule_Registry();
		$registry->load( array( $this->valid_subtype_rule( 'sub_rule_01', 'realtor_buyer_agent', 'realtor' ) ) );
		$rule = $registry->get( 'sub_rule_01' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'sub_rule_01', $rule[ Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_RULE_KEY ] );
		$this->assertSame( 'realtor_buyer_agent', $rule[ Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'realtor', $rule[ Subtype_Compliance_Rule_Registry::FIELD_PARENT_INDUSTRY_KEY ] );
		$this->assertSame( 'caution', $rule[ Subtype_Compliance_Rule_Registry::FIELD_SEVERITY ] );
	}

	public function test_load_skips_invalid_severity(): void {
		$registry = new Subtype_Compliance_Rule_Registry();
		$rule = $this->valid_subtype_rule();
		$rule[ Subtype_Compliance_Rule_Registry::FIELD_SEVERITY ] = 'invalid';
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'test_subtype_rule_01' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_skips_empty_subtype_key(): void {
		$registry = new Subtype_Compliance_Rule_Registry();
		$rule = $this->valid_subtype_rule();
		$rule[ Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_KEY ] = '';
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'test_subtype_rule_01' ) );
	}

	public function test_get_for_subtype_returns_only_matching_parent_and_subtype(): void {
		$registry = new Subtype_Compliance_Rule_Registry();
		$registry->load( array(
			$this->valid_subtype_rule( 'r1', 'realtor_buyer_agent', 'realtor' ),
			$this->valid_subtype_rule( 'r2', 'realtor_listing_agent', 'realtor' ),
			$this->valid_subtype_rule( 'r3', 'realtor_buyer_agent', 'realtor' ),
			$this->valid_subtype_rule( 'r4', 'plumber_residential', 'plumber' ),
		) );
		$buyer = $registry->get_for_subtype( 'realtor', 'realtor_buyer_agent' );
		$this->assertCount( 2, $buyer );
		$listing = $registry->get_for_subtype( 'realtor', 'realtor_listing_agent' );
		$this->assertCount( 1, $listing );
		$plumber = $registry->get_for_subtype( 'plumber', 'plumber_residential' );
		$this->assertCount( 1, $plumber );
		$this->assertSame( array(), $registry->get_for_subtype( 'realtor', '' ) );
		$this->assertSame( array(), $registry->get_for_subtype( '', 'realtor_buyer_agent' ) );
	}

	public function test_builtin_definitions_load_and_validate(): void {
		$defs = Subtype_Compliance_Rule_Registry::get_builtin_definitions();
		$this->assertGreaterThanOrEqual( 1, count( $defs ), 'Expected at least one built-in subtype compliance rule' );
		$registry = new Subtype_Compliance_Rule_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 1, count( $registry->get_all() ) );
		foreach ( $registry->get_all() as $rule ) {
			$this->assertContains( $rule[ Subtype_Compliance_Rule_Registry::FIELD_SEVERITY ] ?? '', array( 'info', 'caution', 'warning' ), 'Severity must be allowed value' );
			$this->assertNotEmpty( $rule[ Subtype_Compliance_Rule_Registry::FIELD_CAUTION_SUMMARY ] ?? '' );
			$this->assertNotEmpty( $rule[ Subtype_Compliance_Rule_Registry::FIELD_SUBTYPE_RULE_KEY ] ?? '' );
			$this->assertNotEmpty( $rule[ Subtype_Compliance_Rule_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' );
		}
		$realtor_buyer = $registry->get_for_subtype( 'realtor', 'realtor_buyer_agent' );
		$this->assertGreaterThanOrEqual( 1, count( $realtor_buyer ), 'Expected at least one subtype rule for realtor_buyer_agent' );
	}
}
