<?php
/**
 * Builds page/section template detail state builders for live preview (same wiring as admin detail screens).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Detail_State_Builder;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Detail_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Centralizes construction of preview state builders so front-end live preview matches admin synthetic preview.
 */
final class Template_Live_Preview_State_Builder_Factory {

	/**
	 * @param Service_Container|null $container Plugin container.
	 */
	public function __construct( private ?Service_Container $container = null ) {
	}

	public function create_page_builder(): Page_Template_Detail_State_Builder {
		$c            = $this->container;
		$page_repo    = $c && $c->has( 'page_template_repository' ) ? $c->get( 'page_template_repository' ) : null;
		$section_repo = $c && $c->has( 'section_template_repository' ) ? $c->get( 'section_template_repository' ) : null;
		if ( ! $page_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository ) {
			$page_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		}
		if ( ! $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository ) {
			$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		}

		$page_provider    = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Repository_Adapter( $page_repo );
		$section_provider = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Template_Repository_Adapter( $section_repo );

		$preview_generator = new \AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator();
		$industry_dummy    = new \AIOPageBuilder\Domain\Industry\Preview\Industry_Dummy_Data_Generator();
		$industry_key      = null;
		if ( $c && $c->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $c->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
				$profile = $store->get_profile();
				$primary = isset( $profile['primary_industry_key'] ) && \is_string( $profile['primary_industry_key'] ) ? \trim( $profile['primary_industry_key'] ) : '';
				if ( $primary !== '' ) {
					$industry_key = $primary;
				}
			}
		}
		$side_panel_builder = new \AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder();
		$context_builder    = $c && $c->has( 'section_render_context_builder' ) ? $c->get( 'section_render_context_builder' ) : null;
		if ( ! $context_builder instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder ) {
			$context_builder = new \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder();
		}
		$section_renderer = $c && $c->has( 'section_renderer_base' ) ? $c->get( 'section_renderer_base' ) : null;
		if ( ! $section_renderer instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base ) {
			$section_renderer = new \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base();
		}
		$assembly_pipeline = $c && $c->has( 'native_block_assembly_pipeline' ) ? $c->get( 'native_block_assembly_pipeline' ) : null;
		if ( ! $assembly_pipeline instanceof \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline ) {
			$assembly_pipeline = new \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline( null, null );
		}
		$lpagery_compatibility = null;
		if ( $c && $c->has( 'library_lpagery_compatibility_service' ) ) {
			$lpagery_compatibility = $c->get( 'library_lpagery_compatibility_service' );
		}
		$preview_cache = null;
		if ( $c && $c->has( 'preview_cache_service' ) ) {
			$preview_cache = $c->get( 'preview_cache_service' );
		}
		if ( $preview_cache !== null && ! $preview_cache instanceof \AIOPageBuilder\Domain\Preview\Preview_Cache_Service ) {
			$preview_cache = null;
		}
		$versioning_service  = null;
		$deprecation_service = null;
		if ( $c && $c->has( 'template_versioning_service' ) ) {
			$v = $c->get( 'template_versioning_service' );
			if ( $v instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service ) {
				$versioning_service = $v;
			}
		}
		if ( $c && $c->has( 'template_deprecation_service' ) ) {
			$d = $c->get( 'template_deprecation_service' );
			if ( $d instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service ) {
				$deprecation_service = $d;
			}
		}
		$form_reference_aggregator = null;
		if ( $c && $c->has( 'form_provider_registry' ) ) {
			$reg = $c->get( 'form_provider_registry' );
			if ( $reg instanceof \AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry ) {
				$form_reference_aggregator = new \AIOPageBuilder\Domain\Rendering\FormProviders\Page_Form_Reference_Aggregator( $reg );
			}
		}

