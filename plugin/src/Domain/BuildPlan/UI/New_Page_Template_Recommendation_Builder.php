<?php
/**
 * Builds template-aware recommendation payloads for Build Plan Step 2 (new-page) rows (spec §33, §33.3–33.4, Prompt 192).
 *
 * Produces proposed_template_summary, hierarchy_context_summary, template_selection_reason,
 * group labels for hierarchy/family grouping, dependency and deprecation awareness. Template
 * detail/compare URLs are added in the Admin layer (Build_Plan_Workspace_Screen).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Recommendations\Template_Explanation_Builder_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\Execution\Pages\Form_Provider_Dependency_Validator;

/**
 * Enriches new_page plan items with family-aware, hierarchy-aware recommendation data for list and detail.
 * When Form_Provider_Dependency_Validator is provided, form-provider dependency errors/warnings appear in dependency_warnings (Prompt 230).
 */
final class New_Page_Template_Recommendation_Builder {

	/** Payload key for template summary (family, variation, CTA, section count, deprecation). */
	public const KEY_PROPOSED_TEMPLATE_SUMMARY = 'proposed_template_summary';

	/** Payload key for parent/child/hierarchy role context. */
	public const KEY_HIERARCHY_CONTEXT_SUMMARY = 'hierarchy_context_summary';

	/** Payload key for short rationale (why this template). */
	public const KEY_TEMPLATE_SELECTION_REASON = 'template_selection_reason';

	/** Payload key for group label (e.g. "Top-level · Home") for scannable grouping. */
	public const KEY_GROUP_LABEL = 'group_label';

	/** Payload key for hierarchy role used in grouping. */
	public const KEY_GROUP_HIERARCHY_ROLE = 'group_hierarchy_role';

	/** Payload key for template family used in grouping. */
	public const KEY_GROUP_TEMPLATE_FAMILY = 'group_template_family';

	/** Payload key for dependency warning messages. */
	public const KEY_DEPENDENCY_WARNINGS = 'dependency_warnings';

	/** Payload key for whether template is deprecated (review-only flag). */
	public const KEY_DEPRECATION_AWARE = 'deprecation_aware';

	/** Payload key for optional confidence note. */
	public const KEY_CONFIDENCE_NOTE = 'confidence_note';

	/** @var Template_Explanation_Builder_Interface */
	private Template_Explanation_Builder_Interface $template_explanation_builder;

	/** @var Form_Provider_Dependency_Validator|null */
	private ?Form_Provider_Dependency_Validator $form_provider_dependency_validator;

	public function __construct(
		Template_Explanation_Builder_Interface $template_explanation_builder,
		?Form_Provider_Dependency_Validator $form_provider_dependency_validator = null
	) {
		$this->template_explanation_builder       = $template_explanation_builder;
		$this->form_provider_dependency_validator = $form_provider_dependency_validator;
	}

	/**
	 * Builds recommendation payload for one new_page item: template summary, hierarchy context, reason, grouping, warnings.
	 *
	 * @param array<string, mixed> $item Plan item (item_id, item_type, payload, status).
	 * @return array<string, mixed> Keys: proposed_template_summary, hierarchy_context_summary, template_selection_reason, group_label, group_hierarchy_role, group_template_family, dependency_warnings, deprecation_aware, confidence_note.
	 */
	public function build_for_item( array $item ): array {
		$payload      = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$template_key = (string) ( $payload['template_key'] ?? '' );
		$template_key = \sanitize_key( $template_key );

		$proposed_template_summary = array();
		$template_selection_reason = '';
		$deprecation_aware         = false;
		$group_hierarchy_role      = '';
		$group_template_family     = '';

		if ( $template_key !== '' ) {
			$explanation               = $this->template_explanation_builder->build_explanation( $template_key, $payload );
			$proposed_template_summary = array(
				'template_key'            => (string) ( $explanation['template_key'] ?? $template_key ),
				'name'                    => (string) ( $explanation['name'] ?? '' ),
				'template_category_class' => (string) ( $explanation['template_category_class'] ?? '' ),
				'template_family'         => (string) ( $explanation['template_family'] ?? '' ),
				'cta_direction_summary'   => (string) ( $explanation['cta_direction_summary'] ?? '' ),
				'section_count'           => (int) ( $explanation['section_count'] ?? 0 ),
				'deprecation_status'      => (string) ( $explanation['deprecation_status'] ?? 'active' ),
				'replacement_keys'        => isset( $explanation['replacement_keys'] ) && is_array( $explanation['replacement_keys'] ) ? $explanation['replacement_keys'] : array(),
			);
			$deprecation_aware         = ( (string) ( $explanation['deprecation_status'] ?? '' ) ) === 'deprecated';
			$group_hierarchy_role      = (string) ( $explanation['template_category_class'] ?? $explanation['hierarchy_hint'] ?? '' );
			$group_template_family     = (string) ( $explanation['template_family'] ?? '' );
			$template_selection_reason = $this->derive_selection_reason( $payload, $explanation );
		}

		$hierarchy_context_summary = $this->build_hierarchy_context_summary( $payload );
		$dependency_warnings       = $this->collect_dependency_warnings( $payload );
		if ( $template_key !== '' && $this->form_provider_dependency_validator !== null ) {
			$validation          = $this->form_provider_dependency_validator->validate_for_template( $template_key );
			$dependency_warnings = array_merge( $dependency_warnings, $validation['errors'], $validation['warnings'] );
		}
		$confidence_note = $this->confidence_note( $payload );
		$group_label     = $this->format_group_label( $group_hierarchy_role, $group_template_family );

		return array(
			self::KEY_PROPOSED_TEMPLATE_SUMMARY => $proposed_template_summary,
			self::KEY_HIERARCHY_CONTEXT_SUMMARY => $hierarchy_context_summary,
			self::KEY_TEMPLATE_SELECTION_REASON => $template_selection_reason,
			self::KEY_GROUP_LABEL               => $group_label,
			self::KEY_GROUP_HIERARCHY_ROLE      => $group_hierarchy_role,
			self::KEY_GROUP_TEMPLATE_FAMILY     => $group_template_family,
			self::KEY_DEPENDENCY_WARNINGS       => $dependency_warnings,
			self::KEY_DEPRECATION_AWARE         => $deprecation_aware,
			self::KEY_CONFIDENCE_NOTE           => $confidence_note,
		);
	}

