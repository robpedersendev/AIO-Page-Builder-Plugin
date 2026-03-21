<?php
/**
 * Builds detail-screen state for a single section template (spec §49.6, §17.1, template-preview-and-dummy-data-contract).
 * Produces metadata panel, field summary, helper ref, compatibility notes, and rendered preview HTML via the real section renderer.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\Industry\Preview\Industry_Dummy_Data_Generator;
use AIOPageBuilder\Domain\Preview\Preview_Cache_Record;
use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;
use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\LPagery\Library_LPagery_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;

/**
 * Builds stable detail-state payload: section definition, side-panel metadata, field_summary,
 * helper_ref, compatibility_notes, preview payload, and rendered_preview_html.
 * Helper-doc admin URLs are resolved at render time via Helper_Doc_Url_Resolver (Section_Template_Detail_Screen), not in this array.
 */
final class Section_Template_Detail_State_Builder {

	/** @var Section_Definition_Provider */
	private Section_Definition_Provider $section_provider;

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

	/** @var Section_Field_Blueprint_Service|null */
	private ?Section_Field_Blueprint_Service $blueprint_service;

	/** @var Library_LPagery_Compatibility_Service|null */
	private ?Library_LPagery_Compatibility_Service $lpagery_compatibility;

	/** @var Preview_Cache_Service|null */
	private ?Preview_Cache_Service $preview_cache;

	/** @var Template_Versioning_Service|null */
	private ?Template_Versioning_Service $versioning_service;

	/** @var Template_Deprecation_Service|null */
	private ?Template_Deprecation_Service $deprecation_service;

	/** @var Form_Section_Field_State_Builder|null */
	private ?Form_Section_Field_State_Builder $form_section_field_state_builder;

	/** @var Industry_Dummy_Data_Generator|null */
	private ?Industry_Dummy_Data_Generator $industry_dummy_generator;

	/** @var string|null */
	private ?string $industry_key;

	public function __construct(
		Section_Definition_Provider $section_provider,
		Synthetic_Preview_Data_Generator $preview_generator,
		Preview_Side_Panel_Builder $side_panel_builder,
		Section_Render_Context_Builder $context_builder,
		Section_Renderer_Base $section_renderer,
		Native_Block_Assembly_Pipeline $assembly_pipeline,
		?Section_Field_Blueprint_Service $blueprint_service = null,
		?Library_LPagery_Compatibility_Service $lpagery_compatibility = null,
		?Preview_Cache_Service $preview_cache = null,
		?Template_Versioning_Service $versioning_service = null,
		?Template_Deprecation_Service $deprecation_service = null,
		?Form_Section_Field_State_Builder $form_section_field_state_builder = null,
		?Industry_Dummy_Data_Generator $industry_dummy_generator = null,
		?string $industry_key = null
	) {
		$this->section_provider                 = $section_provider;
		$this->preview_generator                = $preview_generator;
		$this->side_panel_builder               = $side_panel_builder;
		$this->context_builder                  = $context_builder;
		$this->section_renderer                 = $section_renderer;
		$this->assembly_pipeline                = $assembly_pipeline;
		$this->blueprint_service                = $blueprint_service;
		$this->lpagery_compatibility            = $lpagery_compatibility;
		$this->preview_cache                    = $preview_cache;
		$this->versioning_service               = $versioning_service;
		$this->deprecation_service              = $deprecation_service;
		$this->form_section_field_state_builder = $form_section_field_state_builder;
		$this->industry_dummy_generator         = $industry_dummy_generator;
		$this->industry_key                     = $industry_key !== null && $industry_key !== '' ? $industry_key : null;
	}

