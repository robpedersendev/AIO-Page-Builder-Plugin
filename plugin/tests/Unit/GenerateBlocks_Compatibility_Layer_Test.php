<?php
/**
 * Unit tests for GenerateBlocks_Compatibility_Layer: availability, supported mapping, unsupported fallback, selector contract (spec §7.2, §17.2, Prompt 045).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Compatibility_Layer;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/GenerateBlocks/GenerateBlocks_Mapping_Rules.php';
require_once $plugin_root . '/src/Domain/Rendering/GenerateBlocks/GenerateBlocks_Compatibility_Layer.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';

final class GenerateBlocks_Compatibility_Layer_Test extends TestCase {

	private function eligible_section_result(): Section_Render_Result {
		$structure = array(
			'wrapper_attrs'       => array(
				'class'           => array( 'aio-s-st01_hero', 'aio-s-st01_hero--variant-default' ),
				'id'              => 'aio-section-st01_hero-0',
				'data_attributes' => array(
					'data-aio-section'  => 'st01_hero',
					'data-aio-variant'  => 'default',
					'data-aio-position' => '0',
				),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-st01_hero',
				'inner_class'     => 'aio-s-st01_hero__inner',
				'element_classes' => array( 'inner' => 'aio-s-st01_hero__inner' ),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result(
			'st01_hero',
			'default',
			0,
			array(
				'headline'    => 'Welcome',
				'subheadline' => 'Intro text',
			),
			$structure,
			array()
		);
	}

	private function ineligible_section_result_with_repeater(): Section_Render_Result {
		$structure = array(
			'wrapper_attrs'       => array(
				'class'           => array( 'aio-s-faq' ),
				'id'              => 'aio-section-faq-0',
				'data_attributes' => array(),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-faq',
				'inner_class'     => 'aio-s-faq__inner',
				'element_classes' => array(),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result(
			'st05_faq',
			'default',
			0,
			array(
				'items' => array(
					array(
						'q' => 'Q1',
						'a' => 'A1',
					),
				),
			),
			$structure,
			array()
		);
	}

	public function test_when_availability_false_section_to_gb_markup_returns_null(): void {
		$layer = new GenerateBlocks_Compatibility_Layer(
			function (): bool {
				return false;
			}
		);
		$this->assertFalse( $layer->is_available() );
		$section = $this->eligible_section_result();
		$this->assertNull( $layer->section_to_gb_markup( $section ) );
	}

	public function test_when_availability_true_and_eligible_returns_gb_markup(): void {
		$layer = new GenerateBlocks_Compatibility_Layer(
			function (): bool {
				return true;
			}
		);
		$this->assertTrue( $layer->is_available() );
		$section = $this->eligible_section_result();
		$markup  = $layer->section_to_gb_markup( $section );
		$this->assertNotNull( $markup );
		$this->assertStringContainsString( 'wp:generateblocks/container', $markup );
		$this->assertStringContainsString( 'wp:generateblocks/headline', $markup );
	}

	public function test_supported_mapping_example_preserves_selector_contract(): void {
		$layer   = new GenerateBlocks_Compatibility_Layer(
			function (): bool {
				return true;
			}
		);
		$section = $this->eligible_section_result();
		$markup  = $layer->section_to_gb_markup( $section );
		$this->assertNotNull( $markup );
		$this->assertStringContainsString( 'aio-s-st01_hero', $markup );
		$this->assertStringContainsString( 'aio-s-st01_hero__inner', $markup );
		$this->assertStringContainsString( 'aio-section-st01_hero-0', $markup );
		$this->assertStringContainsString( 'data-aio-section="st01_hero"', $markup );
		$this->assertStringContainsString( 'data-aio-variant="default"', $markup );
		$this->assertStringContainsString( 'data-aio-position="0"', $markup );
	}

	public function test_unsupported_pattern_repeater_returns_null(): void {
		$layer   = new GenerateBlocks_Compatibility_Layer(
			function (): bool {
				return true;
			}
		);
		$section = $this->ineligible_section_result_with_repeater();
		$this->assertNull( $layer->section_to_gb_markup( $section ), 'Section with array/repeater field must fall back to native' );
	}

	public function test_headline_and_title_as_h2_others_as_p(): void {
		$layer     = new GenerateBlocks_Compatibility_Layer(
			function (): bool {
				return true;
			}
		);
		$structure = array(
			'wrapper_attrs'       => array(
				'class'           => array( 'aio-s-cta' ),
				'id'              => 'aio-section-cta-0',
				'data_attributes' => array(),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-cta',
				'inner_class'     => 'aio-s-cta__inner',
				'element_classes' => array(),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		$section   = new Section_Render_Result(
			'st02_cta',
			'default',
			0,
			array(
				'title' => 'Sign Up',
				'cta'   => 'Click here',
			),
			$structure,
			array()
		);
		$markup    = $layer->section_to_gb_markup( $section );
		$this->assertNotNull( $markup );
		$this->assertStringContainsString( '"element":"h2"', $markup );
		$this->assertStringContainsString( '"element":"p"', $markup );
		$this->assertStringContainsString( 'Sign Up', $markup );
		$this->assertStringContainsString( 'Click here', $markup );
	}
}
