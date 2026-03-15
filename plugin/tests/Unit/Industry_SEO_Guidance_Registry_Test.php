<?php
/**
 * Unit tests for Industry_SEO_Guidance_Registry: valid rules, invalid scope/malformed objects, deterministic output (Prompt 336).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_SEO_Guidance_Registry.php';

final class Industry_SEO_Guidance_Registry_Test extends TestCase {

	private function valid_rule( string $key = 'legal_entity_01' ): array {
		return array(
			Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY => $key,
			Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY      => 'legal',
			Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER   => Industry_SEO_Guidance_Registry::SUPPORTED_SCHEMA_VERSION,
			Industry_SEO_Guidance_Registry::FIELD_STATUS           => Industry_SEO_Guidance_Registry::STATUS_ACTIVE,
		);
	}

	public function test_registry_loads_valid_rule_and_get_returns_it(): void {
		$registry = new Industry_SEO_Guidance_Registry();
		$registry->load( array( $this->valid_rule( 'legal_entity_01' ) ) );
		$rule = $registry->get( 'legal_entity_01' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'legal_entity_01', $rule[ Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY ] );
		$this->assertSame( 'legal', $rule[ Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( Industry_SEO_Guidance_Registry::STATUS_ACTIVE, $rule[ Industry_SEO_Guidance_Registry::FIELD_STATUS ] );
	}

	public function test_valid_rule_with_guidance_fields(): void {
		$rule = $this->valid_rule( 'realtor_local_01' );
		$rule[ Industry_SEO_Guidance_Registry::FIELD_PAGE_FAMILY ] = 'landing_realtor';
		$rule[ Industry_SEO_Guidance_Registry::FIELD_TITLE_PATTERNS ] = 'Service Area | Business Name';
		$rule[ Industry_SEO_Guidance_Registry::FIELD_H1_PATTERNS ] = 'Service in {location}';
		$rule[ Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE ] = 'strong';
		$registry = new Industry_SEO_Guidance_Registry();
		$registry->load( array( $rule ) );
		$loaded = $registry->get( 'realtor_local_01' );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'landing_realtor', $loaded[ Industry_SEO_Guidance_Registry::FIELD_PAGE_FAMILY ] );
		$this->assertSame( 'strong', $loaded[ Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE ] );
	}

	public function test_invalid_scope_or_malformed_rule_skipped(): void {
		$registry = new Industry_SEO_Guidance_Registry();
		$invalid_version = $this->valid_rule( 'bad_version' );
		$invalid_version[ Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER ] = '2';
		$invalid_status = $this->valid_rule( 'bad_status' );
		$invalid_status[ Industry_SEO_Guidance_Registry::FIELD_STATUS ] = 'invalid';
		$registry->load( array( $invalid_version, $invalid_status, $this->valid_rule( 'good' ) ) );
		$this->assertNull( $registry->get( 'bad_version' ) );
		$this->assertNull( $registry->get( 'bad_status' ) );
		$this->assertNotNull( $registry->get( 'good' ) );
		$this->assertCount( 1, $registry->get_all() );
		$this->assertNotEmpty( $registry->validate_rule( $invalid_version ) );
		$this->assertContains( 'unsupported_version', $registry->validate_rule( $invalid_version ) );
	}

	public function test_list_by_industry_and_deterministic_output(): void {
		$registry = new Industry_SEO_Guidance_Registry();
		$registry->load( array(
			$this->valid_rule( 'legal_01' ),
			array_merge( $this->valid_rule( 'legal_02' ), array( Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'legal' ) ),
			array_merge( $this->valid_rule( 'realtor_01' ), array( Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'realtor' ) ),
		) );
		$legal = $registry->list_by_industry( 'legal' );
		$this->assertCount( 2, $legal );
		$realtor = $registry->list_by_industry( 'realtor' );
		$this->assertCount( 1, $realtor );
		$all = $registry->get_all();
		$this->assertCount( 3, $all );
		$keys = array_column( $all, Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY );
		$this->assertSame( $keys, array_values( array_unique( $keys ) ) );
	}

	/** Built-in definitions (Prompt 359): four industries; pack seo_guidance_ref keys resolve. */
	public function test_builtin_definitions_load(): void {
		$defs = Industry_SEO_Guidance_Registry::get_builtin_definitions();
		$this->assertGreaterThanOrEqual( 4, count( $defs ) );
		$registry = new Industry_SEO_Guidance_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 4, count( $registry->get_all() ) );
	}

	public function test_pack_seo_guidance_refs_resolve(): void {
		$registry = new Industry_SEO_Guidance_Registry();
		$registry->load( Industry_SEO_Guidance_Registry::get_builtin_definitions() );
		$pack_refs = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );
		foreach ( $pack_refs as $key ) {
			$this->assertNotNull( $registry->get( $key ), "Pack seo_guidance_ref should resolve: {$key}" );
		}
	}
}
