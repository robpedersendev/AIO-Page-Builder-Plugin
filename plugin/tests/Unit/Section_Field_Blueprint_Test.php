<?php
/**
 * Unit tests for section field blueprint validator, normalizer, and service (Prompt 036).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';

final class Section_Field_Blueprint_Test extends TestCase {

	private function valid_minimal_blueprint(): array {
		return array(
			'blueprint_id'    => 'acf_blueprint_st01',
			'section_key'     => 'st01_hero',
			'section_version' => '1',
			'label'           => 'Hero Section Fields',
			'description'     => 'Headline, subhead, CTA.',
			'fields'          => array(
				array(
					'key'          => 'field_st01_hero_headline',
					'name'         => 'headline',
					'label'        => 'Headline',
					'type'         => 'text',
					'required'     => true,
					'instructions' => 'Primary hero headline.',
					'validation'   => array(
						'required'  => true,
						'maxlength' => 120,
					),
					'lpagery'      => array(
						'token_compatible' => true,
						'token_name'       => '{{headline}}',
					),
				),
				array(
					'key'      => 'field_st01_hero_subheadline',
					'name'     => 'subheadline',
					'label'    => 'Subheadline',
					'type'     => 'textarea',
					'required' => false,
				),
				array(
					'key'      => 'field_st01_hero_cta',
					'name'     => 'cta',
					'label'    => 'CTA Link',
					'type'     => 'link',
					'required' => false,
				),
			),
		);
	}

	private function valid_repeater_blueprint(): array {
		return array(
			'blueprint_id'    => 'acf_blueprint_st05_faq',
			'section_key'     => 'st05_faq',
			'section_version' => '1',
			'label'           => 'FAQ Section Fields',
			'fields'          => array(
				array(
					'key'      => 'field_st05_faq_section_title',
					'name'     => 'section_title',
					'label'    => 'Section Title',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'key'          => 'field_st05_faq_items',
					'name'         => 'faq_items',
					'label'        => 'FAQ Items',
					'type'         => 'repeater',
					'required'     => true,
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 20,
					'button_label' => 'Add FAQ',
					'sub_fields'   => array(
						array(
							'key'      => 'field_st05_faq_items_question',
							'name'     => 'question',
							'label'    => 'Question',
							'type'     => 'text',
							'required' => true,
						),
						array(
							'key'      => 'field_st05_faq_items_answer',
							'name'     => 'answer',
							'label'    => 'Answer',
							'type'     => 'wysiwyg',
							'required' => true,
						),
					),
				),
			),
		);
	}

	public function test_validator_accepts_valid_blueprint(): void {
		$validator = new Section_Field_Blueprint_Validator();
		$errors    = $validator->validate( $this->valid_minimal_blueprint(), 'st01_hero', 'acf_blueprint_st01' );
		$this->assertEmpty( $errors );
		$this->assertTrue( $validator->is_valid( $this->valid_minimal_blueprint() ) );
	}

	public function test_validator_rejects_invalid_structure(): void {
		$validator = new Section_Field_Blueprint_Validator();
		$bad       = array(
			'blueprint_id' => 'bad',
			'section_key'  => 'x',
		);
		$errors    = $validator->validate( $bad );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'section_version', implode( ' ', $errors ) );
	}

	public function test_validator_rejects_alignment_mismatch(): void {
		$validator = new Section_Field_Blueprint_Validator();
		$bp        = $this->valid_minimal_blueprint();
		$errors    = $validator->validate( $bp, 'st99_other', 'acf_blueprint_st01' );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'section_key', implode( ' ', $errors ) );
	}

	public function test_validator_rejects_unsupported_field_type(): void {
		$validator      = new Section_Field_Blueprint_Validator();
		$bp             = $this->valid_minimal_blueprint();
		$bp['fields'][] = array(
			'key'   => 'field_st01_x',
			'name'  => 'x',
			'label' => 'X',
			'type'  => 'flexible_content',
		);
		$errors         = $validator->validate( $bp );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Unsupported', implode( ' ', $errors ) );
	}

	public function test_validator_rejects_duplicate_field_keys(): void {
		$validator      = new Section_Field_Blueprint_Validator();
		$bp             = $this->valid_minimal_blueprint();
		$bp['fields'][] = array(
			'key'   => 'field_st01_hero_headline',
			'name'  => 'dup',
			'label' => 'Dup',
			'type'  => 'text',
		);
		$errors         = $validator->validate( $bp );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'duplicate', implode( ' ', $errors ) );
	}

	public function test_normalizer_produces_deterministic_keys(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$bp         = $this->valid_minimal_blueprint();
		$result     = $normalizer->normalize( $bp, 'st01_hero', 'acf_blueprint_st01' );
		$this->assertEmpty( $result['errors'] );
		$n = $result['normalized'];
		$this->assertArrayHasKey( 'fields', $n );
		$this->assertCount( 3, $n['fields'] );
		$this->assertSame( 'field_st01_hero_headline', $n['fields'][0]['key'] );
		$this->assertSame( 'field_st01_hero_subheadline', $n['fields'][1]['key'] );
		$this->assertSame( 'field_st01_hero_cta', $n['fields'][2]['key'] );
	}

	public function test_normalizer_generates_keys_when_missing(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$bp         = $this->valid_minimal_blueprint();
		unset( $bp['fields'][0]['key'] );
		$result = $normalizer->normalize( $bp );
		$this->assertEmpty( $result['errors'] );
		$this->assertStringStartsWith( 'field_', $result['normalized']['fields'][0]['key'] );
		$this->assertStringContainsString( 'headline', $result['normalized']['fields'][0]['key'] );
	}

	public function test_normalizer_preserves_metadata(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$bp         = $this->valid_minimal_blueprint();
		$result     = $normalizer->normalize( $bp );
		$this->assertEmpty( $result['errors'] );
		$f0 = $result['normalized']['fields'][0];
		$this->assertArrayHasKey( 'instructions', $f0 );
		$this->assertSame( 'Primary hero headline.', $f0['instructions'] );
		$this->assertArrayHasKey( 'validation', $f0 );
		$this->assertSame( 120, $f0['validation']['maxlength'] );
		$this->assertArrayHasKey( 'lpagery', $f0 );
		$this->assertTrue( $f0['lpagery']['token_compatible'] );
	}

	public function test_normalizer_repeater_subfield_keys(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$result     = $normalizer->normalize( $this->valid_repeater_blueprint() );
		$this->assertEmpty( $result['errors'] );
		$repeater = $result['normalized']['fields'][1];
		$this->assertSame( 'repeater', $repeater['type'] );
		$this->assertArrayHasKey( 'sub_fields', $repeater );
		$this->assertCount( 2, $repeater['sub_fields'] );
		$this->assertSame( 'field_st05_faq_faq_items_question', $repeater['sub_fields'][0]['key'] );
		$this->assertSame( 'field_st05_faq_faq_items_answer', $repeater['sub_fields'][1]['key'] );
	}

	public function test_normalizer_rejects_invalid_input(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$result     = $normalizer->normalize( array( 'blueprint_id' => 'x' ) );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertEmpty( $result['normalized'] );
	}

	public function test_service_get_blueprint_from_definition(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );

		$definition = array(
			Section_Schema::FIELD_INTERNAL_KEY        => 'st01_hero',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF => 'acf_blueprint_st01',
			'version'                                 => array( 'version' => '1' ),
			Section_Field_Blueprint_Service::EMBEDDED_BLUEPRINT_KEY => $this->valid_minimal_blueprint(),
		);
		$bp         = $service->get_blueprint_from_definition( $definition );
		$this->assertNotNull( $bp );
		$this->assertSame( 'acf_blueprint_st01', $bp['blueprint_id'] );
		$this->assertSame( 'st01_hero', $bp['section_key'] );
		$this->assertCount( 3, $bp['fields'] );
	}

	public function test_service_returns_null_when_no_embedded_blueprint(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );

		$definition = array(
			Section_Schema::FIELD_INTERNAL_KEY        => 'st01_hero',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF => 'acf_blueprint_st01',
		);
		$bp         = $service->get_blueprint_from_definition( $definition );
		$this->assertNull( $bp );
	}

	public function test_service_get_group_key_for_section(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );

		$this->assertSame( 'group_aio_st01_hero', $service->get_group_key_for_section( 'st01_hero' ) );
		$this->assertSame( Field_Key_Generator::group_key( 'st05_faq' ), $service->get_group_key_for_section( 'st05_faq' ) );
	}

	public function test_service_validate_and_normalize(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );

		$result = $service->validate_and_normalize( $this->valid_minimal_blueprint(), 'st01_hero', 'acf_blueprint_st01' );
		$this->assertEmpty( $result['errors'] );
		$this->assertIsArray( $result['normalized'] );
		$this->assertSame( 'st01_hero', $result['normalized']['section_key'] );

		$bad_result = $service->validate_and_normalize( array( 'x' => 1 ) );
		$this->assertNotEmpty( $bad_result['errors'] );
		$this->assertNull( $bad_result['normalized'] );
	}

	public function test_example_normalized_blueprint_structure(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$result     = $normalizer->normalize( $this->valid_minimal_blueprint(), 'st01_hero', 'acf_blueprint_st01' );
		$this->assertEmpty( $result['errors'] );
		$n = $result['normalized'];

		$this->assertArrayHasKey( 'blueprint_id', $n );
		$this->assertArrayHasKey( 'section_key', $n );
		$this->assertArrayHasKey( 'section_version', $n );
		$this->assertArrayHasKey( 'label', $n );
		$this->assertArrayHasKey( 'fields', $n );

		$field = $n['fields'][0];
		$this->assertArrayHasKey( 'key', $field );
		$this->assertArrayHasKey( 'name', $field );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertArrayHasKey( 'type', $field );
		$this->assertArrayHasKey( 'required', $field );
		$this->assertArrayHasKey( 'instructions', $field );
		$this->assertArrayHasKey( 'validation', $field );
		$this->assertArrayHasKey( 'lpagery', $field );

		$this->assertSame( 'field_st01_hero_headline', $field['key'] );
		$this->assertSame( 'headline', $field['name'] );
		$this->assertSame( 'text', $field['type'] );
		$this->assertTrue( $field['required'] );
	}

	public function test_get_lpagery_compatible_field_keys_returns_supported_types_only(): void {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );
		$result     = $normalizer->normalize( $this->valid_minimal_blueprint(), 'st01_hero', 'acf_blueprint_st01' );
		$this->assertEmpty( $result['errors'] );
		$blueprint = $result['normalized'];

		$keys = $service->get_lpagery_compatible_field_keys( $blueprint );
		$this->assertIsArray( $keys );
		$this->assertContains( 'field_st01_hero_headline', $keys );
		$this->assertContains( 'field_st01_hero_subheadline', $keys );
		$this->assertContains( 'field_st01_hero_cta', $keys );
		$this->assertCount( 3, $keys );
	}

	public function test_get_lpagery_compatible_field_keys_empty_when_no_fields(): void {
		$repo       = new Section_Template_Repository();
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );
		$keys       = $service->get_lpagery_compatible_field_keys( array( 'section_key' => 'st01_hero' ) );
		$this->assertSame( array(), $keys );
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}
}
