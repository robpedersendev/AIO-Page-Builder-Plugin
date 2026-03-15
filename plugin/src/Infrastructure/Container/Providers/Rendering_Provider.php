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
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Availability_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Cache_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Discovery_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Ndr_Form_Provider_Picker_Adapter;
use AIOPageBuilder\Domain\Rendering\Assets\Render_Asset_Controller;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Content_Survivability_Checker;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Rendering_Diagnostics_Service;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Fallback_Service;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Tier_Resolver;
use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Compatibility_Layer;
use AIOPageBuilder\Domain\Rendering\LPagery\Library_LPagery_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Token_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\Omission\Smart_Omission_Service;
use AIOPageBuilder\Domain\Reporting\FormProvider\Form_Provider_Health_Summary_Service;
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

		$container->register( 'smart_omission_service', function (): Smart_Omission_Service {
			return new Smart_Omission_Service();
		} );

		$container->register( 'animation_fallback_service', function (): Animation_Fallback_Service {
			return new Animation_Fallback_Service();
		} );

		$container->register( 'animation_tier_resolver', function (): Animation_Tier_Resolver {
			return new Animation_Tier_Resolver();
		} );

		$container->register( 'section_renderer_base', function () use ( $container ): Section_Renderer_Base {
			return new Section_Renderer_Base(
				$container->get( 'smart_omission_service' ),
				$container->get( 'animation_tier_resolver' )
			);
		} );

		$container->register( 'generateblocks_compatibility_layer', function (): GenerateBlocks_Compatibility_Layer {
			return new GenerateBlocks_Compatibility_Layer( GenerateBlocks_Compatibility_Layer::default_availability_check() );
		} );

		$container->register( 'form_provider_registry', function (): Form_Provider_Registry {
			return new Form_Provider_Registry();
		} );

		$container->register( 'form_provider_picker_discovery', function () use ( $container ): Form_Provider_Picker_Discovery_Service {
			$registry = $container->get( 'form_provider_registry' );
			$ndr      = new Ndr_Form_Provider_Picker_Adapter( $registry );
			return new Form_Provider_Picker_Discovery_Service( $registry, array( $ndr->get_provider_key() => $ndr ) );
		} );

		$container->register( 'form_provider_picker_cache', function (): Form_Provider_Picker_Cache_Service {
			return new Form_Provider_Picker_Cache_Service();
		} );

		$container->register( 'form_provider_availability_service', function () use ( $container ): Form_Provider_Availability_Service {
			$registry = $container->get( 'form_provider_registry' );
			$discovery = $container->get( 'form_provider_picker_discovery' );
			$cache = $container->has( 'form_provider_picker_cache' ) ? $container->get( 'form_provider_picker_cache' ) : null;
			return new Form_Provider_Availability_Service( $registry, $discovery, $cache );
		} );

		$container->register( 'form_provider_health_summary_service', function () use ( $container ): Form_Provider_Health_Summary_Service {
			$registry     = $container->get( 'form_provider_registry' );
			$section_repo = $container->has( 'section_template_repository' ) ? $container->get( 'section_template_repository' ) : null;
			$page_repo    = $container->has( 'page_template_repository' ) ? $container->get( 'page_template_repository' ) : null;
			$availability = $container->has( 'form_provider_availability_service' ) ? $container->get( 'form_provider_availability_service' ) : null;
			$validator    = $container->has( 'form_provider_dependency_validator' ) ? $container->get( 'form_provider_dependency_validator' ) : null;
			return new Form_Provider_Health_Summary_Service( $registry, $section_repo, $page_repo, $availability, $validator );
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

		$container->register( 'lpagery_token_compatibility_service', function (): LPagery_Token_Compatibility_Service {
			return new LPagery_Token_Compatibility_Service();
		} );

		$container->register( 'library_lpagery_compatibility_service', function () use ( $container ): Library_LPagery_Compatibility_Service {
			$token = $container->get( 'lpagery_token_compatibility_service' );
			$blueprint = $container->has( 'section_field_blueprint_service' ) ? $container->get( 'section_field_blueprint_service' ) : null;
			$resolver = $container->has( 'blueprint_family_resolver' ) ? $container->get( 'blueprint_family_resolver' ) : null;
			return new Library_LPagery_Compatibility_Service( $token, $blueprint, $resolver );
		} );
	}
}
