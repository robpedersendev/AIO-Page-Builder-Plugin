<?php
/**
 * Builds detail-screen state for a single page template (spec §49.7, §17.1, template-preview-and-dummy-data-contract).
 * Produces metadata panel, used-section list, preview payload, and rendered preview HTML via the real rendering pipeline.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Record;
use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;
use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Rendering\FormProviders\Page_Form_Reference_Aggregator;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service;
use AIOPageBuilder\Domain\Rendering\LPagery\Library_LPagery_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;

/**
 * Builds stable detail-state payload: template definition, side-panel metadata, used_sections,
 * one_pager_link, preview payload, and rendered_preview_html using synthetic data and the real renderer.
 */
final class Page_Template_Detail_State_Builder {

	/** @var Page_Template_Definition_Provider */
	private Page_Template_Definition_Provider $page_template_provider;

	/** @var Section_Definition_Provider_For_Preview */
	private Section_Definition_Provider_For_Preview $section_provider;

	/** @var Synthetic_Preview_Data_Generator */
	private Synthetic_Preview_Data_Generator $preview_generator;

	/** @var Preview_Side_Panel_Builder */
	private Preview_Side_Panel_Builder $side_panel_builder;

	/** @var Section_Render_Context_Builder */
	private Section_Render_Context_Builder $context_builder;

	/** @var Section_Renderer_Base */
	private Section_Renderer_Base $section_renderer;

	/** @var Native_Block_Assembly_Pipeline */
	private Native_Block_Assembly_Pipeline $assembly_pipeline;

	/** @var Library_LPagery_Compatibility_Service|null */
	private ?Library_LPagery_Compatibility_Service $lpagery_compatibility;

	/** @var Preview_Cache_Service|null */
	private ?Preview_Cache_Service $preview_cache;

	/** @var Template_Versioning_Service|null */
	private ?Template_Versioning_Service $versioning_service;

	/** @var Template_Deprecation_Service|null */
	private ?Template_Deprecation_Service $deprecation_service;

	/** @var Page_Form_Reference_Aggregator|null */
	private ?Page_Form_Reference_Aggregator $form_reference_aggregator;

	public function __construct(
		Page_Template_Definition_Provider $page_template_provider,
		Section_Definition_Provider_For_Preview $section_provider,
		Synthetic_Preview_Data_Generator $preview_generator,
		Preview_Side_Panel_Builder $side_panel_builder,
		Section_Render_Context_Builder $context_builder,
		Section_Renderer_Base $section_renderer,
		Native_Block_Assembly_Pipeline $assembly_pipeline,
		?Library_LPagery_Compatibility_Service $lpagery_compatibility = null,
		?Preview_Cache_Service $preview_cache = null,
		?Template_Versioning_Service $versioning_service = null,
		?Template_Deprecation_Service $deprecation_service = null,
		?Page_Form_Reference_Aggregator $form_reference_aggregator = null
	) {
		$this->page_template_provider = $page_template_provider;
		$this->section_provider       = $section_provider;
		$this->preview_generator      = $preview_generator;
		$this->side_panel_builder     = $side_panel_builder;
		$this->context_builder        = $context_builder;
		$this->section_renderer       = $section_renderer;
		$this->assembly_pipeline     = $assembly_pipeline;
		$this->lpagery_compatibility  = $lpagery_compatibility;
		$this->preview_cache          = $preview_cache;
		$this->versioning_service     = $versioning_service;
		$this->deprecation_service    = $deprecation_service;
		$this->form_reference_aggregator = $form_reference_aggregator;
	}

