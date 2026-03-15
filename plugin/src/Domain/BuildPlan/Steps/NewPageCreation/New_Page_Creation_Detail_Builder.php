<?php
/**
 * Builds detail panel sections for Step 2 new-page items (spec §33.3–33.10).
 *
 * Renders proposed metadata, parent/child hierarchy, dependency validation,
 * post-build status placeholder, and retry/recovery messaging.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\BuildPlan\Industry_Build_Plan_Explanation_View_Model;
use AIOPageBuilder\Domain\BuildPlan\Recommendations\Build_Plan_Template_Explanation_Builder;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;

/**
 * Produces detail_panel sections for one new_page item.
 * Payload: proposed_page_title, proposed_slug, purpose, template_key, menu_eligible, section_guidance, confidence;
 * optional: page_type, parent_ref, hierarchy_position, intended_parent, intended_children, dependency_blocking_reasons, post_build_status, retry_message.
 * When Build_Plan_Template_Explanation_Builder is provided, adds a "Template rationale" section (Prompt 190).
 */
final class New_Page_Creation_Detail_Builder {

	/** @var Build_Plan_Template_Explanation_Builder|null */
	private ?Build_Plan_Template_Explanation_Builder $template_explanation_builder;

	public function __construct( ?Build_Plan_Template_Explanation_Builder $template_explanation_builder = null ) {
		$this->template_explanation_builder = $template_explanation_builder;
	}

	/**
	 * Builds sections for the detail panel. Escapes output; no raw AI artifacts.
	 *
	 * @param array<string, mixed> $item Plan item (item_id, item_type, payload, status, ...).
	 * @return array<int, array<string, mixed>> Sections with heading, key, content or content_lines.
	 */
	public function build_sections( array $item ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$status  = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
		$sections = array();

		$sections[] = $this->section_metadata( $payload );
		$template_rationale = $this->section_template_rationale( $payload );
		if ( $template_rationale !== null ) {
			$sections[] = $template_rationale;
		}
		$industry_section = $this->section_industry_explanation( $payload );
		if ( $industry_section !== null ) {
			$sections[] = $industry_section;
		}
		$sections[] = $this->section_parent_child_hierarchy( $payload );
		$sections[] = $this->section_dependency_validation( $payload, $item );
		$sections[] = $this->section_post_build_status( $status, $payload );
		$sections[] = $this->section_retry_recovery( $payload, $status );

		return array_filter( $sections, static function ( $s ) {
			return $s !== null && is_array( $s );
		} );
	}

	/**
	 * Template rationale section from Build_Plan_Template_Explanation_Builder when template_key is set (Prompt 190).
	 *
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|null Section or null when no template_key or no builder.
	 */
	private function section_template_rationale( array $payload ): ?array {
		$template_key = (string) ( $payload['template_key'] ?? '' );
		if ( $template_key === '' || $this->template_explanation_builder === null ) {
			return null;
		}
		$explanation = $this->template_explanation_builder->build_explanation( $template_key, $payload );
		$lines = isset( $explanation['explanation_lines'] ) && is_array( $explanation['explanation_lines'] )
			? $explanation['explanation_lines']
			: array();
		$escaped = array_map( function ( $line ) {
			return \esc_html( (string) $line );
		}, $lines );
		if ( $escaped === array() ) {
			$escaped[] = \esc_html( $explanation['template_key'] ?? $template_key );
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING       => \__( 'Template rationale', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY           => 'template_rationale',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $escaped,
		);
	}

	/**
	 * Industry context section from item payload (Prompt 365). Renders rationale, fit, and warnings.
	 *
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|null Section or null when no industry data.
	 */
	private function section_industry_explanation( array $payload ): ?array {
		$view_model = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload );
		if ( empty( $view_model['has_industry_data'] ) ) {
			return null;
		}
		\ob_start();
		$view_model = $view_model;
		require \dirname( __DIR__, 4 ) . '/Admin/Views/build-plan/industry-plan-explanations.php';
		$content = (string) \ob_get_clean();
		if ( $content === '' ) {
			return null;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Industry context', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'industry_explanation',
			Detail_Panel_Component::SECTION_KEY_CONTENT => $content,
		);
	}

