<?php
/**
 * Builds detail panel sections for Step 3 (navigation) menu_change items (spec §34.1–34.10).
 *
 * Renders current vs proposed comparison, navigation context, diff summary, rename/create/
 * item-assignment/location-assignment, and validation messaging. No raw AI artifacts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Navigation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;

/**
 * Produces detail_panel sections for one menu_change item.
 * Payload: menu_context, action, proposed_menu_name, items; optional: current_menu_name,
 * current_structure, proposed_structure, diff_summary, location_assignment, validation_messages.
 */
final class Navigation_Detail_Builder {

	/**
	 * Builds sections for the detail panel. Escapes output.
	 *
	 * @param array<string, mixed> $item Plan item (item_id, item_type, payload, status, ...).
	 * @return array<int, array<string, mixed>> Sections with heading, key, content or content_lines.
	 */
	public function build_sections( array $item ): array {
		$payload  = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$sections = array();

		$sections[] = $this->section_navigation_context( $payload );
		$sections[] = $this->section_named_menu_summary( $payload );
		$sections[] = $this->section_current_vs_proposed( $payload );
		$sections[] = $this->section_diff_summary( $payload );
		$sections[] = $this->section_menu_action( $payload );
		$sections[] = $this->section_menu_hierarchy( $payload );
		$sections[] = $this->section_item_assignment( $payload );
		$sections[] = $this->section_location_assignment( $payload );
		$sections[] = $this->section_validation( $payload );

		return array_filter(
			$sections,
			static function ( $s ) {
				return $s !== null;
			}
		);
	}

