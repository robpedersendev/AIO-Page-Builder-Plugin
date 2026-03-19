<?php
/**
 * Builds Build Plan template explanation payload for review UI (spec §30, §31, §59.9).
 * Enriches proposed template_key with purpose, category, hierarchy, CTA direction, and deprecation so reviewers see rationale.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Recommendations;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Planning\Template_Recommendation_Context_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Produces build_plan_template_explanation for a given template_key and optional item payload.
 * Used by New Page Creation detail panel to show richer template rationale.
 *
 * Example build_plan_template_explanation payload:
 * [
 *   'template_key' => 'pt_home_conversion_01',
 *   'name' => 'Home (conversion-led)',
 *   'purpose_summary' => 'Home page with conversion emphasis...',
 *   'template_category_class' => 'top_level',
 *   'template_family' => 'home',
 *   'hierarchy_hint' => 'top_level',
 *   'cta_direction_summary' => 'Consultation, booking, quote request...',
 *   'section_count' => 12,
 *   'version' => '1',
 *   'deprecation_status' => 'active',
 *   'replacement_keys' => [],
 *   'one_pager_available' => true,
 *   'explanation_lines' => [
 *     'Purpose: Home page with conversion emphasis...',
 *     'Category / family: top_level · home',
 *     'Hierarchy: top_level',
 *     'CTA direction: Consultation, booking, quote request...',
 *     'Sections: 12',
 *   ],
 * ]
 */
final class Build_Plan_Template_Explanation_Builder implements Template_Explanation_Builder_Interface {

	/** @var Template_Recommendation_Context_Builder|null */
	private ?Template_Recommendation_Context_Builder $context_builder;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repository;

	public function __construct(
		Page_Template_Repository $page_template_repository,
		?Template_Recommendation_Context_Builder $context_builder = null
	) {
		$this->page_template_repository = $page_template_repository;
		$this->context_builder          = $context_builder;
	}

