<?php
/**
 * Registers section rendering domain services (spec §17, §59.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Rendering\Assets\Render_Asset_Controller;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Content_Survivability_Checker;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Rendering_Diagnostics_Service;
use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Compatibility_Layer;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Payload_Builder;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiator;
use AIOPageBuilder\Domain\Rendering\Preview\Render_Preview_Helper;
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

		$container->register( 'form_provider_registry', function (): Form_Provider_Registry {
			return new Form_Provider_Registry();
		} );

		$container->register( 'native_block_assembly_pipeline', function () use ( $container ): Native_Block_Assembly_Pipeline {
			$gb_layer   = $container->get( 'generateblocks_compatibility_layer' );
			$form_registry = $container->get( 'form_provider_registry' );
			return new Native_Block_Assembly_Pipeline( $gb_layer, $form_registry );
		} );

		$container->register( 'page_instantiation_payload_builder', function (): Page_Instantiation_Payload_Builder {
			return new Page_Instantiation_Payload_Builder();
		} );

		$container->register( 'page_instantiator', function () use ( $container ): Page_Instantiator {
			$builder = $container->get( 'page_instantiation_payload_builder' );
			return new Page_Instantiator( $builder );
		} );

		$container->register( 'content_survivability_checker', function (): Content_Survivability_Checker {
			return new Content_Survivability_Checker();
		} );

		$container->register( 'rendering_diagnostics_service', function (): Rendering_Diagnostics_Service {
			return new Rendering_Diagnostics_Service();
		} );

		$container->register( 'render_preview_helper', function (): Render_Preview_Helper {
			return new Render_Preview_Helper();
		} );

		$container->register( 'render_asset_controller', function (): Render_Asset_Controller {
			return new Render_Asset_Controller();
		} );
	}
}