	private function section_navigation_context( array $payload ): array {
		$context = (string) ( $payload['menu_context'] ?? '' );
		$label   = $context !== '' ? $this->context_label( $context ) : '—';
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Navigation context', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'navigation_context',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => array( $label ),
		);
	}

	private function section_named_menu_summary( array $payload ): array {
		$name = (string) ( $payload['proposed_menu_name'] ?? '' );
		if ( $name === '' ) {
			$name = (string) ( $payload['menu_name'] ?? '' );
		}
		if ( $name === '' ) {
			return array(
				Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'WordPress menu name', 'aio-page-builder' ),
				Detail_Panel_Component::SECTION_KEY_KEY => 'menu_name_summary',
				Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => array( \__( 'Not specified in plan data.', 'aio-page-builder' ) ),
			);
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'WordPress menu name', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'menu_name_summary',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => array( \esc_html( $name ) ),
		);
	}

	private function section_current_vs_proposed( array $payload ): array {
		$current  = $payload['current_structure'] ?? $payload['current_menu_name'] ?? '';
		$proposed = $payload['proposed_structure'] ?? $payload['proposed_menu_name'] ?? $payload['menu_name'] ?? '';
		$lines    = array();
		$lines[]  = \__( 'Current:', 'aio-page-builder' ) . ' ' . ( is_string( $current ) ? \esc_html( $current ) : \esc_html( (string) \wp_json_encode( $current ) ) );
		$lines[]  = \__( 'Proposed:', 'aio-page-builder' ) . ' ' . ( is_string( $proposed ) ? \esc_html( $proposed ) : \esc_html( (string) \wp_json_encode( $proposed ) ) );
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Current vs proposed navigation', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'current_vs_proposed',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_diff_summary( array $payload ): array {
		$diff = $payload['diff_summary'] ?? $payload['differences'] ?? array();
		if ( ! is_array( $diff ) ) {
			$diff = array( $diff );
		}
		$lines = array();
		foreach ( $diff as $d ) {
			$lines[] = is_string( $d ) ? \esc_html( $d ) : \esc_html( (string) \wp_json_encode( $d ) );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Detected differences', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'diff_summary',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_menu_action( array $payload ): array {
		$action   = (string) ( $payload['action'] ?? '' );
		$current  = (string) ( $payload['current_menu_name'] ?? '' );
		$proposed = (string) ( $payload['proposed_menu_name'] ?? '' );
		$lines    = array();
		$lines[]  = \__( 'Action:', 'aio-page-builder' ) . ' ' . \esc_html( $action !== '' ? $action : '—' );
		if ( $current !== '' ) {
			$lines[] = \__( 'Current menu name:', 'aio-page-builder' ) . ' ' . \esc_html( $current );
		}
		$lines[] = \__( 'Proposed menu name:', 'aio-page-builder' ) . ' ' . \esc_html( $proposed !== '' ? $proposed : '—' );
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Menu rename / create / location', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'menu_action',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_menu_hierarchy( array $payload ): ?array {
		$items = $payload['items'] ?? array();
		if ( ! is_array( $items ) || $items === array() ) {
			return null;
		}
		$lines = $this->flatten_menu_tree_lines( $items, 0 );
		if ( $lines === array() ) {
			return null;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Proposed menu hierarchy (preview)', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'menu_hierarchy_preview',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	/**
	 * @param array<int, mixed> $nodes
	 * @return array<int, string> Escaped HTML lines.
	 */
	private function flatten_menu_tree_lines( array $nodes, int $depth ): array {
		$lines = array();
		foreach ( $nodes as $node ) {
			if ( is_string( $node ) ) {
				$lines[] = \esc_html( str_repeat( '  ', $depth ) . '- ' . $node );
				continue;
			}
			if ( ! is_array( $node ) ) {
				continue;
			}
			$label = (string) ( $node['label'] ?? $node['title'] ?? '' );
			if ( $label === '' ) {
				$label = (string) ( $node['page_ref'] ?? $node['slug'] ?? '' );
			}
			if ( $label === '' ) {
				$label = (string) \wp_json_encode( $node );
			}
			$lines[] = \esc_html( str_repeat( '  ', $depth ) . '- ' . $label );
			$nested  = $node['children'] ?? $node['subitems'] ?? $node['child_items'] ?? null;
			if ( is_array( $nested ) && $nested !== array() ) {
				$lines = array_merge( $lines, $this->flatten_menu_tree_lines( $nested, $depth + 1 ) );
			}
		}
		return $lines;
	}

	private function section_item_assignment( array $payload ): array {
		$items = $payload['items'] ?? array();
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$lines = array();
		foreach ( $items as $i => $entry ) {
			if ( is_string( $entry ) ) {
				$lines[] = \esc_html( $entry );
			} elseif ( is_array( $entry ) ) {
				$label = (string) ( $entry['label'] ?? $entry['title'] ?? '' );
				$url   = (string) ( $entry['url'] ?? $entry['target'] ?? '' );
				$order = isset( $entry['order'] ) ? (string) $entry['order'] : '';
				$line  = $label !== '' ? $label : (string) ( $entry['page_ref'] ?? '' );
				if ( $url !== '' ) {
					$line .= ' → ' . $url;
				}
				if ( $order !== '' ) {
					$line .= ' (order: ' . $order . ')';
				}
				$lines[] = \esc_html( $line !== '' ? $line : \wp_json_encode( $entry ) );
			} else {
				$lines[] = \esc_html( (string) $entry );
			}
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Menu item assignment', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'item_assignment',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_location_assignment( array $payload ): array {
		$loc   = $payload['location_assignment'] ?? $payload['theme_location'] ?? '';
		$lines = array();
		if ( is_string( $loc ) && $loc !== '' ) {
			$lines[] = \esc_html( $loc );
		} elseif ( is_array( $loc ) && ! empty( $loc ) ) {
			$lines[] = \esc_html( implode( ', ', array_map( 'strval', $loc ) ) );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Menu location assignment', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'location_assignment',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_validation( array $payload ): array {
		$messages = $payload['validation_messages'] ?? $payload['validation_errors'] ?? array();
		if ( ! is_array( $messages ) ) {
			$messages = array( $messages );
		}
		$lines = array();
		foreach ( $messages as $m ) {
			$lines[] = is_string( $m ) ? \esc_html( $m ) : \esc_html( (string) \wp_json_encode( $m ) );
		}
		if ( $lines === array() ) {
			$lines[] = \__( 'No validation issues reported.', 'aio-page-builder' );
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Navigation validation', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'validation',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function context_label( string $context ): string {
		$map = array(
			'header'     => \__( 'Header', 'aio-page-builder' ),
			'footer'     => \__( 'Footer', 'aio-page-builder' ),
			'mobile'     => \__( 'Mobile', 'aio-page-builder' ),
			'off_canvas' => \__( 'Off-canvas', 'aio-page-builder' ),
			'sidebar'    => \__( 'Sidebar', 'aio-page-builder' ),
		);
		return $map[ $context ] ?? \esc_html( $context );
	}
}
