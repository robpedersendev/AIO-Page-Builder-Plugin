<?php
/**
 * Builds detail panel sections for Step 1 existing-page update items (spec §32.4).
 *
 * Renders current page identity, suggested action, rationale, proposed outcome,
 * target title/slug, hierarchy implications, section notes, warnings/assumptions/dependencies.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Existing_Page_Template_Change_Builder;

/**
 * Produces detail_panel sections array for one existing_page_change item.
 * Payload keys: current_page_title, current_page_url, action, reason, risk_level, confidence;
 * optional: target_template, target_title, target_slug, hierarchy_notes, section_notes, warnings, assumptions, dependencies.
 * When Existing_Page_Template_Change_Builder is provided, adds "Proposed template and change type" section (Prompt 193).
 */
final class Existing_Page_Update_Detail_Builder {

	/** @var Existing_Page_Template_Change_Builder|null */
	private ?Existing_Page_Template_Change_Builder $template_change_builder;

	public function __construct( ?Existing_Page_Template_Change_Builder $template_change_builder = null ) {
		$this->template_change_builder = $template_change_builder;
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
		$sections = array();

		$sections[] = $this->section_page_identity( $payload );
		$sections[] = $this->section_suggested_action( $payload );
		$template_change_section = $this->section_proposed_template_and_change_type( $item );
		if ( $template_change_section !== null ) {
			$sections[] = $template_change_section;
		}
		$sections[] = $this->section_rationale( $payload );
		$sections[] = $this->section_proposed_outcome( $payload );
		$sections[] = $this->section_target_title_slug( $payload );
		$sections[] = $this->section_hierarchy( $payload );
		$sections[] = $this->section_content_notes( $payload );
		$sections[] = $this->section_warnings_assumptions_dependencies( $payload );

		return array_filter( $sections, static function ( $s ) {
			return $s !== null && is_array( $s );
		} );
	}

	private function section_page_identity( array $payload ): array {
		$title = (string) ( $payload['current_page_title'] ?? '' );
		$url   = (string) ( $payload['current_page_url'] ?? '' );
		$lines = array();
		if ( $title !== '' ) {
			$lines[] = \__( 'Title:', 'aio-page-builder' ) . ' ' . $title;
		}
		if ( $url !== '' ) {
			$lines[] = \__( 'URL:', 'aio-page-builder' ) . ' ' . $url;
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Current page identity', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'page_identity',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_suggested_action( array $payload ): array {
		$action = (string) ( $payload['action'] ?? '' );
		$label  = $action !== '' ? $action : '—';
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Suggested action', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'suggested_action',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => array( $label ),
		);
	}

