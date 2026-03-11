<?php
/**
 * Registers section rendering domain services (spec §17, §59.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Compatibility_Layer;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Payload_Builder;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiator;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers section rendering, block assembly, GenerateBlocks compatibility, and page instantiation services.
 */
final class Rendering_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'section_render_context_builder', function (): Section_Render_Context_Builder {
			return new Section_Render_Context_Builder();
		} );

		$container->register( 'section_renderer_base', function (): Section_Renderer_Base {
			return new Section_Renderer_Base();
		} );

		$container->register( 'generateblocks_compatibility_layer', function (): GenerateBlocks_Compatibility_Layer {
			return new GenerateBlocks_Compatibility_Layer( GenerateBlocks_Compatibility_Layer::default_availability_check() );
		} );

		$container->register( 'native_block_assembly_pipeline', function () use ( $container ): Native_Block_Assembly_Pipeline {
			$gb_layer = $container->get( 'generateblocks_compatibility_layer' );
			return new Native_Block_Assembly_Pipeline( $gb_layer );
		} );

		$container->register( 'page_instantiation_payload_builder', function (): Page_Instantiation_Payload_Builder {
			return new Page_Instantiation_Payload_Builder();
		} );

		$container->register( 'page_instantiator', function () use ( $container ): Page_Instantiator {
			$builder = $container->get( 'page_instantiation_payload_builder' );
			return new Page_Instantiator( $builder );
		} );
	}
}
