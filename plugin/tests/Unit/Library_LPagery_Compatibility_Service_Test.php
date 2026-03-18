<?php
/**
 * Unit tests for Library_LPagery_Compatibility_Service and LPagery_Compatibility_Result (Prompt 179).
 * Covers: supported mapping resolution, unsupported-case rejection, preview-safe metadata, canonical token identity.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Rendering\LPagery\Library_LPagery_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Compatibility_Result;
use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Token_Compatibility_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/LPagery/LPagery_Compatibility_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/LPagery/LPagery_Token_Compatibility_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/LPagery/LPagery_Token_Mapping_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/LPagery/Library_LPagery_Compatibility_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';

final class Library_LPagery_Compatibility_Service_Test extends TestCase {

	private function create_service(): Library_LPagery_Compatibility_Service {
		$token_service = new LPagery_Token_Compatibility_Service();
		return new Library_LPagery_Compatibility_Service( $token_service, null, null );
	}

	public function test_supported_mapping_resolution(): void {
		$service    = $this->create_service();
		$definition = array(
			'internal_key'    => 'hero_01',
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => 'text',
					),
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'cta_url',
						Field_Blueprint_Schema::FIELD_TYPE => 'url',
					),
				),
			),
		);
		$result     = $service->get_compatibility_for_section( 'hero_01', $definition );
		$this->assertTrue( $result->is_compatible() );
		$this->assertSame( LPagery_Compatibility_Result::STATE_SUPPORTED, $result->get_compatibility_state() );
		$summary = $result->get_lpagery_mapping_summary();
		$this->assertCount( 2, $summary['supported_mappings'] );
		$this->assertEmpty( $result->get_unsupported_mapping_reasons() );
	}

	public function test_unsupported_case_rejection(): void {
		$service    = $this->create_service();
		$definition = array(
			'internal_key'    => 'proof_01',
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => 'text',
					),
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'related_post',
						Field_Blueprint_Schema::FIELD_TYPE => 'relationship',
					),
				),
			),
		);
		$result     = $service->get_compatibility_for_section( 'proof_01', $definition );
		$reasons    = $result->get_unsupported_mapping_reasons();
		$this->assertNotEmpty( $reasons );
		$this->assertSame( 'related_post', $reasons[0]['field_name'] ?? '' );
		$this->assertStringContainsString( 'relationship', $reasons[0]['reason'] ?? '' );
	}

	public function test_preview_safe_and_canonical_identity_preserved(): void {
		$service    = $this->create_service();
		$definition = array(
			'internal_key'    => 'cta_01',
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => 'text',
					),
				),
			),
		);
		$result     = $service->get_compatibility_for_section( 'cta_01', $definition );
		$summary    = $result->get_lpagery_mapping_summary();
		$this->assertTrue( $summary['preview_safe'] );
		$this->assertTrue( $summary['canonical_identity_preserved'] );
	}

	public function test_validate_field_mapping_supported(): void {
		$service = $this->create_service();
		$out     = $service->validate_field_mapping( 'headline', 'text' );
		$this->assertTrue( $out['supported'] );
		$this->assertSame( '', $out['reason'] );
	}

	public function test_validate_field_mapping_unsupported_type(): void {
		$service = $this->create_service();
		$out     = $service->validate_field_mapping( 'related', 'relationship' );
		$this->assertFalse( $out['supported'] );
		$this->assertNotEmpty( $out['reason'] );
	}

	public function test_page_template_aggregate(): void {
		$service                 = $this->create_service();
		$section_compatibilities = array(
			array(
				'section_key'   => 'hero_01',
				'lpagery_state' => LPagery_Compatibility_Result::STATE_SUPPORTED,
			),
			array(
				'section_key'   => 'cta_01',
				'lpagery_state' => LPagery_Compatibility_Result::STATE_SUPPORTED,
			),
		);
		$result                  = $service->get_compatibility_for_page_template( 'tpl_home', array(), $section_compatibilities );
		$this->assertTrue( $result->is_compatible() );
		$this->assertSame( LPagery_Compatibility_Result::STATE_SUPPORTED, $result->get_compatibility_state() );
		$summary = $result->get_lpagery_mapping_summary();
		$this->assertTrue( $summary['aggregate_from_sections'] );
		$this->assertSame( 2, $summary['sections_with_lpagery_support'] );
	}

	public function test_page_template_unknown_when_no_sections(): void {
		$service = $this->create_service();
		$result  = $service->get_compatibility_for_page_template( 'tpl_empty', array(), array() );
		$this->assertFalse( $result->is_compatible() );
		$this->assertSame( LPagery_Compatibility_Result::STATE_UNKNOWN, $result->get_compatibility_state() );
	}

	/**
	 * Example LPagery compatibility result payload for a section (Prompt 179). Real to_array() output.
	 */
	public function test_example_section_lpagery_compatibility_payload(): void {
		$service    = $this->create_service();
		$definition = array(
			'internal_key'    => 'hero_primary',
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => 'text',
					),
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'cta_url',
						Field_Blueprint_Schema::FIELD_TYPE => 'url',
					),
				),
			),
		);
		$result     = $service->get_compatibility_for_section( 'hero_primary', $definition );
		$payload    = $result->to_array();

		$this->assertArrayHasKey( 'compatible', $payload );
		$this->assertArrayHasKey( 'lpagery_compatibility_state', $payload );
		$this->assertArrayHasKey( 'lpagery_mapping_summary', $payload );
		$this->assertArrayHasKey( 'unsupported_mapping_reasons', $payload );

		$this->assertTrue( $payload['compatible'] );
		$this->assertSame( 'supported', $payload['lpagery_compatibility_state'] );
		$summary = $payload['lpagery_mapping_summary'];
		$this->assertTrue( $summary['preview_safe'] );
		$this->assertTrue( $summary['canonical_identity_preserved'] );
		$this->assertCount( 2, $summary['supported_mappings'] );

		$example_section_payload = array(
			'compatible'                  => true,
			'lpagery_compatibility_state' => 'supported',
			'lpagery_mapping_summary'     => array(
				'supported_mappings'           => array(
					array(
						'field_name'                   => 'headline',
						'field_type'                   => 'text',
						'canonical_identity_preserved' => true,
					),
					array(
						'field_name'                   => 'cta_url',
						'field_type'                   => 'url',
						'canonical_identity_preserved' => true,
					),
				),
				'unsupported_mappings'         => array(),
				'allowed_groups'               => array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' ),
				'canonical_identity_preserved' => true,
				'preview_safe'                 => true,
				'mapping_convention'           => 'group.name',
			),
			'unsupported_mapping_reasons' => array(),
		);
		$this->assertEquals( $example_section_payload['compatible'], $payload['compatible'] );
		$this->assertEquals( $example_section_payload['lpagery_compatibility_state'], $payload['lpagery_compatibility_state'] );
		$this->assertEquals( array_keys( $example_section_payload['lpagery_mapping_summary'] ), array_keys( $payload['lpagery_mapping_summary'] ) );
	}

	/**
	 * Example LPagery compatibility result payload for a page template (Prompt 179). Real to_array() output.
	 */
	public function test_example_page_template_lpagery_compatibility_payload(): void {
		$service                 = $this->create_service();
		$section_compatibilities = array(
			array(
				'section_key'   => 'hero_01',
				'lpagery_state' => LPagery_Compatibility_Result::STATE_SUPPORTED,
			),
			array(
				'section_key'   => 'cta_01',
				'lpagery_state' => LPagery_Compatibility_Result::STATE_UNSUPPORTED,
			),
		);
		$result                  = $service->get_compatibility_for_page_template( 'tpl_landing', array( 'ordered_sections' => array( array(), array() ) ), $section_compatibilities );
		$payload                 = $result->to_array();

		$this->assertArrayHasKey( 'compatible', $payload );
		$this->assertArrayHasKey( 'lpagery_compatibility_state', $payload );
		$this->assertArrayHasKey( 'lpagery_mapping_summary', $payload );
		$summary = $payload['lpagery_mapping_summary'];
		$this->assertTrue( $summary['aggregate_from_sections'] );
		$this->assertTrue( $summary['preview_safe'] );
		$this->assertTrue( $summary['canonical_identity_preserved'] );
		$this->assertSame( 1, $summary['sections_with_lpagery_support'] );
		$this->assertSame( 1, $summary['sections_unsupported'] );
		$this->assertSame( LPagery_Compatibility_Result::STATE_PARTIAL, $payload['lpagery_compatibility_state'] );
	}
}
