<?php
/**
 * Builds form field definitions for global style token settings (Prompt 247).
 * Driven by token spec and repository; no raw CSS or component overrides.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Forms;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;

/**
 * Produces field definitions for the global token settings form from registry and current values.
 */
final class Global_Style_Token_Form_Builder {

	/** POST key for token values: aio_global_tokens[group][name]. */
	public const FORM_TOKENS_KEY = 'aio_global_tokens';

	/** @var Style_Token_Registry */
	private Style_Token_Registry $token_registry;

	/** @var Global_Style_Settings_Repository */
	private Global_Style_Settings_Repository $repository;

	public function __construct(
		Style_Token_Registry $token_registry,
		Global_Style_Settings_Repository $repository
	) {
		$this->token_registry = $token_registry;
		$this->repository     = $repository;
	}

	/**
	 * Returns field definitions for all approved global token groups (excludes component).
	 * Each field has: group, name, name_attr, label, value, value_type, max_length.
	 *
	 * @return array<int, array{group: string, name: string, name_attr: string, label: string, value: string, value_type: string, max_length: int}>
	 */
	public function get_field_definitions(): array {
		if ( ! $this->token_registry->is_loaded() ) {
			return array();
		}
		$current = $this->repository->get_global_tokens();
		$groups  = $this->token_registry->get_token_group_names();
		$out     = array();
		foreach ( $groups as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			$names      = $this->token_registry->get_allowed_names_for_group( $group );
			$san        = $this->token_registry->get_sanitization_for_group( $group );
			$max        = isset( $san['max_length'] ) && is_numeric( $san['max_length'] ) ? (int) $san['max_length'] : 512;
			$value_type = isset( $san['value_type'] ) && is_string( $san['value_type'] ) ? $san['value_type'] : 'text';
			foreach ( $names as $name ) {
				$value = isset( $current[ $group ][ $name ] ) && is_string( $current[ $group ][ $name ] )
					? $current[ $group ][ $name ]
					: '';
				$out[] = array(
					'group'      => $group,
					'name'       => $name,
					'name_attr'  => self::FORM_TOKENS_KEY . '[' . \esc_attr( $group ) . '][' . \esc_attr( $name ) . ']',
					'label'      => $this->format_label( $group, $name ),
					'value'      => $value,
					'value_type' => $value_type,
					'max_length' => $max,
				);
			}
		}
		return $out;
	}

	/**
	 * Groups field definitions by token group for sectioned rendering.
	 *
	 * @return array<string, array<int, array{group: string, name: string, name_attr: string, label: string, value: string, value_type: string, max_length: int}>>
	 */
	public function get_fields_by_group(): array {
		$defs   = $this->get_field_definitions();
		$by_grp = array();
		foreach ( $defs as $def ) {
			$g = $def['group'];
			if ( ! isset( $by_grp[ $g ] ) ) {
				$by_grp[ $g ] = array();
			}
			$by_grp[ $g ][] = $def;
		}
		return $by_grp;
	}

	private function format_label( string $group, string $name ): string {
		$group_label = \ucfirst( $group );
		$name_label  = \str_replace( array( '-', '_' ), ' ', $name );
		$name_label  = \ucwords( $name_label );
		return $group_label . ' — ' . $name_label;
	}
}
