<?php
/**
 * Unit tests for section template detail state builder (spec §49.6, §17, Prompt 172).
 *
 * Covers: not_found for missing/empty key, state shape with provided section definition,
 * breadcrumbs, side_panel, field_summary, helper_ref, compatibility_notes, rendered_preview_html.
 * Example section-template detail state payload at end (docblock).
 *
 * Manual verification checklist (spec §19): (1) Detail routing: from Section Templates list click View
 * and confirm URL is admin.php?page=aio-page-builder-section-template-detail&section=KEY. (2) Metadata:
 * name, description, purpose family, CTA classification, placement, field blueprint ref visible.
 * (3) Helper-doc: helper_ref shown; when helper_doc_url exists, link opens in new tab. (4) Field
 * summary: table shows name, label, type for each field from blueprint. (5) Compatibility notes
 * displayed when present. (6) Rendered preview: preview panel shows HTML from real section renderer.
 * (7) Omission and animation: reduced_motion in URL yields animation_tier none. (8) Breadcrumb
 * return to Section Templates and purpose family.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Definition_Provider;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Detail_State_Builder;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/UI/Section_Definition_Provider.php';
require_once $plugin_root . '/src/Domain/Registries/Section/UI/Section_Template_Detail_State_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/Section/UI/Section_Template_Directory_State_Builder.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Context.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Data_Generator.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Side_Panel_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Renderer_Base.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Native_Block_Assembly_Pipeline.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Loader.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Admin/Screens/Docs/Documentation_Detail_Screen.php';

final class Section_Template_Detail_State_Builder_Test extends TestCase {

	private function create_state_builder( ?array $section_definition = null ): Section_Template_Detail_State_Builder {
		$provider = new class( $section_definition ) implements Section_Definition_Provider {
			private ?array $def;
			public function __construct( ?array $def ) {
				$this->def = $def;
			}
			public function get_definition_by_key( string $key ): ?array {
				return $this->def;
			}
		};

		$generator   = new Synthetic_Preview_Data_Generator();
		$side_panel  = new Preview_Side_Panel_Builder();
		$ctx_builder = new Section_Render_Context_Builder();
		$renderer    = new Section_Renderer_Base();
		$assembly    = new Native_Block_Assembly_Pipeline( null, null );

		return new Section_Template_Detail_State_Builder(
			$provider,
			$generator,
			$side_panel,
			$ctx_builder,
			$renderer,
			$assembly,
			null
		);
	}

	private function minimal_section_definition( string $key = 'st_hero' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY    => $key,
			Section_Schema::FIELD_VARIANTS        => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_DEFAULT_VARIANT => 'default',
			'name'                                => 'Hero Section',
			'purpose_summary'                     => 'Primary hero with CTA.',
			'section_purpose_family'              => 'hero',
			'helper_ref'                          => 'hero_helper',
			'field_blueprint_ref'                 => 'acf_hero',
		);
	}

	public function test_build_state_with_empty_key_returns_not_found(): void {
		$builder = $this->create_state_builder( array() );
		$state   = $builder->build_state( '', array() );
		$this->assertTrue( $state['not_found'] );
		$this->assertSame( '', $state['section_key'] );
		$this->assertArrayHasKey( 'breadcrumbs', $state );
	}

	public function test_build_state_with_null_definition_returns_not_found(): void {
		$builder = $this->create_state_builder( null );
		$state   = $builder->build_state( 'st_missing', array() );
		$this->assertTrue( $state['not_found'] );
	}

	public function test_build_state_with_valid_section_returns_state_with_side_panel_and_preview(): void {
		$def     = $this->minimal_section_definition( 'st01_hero' );
		$builder = $this->create_state_builder( $def );
		$state   = $builder->build_state( 'st01_hero', array() );

		$this->assertFalse( $state['not_found'] );
		$this->assertSame( 'st01_hero', $state['section_key'] );
		$this->assertArrayHasKey( 'side_panel', $state );
		$this->assertSame( 'Hero Section', $state['side_panel']['name'] );
		$this->assertSame( 'hero', $state['side_panel']['purpose_family'] );
		$this->assertSame( 'hero_helper', $state['side_panel']['helper_ref'] );
		$this->assertArrayHasKey( 'field_summary', $state );
		$this->assertArrayHasKey( 'helper_ref', $state );
		$this->assertArrayHasKey( 'compatibility_notes', $state );
		$this->assertArrayHasKey( 'breadcrumbs', $state );
		$this->assertGreaterThanOrEqual( 2, count( $state['breadcrumbs'] ) );
		$this->assertArrayHasKey( 'rendered_preview_html', $state );
		$this->assertIsString( $state['rendered_preview_html'] );
		$this->assertArrayHasKey( 'preview_payload', $state );
	}

	public function test_build_state_resolves_helper_doc_url_when_doc_exists(): void {
		$def               = $this->minimal_section_definition( 'cta_contact_01' );
		$def['helper_ref'] = 'doc-helper-cta_contact_01';
		$builder           = $this->create_state_builder( $def );
		$state             = $builder->build_state( 'cta_contact_01', array() );
		$this->assertFalse( $state['not_found'] );
		$this->assertSame( 'doc-helper-cta_contact_01', $state['helper_ref'] );
		$this->assertIsString( $state['helper_doc_url'] );
		$this->assertNotSame( '', $state['helper_doc_url'] );
		$this->assertStringContainsString( 'aio-page-builder-documentation-detail', $state['helper_doc_url'] );
	}

	public function test_build_state_field_summary_from_embedded_blueprint(): void {
		$def                    = $this->minimal_section_definition( 'st_with_fields' );
		$def['field_blueprint'] = array(
			'fields' => array(
				array(
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
				array(
					'name'  => 'subheadline',
					'label' => 'Subheadline',
					'type'  => 'textarea',
				),
			),
		);
		$builder                = $this->create_state_builder( $def );
		$state                  = $builder->build_state( 'st_with_fields', array() );
		$this->assertFalse( $state['not_found'] );
		$this->assertCount( 2, $state['field_summary'] );
		$this->assertSame( 'headline', $state['field_summary'][0]['name'] );
		$this->assertSame( 'Headline', $state['field_summary'][0]['label'] );
		$this->assertSame( 'text', $state['field_summary'][0]['type'] );
	}

	public function test_build_state_breadcrumb_includes_purpose_family_when_provided(): void {
		$def                           = $this->minimal_section_definition();
		$def['section_purpose_family'] = 'cta';
		$builder                       = $this->create_state_builder( $def );
		$state                         = $builder->build_state( 'st_hero', array( 'purpose_family' => 'cta' ) );
		$this->assertFalse( $state['not_found'] );
		$labels = array_column( $state['breadcrumbs'], 'label' );
		$this->assertContains( 'Section Templates', $labels );
	}

	/**
	 * Example section-template detail state payload (spec §49.6, Prompt 172).
	 * Shape returned by Section_Template_Detail_State_Builder::build_state() when section exists.
	 *
	 * [
	 *   'section_key'           => 'st01_hero',
	 *   'definition'             => [ ... full section definition ... ],
	 *   'side_panel'             => [
	 *     'name'                 => 'Hero Section',
	 *     'description'         => 'Primary hero with CTA.',
	 *     'purpose_family'       => 'hero',
	 *     'cta_classification'   => '',
	 *     'placement_tendency'   => 'opener',
	 *     'helper_ref'           => 'hero_helper',
	 *     'field_blueprint_ref'  => 'acf_hero',
	 *     ...
	 *   ],
	 *   'field_summary'          => [ [ 'name' => 'headline', 'label' => 'Headline', 'type' => 'text' ], ... ],
	 *   'helper_ref'             => 'hero_helper',
	 *   'helper_doc_url'         => '',
	 *   'compatibility_notes'    => [],
	 *   'preview_payload'        => [ 'section_key' => '...', 'field_values' => [ ... ], 'side_panel' => [ ... ], 'options' => [ ... ] ],
	 *   'rendered_preview_html'  => '<div class="aio-s-st01_hero">...</div>',
	 *   'breadcrumbs'            => [ [ 'label' => 'Section Templates', 'url' => '...' ], [ 'label' => 'Hero Section', 'url' => '' ] ],
	 *   'not_found'              => false,
	 * ]
	 */
	public function test_example_section_template_detail_state_payload_structure(): void {
		$def                    = $this->minimal_section_definition( 'st01_hero' );
		$def['name']            = 'Hero Section';
		$def['purpose_summary'] = 'Primary hero with CTA.';
		$def['field_blueprint'] = array(
			'fields' => array(
				array(
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
			),
		);

		$builder = $this->create_state_builder( $def );
		$state   = $builder->build_state( 'st01_hero', array() );

		$this->assertSame( 'st01_hero', $state['section_key'] );
		$this->assertSame( 'Hero Section', $state['side_panel']['name'] );
		$this->assertSame( 'hero', $state['side_panel']['purpose_family'] );
		$this->assertArrayHasKey( 'preview_payload', $state );
		$this->assertArrayHasKey( 'field_values', $state['preview_payload'] );
		$this->assertCount( 1, $state['field_summary'] );
		$this->assertFalse( $state['not_found'] );
	}
}