	private function section_metadata( array $payload ): array {
		$title   = (string) ( $payload['proposed_page_title'] ?? '' );
		$slug    = (string) ( $payload['proposed_slug'] ?? '' );
		$purpose = (string) ( $payload['purpose'] ?? '' );
		$template = (string) ( $payload['template_key'] ?? '' );
		$page_type = (string) ( $payload['page_type'] ?? '' );
		$confidence = (string) ( $payload['confidence'] ?? '' );
		$lines = array();
		if ( $title !== '' ) {
			$lines[] = \__( 'Proposed title:', 'aio-page-builder' ) . ' ' . \esc_html( $title );
		}
		if ( $slug !== '' ) {
			$lines[] = \__( 'Proposed slug:', 'aio-page-builder' ) . ' ' . \esc_html( $slug );
		}
		if ( $purpose !== '' ) {
			$lines[] = \__( 'Purpose:', 'aio-page-builder' ) . ' ' . \esc_html( $purpose );
		}
		if ( $template !== '' ) {
			$lines[] = \__( 'Target template / composition:', 'aio-page-builder' ) . ' ' . \esc_html( $template );
		}
		if ( $page_type !== '' ) {
			$lines[] = \__( 'Page type:', 'aio-page-builder' ) . ' ' . \esc_html( $page_type );
		}
		if ( $confidence !== '' ) {
			$lines[] = \__( 'Confidence:', 'aio-page-builder' ) . ' ' . \esc_html( $confidence );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Page metadata', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'metadata',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_parent_child_hierarchy( array $payload ): array {
		$parent   = (string) ( $payload['intended_parent'] ?? $payload['parent_ref'] ?? '' );
		$children = $payload['intended_children'] ?? $payload['child_refs'] ?? array();
		$position = (string) ( $payload['hierarchy_position'] ?? '' );
		$role     = (string) ( $payload['hierarchy_role'] ?? $payload['page_type'] ?? '' );
		$lines = array();
		if ( $parent !== '' ) {
			$lines[] = \__( 'Intended parent:', 'aio-page-builder' ) . ' ' . \esc_html( $parent );
		}
		if ( is_array( $children ) && ! empty( $children ) ) {
			$lines[] = \__( 'Intended child pages:', 'aio-page-builder' ) . ' ' . \esc_html( implode( ', ', array_map( 'strval', $children ) ) );
		}
		if ( $position !== '' ) {
			$lines[] = \__( 'Hierarchy position:', 'aio-page-builder' ) . ' ' . \esc_html( $position );
		}
		if ( $role !== '' ) {
			$lines[] = \__( 'Hub / branch / leaf:', 'aio-page-builder' ) . ' ' . \esc_html( $role );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Parent / child hierarchy', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'parent_child',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_dependency_validation( array $payload, array $item ): array {
		$reasons = $payload['dependency_blocking_reasons'] ?? $payload['blocking_reasons'] ?? array();
		if ( ! is_array( $reasons ) ) {
			$reasons = array();
		}
		$lines = array();
		foreach ( $reasons as $r ) {
			$lines[] = is_string( $r ) ? $r : (string) \wp_json_encode( $r );
		}
		if ( $lines === array() ) {
			$lines[] = \__( 'No blocking dependencies reported.', 'aio-page-builder' );
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Dependency validation', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'dependency_validation',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_post_build_status( string $status, array $payload ): array {
		$post_status = (string) ( $payload['post_build_status'] ?? '' );
		$lines = array( \__( 'Current Build Plan state:', 'aio-page-builder' ) . ' ' . $status );
		if ( $post_status !== '' ) {
			$lines[] = \__( 'Post-build placeholder:', 'aio-page-builder' ) . ' ' . $post_status;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Post-build status', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'post_build_status',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_retry_recovery( array $payload, string $status ): array {
		$message = (string) ( $payload['retry_message'] ?? $payload['recovery_message'] ?? '' );
		if ( $message === '' && $status === 'failed' ) {
			$message = \__( 'This item can be retried after resolving dependency or execution issues.', 'aio-page-builder' );
		}
		if ( $message === '' ) {
			$message = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Retry and recovery', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'retry_recovery',
			Detail_Panel_Component::SECTION_KEY_CONTENT => '<p>' . \esc_html( $message ) . '</p>',
		);
	}
}
