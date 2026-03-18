<?php
/**
 * Unit tests for Render_Asset_Controller and Render_Asset_Requirements: requirement derivation, summary, loading policy (spec §7.7, §17, Prompt 048).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Assets\Render_Asset_Controller;
use AIOPageBuilder\Domain\Rendering\Assets\Render_Asset_Requirements;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Assets/Render_Asset_Requirements.php';
require_once $plugin_root . '/src/Domain/Rendering/Assets/Render_Asset_Controller.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';

final class Render_Asset_Controller_Test extends TestCase {

	private function section_result( string $key, array $asset_hints = array() ): Section_Render_Result {
		$structure = array(
			'wrapper_attrs'       => array(
				'class'           => array( 'aio-s-' . $key ),
				'id'              => '',
				'data_attributes' => array(),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-' . $key,
				'inner_class'     => '',
				'element_classes' => array(),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => '',
			'asset_hints'         => $asset_hints,
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result( $key, 'default', 0, array(), $structure, array() );
	}

	public function test_get_requirements_from_sections(): void {
		$controller = new Render_Asset_Controller();
		$sections   = array(
			$this->section_result( 'st01_hero', array( 'none' => true ) ),
			$this->section_result( 'st02_cta', array( 'frontend_css' => true ) ),
		);

		$requirements = $controller->get_requirements_from_sections( $sections, Render_Asset_Requirements::SCOPE_FRONTEND );

		$this->assertCount( 2, $requirements );
		$this->assertSame( 'aio-render-section-st01_hero', $requirements[0]->get_handle() );
		$this->assertSame( 'st01_hero', $requirements[0]->get_source_ref() );
		$this->assertSame( Render_Asset_Requirements::SCOPE_FRONTEND, $requirements[0]->get_scope() );
		$this->assertSame( 'aio-render-section-st02_cta', $requirements[1]->get_handle() );
		$this->assertArrayHasKey( 'frontend_css', $requirements[1]->get_meta() );
	}

	public function test_get_requirements_from_assembly(): void {
		$assembly   = new Page_Block_Assembly_Result(
			'page_template',
			'tpl_landing',
			array(
				array(
					'section_key' => 'st01_hero',
					'asset_hints' => array( 'none' => true ),
				),
				array(
					'section_key' => 'st02_cta',
					'asset_hints' => array(),
				),
			),
			'',
			array(),
			array(),
			array()
		);
		$controller = new Render_Asset_Controller();

		$requirements = $controller->get_requirements_from_assembly( $assembly );

		$this->assertCount( 2, $requirements );
		$this->assertSame( 'aio-render-section-st01_hero', $requirements[0]->get_handle() );
		$this->assertSame( 'aio-render-section-st02_cta', $requirements[1]->get_handle() );
	}

	public function test_summarize_requirements_example_asset_summary(): void {
		$req1       = new Render_Asset_Requirements( 'aio-render-section-st01_hero', 'st01_hero', Render_Asset_Requirements::SCOPE_FRONTEND, array( 'none' => true ) );
		$req2       = new Render_Asset_Requirements( 'aio-render-section-st02_cta', 'st02_cta', Render_Asset_Requirements::SCOPE_FRONTEND, array( 'frontend_css' => true ) );
		$controller = new Render_Asset_Controller();

		$summary = $controller->summarize_requirements( array( $req1, $req2 ) );

		$this->assertCount( 2, $summary );
		$this->assertSame( 'aio-render-section-st01_hero', $summary[0]['handle'] );
		$this->assertSame( 'st01_hero', $summary[0]['source_ref'] );
		$this->assertSame( 'frontend', $summary[0]['scope'] );
		$this->assertSame( array( 'none' => true ), $summary[0]['meta'] );
		$this->assertSame( array( 'frontend_css' => true ), $summary[1]['meta'] );
	}

	public function test_should_load_for_context(): void {
		$controller = new Render_Asset_Controller();

		$this->assertTrue( $controller->should_load_for_context( Render_Asset_Requirements::SCOPE_FRONTEND, 'frontend' ) );
		$this->assertFalse( $controller->should_load_for_context( Render_Asset_Requirements::SCOPE_FRONTEND, 'admin' ) );
		$this->assertTrue( $controller->should_load_for_context( Render_Asset_Requirements::SCOPE_ADMIN, 'admin' ) );
		$this->assertTrue( $controller->should_load_for_context( Render_Asset_Requirements::SCOPE_SECTION, 'frontend' ) );
	}

	public function test_apply_preview_asset_budget_list_caps_handles(): void {
		$controller = new Render_Asset_Controller();
		$reqs       = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$reqs[] = new Render_Asset_Requirements( 'handle-' . $i, 'st_' . $i, Render_Asset_Requirements::SCOPE_FRONTEND, array() );
		}
		$trimmed = $controller->apply_preview_asset_budget( $reqs, Render_Asset_Controller::PREVIEW_CONTEXT_LIST );
		$this->assertLessThanOrEqual( 12, \count( $trimmed ) );
		$this->assertGreaterThanOrEqual( 1, \count( $trimmed ) );
	}

	public function test_apply_preview_asset_budget_detail_caps_handles(): void {
		$controller = new Render_Asset_Controller();
		$reqs       = array();
		for ( $i = 0; $i < 100; $i++ ) {
			$reqs[] = new Render_Asset_Requirements( 'handle-' . $i, 'st_' . $i, Render_Asset_Requirements::SCOPE_FRONTEND, array() );
		}
		$trimmed = $controller->apply_preview_asset_budget( $reqs, Render_Asset_Controller::PREVIEW_CONTEXT_DETAIL );
		$this->assertLessThanOrEqual( 60, \count( $trimmed ) );
		$this->assertSame( 60, \count( $trimmed ) );
	}

	public function test_apply_preview_asset_budget_custom_max_handles(): void {
		$controller = new Render_Asset_Controller();
		$reqs       = array(
			new Render_Asset_Requirements( 'h1', 'st1', Render_Asset_Requirements::SCOPE_FRONTEND, array() ),
			new Render_Asset_Requirements( 'h2', 'st2', Render_Asset_Requirements::SCOPE_FRONTEND, array() ),
			new Render_Asset_Requirements( 'h3', 'st3', Render_Asset_Requirements::SCOPE_FRONTEND, array() ),
		);
		$trimmed    = $controller->apply_preview_asset_budget( $reqs, Render_Asset_Controller::PREVIEW_CONTEXT_DETAIL, 2 );
		$this->assertCount( 2, $trimmed );
		$this->assertSame( 'h1', $trimmed[0]->get_handle() );
		$this->assertSame( 'h2', $trimmed[1]->get_handle() );
	}
}