	/**
	 * Builds full detail state for the given section key. Returns not_found when section does not exist.
	 *
	 * @param string               $section_key Section template internal_key.
	 * @param array<string, mixed> $request_params Optional: purpose_family (for breadcrumb), reduced_motion.
	 * @return array<string, mixed> State: section_key, definition, side_panel, field_summary, helper_ref, compatibility_notes, preview_payload, rendered_preview_html, breadcrumbs, not_found.
	 */
	public function build_state( string $section_key, array $request_params = array() ): array {
		$section_key = \sanitize_key( $section_key );
		if ( $section_key === '' ) {
			return $this->not_found_state( $section_key );
		}

		$definition = $this->section_provider->get_definition_by_key( $section_key );
		if ( $definition === null || empty( $definition ) ) {
			return $this->not_found_state( $section_key );
		}

		$purpose_family = isset( $request_params['purpose_family'] ) ? \sanitize_key( (string) $request_params['purpose_family'] ) : (string) ( $definition['section_purpose_family'] ?? 'other' );
		$reduced_motion = ! empty( $request_params['reduced_motion'] );

		$context = Synthetic_Preview_Context::for_section(
			$section_key,
			$purpose_family !== '' ? $purpose_family : 'other',
			'default',
			$reduced_motion,
			$reduced_motion ? Synthetic_Preview_Context::ANIMATION_TIER_NONE : Synthetic_Preview_Context::ANIMATION_TIER_NONE
		);

		$field_values = $this->preview_generator->generate_for_section( $context );
		if ( $this->industry_dummy_generator !== null && $this->industry_key !== null ) {
			$overrides = $this->industry_dummy_generator->get_overrides_for_family( $purpose_family !== '' ? $purpose_family : 'other', $this->industry_key );
			if ( ! empty( $overrides ) ) {
				$field_values = array_merge( $field_values, $overrides );
			}
		}
		$preview_payload = $this->side_panel_builder->build_section_preview_payload( $definition, $field_values, $context );
		$side_panel      = $preview_payload['side_panel'] ?? $this->side_panel_builder->build_for_section( $definition, $context );

		$field_summary       = $this->build_field_summary( $section_key, $definition );
		$helper_ref          = (string) ( $definition[ Section_Schema::FIELD_HELPER_REF ] ?? $side_panel['helper_ref'] ?? '' );
		$compatibility       = $definition['compatibility'] ?? array();
		$compatibility_notes = \is_array( $compatibility ) ? $compatibility : array();

		$lpagery_compatibility_state = null;
		if ( $this->lpagery_compatibility !== null ) {
			$lpagery_result              = $this->lpagery_compatibility->get_compatibility_for_section( $section_key, $definition );
			$lpagery_compatibility_state = $lpagery_result->to_array();
		}

		$preview_cache_hit     = false;
		$rendered_preview_html = '';
		$cache_key             = $this->preview_cache !== null ? $this->preview_cache->get_cache_key( $context, $definition ) : '';
		if ( $cache_key !== '' && $this->preview_cache !== null ) {
			$cached = $this->preview_cache->get( $cache_key );
			if ( $cached !== null ) {
				$rendered_preview_html = $cached->get_html();
				$preview_cache_hit     = true;
			}
		}
		if ( $rendered_preview_html === '' ) {
			$rendered_preview_html = $this->render_preview_html( $definition, $field_values, array( 'reduced_motion' => $reduced_motion ) );
			if ( $cache_key !== '' && $this->preview_cache !== null && $rendered_preview_html !== '' ) {
				$version_hash = $this->preview_cache->definition_version_hash( $definition, Synthetic_Preview_Context::TYPE_SECTION );
				$record       = new Preview_Cache_Record(
					$cache_key,
					Preview_Cache_Record::TYPE_SECTION,
					$section_key,
					$version_hash,
					$rendered_preview_html,
					\time(),
					$reduced_motion,
					$context->get_animation_tier()
				);
				$this->preview_cache->set( $record );
			}
		}

		$form_section_field_state = null;
		if ( $this->form_section_field_state_builder !== null ) {
			$form_section_field_state = $this->form_section_field_state_builder->build_state( $definition, $field_values );
		}

		$breadcrumbs         = $this->build_breadcrumbs( $definition, $purpose_family );
		$version_summary     = $this->versioning_service !== null
			? $this->versioning_service->get_version_summary( $definition, 'section' )
			: $this->build_version_summary_from_definition( $definition, 'section' );
		$deprecation_summary = $this->deprecation_service !== null
			? $this->deprecation_service->get_deprecation_summary( $definition, 'section' )
			: $this->build_deprecation_summary_from_definition( $definition, 'section' );

		return array(
			'section_key'                 => $section_key,
			'definition'                  => $definition,
			'version_summary'             => $version_summary,
			'deprecation_summary'         => $deprecation_summary,
			'side_panel'                  => $side_panel,
			'field_summary'               => $field_summary,
			'helper_ref'                  => $helper_ref,
			'compatibility_notes'         => $compatibility_notes,
			'lpagery_compatibility_state' => $lpagery_compatibility_state,
			'preview_payload'             => $preview_payload,
			'rendered_preview_html'       => $rendered_preview_html,
			'preview_cache_hit'           => $preview_cache_hit,
			'breadcrumbs'                 => $breadcrumbs,
			'form_section_field_state'    => $form_section_field_state,
			'not_found'                   => false,
		);
	}

