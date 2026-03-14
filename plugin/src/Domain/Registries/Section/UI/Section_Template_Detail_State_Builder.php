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
use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;

/**
 * Builds stable detail-state payload: section definition, side-panel metadata, field_summary,
 * helper_ref, helper_doc_url, compatibility_notes, preview payload, and rendered_preview_html.
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

	public function __construct(
		Section_Definition_Provider $section_provider,
		Synthetic_Preview_Data_Generator $preview_generator,
		Preview_Side_Panel_Builder $side_panel_builder,
		Section_Render_Context_Builder $context_builder,
		Section_Renderer_Base $section_renderer,
		Native_Block_Assembly_Pipeline $assembly_pipeline,
		?Section_Field_Blueprint_Service $blueprint_service = null
	) {
		$this->section_provider   = $section_provider;
		$this->preview_generator  = $preview_generator;
		$this->side_panel_builder = $side_panel_builder;
		$this->context_builder    = $context_builder;
		$this->section_renderer   = $section_renderer;
		$this->assembly_pipeline  = $assembly_pipeline;
		$this->blueprint_service  = $blueprint_service;
	}

	/**
	 * Builds full detail state for the given section key. Returns not_found when section does not exist.
	 *
	 * @param string $section_key Section template internal_key.
	 * @param array<string, mixed> $request_params Optional: purpose_family (for breadcrumb), reduced_motion.
	 * @return array<string, mixed> State: section_key, definition, side_panel, field_summary, helper_ref, helper_doc_url, compatibility_notes, preview_payload, rendered_preview_html, breadcrumbs, not_found.
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

		$field_values   = $this->preview_generator->generate_for_section( $context );
		$preview_payload = $this->side_panel_builder->build_section_preview_payload( $definition, $field_values, $context );
		$side_panel     = $preview_payload['side_panel'] ?? $this->side_panel_builder->build_for_section( $definition, $context );

		$field_summary = $this->build_field_summary( $section_key, $definition );
		$helper_ref    = (string) ( $definition[ Section_Schema::FIELD_HELPER_REF ] ?? $side_panel['helper_ref'] ?? '' );
		$helper_doc_url = $this->build_helper_doc_url( $helper_ref );
		$compatibility  = $definition['compatibility'] ?? array();
		$compatibility_notes = \is_array( $compatibility ) ? $compatibility : array();

		$rendered_preview_html = $this->render_preview_html( $definition, $field_values, array( 'reduced_motion' => $reduced_motion ) );
		$breadcrumbs = $this->build_breadcrumbs( $definition, $purpose_family );

		return array(
			'section_key'            => $section_key,
			'definition'             => $definition,
			'side_panel'             => $side_panel,
			'field_summary'          => $field_summary,
			'helper_ref'             => $helper_ref,
			'helper_doc_url'         => $helper_doc_url,
			'compatibility_notes'    => $compatibility_notes,
			'preview_payload'        => $preview_payload,
			'rendered_preview_html'  => $rendered_preview_html,
			'breadcrumbs'            => $breadcrumbs,
			'not_found'              => false,
		);
	}

	/**
	 * Builds field summary from blueprint (service or embedded in definition). Returns list of { name, label, type }.
	 *
	 * @param string $section_key
	 * @param array<string, mixed> $definition
	 * @return list<array{name: string, label: string, type: string}>
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
				$out[] = array( 'name' => $name, 'label' => $label !== '' ? $label : $name, 'type' => $type );
			}
		}
		return $out;
	}

	/**
	 * Builds helper documentation URL from helper_ref. Placeholder until helper-doc URL builder exists.
	 *
	 * @param string $helper_ref
	 * @return string
	 */
	private function build_helper_doc_url( string $helper_ref ): string {
		if ( $helper_ref === '' ) {
			return '';
		}
		// * Helper-doc URL: placeholder; replace with real helper-doc resolver when available (spec §15).
		return '';
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
		$result = $this->section_renderer->render( $built['context'], $options );
		$assembly = $this->assembly_pipeline->assemble(
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
	 * Breadcrumb segments: Section Templates → [Purpose family] → Section name.
	 *
	 * @param array<string, mixed> $definition
	 * @param string $purpose_family
	 * @return list<array{label: string, url: string}>
	 */
	private function build_breadcrumbs( array $definition, string $purpose_family ): array {
		$base_url = \admin_url( 'admin.php?page=' . Section_Template_Directory_State_Builder::SCREEN_SLUG );
		$segments = array( array( 'label' => __( 'Section Templates', 'aio-page-builder' ), 'url' => $base_url ) );
		if ( $purpose_family !== '' ) {
			$purpose_label = \ucfirst( \str_replace( array( '_', '-' ), ' ', $purpose_family ) );
			$segments[] = array( 'label' => $purpose_label, 'url' => $base_url . '&purpose_family=' . \rawurlencode( $purpose_family ) );
		}
		$name = (string) ( $definition['name'] ?? $definition['internal_key'] ?? '' );
		$segments[] = array( 'label' => $name !== '' ? $name : (string) ( $definition['internal_key'] ?? '' ), 'url' => '' );
		return $segments;
	}

	private function not_found_state( string $section_key ): array {
		$base_url = \admin_url( 'admin.php?page=' . Section_Template_Directory_State_Builder::SCREEN_SLUG );
		return array(
			'section_key'           => $section_key,
			'definition'            => array(),
			'side_panel'            => array(),
			'field_summary'         => array(),
			'helper_ref'            => '',
			'helper_doc_url'        => '',
			'compatibility_notes'   => array(),
			'preview_payload'        => array(),
			'rendered_preview_html' => '',
			'breadcrumbs'           => array( array( 'label' => __( 'Section Templates', 'aio-page-builder' ), 'url' => $base_url ) ),
			'not_found'             => true,
		);
	}
}
