<?php
/**
 * Unit tests for Industry_Question_Pack_Registry: get, get_supported_industry_keys, load (industry-question-pack-contract; Prompt 329).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Definitions.php';

final class Industry_Question_Pack_Registry_Test extends TestCase {

	public function test_load_and_get_returns_pack_for_supported_industry(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$pack = $registry->get( 'realtor' );
		$this->assertIsArray( $pack );
		$this->assertSame( 'realtor', $pack[ Industry_Question_Pack_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertArrayHasKey( Industry_Question_Pack_Registry::FIELD_FIELDS, $pack );
		$this->assertIsArray( $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ] );
	}

	public function test_get_returns_null_for_unknown_industry(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$this->assertNull( $registry->get( 'unknown_vertical' ) );
		$this->assertNull( $registry->get( '' ) );
	}

	public function test_get_supported_industry_keys_returns_first_four(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$keys = $registry->get_supported_industry_keys();
		$this->assertContains( 'cosmetology_nail', $keys );
		$this->assertContains( 'realtor', $keys );
		$this->assertContains( 'plumber', $keys );
		$this->assertContains( 'disaster_recovery', $keys );
		$this->assertCount( 4, $keys );
	}

	public function test_load_skips_invalid_entries(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load(
			array(
				array(
					Industry_Question_Pack_Registry::FIELD_INDUSTRY_KEY => 'valid',
					Industry_Question_Pack_Registry::FIELD_FIELDS => array(
						array(
							'key'   => 'f1',
							'label' => 'F1',
							'type'  => 'text',
						),
					),
				),
				array( Industry_Question_Pack_Registry::FIELD_INDUSTRY_KEY => '' ),
				array(
					'industry_key' => 'no_fields',
					'fields'       => 'not_array',
				),
			)
		);
		$this->assertNotNull( $registry->get( 'valid' ) );
		$this->assertNull( $registry->get( '' ) );
		$this->assertNull( $registry->get( 'no_fields' ) );
	}

	public function test_unsupported_industry_fails_safely(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load( array() );
		$this->assertNull( $registry->get( 'cosmetology_nail' ) );
		$this->assertSame( array(), $registry->get_supported_industry_keys() );
	}

	/** @see Prompt 362: seeded packs have expected structure; field keys map to question_pack_answers[industry_key][field_key]. */
	public function test_seeded_packs_have_expected_structure_and_field_keys(): void {
		$registry = new Industry_Question_Pack_Registry();
		$registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$expected = array(
			'cosmetology_nail'  => array( 'service_types', 'booking_style', 'license_notes' ),
			'realtor'           => array( 'market_focus', 'listing_types', 'service_areas' ),
			'plumber'           => array( 'service_scope', 'emergency_offered', 'service_areas' ),
			'disaster_recovery' => array( 'response_type', 'emergency_24_7', 'coverage_areas' ),
		);
		foreach ( $expected as $industry_key => $field_keys ) {
			$pack = $registry->get( $industry_key );
			$this->assertNotNull( $pack, "Pack for {$industry_key} must load." );
			$this->assertSame( $industry_key, $pack[ Industry_Question_Pack_Registry::FIELD_INDUSTRY_KEY ] );
			$fields      = $pack[ Industry_Question_Pack_Registry::FIELD_FIELDS ];
			$actual_keys = array_column( $fields, 'key' );
			$this->assertSame( $field_keys, $actual_keys, "Field keys for {$industry_key} must match storage mapping." );
			foreach ( $fields as $field ) {
				$this->assertArrayHasKey( 'label', $field );
				$this->assertArrayHasKey( 'type', $field );
				$this->assertContains( $field['type'], array( 'text', 'textarea', 'boolean' ), "Field type must be allowed: {$field['key']}" );
			}
		}
	}
}
