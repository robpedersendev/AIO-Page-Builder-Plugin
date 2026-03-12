<?php
/**
 * Integration tests: rendering pipeline output remains survivable after deactivation (spec §9.12, §17.3, §56.3, Prompt 047).
 * Proves built page content is meaningful without plugin runtime; negative tests fail if lock-in is introduced.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration;

use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Content_Survivability_Checker;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Native_Block_Assembly_Pipeline.php';
require_once $plugin_root . '/src/Domain/Rendering/GenerateBlocks/GenerateBlocks_Mapping_Rules.php';
require_once $plugin_root . '/src/Domain/Rendering/GenerateBlocks/GenerateBlocks_Compatibility_Layer.php';
require_once $plugin_root . '/src/Domain/Rendering/Diagnostics/Content_Survivability_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Diagnostics/Content_Survivability_Checker.php';

final class Rendering_Survivability_Integration_Test extends TestCase {

	private function section_result( string $section_key, int $position, array $field_values = array() ): Section_Render_Result {
		$wrapper_class = 'aio-s-' . $section_key;
		$variant_mod   = $wrapper_class . '--variant-default';
		$inner_class   = $wrapper_class . '__inner';
		$section_id    = 'aio-section-' . $section_key . '-' . $position;
		$structure     = array(
			'wrapper_attrs'       => array(
				'class'           => array( $wrapper_class, $variant_mod ),
				'id'              => $section_id,
				'data_attributes' => array(
					'data-aio-section'  => $section_key,
					'data-aio-variant'  => 'default',
					'data-aio-position' => (string) $position,
				),
			),
			'selector_map'        => array( 'wrapper_class' => $wrapper_class, 'inner_class' => $inner_class, 'element_classes' => array() ),
			'structural_nodes'   => array(),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result( $section_key, 'default', $position, $field_values, $structure, array() );
	}

	public function test_template_backed_assembly_passes_survivability(): void {
		$pipeline = new Native_Block_Assembly_Pipeline();
		$s1       = $this->section_result( 'st01_hero', 0, array( 'headline' => 'Landing', 'subheadline' => 'Intro' ) );
		$s2       = $this->section_result( 'st02_cta', 1, array( 'title' => 'Sign up', 'cta' => 'Go' ) );

		$assembly = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_landing',
			array( $s1, $s2 )
		);

		$checker = new Content_Survivability_Checker();
		$result  = $checker->check_assembly_result( $assembly );

		$this->assertTrue( $result->is_survivable(), 'Template-backed output must pass survivability' );
		$this->assertTrue( $result->is_deactivation_ready() );
		$this->assertEmpty( $result->get_prohibited_runtime_dependencies() );
		$this->assertNotEmpty( $assembly->get_block_content(), 'Content must be non-empty' );
	}

	public function test_composition_backed_assembly_passes_survivability(): void {
		$pipeline = new Native_Block_Assembly_Pipeline();
		$s1       = $this->section_result( 'st01_hero', 0, array( 'headline' => 'Composed' ) );

		$assembly = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_COMPOSITION,
			'comp_uuid-123',
			array( $s1 )
		);

		$checker = new Content_Survivability_Checker();
		$result  = $checker->check_assembly_result( $assembly );

		$this->assertTrue( $result->is_survivable(), 'Composition-backed output must pass survivability' );
		$this->assertTrue( $result->is_deactivation_ready() );
	}

	public function test_negative_prohibited_shortcode_fails_survivability(): void {
		$checker = new Content_Survivability_Checker();
		$content = "<!-- wp:html --><div>Content with [aio_build_status] shortcode</div><!-- /wp:html -->";

		$result = $checker->check( $content );

		$this->assertFalse( $result->is_survivable(), 'Content with plugin shortcode must fail survivability' );
		$this->assertContains( 'plugin_shortcode_detected', $result->get_prohibited_runtime_dependencies() );
	}

	public function test_negative_unreplaced_token_fails_survivability(): void {
		$checker = new Content_Survivability_Checker();
		$content = "<!-- wp:html --><p>Hello {{ visitor_name }}</p><!-- /wp:html -->";

		$result = $checker->check( $content );

		$this->assertFalse( $result->is_survivable(), 'Content with unreplaced token must fail survivability' );
		$this->assertContains( 'unreplaced_token_placeholder', $result->get_prohibited_runtime_dependencies() );
	}
}
