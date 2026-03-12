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

/**
 * Produces detail_panel sections array for one existing_page_change item.
 * Payload keys: current_page_title, current_page_url, action, reason, risk_level, confidence;
 * optional: target_template, target_title, target_slug, hierarchy_notes, section_notes, warnings, assumptions, dependencies.
 */
final class Existing_Page_Update_Detail_Builder {

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
