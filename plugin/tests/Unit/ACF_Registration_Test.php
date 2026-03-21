<?php
/**
 * Unit tests for ACF registration pipeline: field builder, group builder, registrar (Prompt 037).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar;
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
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';

final class ACF_Registration_Test extends TestCase {

	private function normalized_hero_blueprint(): array {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$blueprint  = array(
			'blueprint_id'    => 'acf_blueprint_st01',
			'section_key'     => 'st01_hero',
			'section_version' => '1',
			'label'           => 'Hero Section Fields',
			'fields'          => array(
				array(
					'key'      => 'field_st01_hero_headline',
					'name'     => 'headline',
					'label'    => 'Headline',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'key'   => 'field_st01_hero_subheadline',
					'name'  => 'subheadline',
					'label' => 'Subheadline',
					'type'  => 'textarea',
				),
				array(
					'key'   => 'field_st01_hero_cta',
					'name'  => 'cta',
					'label' => 'CTA Link',
					'type'  => 'link',
				),
			),
		);
		$result     = $normalizer->normalize( $blueprint );
		$this->assertEmpty( $result['errors'] );
		return $result['normalized'];
	}

	private function normalized_repeater_blueprint(): array {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$blueprint  = array(
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
					'key'        => 'field_st05_faq_faq_items',
					'name'       => 'faq_items',
					'label'      => 'FAQ Items',
					'type'       => 'repeater',
					'sub_fields' => array(
						array(
							'key'      => 'field_st05_faq_faq_items_question',
							'name'     => 'question',
							'label'    => 'Question',
							'type'     => 'text',
							'required' => true,
						),
						array(
							'key'      => 'field_st05_faq_faq_items_answer',
							'name'     => 'answer',
							'label'    => 'Answer',
							'type'     => 'wysiwyg',
							'required' => true,
						),
					),
				),
			),
		);
		$result     = $normalizer->normalize( $blueprint );
		$this->assertEmpty( $result['errors'] );
		return $result['normalized'];
	}

	private function create_registrar( bool $with_section_repo = false ): ACF_Group_Registrar {
		$validator  = new Section_Field_Blueprint_Validator();
		$normalizer = new Section_Field_Blueprint_Normalizer( $validator );
		$repo       = new Section_Template_Repository();
		$service    = new Section_Field_Blueprint_Service( $repo, $validator, $normalizer );
		$builder    = new ACF_Group_Builder( new ACF_Field_Builder() );
		return new ACF_Group_Registrar( $service, $builder, $with_section_repo ? $repo : null );
	}

	public function test_field_builder_produces_deterministic_field(): void {
		$builder = new ACF_Field_Builder();
		$field   = array(
			'key'      => 'field_st01_hero_headline',
			'name'     => 'headline',
			'label'    => 'Headline',
			'type'     => 'text',
			'required' => true,
		);
		$acf     = $builder->build_field( $field, 'group_aio_st01_hero' );
		$this->assertSame( 'field_st01_hero_headline', $acf['key'] );
		$this->assertSame( 'headline', $acf['name'] );
		$this->assertSame( 'Headline', $acf['label'] );
		$this->assertSame( 'text', $acf['type'] );
		$this->assertSame( 1, $acf['required'] );
		$this->assertSame( 'group_aio_st01_hero', $acf['parent'] );
	}

	public function test_field_builder_supports_nested_subfields(): void {
		$builder  = new ACF_Field_Builder();
		$repeater = array(
			'key'        => 'field_st05_faq_faq_items',
			'name'       => 'faq_items',
			'label'      => 'FAQ Items',
			'type'       => 'repeater',
			'sub_fields' => array(
				array(
					'key'   => 'field_st05_faq_faq_items_question',
					'name'  => 'question',
					'label' => 'Question',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_st05_faq_faq_items_answer',
					'name'  => 'answer',
					'label' => 'Answer',
					'type'  => 'wysiwyg',
				),
			),
		);
		$acf      = $builder->build_field( $repeater, 'group_aio_st05_faq' );
		$this->assertArrayHasKey( 'sub_fields', $acf );
		$this->assertCount( 2, $acf['sub_fields'] );
		$this->assertSame( 'field_st05_faq_faq_items_question', $acf['sub_fields'][0]['key'] );
		$this->assertSame( 'field_st05_faq_faq_items_answer', $acf['sub_fields'][1]['key'] );
		$this->assertSame( 'field_st05_faq_faq_items', $acf['sub_fields'][0]['parent'] );
	}

	public function test_group_builder_produces_deterministic_group(): void {
		$builder   = new ACF_Group_Builder( new ACF_Field_Builder() );
		$blueprint = $this->normalized_hero_blueprint();
		$group     = $builder->build_group( $blueprint );
		$this->assertNotNull( $group );
		$this->assertSame( 'group_aio_st01_hero', $group['key'] );
		$this->assertSame( 'Hero Section Fields', $group['title'] );
		$this->assertArrayHasKey( 'fields', $group );
		$this->assertCount( 3, $group['fields'] );
		$this->assertArrayHasKey( 'location', $group );
		$this->assertSame( ACF_Group_Builder::PLACEHOLDER_POST_TYPE, $group['location'][0][0]['value'] );
		$this->assertSame( 'st01_hero', $group['_aio_section_key'] );
	}

	public function test_group_builder_includes_nested_repeater(): void {
		$builder   = new ACF_Group_Builder( new ACF_Field_Builder() );
		$blueprint = $this->normalized_repeater_blueprint();
		$group     = $builder->build_group( $blueprint );
		$this->assertNotNull( $group );
		$this->assertSame( 'group_aio_st05_faq', $group['key'] );
		$repeater = null;
		foreach ( $group['fields'] as $f ) {
			if ( ( $f['type'] ?? '' ) === 'repeater' ) {
				$repeater = $f;
				break;
			}
		}
		$this->assertNotNull( $repeater );
		$this->assertArrayHasKey( 'sub_fields', $repeater );
		$this->assertCount( 2, $repeater['sub_fields'] );
	}

	public function test_group_builder_rejects_invalid_blueprint(): void {
		$builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$bad     = array( 'section_key' => 'x' );
		$this->assertNull( $builder->build_group( $bad ) );

		$empty_fields = array(
			'blueprint_id'    => 'acf_x',
			'section_key'     => 'st99_x',
			'section_version' => '1',
			'label'           => 'X',
			'fields'          => array(),
		);
		$this->assertNull( $builder->build_group( $empty_fields ) );
	}

	public function test_registrar_assembles_group_without_registering(): void {
		$registrar = $this->create_registrar();
		$blueprint = $this->normalized_hero_blueprint();
		$group     = $registrar->assemble_group( $blueprint );
		$this->assertNotNull( $group );
		$this->assertSame( 'group_aio_st01_hero', $group['key'] );
		$this->assertCount( 3, $group['fields'] );
	}

	public function test_registrar_rejects_invalid_blueprint(): void {
		$registrar = $this->create_registrar();
		$this->assertNull( $registrar->assemble_group( array() ) );
		$this->assertNull( $registrar->assemble_group( array( 'section_key' => 'x' ) ) );
	}

	public function test_registrar_is_acf_available_true_when_bootstrap_stubs_acf(): void {
		$registrar = $this->create_registrar();
		// * PHPUnit bootstrap defines acf_add_local_field_group; same as real WP+ACF presence for this check.
		$this->assertTrue( $registrar->is_acf_available() );
	}

	public function test_registrar_register_all_returns_zero_when_no_blueprints_in_repo(): void {
		$registrar = $this->create_registrar();
		$count     = $registrar->register_all();
		$this->assertSame( 0, $count );
	}

	public function test_registrar_register_sections_for_page_equals_register_sections(): void {
		$registrar = $this->create_registrar();
		$keys      = array( 'st01_hero', 'st05_faq' );
		$this->assertSame( $registrar->register_sections( $keys ), $registrar->register_sections_for_page( $keys ) );
	}

	public function test_registrar_register_by_family_returns_zero_when_no_section_repo(): void {
		$registrar = $this->create_registrar( false );
		$this->assertSame( 0, $registrar->register_by_family( 'hero_primary' ) );
	}

	public function test_example_assembled_acf_group_array(): void {
		$registrar = $this->create_registrar();
		$blueprint = $this->normalized_hero_blueprint();
		$group     = $registrar->assemble_group( $blueprint );
		$this->assertIsArray( $group );
		$this->assertArrayHasKey( 'key', $group );
		$this->assertArrayHasKey( 'title', $group );
		$this->assertArrayHasKey( 'fields', $group );
		$this->assertArrayHasKey( 'location', $group );

		$first_field = $group['fields'][0];
		$this->assertArrayHasKey( 'key', $first_field );
		$this->assertArrayHasKey( 'name', $first_field );
		$this->assertArrayHasKey( 'label', $first_field );
		$this->assertArrayHasKey( 'type', $first_field );
		$this->assertArrayHasKey( 'parent', $first_field );
		$this->assertSame( 'group_aio_st01_hero', $first_field['parent'] );
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}
}
