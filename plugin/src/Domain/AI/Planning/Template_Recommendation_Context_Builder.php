<?php
/**
 * Builds planner-facing template recommendation context from the page template registry (spec §5.2, §5.4, §13, §59.8).
 * Exposes category, family, hierarchy, and purpose metadata for AI/planning and Build Plan generation. Deprecation-aware; active-only by default.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Produces template_recommendation_context and recommended_template_summary payloads for input artifacts and Build Plan display.
 * Bounded so the planner is not overwhelmed; no secrets or raw provider data.
 *
 * Example template_recommendation_context entry (one item from template_recommendation_context array):
 * [
 *   'template_key' => 'pt_home_conversion_01',
 *   'name' => 'Home (conversion-led)',
 *   'purpose_summary' => 'Home page with conversion emphasis: hero, proof, value prop...',
 *   'template_category_class' => 'top_level',
 *   'template_family' => 'home',
 *   'archetype' => 'landing_page',
 *   'hierarchy_hint' => 'top_level',
 *   'cta_direction_summary' => 'Consultation, booking, quote request...',
 *   'section_count' => 12,
 *   'version' => '1',
 *   'deprecation_status' => 'active',
 *   'one_pager_available' => true,
 * ]
 */
final class Template_Recommendation_Context_Builder {

	/** Default max templates included in recommendation context (avoid overload). */
	public const DEFAULT_MAX_TEMPLATES = 80;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repository;

	public function __construct( Page_Template_Repository $page_template_repository ) {
		$this->page_template_repository = $page_template_repository;
	}

	/**
	 * Builds template recommendation context for planning (input artifact registry or prompt context).
	 * Only active, non-deprecated templates; capped count. Safe for inclusion in artifact (no secrets).
	 *
	 * @param array<string, mixed> $options Optional: max_templates (int), template_category_class (string), template_family (string).
	 * @return array{template_recommendation_context: list<array<string, mixed>>, total_active: int}
	 */
	public function build( array $options = array() ): array {
		$max = (int) ( $options['max_templates'] ?? self::DEFAULT_MAX_TEMPLATES );
		$max = min( max( 1, $max ), 150 );
		$filter_class = isset( $options['template_category_class'] ) ? (string) $options['template_category_class'] : '';
		$filter_family = isset( $options['template_family'] ) ? (string) $options['template_family'] : '';

		$definitions = $this->page_template_repository->list_definitions_by_status( 'active', $max * 2, 0 );
		$list = array();
		foreach ( $definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			if ( ! Deprecation_Metadata::is_eligible_for_new_use( $def ) ) {
				continue;
			}
			$class = (string) ( $def['template_category_class'] ?? '' );
			$family = (string) ( $def['template_family'] ?? '' );
			if ( $filter_class !== '' && $class !== $filter_class ) {
				continue;
			}
			if ( $filter_family !== '' && $family !== $filter_family ) {
				continue;
			}
			$list[] = $this->build_recommended_template_summary( $def );
			if ( count( $list ) >= $max ) {
				break;
			}
		}
		return array(
			'template_recommendation_context' => $list,
			'total_active'                   => count( $list ),
		);
	}

	/**
	 * Returns a single recommended_template_summary for a template key (for Build Plan display or validation).
	 * If template is deprecated, includes deprecation_note and replacement_keys.
	 *
	 * @param string $template_key Page template internal_key.
	 * @return array<string, mixed> recommended_template_summary; empty template_key if not found.
	 */
	public function get_recommended_template_summary( string $template_key ): array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return $this->empty_summary();
		}
		$def = $this->page_template_repository->get_definition_by_key( $template_key );
		if ( $def === null || ! is_array( $def ) ) {
			return $this->empty_summary( $template_key );
		}
		$summary = $this->build_recommended_template_summary( $def );
		$status = (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
		$dep = $def['deprecation'] ?? array();
		if ( $status === 'deprecated' || ( ! empty( $dep['deprecated'] ) ) ) {
			$summary['deprecation_status'] = 'deprecated';
			$summary['deprecation_note']   = (string) ( $dep['reason'] ?? __( 'Template is deprecated.', 'aio-page-builder' ) );
			$summary['replacement_keys']   = isset( $def['replacement_template_refs'] ) && is_array( $def['replacement_template_refs'] )
				? array_values( array_map( 'strval', $def['replacement_template_refs'] ) )
				: array();
		}
		return $summary;
	}

	/**
	 * Builds recommended_template_summary from a single page template definition.
	 *
	 * @param array<string, mixed> $def
	 * @return array<string, mixed>
	 */
	private function build_recommended_template_summary( array $def ): array {
		$key    = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$name   = (string) ( $def[ Page_Template_Schema::FIELD_NAME ] ?? $key );
		$purpose = (string) ( $def[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' );
		$class  = (string) ( $def['template_category_class'] ?? '' );
		$family = (string) ( $def['template_family'] ?? '' );
		$arch   = (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' );
		$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$section_count = is_array( $ordered ) ? count( $ordered ) : 0;
		$version_arr = $def[ Page_Template_Schema::FIELD_VERSION ] ?? array();
		$version = is_array( $version_arr ) && isset( $version_arr['version'] ) ? (string) $version_arr['version'] : '1';
		$hierarchy_hints = $def['hierarchy_hints'] ?? array();
		$hierarchy_hint = is_array( $hierarchy_hints ) && isset( $hierarchy_hints['hierarchy_role'] )
			? (string) $hierarchy_hints['hierarchy_role']
			: ( $class !== '' ? $class : '' );
		$cta_direction = (string) ( $def['cta_direction_summary'] ?? '' );
		$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
		$one_pager_available = is_array( $one_pager ) && ( isset( $one_pager['link'] ) || isset( $one_pager['page_purpose_summary'] ) );

		return array(
			'template_key'               => $key,
			'name'                      => $name,
			'purpose_summary'            => $purpose,
			'template_category_class'   => $class,
			'template_family'           => $family,
			'archetype'                 => $arch,
			'hierarchy_hint'            => $hierarchy_hint,
			'cta_direction_summary'    => $cta_direction,
			'section_count'             => $section_count,
			'version'                   => $version,
			'deprecation_status'        => ( (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' ) ) === 'deprecated' ? 'deprecated' : 'active',
			'one_pager_available'      => $one_pager_available,
		);
	}

	private function empty_summary( string $template_key = '' ): array {
		return array(
			'template_key'               => $template_key,
			'name'                       => '',
			'purpose_summary'            => '',
			'template_category_class'   => '',
			'template_family'           => '',
			'archetype'                  => '',
			'hierarchy_hint'             => '',
			'cta_direction_summary'     => '',
			'section_count'              => 0,
			'version'                    => '1',
			'deprecation_status'         => 'unknown',
			'one_pager_available'        => false,
			'deprecation_note'           => '',
			'replacement_keys'           => array(),
		);
	}
}
