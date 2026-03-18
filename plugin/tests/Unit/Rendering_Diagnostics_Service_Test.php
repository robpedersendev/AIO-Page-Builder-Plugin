<?php
/**
 * Unit tests for Rendering_Diagnostics_Service: render_summary, assembly_summary, instantiation_readiness (spec §17, §45.4, Prompt 048).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Rendering_Diagnostics_Service;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Diagnostics/Rendering_Diagnostics_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Page/Page_Instantiation_Result.php';

final class Rendering_Diagnostics_Service_Test extends TestCase {

	private function section_result( string $key, int $position, bool $with_errors = false ): Section_Render_Result {
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
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result( $key, 'default', $position, array( 'headline' => 'X' ), $structure, $with_errors ? array( 'error' ) : array() );
	}

	public function test_build_render_summary(): void {
		$service = new Rendering_Diagnostics_Service();
		$results = array(
			$this->section_result( 'st01_hero', 0 ),
			$this->section_result( 'st02_cta', 1, true ),
		);

		$summary = $service->build_render_summary( $results );

		$this->assertSame( 2, $summary['section_count'] );
		$this->assertSame( 1, $summary['valid_count'] );
		$this->assertTrue( $summary['has_errors'] );
		$this->assertCount( 2, $summary['sections'] );
		$this->assertSame( 'st01_hero', $summary['sections'][0]['section_key'] );
		$this->assertSame( 'st02_cta', $summary['sections'][1]['section_key'] );
		$this->assertSame( 1, $summary['sections'][1]['error_count'] );
	}

	public function test_build_assembly_summary(): void {
		$assembly = new Page_Block_Assembly_Result( 'page_template', 'tpl_landing', array(), '<!-- wp:html -->x<!-- /wp:html -->', array(), array( 'durable_native_blocks' ), array() );
		$service  = new Rendering_Diagnostics_Service();

		$summary = $service->build_assembly_summary( $assembly );

		$this->assertSame( 'page_template', $summary['source_type'] );
		$this->assertSame( 'tpl_landing', $summary['source_key'] );
		$this->assertSame( 0, $summary['section_count'] );
		$this->assertSame( strlen( '<!-- wp:html -->x<!-- /wp:html -->' ), $summary['block_content_length'] );
		$this->assertTrue( $summary['valid'] );
	}

	public function test_build_instantiation_readiness_with_result(): void {
		$result  = new Page_Instantiation_Result( true, 42, array( 'source_key' => 'tpl_x' ), array() );
		$service = new Rendering_Diagnostics_Service();

		$readiness = $service->build_instantiation_readiness( $result, null );

		$this->assertTrue( $readiness['ready'] );
		$this->assertTrue( $readiness['success'] );
		$this->assertSame( 42, $readiness['post_id'] );
		$this->assertSame( 0, $readiness['error_count'] );
	}

	public function test_build_instantiation_readiness_with_payload(): void {
		$payload = array(
			'source_type' => 'composition',
			'source_key'  => 'comp_1',
			'page_title'  => 'Test',
		);
		$service = new Rendering_Diagnostics_Service();

		$readiness = $service->build_instantiation_readiness( null, $payload );

		$this->assertSame( 'composition', $readiness['source_type'] );
		$this->assertSame( 'comp_1', $readiness['source_key'] );
		$this->assertSame( 'Test', $readiness['page_title'] );
	}
}