	/**
	 * Proposed template and change type section (Prompt 193): replacement vs update, template family, CTA posture.
	 *
	 * @param array<string, mixed> $item Plan item.
	 * @return array<string, mixed>|null Section or null when no template change builder or no template.
	 */
	private function section_proposed_template_and_change_type( array $item ): ?array {
		if ( $this->template_change_builder === null ) {
			return null;
		}
		$change = $this->template_change_builder->build_for_item( $item );
		$template_summary = $change[ Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY ] ?? array();
		$reason_summary   = $change[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ] ?? array();
		$lines = array();
		$action_label = (string) ( $reason_summary['action_label'] ?? '' );
		if ( $action_label !== '' ) {
			$lines[] = \__( 'Change type:', 'aio-page-builder' ) . ' ' . \esc_html( $action_label );
		}
		if ( is_array( $template_summary ) && ! empty( $template_summary ) ) {
			$name = (string) ( $template_summary['name'] ?? '' );
			if ( $name !== '' ) {
				$lines[] = \__( 'Proposed template:', 'aio-page-builder' ) . ' ' . \esc_html( $name );
			}
			$family = (string) ( $template_summary['template_family'] ?? '' );
			if ( $family !== '' ) {
				$lines[] = \__( 'Template family:', 'aio-page-builder' ) . ' ' . \esc_html( $family );
			}
			$cta = (string) ( $template_summary['cta_direction_summary'] ?? '' );
			if ( $cta !== '' ) {
				$lines[] = \__( 'CTA direction:', 'aio-page-builder' ) . ' ' . \esc_html( $cta );
			}
			$section_count = (int) ( $template_summary['section_count'] ?? 0 );
			if ( $section_count > 0 ) {
				$lines[] = \sprintf( \__( 'Sections: %d', 'aio-page-builder' ), $section_count );
			}
		}
		$reason_short = (string) ( $reason_summary['reason_short'] ?? '' );
		if ( $reason_short !== '' ) {
			$lines[] = \__( 'Reason:', 'aio-page-builder' ) . ' ' . \esc_html( $reason_short );
		}
		if ( $lines === array() ) {
			return null;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING       => \__( 'Proposed template and change type', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY           => 'proposed_template_and_change_type',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_rationale( array $payload ): array {
		$reason = (string) ( $payload['reason'] ?? '' );
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Rationale for change', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'rationale',
			Detail_Panel_Component::SECTION_KEY_CONTENT  => $reason !== '' ? '<p>' . \esc_html( $reason ) . '</p>' : '<p>—</p>',
		);
	}

	private function section_proposed_outcome( array $payload ): array {
		$template = (string) ( $payload['target_template'] ?? $payload['template_key'] ?? '' );
		$out      = array();
		if ( $template !== '' ) {
			$out[] = \__( 'Target template:', 'aio-page-builder' ) . ' ' . \esc_html( $template );
		}
		if ( $out === array() ) {
			$out[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Proposed replacement or rebuild outcome', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'proposed_outcome',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $out,
		);
	}

	private function section_target_title_slug( array $payload ): array {
		$title = (string) ( $payload['target_title'] ?? '' );
		$slug  = (string) ( $payload['target_slug'] ?? $payload['proposed_slug'] ?? '' );
		$lines = array();
		if ( $title !== '' ) {
			$lines[] = \__( 'Target title:', 'aio-page-builder' ) . ' ' . $title;
		}
		if ( $slug !== '' ) {
			$lines[] = \__( 'Target slug:', 'aio-page-builder' ) . ' ' . $slug;
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Target title and slug', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'target_title_slug',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_hierarchy( array $payload ): array {
		$notes = (string) ( $payload['hierarchy_notes'] ?? $payload['hierarchy_implications'] ?? '' );
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Hierarchy implications', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'hierarchy',
			Detail_Panel_Component::SECTION_KEY_CONTENT => $notes !== '' ? '<p>' . \esc_html( $notes ) . '</p>' : '<p>—</p>',
		);
	}

	private function section_content_notes( array $payload ): array {
		$notes = (string) ( $payload['section_notes'] ?? $payload['section_guidance'] ?? '' );
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Section-level content instructions', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'content_notes',
			Detail_Panel_Component::SECTION_KEY_CONTENT => $notes !== '' ? '<p>' . \esc_html( $notes ) . '</p>' : '<p>—</p>',
		);
	}

	private function section_warnings_assumptions_dependencies( array $payload ): array {
		$warnings = $payload['warnings'] ?? array();
		$assumptions = $payload['assumptions'] ?? array();
		$deps    = $payload['dependencies'] ?? $payload['depends_on_item_ids'] ?? array();
		if ( ! is_array( $warnings ) ) {
			$warnings = array();
		}
		if ( ! is_array( $assumptions ) ) {
			$assumptions = array();
		}
		if ( ! is_array( $deps ) ) {
			$deps = array();
		}
		$risk = (string) ( $payload['risk_level'] ?? '' );
		$lines = array();
		if ( $risk !== '' ) {
			$lines[] = \__( 'Risk level:', 'aio-page-builder' ) . ' ' . \esc_html( $risk );
		}
		foreach ( $warnings as $w ) {
			$lines[] = \__( 'Warning:', 'aio-page-builder' ) . ' ' . ( is_string( $w ) ? $w : \wp_json_encode( $w ) );
		}
		foreach ( $assumptions as $a ) {
			$lines[] = \__( 'Assumption:', 'aio-page-builder' ) . ' ' . ( is_string( $a ) ? $a : \wp_json_encode( $a ) );
		}
		foreach ( $deps as $d ) {
			$lines[] = \__( 'Dependency:', 'aio-page-builder' ) . ' ' . ( is_string( $d ) ? $d : \wp_json_encode( $d ) );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Warnings, assumptions, and dependencies', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'warnings_assumptions_dependencies',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}
}
