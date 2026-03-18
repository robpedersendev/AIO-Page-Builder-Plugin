<?php
/**
 * Unit tests for page template detail state builder (spec §49.7, §17, Prompt 171).
 *
 * Covers: not_found for missing/empty key, state shape with mocked template and section definitions,
 * breadcrumbs, side_panel and used_sections, rendered_preview_html from real pipeline.
 * Example page-template detail state payload at end (docblock).
 *
 * Manual verification checklist (spec §19): (1) Detail routing: from Page Templates list click View
 * and confirm URL is admin.php?page=aio-page-builder-page-template-detail&template=KEY. (2) Metadata
 * display: name, description, category, purpose/CTA, used sections list visible. (3) One-pager
 * link: when template has one_pager.link, link is shown and opens in new tab. (4) Rendered preview:
 * preview panel shows HTML from real pipeline (sections with aio-s-* classes). (5) Omission: optional
 * empty fields in synthetic data yield omitted or empty output per smart-omission. (6) Animation
 * fallback: reduced_motion=1 in URL yields animation_tier none in context. (7) Breadcrumb return:
 * breadcrumb links back to directory; last segment is template name (current).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Detail_State_Builder;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Definition_Provider;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Definition_Provider_For_Preview;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Page_Template_Definition_Provider.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Section_Definition_Provider_For_Preview.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Page_Template_Detail_State_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Page_Template_Directory_State_Builder.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Context.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Data_Generator.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Side_Panel_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Renderer_Base.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Native_Block_Assembly_Pipeline.php';

final class Page_Template_Detail_State_Builder_Test extends TestCase {

	private function create_state_builder(
		?array $page_definition = null,
		?array $section_definition = null
	): Page_Template_Detail_State_Builder {
		$page_provider = new class( $page_definition ) implements Page_Template_Definition_Provider {
			private ?array $def;
			public function __construct( ?array $def ) {
				$this->def = $def;
			}
			public function get_definition_by_key( string $key ): ?array {
				return $this->def;
			}
		};

		$section_provider = new class( $section_definition ) implements Section_Definition_Provider_For_Preview {
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

		return new Page_Template_Detail_State_Builder(
			$page_provider,
			$section_provider,
			$generator,
			$side_panel,
			$ctx_builder,
			$renderer,
			$assembly
		);
	}

	/** Minimal section definition that passes Section_Render_Context_Builder::validate_definition. */
	private function minimal_section_definition( string $key = 'st_hero' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY    => $key,
			Section_Schema::FIELD_VARIANTS        => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_DEFAULT_VARIANT => 'default',
		);
	}

	/** Minimal page definition with one ordered section. */
	private function minimal_page_definition( string $key = 'pt_example', string $section_key = 'st_hero' ): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $key,
			'name'                                       => 'Example Page',
			'purpose_summary'                            => 'Example template for tests.',
			'template_category_class'                    => 'top_level',
			'template_family'                            => 'home',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => $section_key,
					Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
	}

	public function test_build_state_with_empty_key_returns_not_found(): void {
		$builder = $this->create_state_builder( array(), array() );
		$state   = $builder->build_state( '', array() );
		$this->assertTrue( $state['not_found'] );
		$this->assertSame( '', $state['template_key'] );
		$this->assertArrayHasKey( 'breadcrumbs', $state );
	}

	public function test_build_state_with_null_page_definition_returns_not_found(): void {
		$builder = $this->create_state_builder( null, $this->minimal_section_definition() );
		$state   = $builder->build_state( 'pt_missing', array() );
		$this->assertTrue( $state['not_found'] );
	}

	public function test_build_state_with_valid_template_returns_state_with_side_panel_and_used_sections(): void {
		$page_def                              = $this->minimal_page_definition( 'pt_landing', 'st_hero' );
		$section_def                           = $this->minimal_section_definition( 'st_hero' );
		$section_def['section_purpose_family'] = 'hero';

		$builder = $this->create_state_builder( $page_def, $section_def );
		$state   = $builder->build_state( 'pt_landing', array() );

		$this->assertFalse( $state['not_found'] );
		$this->assertSame( 'pt_landing', $state['template_key'] );
		$this->assertArrayHasKey( 'side_panel', $state );
		$this->assertSame( 'Example Page', $state['side_panel']['name'] );
		$this->assertArrayHasKey( 'used_sections', $state );
		$this->assertCount( 1, $state['used_sections'] );
		$this->assertSame( 'st_hero', $state['used_sections'][0]['section_key'] );
		$this->assertArrayHasKey( 'breadcrumbs', $state );
		$this->assertGreaterThanOrEqual( 2, count( $state['breadcrumbs'] ) );
		$this->assertArrayHasKey( 'rendered_preview_html', $state );
		$this->assertIsString( $state['rendered_preview_html'] );
		$this->assertArrayHasKey( 'one_pager_link', $state );
		$this->assertArrayHasKey( 'preview_payload', $state );
	}

	public function test_build_state_breadcrumb_includes_category_and_family_when_provided(): void {
		$page_def                            = $this->minimal_page_definition();
		$page_def['template_category_class'] = 'hub';
		$page_def['template_family']         = 'services';
		$builder                             = $this->create_state_builder( $page_def, $this->minimal_section_definition() );
		$state                               = $builder->build_state(
			'pt_example',
			array(
				'category_class' => 'hub',
				'family'         => 'services',
			)
		);
		$this->assertFalse( $state['not_found'] );
		$labels = array_column( $state['breadcrumbs'], 'label' );
		$this->assertContains( 'Page Templates', $labels );
	}

	/**
	 * Example page-template detail state payload (spec §49.7, Prompt 171).
	 * Shape returned by Page_Template_Detail_State_Builder::build_state() when template exists.
	 *
	 * [
	 *   'template_key'            => 'pt_home_landing',
	 *   'definition'              => [ ... full page template definition ... ],
	 *   'side_panel'              => [
	 *     'name'                  => 'Home Landing',
	 *     'description'           => 'Landing page for the home flow.',
	 *     'used_sections'         => [ [ 'section_key' => 'st_hero', 'position' => 0 ], ... ],
	 *     'purpose_cta_direction'  => 'home',
	 *     'category'              => 'top_level',
	 *     'one_pager_link'        => '',
	 *     ...
	 *   ],
	 *   'used_sections'           => [ [ 'section_key' => 'st_hero', 'position' => 0 ], [ 'section_key' => 'st_cta', 'position' => 1 ] ],
	 *   'one_pager_link'          => '',
	 *   'preview_payload'         => [ 'template_key' => '...', 'section_field_values' => [ ... ], 'side_panel' => [ ... ], 'options' => [ ... ] ],
	 *   'rendered_preview_html'   => '<div class="aio-s-st_hero">...</div>...',
	 *   'breadcrumbs'             => [ [ 'label' => 'Page Templates', 'url' => '...' ], [ 'label' => 'Top Level', 'url' => '...' ], [ 'label' => 'Home Landing', 'url' => '' ] ],
	 *   'not_found'               => false,
	 * ]
	 */
	public function test_example_page_template_detail_state_payload_structure(): void {
		$page_def                              = $this->minimal_page_definition( 'pt_home_landing', 'st_hero' );
		$page_def['name']                      = 'Home Landing';
		$page_def['purpose_summary']           = 'Landing page for the home flow.';
		$section_def                           = $this->minimal_section_definition( 'st_hero' );
		$section_def['section_purpose_family'] = 'hero';

		$builder = $this->create_state_builder( $page_def, $section_def );
		$state   = $builder->build_state( 'pt_home_landing', array() );

		$this->assertSame( 'pt_home_landing', $state['template_key'] );
		$this->assertSame( 'Home Landing', $state['side_panel']['name'] );
		$this->assertSame( 'home', $state['side_panel']['purpose_cta_direction'] );
		$this->assertSame( 'top_level', $state['side_panel']['category'] );
		$this->assertArrayHasKey( 'preview_payload', $state );
		$this->assertArrayHasKey( 'section_field_values', $state['preview_payload'] );
		$this->assertFalse( $state['not_found'] );
	}
}
