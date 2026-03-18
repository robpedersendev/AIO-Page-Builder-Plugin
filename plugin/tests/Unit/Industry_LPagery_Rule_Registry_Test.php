<?php
/**
 * Unit tests for Industry_LPagery_Rule_Registry: valid rules, invalid token refs/posture, alignment with LPagery contracts (Prompt 337).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/LPagery/Industry_LPagery_Rule_Registry.php';

final class Industry_LPagery_Rule_Registry_Test extends TestCase {

	private function valid_rule( string $key = 'legal_entity_01' ): array {
		return array(
			Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY => $key,
			Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY => 'legal',
			Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER => Industry_LPagery_Rule_Registry::SUPPORTED_SCHEMA_VERSION,
			Industry_LPagery_Rule_Registry::FIELD_STATUS => Industry_LPagery_Rule_Registry::STATUS_ACTIVE,
			Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE => Industry_LPagery_Rule_Registry::POSTURE_CENTRAL,
		);
	}

	public function test_registry_loads_valid_rule_and_get_returns_it(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'legal_entity_01' ) ) );
		$rule = $registry->get( 'legal_entity_01' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'legal_entity_01', $rule[ Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY ] );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_CENTRAL, $rule[ Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE ] );
	}

	public function test_valid_rule_with_token_refs_and_hierarchy(): void {
		$rule = $this->valid_rule( 'realtor_listing_01' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY ]        = 'realtor';
		$rule[ Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS ] = array( '{{location_name}}', '{{service_title}}' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS ] = array( '{{booking_url}}' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE ]  = 'Hub then child-detail.';
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $rule ) );
		$loaded = $registry->get( 'realtor_listing_01' );
		$this->assertNotNull( $loaded );
		$this->assertSame( array( '{{location_name}}', '{{service_title}}' ), $loaded[ Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS ] );
		$this->assertSame( 'Hub then child-detail.', $loaded[ Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE ] );
	}

	public function test_invalid_posture_rejected(): void {
		$rule = $this->valid_rule( 'bad_posture' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE ] = 'invalid';
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'bad_posture' ) );
		$this->assertContains( 'invalid_lpagery_posture', $registry->validate_rule( $rule ) );
	}

	public function test_unsupported_version_rejected(): void {
		$rule = $this->valid_rule( 'v2_rule' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER ] = '2';
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $rule ) );
		$this->assertNull( $registry->get( 'v2_rule' ) );
	}

	public function test_list_by_industry_deterministic(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load(
			array(
				$this->valid_rule( 'legal_01' ),
				array_merge( $this->valid_rule( 'legal_02' ), array( Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE => Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL ) ),
				array_merge( $this->valid_rule( 'realtor_01' ), array( Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY => 'realtor' ) ),
			)
		);
		$this->assertCount( 2, $registry->list_by_industry( 'legal' ) );
		$this->assertCount( 1, $registry->list_by_industry( 'realtor' ) );
		$this->assertCount( 3, $registry->get_all() );
	}

	/** Built-in definitions (Prompt 360): four industries; pack lpagery_rule_ref keys resolve. */
	public function test_builtin_definitions_load(): void {
		$defs = Industry_LPagery_Rule_Registry::get_builtin_definitions();
		$this->assertGreaterThanOrEqual( 4, count( $defs ) );
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 4, count( $registry->get_all() ) );
	}

	public function test_pack_lpagery_rule_refs_resolve(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( Industry_LPagery_Rule_Registry::get_builtin_definitions() );
		$pack_refs = array( 'cosmetology_nail_01', 'realtor_01', 'plumber_01', 'disaster_recovery_01' );
		foreach ( $pack_refs as $key ) {
			$this->assertNotNull( $registry->get( $key ), "Pack lpagery_rule_ref should resolve: {$key}" );
		}
	}
}