	/**
	 * Builds build_plan_template_explanation for the given template key and optional new_page item payload.
	 *
	 * @param string               $template_key Page template internal_key from plan item payload.
	 * @param array<string, mixed> $item_payload Optional: purpose, page_type for display context.
	 * @return array<string, mixed> build_plan_template_explanation (template_key, template_name, purpose_summary, template_category_class, template_family, hierarchy_hint, cta_direction_summary, section_count, version, deprecation_status, replacement_keys, one_pager_available, explanation_lines).
	 */
	public function build_explanation( string $template_key, array $item_payload = array() ): array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return $this->empty_explanation( $template_key );
		}
		if ( $this->context_builder !== null ) {
			$summary = $this->context_builder->get_recommended_template_summary( $template_key );
		} else {
			$def     = $this->page_template_repository->get_definition_by_key( $template_key );
			$summary = $def !== null ? $this->summary_from_definition( $def ) : $this->empty_explanation( $template_key );
		}
		$explanation_lines            = $this->build_explanation_lines( $summary, $item_payload );
		$summary['explanation_lines'] = $explanation_lines;
		return $summary;
	}

	/**
	 * Builds short explanation lines for detail panel (escaped for output by caller).
	 *
	 * @param array<string, mixed> $summary
	 * @param array<string, mixed> $item_payload
	 * @return array<int, string>
	 */
	private function build_explanation_lines( array $summary, array $item_payload ): array {
		$lines   = array();
		$purpose = (string) ( $summary['purpose_summary'] ?? '' );
		if ( $purpose !== '' ) {
			$lines[] = \__( 'Purpose:', 'aio-page-builder' ) . ' ' . $purpose;
		}
		$class  = (string) ( $summary['template_category_class'] ?? '' );
		$family = (string) ( $summary['template_family'] ?? '' );
		if ( $class !== '' || $family !== '' ) {
			$parts   = array_filter( array( $class, $family ) );
			$lines[] = \__( 'Category / family:', 'aio-page-builder' ) . ' ' . \implode( ' · ', $parts );
		}
		$hint = (string) ( $summary['hierarchy_hint'] ?? '' );
		if ( $hint !== '' ) {
			$lines[] = \__( 'Hierarchy:', 'aio-page-builder' ) . ' ' . $hint;
		}
		$cta = (string) ( $summary['cta_direction_summary'] ?? '' );
		if ( $cta !== '' ) {
			$lines[] = \__( 'CTA direction:', 'aio-page-builder' ) . ' ' . $cta;
		}
		$section_count = (int) ( $summary['section_count'] ?? 0 );
		if ( $section_count > 0 ) {
			$lines[] = \sprintf( \__( 'Sections: %d', 'aio-page-builder' ), $section_count );
		}
		$dep_status = (string) ( $summary['deprecation_status'] ?? 'active' );
		if ( $dep_status === 'deprecated' ) {
			$note    = (string) ( $summary['deprecation_note'] ?? '' );
			$lines[] = \__( 'Deprecated.', 'aio-page-builder' ) . ( $note !== '' ? ' ' . $note : '' );
			$repl    = $summary['replacement_keys'] ?? array();
			if ( is_array( $repl ) && $repl !== array() ) {
				$lines[] = \__( 'Recommended replacement(s):', 'aio-page-builder' ) . ' ' . \implode( ', ', array_map( 'strval', $repl ) );
			}
		}
		return $lines;
	}

	private function summary_from_definition( array $def ): array {
		$key                 = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$name                = (string) ( $def[ Page_Template_Schema::FIELD_NAME ] ?? $key );
		$purpose             = (string) ( $def[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' );
		$class               = (string) ( $def['template_category_class'] ?? '' );
		$family              = (string) ( $def['template_family'] ?? '' );
		$ordered             = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$section_count       = is_array( $ordered ) ? count( $ordered ) : 0;
		$version_arr         = $def[ Page_Template_Schema::FIELD_VERSION ] ?? array();
		$version             = is_array( $version_arr ) && isset( $version_arr['version'] ) ? (string) $version_arr['version'] : '1';
		$status              = (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
		$dep                 = $def['deprecation'] ?? array();
		$hierarchy_hints     = $def['hierarchy_hints'] ?? array();
		$hierarchy_hint      = is_array( $hierarchy_hints ) && isset( $hierarchy_hints['hierarchy_role'] ) ? (string) $hierarchy_hints['hierarchy_role'] : $class;
		$one_pager           = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
		$one_pager_available = is_array( $one_pager ) && ( isset( $one_pager['link'] ) || isset( $one_pager['page_purpose_summary'] ) );
		$replacement_keys    = isset( $def['replacement_template_refs'] ) && is_array( $def['replacement_template_refs'] )
			? array_values( array_map( 'strval', $def['replacement_template_refs'] ) )
			: array();

		return array(
			'template_key'            => $key,
			'name'                    => $name,
			'purpose_summary'         => $purpose,
			'template_category_class' => $class,
			'template_family'         => $family,
			'hierarchy_hint'          => $hierarchy_hint,
			'cta_direction_summary'   => (string) ( $def['cta_direction_summary'] ?? '' ),
			'section_count'           => $section_count,
			'version'                 => $version,
			'deprecation_status'      => $status === 'deprecated' ? 'deprecated' : 'active',
			'one_pager_available'     => $one_pager_available,
			'deprecation_note'        => (string) ( $dep['reason'] ?? '' ),
			'replacement_keys'        => $replacement_keys,
		);
	}

	private function empty_explanation( string $template_key ): array {
		return array(
			'template_key'            => $template_key,
			'name'                    => '',
			'purpose_summary'         => '',
			'template_category_class' => '',
			'template_family'         => '',
			'hierarchy_hint'          => '',
			'cta_direction_summary'   => '',
			'section_count'           => 0,
			'version'                 => '1',
			'deprecation_status'      => 'unknown',
			'replacement_keys'        => array(),
			'one_pager_available'     => false,
			'explanation_lines'       => array(),
		);
	}
}