		return new Page_Template_Detail_State_Builder(
			$page_provider,
			$section_provider,
			$preview_generator,
			$side_panel_builder,
			$context_builder,
			$section_renderer,
			$assembly_pipeline,
			$lpagery_compatibility,
			$preview_cache,
			$versioning_service,
			$deprecation_service,
			$form_reference_aggregator,
			$industry_dummy,
			$industry_key
		);
	}

	public function create_section_builder(): Section_Template_Detail_State_Builder {
		$c            = $this->container;
		$section_repo = $c && $c->has( 'section_template_repository' ) ? $c->get( 'section_template_repository' ) : null;
		if ( ! $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository ) {
			$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		}
		$section_provider = new \AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Repository_Adapter( $section_repo );

		$preview_generator = new \AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator();
		$industry_dummy    = new \AIOPageBuilder\Domain\Industry\Preview\Industry_Dummy_Data_Generator();
		$industry_key      = null;
		if ( $c && $c->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $c->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
				$profile = $store->get_profile();
				$primary = isset( $profile['primary_industry_key'] ) && \is_string( $profile['primary_industry_key'] ) ? \trim( $profile['primary_industry_key'] ) : '';
				if ( $primary !== '' ) {
					$industry_key = $primary;
				}
			}
		}
		$side_panel_builder = new \AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder();
		$context_builder    = $c && $c->has( 'section_render_context_builder' ) ? $c->get( 'section_render_context_builder' ) : null;
		if ( ! $context_builder instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder ) {
			$context_builder = new \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder();
		}
		$section_renderer = $c && $c->has( 'section_renderer_base' ) ? $c->get( 'section_renderer_base' ) : null;
		if ( ! $section_renderer instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base ) {
			$section_renderer = new \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base();
		}
		$assembly_pipeline = $c && $c->has( 'native_block_assembly_pipeline' ) ? $c->get( 'native_block_assembly_pipeline' ) : null;
		if ( ! $assembly_pipeline instanceof \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline ) {
			$assembly_pipeline = new \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline( null, null );
		}
		$blueprint_service = null;
		if ( $c && $c->has( 'section_field_blueprint_service' ) ) {
			$svc = $c->get( 'section_field_blueprint_service' );
			if ( $svc instanceof \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service ) {
				$blueprint_service = $svc;
			}
		}
		$lpagery_compatibility = null;
		if ( $c && $c->has( 'library_lpagery_compatibility_service' ) ) {
			$lpagery_compatibility = $c->get( 'library_lpagery_compatibility_service' );
		}
		$preview_cache = null;
		if ( $c && $c->has( 'preview_cache_service' ) ) {
			$preview_cache = $c->get( 'preview_cache_service' );
		}
		if ( $preview_cache !== null && ! $preview_cache instanceof \AIOPageBuilder\Domain\Preview\Preview_Cache_Service ) {
			$preview_cache = null;
		}
		$versioning_service  = null;
		$deprecation_service = null;
		if ( $c && $c->has( 'template_versioning_service' ) ) {
			$v = $c->get( 'template_versioning_service' );
			if ( $v instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service ) {
				$versioning_service = $v;
			}
		}
		if ( $c && $c->has( 'template_deprecation_service' ) ) {
			$d = $c->get( 'template_deprecation_service' );
			if ( $d instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service ) {
				$deprecation_service = $d;
			}
		}
		$form_section_field_state_builder = null;
		if ( $c && $c->has( 'form_provider_registry' ) ) {
			$reg = $c->get( 'form_provider_registry' );
			if ( $reg instanceof \AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry ) {
				$discovery                        = $c->has( 'form_provider_picker_discovery' ) ? $c->get( 'form_provider_picker_discovery' ) : null;
				$availability                     = $c->has( 'form_provider_availability_service' ) ? $c->get( 'form_provider_availability_service' ) : null;
				$form_section_field_state_builder = new \AIOPageBuilder\Domain\Registries\Section\UI\Form_Section_Field_State_Builder( $reg, $discovery, $availability );
			}
		}

		return new Section_Template_Detail_State_Builder(
			$section_provider,
			$preview_generator,
			$side_panel_builder,
			$context_builder,
			$section_renderer,
			$assembly_pipeline,
			$blueprint_service,
			$lpagery_compatibility,
			$preview_cache,
			$versioning_service,
			$deprecation_service,
			$form_section_field_state_builder,
			$industry_dummy,
			$industry_key
		);
	}
}