	/**
	 * Builds full detail state for the given template key. Returns not_found when template does not exist.
	 *
	 * @param string $template_key Page template internal_key.
	 * @param array<string, mixed> $request_params Optional: category_class, family (for breadcrumb), reduced_motion.
	 * @return array<string, mixed> State: template_key, definition, side_panel, used_sections, one_pager_link, preview_payload, rendered_preview_html, breadcrumbs, not_found.
	 */
	public function build_state( string $template_key, array $request_params = array() ): array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return $this->not_found_state( $template_key, $request_params );
		}

		$definition = $this->page_template_provider->get_definition_by_key( $template_key );
		if ( $definition === null || empty( $definition ) ) {
			return $this->not_found_state( $template_key, $request_params );
		}

		$category_class = isset( $request_params['category_class'] ) ? \sanitize_key( (string) $request_params['category_class'] ) : (string) ( $definition['template_category_class'] ?? '' );
		$family         = isset( $request_params['family'] ) ? \sanitize_key( (string) $request_params['family'] ) : (string) ( $definition['template_family'] ?? '' );
		$reduced_motion = ! empty( $request_params['reduced_motion'] );

		$context = Synthetic_Preview_Context::for_page(
			$template_key,
			$category_class !== '' ? $category_class : 'top_level',
			$family !== '' ? $family : 'home',
			'default',
			$reduced_motion,
			$reduced_motion ? Synthetic_Preview_Context::ANIMATION_TIER_NONE : Synthetic_Preview_Context::ANIMATION_TIER_NONE
		);

		$ordered_sections = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$ordered_for_gen  = $this->build_ordered_sections_for_generator( $ordered_sections );

		$section_field_values = $this->preview_generator->generate_for_page( $context, $ordered_for_gen );
		$preview_payload       = $this->side_panel_builder->build_page_preview_payload( $definition, $section_field_values, $context );

		$side_panel   = $preview_payload['side_panel'] ?? $this->side_panel_builder->build_for_page( $definition, $context );
		$used_sections = $side_panel['used_sections'] ?? array();
		$one_pager     = $definition['one_pager'] ?? array();
		$one_pager_link = \is_array( $one_pager ) && isset( $one_pager['link'] ) ? (string) $one_pager['link'] : '';

		$preview_cache_hit = false;
		$rendered_preview_html = '';
		$cache_key = $this->preview_cache !== null ? $this->preview_cache->get_cache_key( $context, $definition ) : '';
		if ( $cache_key !== '' && $this->preview_cache !== null ) {
			$cached = $this->preview_cache->get( $cache_key );
			if ( $cached !== null ) {
				$rendered_preview_html = $cached->get_html();
				$preview_cache_hit = true;
			}
		}
		if ( $rendered_preview_html === '' ) {
			$rendered_preview_html = $this->render_preview_html( $definition, $section_field_values, array(
				'reduced_motion' => $reduced_motion,
				'page_template'  => $definition,
			) );
			if ( $cache_key !== '' && $this->preview_cache !== null && $rendered_preview_html !== '' ) {
				$version_hash = $this->preview_cache->definition_version_hash( $definition, Synthetic_Preview_Context::TYPE_PAGE );
				$record = new Preview_Cache_Record(
					$cache_key,
					Preview_Cache_Record::TYPE_PAGE,
					$template_key,
					$version_hash,
					$rendered_preview_html,
					\time(),
					$reduced_motion,
					$context->get_animation_tier()
				);
				$this->preview_cache->set( $record );
			}
		}

		$section_compatibilities = array();
		if ( $this->lpagery_compatibility !== null ) {
			foreach ( $ordered_sections as $item ) {
				if ( ! \is_array( $item ) ) {
					continue;
				}
				$sec_key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				if ( $sec_key === '' ) {
					continue;
				}
				$section_def = $this->section_provider->get_definition_by_key( $sec_key );
				$comp = $this->lpagery_compatibility->get_compatibility_for_section( $sec_key, \is_array( $section_def ) ? $section_def : null );
				$section_compatibilities[] = array(
					'section_key'        => $sec_key,
					'lpagery_state'      => $comp->get_compatibility_state(),
					'compatibility_state' => $comp->get_compatibility_state(),
				);
			}
			$page_comp = $this->lpagery_compatibility->get_compatibility_for_page_template( $template_key, $definition, $section_compatibilities );
			$lpagery_compatibility_state = $page_comp->to_array();
		} else {
			$lpagery_compatibility_state = null;
		}

		$page_form_references = array();
		if ( $this->form_reference_aggregator !== null ) {
			$items = array_map( function ( $e ) {
				return array( 'field_values' => isset( $e['field_values'] ) && is_array( $e['field_values'] ) ? $e['field_values'] : array() );
			}, $section_field_values );
			$page_form_references = $this->form_reference_aggregator->aggregate( $items );
		}

		$breadcrumbs = $this->build_breadcrumbs( $definition, $category_class, $family );
		$version_summary = $this->versioning_service !== null
			? $this->versioning_service->get_version_summary( $definition, 'page' )
			: $this->build_version_summary_from_definition( $definition, 'page' );
		$deprecation_summary = $this->deprecation_service !== null
			? $this->deprecation_service->get_deprecation_summary( $definition, 'page' )
			: $this->build_deprecation_summary_from_definition( $definition, 'page' );

		return array(
			'template_key'                 => $template_key,
			'definition'                   => $definition,
			'version_summary'              => $version_summary,
			'deprecation_summary'          => $deprecation_summary,
			'side_panel'                   => $side_panel,
			'used_sections'                => $used_sections,
			'one_pager_link'               => $one_pager_link,
			'lpagery_compatibility_state'  => $lpagery_compatibility_state,
			'preview_payload'              => $preview_payload,
			'rendered_preview_html'        => $rendered_preview_html,
			'preview_cache_hit'            => $preview_cache_hit,
			'page_form_references'         => $page_form_references,
			'breadcrumbs'                  => $breadcrumbs,
			'not_found'                    => false,
		);
	}

	/**
	 * Builds ordered list for synthetic generator: section_key, position, purpose_family (from section definition).
	 *
	 * @param array<int, array<string, mixed>> $ordered_sections
	 * @return list<array{section_key: string, position: int, purpose_family: string}>
	 */
	private function build_ordered_sections_for_generator( array $ordered_sections ): array {
		$out = array();
		$pos = 0;
		foreach ( $ordered_sections as $item ) {
			if ( ! \is_array( $item ) ) {
				continue;
			}
			$section_key = isset( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ) && \is_string( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] )
				? $item[ Page_Template_Schema::SECTION_ITEM_KEY ]
				: '';
			if ( $section_key === '' ) {
				continue;
			}
			$position = isset( $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) && is_numeric( $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] )
				? (int) $item[ Page_Template_Schema::SECTION_ITEM_POSITION ]
				: $pos;
			$section_def = $this->section_provider->get_definition_by_key( $section_key );
			$purpose_family = 'other';
			if ( \is_array( $section_def ) && isset( $section_def['section_purpose_family'] ) && \is_string( $section_def['section_purpose_family'] ) ) {
				$purpose_family = $section_def['section_purpose_family'];
			}
			$out[] = array( 'section_key' => $section_key, 'position' => $position, 'purpose_family' => $purpose_family );
			++$pos;
		}
		return $out;
	}

	/**
	 * Renders preview HTML via real pipeline: section definitions + synthetic field values → context → renderer → assemble → do_blocks.
	 *
	 * @param array<string, mixed> $definition
	 * @param list<array{section_key: string, position: int, field_values: array<string, mixed>}> $section_field_values
	 * @param array<string, mixed> $options Optional: reduced_motion (bool), page_template (array) for animation resolution.
	 * @return string HTML safe for admin output (escaped later if needed; block content is run through do_blocks).
	 */
	private function render_preview_html( array $definition, array $section_field_values, array $options = array() ): string {
		$ordered = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$section_results = array();
		$position = 0;

		foreach ( $section_field_values as $entry ) {
			$section_key  = (string) ( $entry['section_key'] ?? '' );
			$field_values = isset( $entry['field_values'] ) && \is_array( $entry['field_values'] ) ? $entry['field_values'] : array();
			$pos          = isset( $entry['position'] ) ? (int) $entry['position'] : $position;

			if ( $section_key === '' ) {
				continue;
			}

			$section_def = $this->section_provider->get_definition_by_key( $section_key );
			if ( $section_def === null || empty( $section_def ) ) {
				continue;
			}

			$built = $this->context_builder->build( $section_def, $field_values, $pos, null );
			if ( $built['context'] === null ) {
				continue;
			}
			$section_results[] = $this->section_renderer->render( $built['context'], $options );
			++$position;
		}

		if ( empty( $section_results ) ) {
			return '';
		}

		$template_key = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$assembly = $this->assembly_pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			$template_key,
			$section_results
		);

		$block_content = $assembly->get_block_content();
		if ( $block_content === '' ) {
			return '';
		}

		return \do_blocks( $block_content );
	}

	/**
	 * Builds version summary from definition when versioning service is not injected (Prompt 189).
	 *
	 * @param array<string, mixed> $definition
	 * @param string $type 'section' or 'page'
	 * @return array{version: string, stable_key_retained: bool, changelog_ref: string, breaking: bool}
	 */
	private function build_version_summary_from_definition( array $definition, string $type ): array {
		$version_data = $definition[ Page_Template_Schema::FIELD_VERSION ] ?? array();
		if ( ! \is_array( $version_data ) ) {
			$version_data = array();
		}
		return array(
			'version'              => isset( $version_data['version'] ) ? (string) $version_data['version'] : '1',
			'stable_key_retained'  => (bool) ( $version_data['stable_key_retained'] ?? true ),
			'changelog_ref'        => (string) ( $version_data['changelog_ref'] ?? '' ),
			'breaking'             => (bool) ( $version_data['breaking'] ?? false ),
		);
	}

	/**
	 * Builds deprecation summary from definition when deprecation service is not injected (Prompt 189).
	 *
	 * @param array<string, mixed> $definition
	 * @param string $type 'section' or 'page'
	 * @return array{is_deprecated: bool, reason: string, replacement_keys: list<string>, deprecated_at: string}
	 */
	private function build_deprecation_summary_from_definition( array $definition, string $type ): array {
		$status = (string) ( $definition[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
		$dep = $definition['deprecation'] ?? array();
		if ( ! \is_array( $dep ) ) {
			$dep = array();
		}
		$refs = $definition['replacement_template_refs'] ?? $dep['replacement_template_key'] ?? '';
		$replacement_keys = array();
		if ( \is_array( $refs ) ) {
			$replacement_keys = array_values( array_filter( array_map( 'strval', $refs ) ) );
		} elseif ( (string) $refs !== '' ) {
			$replacement_keys = array( (string) $refs );
		}
		return array(
			'is_deprecated'   => $status === 'deprecated' || (bool) ( $dep['deprecated'] ?? false ),
			'reason'          => (string) ( $dep['reason'] ?? '' ),
			'replacement_keys' => $replacement_keys,
			'deprecated_at'   => (string) ( $dep['deprecated_at'] ?? '' ),
		);
	}

	/**
	 * Breadcrumb segments for detail view: Page Templates → [Category] → [Family] → Template name.
	 *
	 * @param array<string, mixed> $definition
	 * @param string $category_class
	 * @param string $family
	 * @return list<array{label: string, url: string}>
	 */
	private function build_breadcrumbs( array $definition, string $category_class, string $family ): array {
		$base_url = \admin_url( 'admin.php?page=' . Page_Template_Directory_State_Builder::SCREEN_SLUG );
		$segments = array( array( 'label' => __( 'Page Templates', 'aio-page-builder' ), 'url' => $base_url ) );

		if ( $category_class !== '' ) {
			$cat_labels = array(
				'top_level'    => __( 'Top Level', 'aio-page-builder' ),
				'hub'          => __( 'Hub', 'aio-page-builder' ),
				'nested_hub'   => __( 'Nested Hub', 'aio-page-builder' ),
				'child_detail' => __( 'Child/Detail', 'aio-page-builder' ),
			);
			$cat_label = $cat_labels[ $category_class ] ?? $category_class;
			$segments[] = array( 'label' => $cat_label, 'url' => $base_url . '&category_class=' . \rawurlencode( $category_class ) );
		}

		if ( $family !== '' ) {
			$family_label = \ucfirst( \str_replace( array( '_', '-' ), ' ', $family ) );
			$fam_url = $base_url . '&category_class=' . \rawurlencode( $category_class ) . '&family=' . \rawurlencode( $family );
			$segments[] = array( 'label' => $family_label, 'url' => $fam_url );
		}

		$name = (string) ( $definition['name'] ?? $definition['internal_key'] ?? '' );
		$segments[] = array( 'label' => $name !== '' ? $name : $definition['internal_key'], 'url' => '' );
		return $segments;
	}

	/**
	 * @param string $template_key
	 * @param array<string, mixed> $request_params
	 * @return array<string, mixed>
	 */
	private function not_found_state( string $template_key, array $request_params ): array {
		$base_url = \admin_url( 'admin.php?page=' . Page_Template_Directory_State_Builder::SCREEN_SLUG );
		return array(
			'template_key'                 => $template_key,
			'definition'                   => array(),
			'version_summary'              => array( 'version' => '1', 'stable_key_retained' => true, 'changelog_ref' => '', 'breaking' => false ),
			'deprecation_summary'          => array( 'is_deprecated' => false, 'reason' => '', 'replacement_keys' => array(), 'deprecated_at' => '' ),
			'side_panel'                   => array(),
			'used_sections'                => array(),
			'one_pager_link'               => '',
			'lpagery_compatibility_state'  => null,
			'preview_payload'              => array(),
			'rendered_preview_html'        => '',
			'preview_cache_hit'            => false,
			'page_form_references'         => array(),
			'breadcrumbs'                  => array( array( 'label' => __( 'Page Templates', 'aio-page-builder' ), 'url' => $base_url ) ),
			'not_found'                    => true,
		);
	}
}
