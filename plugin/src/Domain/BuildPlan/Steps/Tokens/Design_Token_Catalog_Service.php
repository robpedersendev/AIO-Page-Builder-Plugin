<?php
/**
 * Searchable catalog from core style spec: group → purpose → token name (Prompt 244).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Style_Token_Registry;

/**
 * Builds flat, sorted entries for admin UI and JSON payloads.
 */
final class Design_Token_Catalog_Service {

	/** @var Style_Token_Registry */
	private $registry;

	public function __construct( Style_Token_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Stable sort: purpose label, group, name.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_sorted_entries(): array {
		if ( ! $this->registry->is_loaded() ) {
			return array();
		}
		$entries = array();
		foreach ( $this->registry->get_token_group_names() as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			$purpose      = $this->purpose_label_for_group( $group );
			$purpose_sort = $this->purpose_sort_key( $group );
			$sani         = $this->registry->get_sanitization_for_group( $group );
			$value_type   = isset( $sani['value_type'] ) && is_string( $sani['value_type'] ) ? $sani['value_type'] : '';
			foreach ( $this->registry->get_allowed_names_for_group( $group ) as $name ) {
				$css_var   = $this->registry->get_token_variable_name( $group, $name );
				$entries[] = array(
					'id'           => $group . '|' . $name,
					'group'        => $group,
					'name'         => $name,
					'purpose'      => $purpose,
					'purpose_sort' => $purpose_sort,
					'label'        => $group . ' › ' . $name,
					'value_type'   => $value_type,
					'css_var'      => $css_var,
				);
			}
		}
		usort(
			$entries,
			static function ( array $a, array $b ): int {
				$c = strcmp( (string) $a['purpose_sort'], (string) $b['purpose_sort'] );
				if ( $c !== 0 ) {
					return $c;
				}
				$c = strcmp( (string) $a['group'], (string) $b['group'] );
				if ( $c !== 0 ) {
					return $c;
				}
				return strcmp( (string) $a['name'], (string) $b['name'] );
			}
		);
		return $entries;
	}

	/**
	 * JSON-safe list (no purpose_sort) for wp_localize_script.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_entries_for_json(): array {
		$out = array();
		foreach ( $this->get_sorted_entries() as $row ) {
			$out[] = array(
				'id'         => (string) ( $row['id'] ?? '' ),
				'group'      => (string) ( $row['group'] ?? '' ),
				'name'       => (string) ( $row['name'] ?? '' ),
				'purpose'    => (string) ( $row['purpose'] ?? '' ),
				'label'      => (string) ( $row['label'] ?? '' ),
				'value_type' => (string) ( $row['value_type'] ?? '' ),
				'css_var'    => (string) ( $row['css_var'] ?? '' ),
			);
		}
		return $out;
	}

	public function is_allowed_pair( string $group, string $name ): bool {
		if ( $group === '' || $name === '' || ! $this->registry->is_loaded() ) {
			return false;
		}
		return in_array( $name, $this->registry->get_allowed_names_for_group( $group ), true );
	}

	private function purpose_sort_key( string $group ): string {
		$map = array(
			'color'      => '10_color',
			'typography' => '20_typography',
			'spacing'    => '30_spacing',
			'radius'     => '40_radius',
			'shadow'     => '50_shadow',
		);
		return $map[ $group ] ?? '90_' . $group;
	}

	private function purpose_label_for_group( string $group ): string {
		switch ( $group ) {
			case 'color':
				return \__( 'Colors & surfaces', 'aio-page-builder' );
			case 'typography':
				return \__( 'Typography & fonts', 'aio-page-builder' );
			case 'spacing':
				return \__( 'Spacing & layout', 'aio-page-builder' );
			case 'radius':
				return \__( 'Corners & radii', 'aio-page-builder' );
			case 'shadow':
				return \__( 'Shadows & elevation', 'aio-page-builder' );
			default:
				return $group;
		}
	}
}