	/**
	 * Returns group label for display (e.g. "Top-level · Home"). Empty when no role/family.
	 *
	 * @param string $hierarchy_role
	 * @param string $template_family
	 * @return string
	 */
	private function format_group_label( string $hierarchy_role, string $template_family ): string {
		$parts = array();
		if ( $hierarchy_role !== '' ) {
			$parts[] = str_replace( '_', ' ', ucfirst( $hierarchy_role ) );
		}
		if ( $template_family !== '' ) {
			$parts[] = ucfirst( $template_family );
		}
		return implode( ' · ', $parts );
	}

	/**
	 * Derives a short template selection reason from payload purpose or explanation lines.
	 *
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $explanation
	 * @return string
	 */
	private function derive_selection_reason( array $payload, array $explanation ): string {
		$purpose = (string) ( $payload['purpose'] ?? '' );
		if ( $purpose !== '' ) {
			return $purpose;
		}
		$lines = isset( $explanation['explanation_lines'] ) && is_array( $explanation['explanation_lines'] ) ? $explanation['explanation_lines'] : array();
		$first = isset( $lines[0] ) ? (string) $lines[0] : '';
		if ( $first !== '' ) {
			return $first;
		}
		$name = (string) ( $explanation['name'] ?? '' );
		if ( $name !== '' ) {
			return $name;
		}
		return '';
	}

	/**
	 * Builds hierarchy context summary (parent, children, position, role).
	 *
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private function build_hierarchy_context_summary( array $payload ): array {
		$parent   = (string) ( $payload['intended_parent'] ?? $payload['parent_ref'] ?? '' );
		$children = $payload['intended_children'] ?? $payload['child_refs'] ?? array();
		$position = (string) ( $payload['hierarchy_position'] ?? '' );
		$role     = (string) ( $payload['hierarchy_role'] ?? $payload['page_type'] ?? '' );
		if ( ! is_array( $children ) ) {
			$children = array();
		}
		return array(
			'intended_parent'    => $parent,
			'intended_children'  => array_values( array_map( 'strval', $children ) ),
			'hierarchy_position' => $position,
			'hierarchy_role'     => $role,
		);
	}

	/**
	 * Collects dependency blocking messages from payload.
	 *
	 * @param array<string, mixed> $payload
	 * @return array<int, string>
	 */
	private function collect_dependency_warnings( array $payload ): array {
		$reasons = $payload['dependency_blocking_reasons'] ?? $payload['blocking_reasons'] ?? array();
		if ( ! is_array( $reasons ) ) {
			return array();
		}
		$out = array();
		foreach ( $reasons as $r ) {
			$out[] = is_string( $r ) ? $r : (string) \wp_json_encode( $r );
		}
		return $out;
	}

	/**
	 * Optional confidence note for display (e.g. "Medium confidence").
	 *
	 * @param array<string, mixed> $payload
	 * @return string
	 */
	private function confidence_note( array $payload ): string {
		$confidence = (string) ( $payload['confidence'] ?? '' );
		if ( $confidence === '' ) {
			return '';
		}
		return \__( 'Confidence:', 'aio-page-builder' ) . ' ' . $confidence;
	}
}