	/**
	 * Builds field summary from blueprint (service or embedded in definition). Returns list of { name, label, type }.
	 *
	 * @param string               $section_key
	 * @param array<string, mixed> $definition
	 * @return array<int, array{name: string, label: string, type: string}>
	 */
	private function build_field_summary( string $section_key, array $definition ): array {
		$blueprint = null;
		if ( $this->blueprint_service !== null ) {
			$blueprint = $this->blueprint_service->get_blueprint_for_section( $section_key );
		}
		if ( $blueprint === null ) {
			$embedded = $definition['field_blueprint'] ?? null;
			if ( \is_array( $embedded ) && ! empty( $embedded[ Field_Blueprint_Schema::FIELDS ] ) && \is_array( $embedded[ Field_Blueprint_Schema::FIELDS ] ) ) {
				$blueprint = array( Field_Blueprint_Schema::FIELDS => $embedded[ Field_Blueprint_Schema::FIELDS ] );
			}
		}
		if ( $blueprint === null || empty( $blueprint[ Field_Blueprint_Schema::FIELDS ] ) ) {
			return array();
		}
		$out = array();
		foreach ( (array) $blueprint[ Field_Blueprint_Schema::FIELDS ] as $field ) {
			if ( ! \is_array( $field ) ) {
				continue;
			}
			$name  = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			$label = (string) ( $field[ Field_Blueprint_Schema::FIELD_LABEL ] ?? $name );
			$type  = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? 'text' );
			if ( $name !== '' ) {
				$out[] = array(
					'name'  => $name,
					'label' => $label !== '' ? $label : $name,
					'type'  => $type,
				);
			}
		}
		return $out;
	}

	/**
	 * Renders single-section preview HTML via real pipeline: context → renderer → assemble → do_blocks.
	 *
	 * @param array<string, mixed> $definition
	 * @param array<string, mixed> $field_values
	 * @param array<string, mixed> $options Optional: reduced_motion (bool), page_template (array) for animation resolution.
	 * @return string
	 */
	private function render_preview_html( array $definition, array $field_values, array $options = array() ): string {
		$built = $this->context_builder->build( $definition, $field_values, 0, null );
		if ( $built['context'] === null ) {
			return '';
		}
		$result        = $this->section_renderer->render( $built['context'], $options );
		$assembly      = $this->assembly_pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			(string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ),
			array( $result )
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
	 * @param string               $type 'section' or 'page'
	 * @return array{version: string, stable_key_retained: bool, changelog_ref: string, breaking: bool}
	 */
	private function build_version_summary_from_definition( array $definition, string $type ): array {
		$field        = $type === 'page' ? \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema::FIELD_VERSION : Section_Schema::FIELD_VERSION;
		$version_data = $definition[ $field ] ?? array();
		if ( ! \is_array( $version_data ) ) {
			$version_data = array();
		}
		return array(
			'version'             => isset( $version_data['version'] ) ? (string) $version_data['version'] : '1',
			'stable_key_retained' => (bool) ( $version_data['stable_key_retained'] ?? true ),
			'changelog_ref'       => (string) ( $version_data['changelog_ref'] ?? '' ),
			'breaking'            => (bool) ( $version_data['breaking'] ?? false ),
		);
	}

	/**
	 * Builds deprecation summary from definition when deprecation service is not injected (Prompt 189).
	 *
	 * @param array<string, mixed> $definition
	 * @param string               $type 'section' or 'page'
	 * @return array{is_deprecated: bool, reason: string, replacement_keys: array<int, string>, deprecated_at: string}
	 */
	private function build_deprecation_summary_from_definition( array $definition, string $type ): array {
		$status_field = $type === 'page' ? \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema::FIELD_STATUS : Section_Schema::FIELD_STATUS;
		$status       = (string) ( $definition[ $status_field ] ?? '' );
		$dep          = $definition['deprecation'] ?? array();
		if ( ! \is_array( $dep ) ) {
			$dep = array();
		}
		$replacement_keys = array();
		if ( $type === 'page' ) {
			$refs = $definition['replacement_template_refs'] ?? $dep['replacement_template_key'] ?? '';
			if ( \is_array( $refs ) ) {
				$replacement_keys = array_values( array_filter( array_map( 'strval', $refs ) ) );
			} elseif ( (string) $refs !== '' ) {
				$replacement_keys = array( (string) $refs );
			}
		} else {
			$refs = $definition['replacement_section_suggestions'] ?? $dep['replacement_section_key'] ?? '';
			if ( \is_array( $refs ) ) {
				$replacement_keys = array_values( array_filter( array_map( 'strval', $refs ) ) );
			} elseif ( (string) $refs !== '' ) {
				$replacement_keys = array( (string) $refs );
			}
		}
		return array(
			'is_deprecated'    => $status === 'deprecated' || (bool) ( $dep['deprecated'] ?? false ),
			'reason'           => (string) ( $dep['reason'] ?? '' ),
			'replacement_keys' => $replacement_keys,
			'deprecated_at'    => (string) ( $dep['deprecated_at'] ?? '' ),
		);
	}

	/**
	 * Breadcrumb segments: Section Templates → [Purpose family] → Section name.
	 *
	 * @param array<string, mixed> $definition
	 * @param string               $purpose_family
	 * @return array<int, array{label: string, url: string}>
	 */
	private function build_breadcrumbs( array $definition, string $purpose_family ): array {
		$base_url = \admin_url( 'admin.php?page=' . Section_Template_Directory_State_Builder::SCREEN_SLUG );
		$segments = array(
			array(
				'label' => __( 'Section Templates', 'aio-page-builder' ),
				'url'   => $base_url,
			),
		);
		if ( $purpose_family !== '' ) {
			$purpose_label = \ucfirst( \str_replace( array( '_', '-' ), ' ', $purpose_family ) );
			$segments[]    = array(
				'label' => $purpose_label,
				'url'   => $base_url . '&purpose_family=' . \rawurlencode( $purpose_family ),
			);
		}
		$name       = (string) ( $definition['name'] ?? $definition['internal_key'] ?? '' );
		$segments[] = array(
			'label' => $name !== '' ? $name : (string) ( $definition['internal_key'] ?? '' ),
			'url'   => '',
		);
		return $segments;
	}

	private function not_found_state( string $section_key ): array {
		$base_url = \admin_url( 'admin.php?page=' . Section_Template_Directory_State_Builder::SCREEN_SLUG );
		return array(
			'section_key'                 => $section_key,
			'definition'                  => array(),
			'version_summary'             => array(
				'version'             => '1',
				'stable_key_retained' => true,
				'changelog_ref'       => '',
				'breaking'            => false,
			),
			'deprecation_summary'         => array(
				'is_deprecated'    => false,
				'reason'           => '',
				'replacement_keys' => array(),
				'deprecated_at'    => '',
			),
			'side_panel'                  => array(),
			'field_summary'               => array(),
			'helper_ref'                  => '',
			'compatibility_notes'         => array(),
			'lpagery_compatibility_state' => null,
			'preview_payload'             => array(),
			'rendered_preview_html'       => '',
			'preview_cache_hit'           => false,
			'breadcrumbs'                 => array(
				array(
					'label' => __( 'Section Templates', 'aio-page-builder' ),
					'url'   => $base_url,
				),
			),
			'form_section_field_state'    => null,
			'not_found'                   => true,
		);
	}
}
