<?php
/**
 * Unit tests for Template_Compare_State_Builder (Prompt 180, spec §49.6, §49.7).
 *
 * Covers: empty compare list state, section_compare_matrix shape, page_compare_matrix shape,
 * MAX_COMPARE_ITEMS cap, template_compare_row fields. Includes one example page compare payload
 * and one example section compare payload (real structure) for manual/contract reference.
 *
 * Manual verification: (1) Template Compare menu item visible for users with VIEW_BUILD_PLANS.
 * (2) Add to compare from Section/Page directory adds key and redirects to compare screen.
 * (3) Side-by-side table shows name, purpose, CTA, used sections (page), compatibility, preview excerpt.
 * (4) Remove from compare link removes key and refreshes. (5) Unauthorized users get wp_die.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Definition_Provider;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Definition_Provider_For_Preview;
use AIOPageBuilder\Domain\Registries\Shared\UI\Template_Compare_State_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Section_Definition_Provider_For_Preview.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Page_Template_Definition_Provider.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/UI/Template_Compare_State_Builder.php';

final class Template_Compare_State_Builder_Test extends TestCase {

	/** Example section_compare_matrix payload (one template_compare_row). Real structure for contract reference. */
	public const EXAMPLE_SECTION_COMPARE_PAYLOAD = array(
		'type'                   => 'section',
		'compare_list_keys'      => array( 'st01_hero_intro' ),
		'section_compare_matrix' => array(
			array(
				'template_key'          => 'st01_hero_intro',
				'name'                  => 'Hero Intro',
				'purpose_family'        => 'hero_intro',
				'cta_direction'         => 'primary_cta',
				'used_sections'         => array(),
				'compatibility_notes'   => array( 'lpagery' => true ),
				'animation_tier'        => 'default',
				'helper_ref'            => 'helper_st01_hero_intro',
				'one_pager_ref'         => '',
				'preview_excerpt'       => 'Preview on detail',
				'differentiation_notes' => array(),
				'detail_url'            => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-section-template-detail&section=st01_hero_intro',
			),
		),
		'page_compare_matrix'    => array(),
		'template_compare_rows'  => array(
			array(
				'template_key'          => 'st01_hero_intro',
				'name'                  => 'Hero Intro',
				'purpose_family'        => 'hero_intro',
				'cta_direction'         => 'primary_cta',
				'used_sections'         => array(),
				'compatibility_notes'   => array( 'lpagery' => true ),
				'animation_tier'        => 'default',
				'helper_ref'            => 'helper_st01_hero_intro',
				'one_pager_ref'         => '',
				'preview_excerpt'       => 'Preview on detail',
				'differentiation_notes' => array(),
				'detail_url'            => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-section-template-detail&section=st01_hero_intro',
			),
		),
		'base_url_sections'      => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=section_templates',
		'base_url_pages'         => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=page_templates',
		'compare_screen_url'     => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=compare',
		'empty_message'          => '',
	);

	/** Example page_compare_matrix payload (one template_compare_row). Real structure for contract reference. */
	public const EXAMPLE_PAGE_COMPARE_PAYLOAD = array(
		'type'                   => 'page',
		'compare_list_keys'      => array( 'pt_marketing_landing' ),
		'section_compare_matrix' => array(),
		'page_compare_matrix'    => array(
			array(
				'template_key'          => 'pt_marketing_landing',
				'name'                  => 'Marketing Landing',
				'purpose_family'        => 'Landing page for campaigns',
				'category_class'        => 'marketing',
				'template_family'       => 'top_level',
				'cta_direction'         => '',
				'used_sections'         => array( 'st01_hero_intro', 'st_cta_conversion', 'st_faq' ),
				'compatibility_notes'   => array(),
				'animation_tier'        => 'default',
				'helper_ref'            => '',
				'one_pager_ref'         => 'https://example.org/one-pager/marketing-landing',
				'preview_excerpt'       => 'Preview on detail',
				'differentiation_notes' => array(),
				'detail_url'            => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-template-detail&template=pt_marketing_landing',
			),
		),
		'template_compare_rows'  => array(
			array(
				'template_key'          => 'pt_marketing_landing',
				'name'                  => 'Marketing Landing',
				'purpose_family'        => 'Landing page for campaigns',
				'category_class'        => 'marketing',
				'template_family'       => 'top_level',
				'cta_direction'         => '',
				'used_sections'         => array( 'st01_hero_intro', 'st_cta_conversion', 'st_faq' ),
				'compatibility_notes'   => array(),
				'animation_tier'        => 'default',
				'helper_ref'            => '',
				'one_pager_ref'         => 'https://example.org/one-pager/marketing-landing',
				'preview_excerpt'       => 'Preview on detail',
				'differentiation_notes' => array(),
				'detail_url'            => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-template-detail&template=pt_marketing_landing',
			),
		),
		'base_url_sections'      => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=section_templates',
		'base_url_pages'         => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=page_templates',
		'compare_screen_url'     => 'http://example.org/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=compare',
		'empty_message'          => '',
	);

	private function section_provider_with_definition( array $definition ): Section_Definition_Provider_For_Preview {
		$provider = $this->createMock( Section_Definition_Provider_For_Preview::class );
		$provider->method( 'get_definition_by_key' )->willReturn( $definition );
		return $provider;
	}

	private function page_provider_with_definition( array $definition ): Page_Template_Definition_Provider {
		$provider = $this->createMock( Page_Template_Definition_Provider::class );
		$provider->method( 'get_definition_by_key' )->willReturn( $definition );
		return $provider;
	}

	public function test_build_state_empty_section_returns_empty_matrix_and_message(): void {
		$section_provider = $this->createMock( Section_Definition_Provider_For_Preview::class );
		$page_provider    = $this->createMock( Page_Template_Definition_Provider::class );
		$builder          = new Template_Compare_State_Builder( $section_provider, $page_provider );

		$state = $builder->build_state( 'section', array() );

		$this->assertSame( 'section', $state['type'] );
		$this->assertSame( array(), $state['compare_list_keys'] );
		$this->assertSame( array(), $state['section_compare_matrix'] );
		$this->assertSame( array(), $state['template_compare_rows'] );
		$this->assertNotEmpty( $state['empty_message'] );
		$this->assertArrayHasKey( 'compare_screen_url', $state );
	}

	public function test_build_state_empty_page_returns_empty_matrix_and_message(): void {
		$section_provider = $this->createMock( Section_Definition_Provider_For_Preview::class );
		$page_provider    = $this->createMock( Page_Template_Definition_Provider::class );
		$builder          = new Template_Compare_State_Builder( $section_provider, $page_provider );

		$state = $builder->build_state( 'page', array() );

		$this->assertSame( 'page', $state['type'] );
		$this->assertSame( array(), $state['page_compare_matrix'] );
		$this->assertNotEmpty( $state['empty_message'] );
	}

	public function test_build_state_section_with_definition_returns_template_compare_row(): void {
		$def              = array(
			'internal_key'       => 'st01_hero_intro',
			'name'               => 'Hero Intro',
			'category'           => 'hero_intro',
			'purpose_summary'    => 'Hero section',
			'cta_classification' => 'primary_cta',
			'helper_ref'         => 'helper_st01_hero_intro',
			'compatibility'      => array( 'lpagery' => true ),
		);
		$section_provider = $this->section_provider_with_definition( $def );
		$page_provider    = $this->createMock( Page_Template_Definition_Provider::class );
		$builder          = new Template_Compare_State_Builder( $section_provider, $page_provider );

		$state = $builder->build_state( 'section', array( 'st01_hero_intro' ) );

		$this->assertSame( array( 'st01_hero_intro' ), $state['compare_list_keys'] );
		$this->assertCount( 1, $state['section_compare_matrix'] );
		$row = $state['section_compare_matrix'][0];
		$this->assertSame( 'st01_hero_intro', $row['template_key'] );
		$this->assertSame( 'Hero Intro', $row['name'] );
		$this->assertSame( 'primary_cta', $row['cta_direction'] );
		$this->assertSame( 'default', $row['animation_tier'] );
		$this->assertArrayHasKey( 'detail_url', $row );
		$this->assertSame( $state['section_compare_matrix'], $state['template_compare_rows'] );
	}

	public function test_build_state_page_with_definition_returns_template_compare_row_with_used_sections(): void {
		$def              = array(
			'internal_key'            => 'pt_marketing_landing',
			'name'                    => 'Marketing Landing',
			'purpose_summary'         => 'Landing page for campaigns',
			'template_category_class' => 'marketing',
			'template_family'         => 'top_level',
			'ordered_sections'        => array(
				array( 'section_key' => 'st01_hero_intro' ),
				array( 'section_key' => 'st_cta_conversion' ),
			),
			'compatibility'           => array(),
			'one_pager'               => array( 'link' => 'https://example.org/one-pager/marketing' ),
		);
		$section_provider = $this->createMock( Section_Definition_Provider_For_Preview::class );
		$page_provider    = $this->page_provider_with_definition( $def );
		$builder          = new Template_Compare_State_Builder( $section_provider, $page_provider );

		$state = $builder->build_state( 'page', array( 'pt_marketing_landing' ) );

		$this->assertCount( 1, $state['page_compare_matrix'] );
		$row = $state['page_compare_matrix'][0];
		$this->assertSame( 'pt_marketing_landing', $row['template_key'] );
		$this->assertSame( 'Marketing Landing', $row['name'] );
		$this->assertSame( array( 'st01_hero_intro', 'st_cta_conversion' ), $row['used_sections'] );
		$this->assertSame( 'https://example.org/one-pager/marketing', $row['one_pager_ref'] );
	}

	public function test_build_state_caps_at_max_compare_items(): void {
		$section_provider = $this->section_provider_with_definition(
			array(
				'internal_key' => 'st1',
				'name'         => 'S1',
			)
		);
		$page_provider    = $this->createMock( Page_Template_Definition_Provider::class );
		$builder          = new Template_Compare_State_Builder( $section_provider, $page_provider );

		$keys = array();
		for ( $i = 0; $i < Template_Compare_State_Builder::MAX_COMPARE_ITEMS + 3; $i++ ) {
			$keys[] = 'st_' . $i;
		}
		$state = $builder->build_state( 'section', $keys );

		$this->assertCount( Template_Compare_State_Builder::MAX_COMPARE_ITEMS, $state['compare_list_keys'] );
	}

	public function test_example_section_payload_has_expected_keys(): void {
		$this->assertArrayHasKey( 'section_compare_matrix', self::EXAMPLE_SECTION_COMPARE_PAYLOAD );
		$this->assertArrayHasKey( 'template_compare_rows', self::EXAMPLE_SECTION_COMPARE_PAYLOAD );
		$row = self::EXAMPLE_SECTION_COMPARE_PAYLOAD['template_compare_rows'][0];
		$this->assertArrayHasKey( 'template_key', $row );
		$this->assertArrayHasKey( 'name', $row );
		$this->assertArrayHasKey( 'purpose_family', $row );
		$this->assertArrayHasKey( 'cta_direction', $row );
		$this->assertArrayHasKey( 'detail_url', $row );
	}

	public function test_example_page_payload_has_expected_keys(): void {
		$this->assertArrayHasKey( 'page_compare_matrix', self::EXAMPLE_PAGE_COMPARE_PAYLOAD );
		$row = self::EXAMPLE_PAGE_COMPARE_PAYLOAD['template_compare_rows'][0];
		$this->assertArrayHasKey( 'used_sections', $row );
		$this->assertArrayHasKey( 'one_pager_ref', $row );
		$this->assertArrayHasKey( 'category_class', $row );
	}
}
