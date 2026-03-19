<?php
/**
 * Builds form field definitions for global component override settings (Prompt 248).
 * Driven by component spec and repository; no arbitrary selectors or CSS.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Forms;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;

/**
 * Produces field definitions for the global component override form from component registry and current values.
 */
final class Global_Component_Override_Form_Builder {

	/** POST key for override values: aio_global_component_overrides[component_id][token_var_name]. */
	public const FORM_OVERRIDES_KEY = 'aio_global_component_overrides';

	/** Default max length when token registry does not provide one. */
	private const DEFAULT_MAX_LENGTH = 256;

	/** @var Component_Override_Registry */
	private Component_Override_Registry $component_registry;

	/** @var Global_Style_Settings_Repository */
	private Global_Style_Settings_Repository $repository;

	/** @var Style_Token_Registry|null For max_length from core spec (inherit_from_core_spec). */
	private ?Style_Token_Registry $token_registry;

	public function __construct(
		Component_Override_Registry $component_registry,
		Global_Style_Settings_Repository $repository,
		?Style_Token_Registry $token_registry = null
	) {
		$this->component_registry = $component_registry;
		$this->repository         = $repository;
		$this->token_registry     = $token_registry;
	}

	/**
	 * Returns field definitions for all approved component overrides.
	 * Each field has: component_id, token_var_name, name_attr, label, value, max_length.
	 *
	 * @return array<int, array{component_id: string, token_var_name: string, name_attr: string, label: string, value: string, max_length: int}>
	 */
	public function get_field_definitions(): array {
		if ( ! $this->component_registry->is_loaded() ) {
			return array();
		}
		$current = $this->repository->get_global_component_overrides();
		$out     = array();
		foreach ( $this->component_registry->get_component_ids() as $component_id ) {
			$allowed = $this->component_registry->get_allowed_token_overrides( $component_id );
			foreach ( $allowed as $token_var_name ) {
				$value = isset( $current[ $component_id ][ $token_var_name ] ) && is_string( $current[ $component_id ][ $token_var_name ] )
					? $current[ $component_id ][ $token_var_name ]
					: '';
				$out[] = array(
					'component_id'   => $component_id,
					'token_var_name' => $token_var_name,
					'name_attr'      => self::FORM_OVERRIDES_KEY . '[' . \esc_attr( $component_id ) . '][' . \esc_attr( $token_var_name ) . ']',
					'label'          => $this->format_label( $component_id, $token_var_name ),
					'value'          => $value,
					'max_length'     => $this->get_max_length_for_single_token_var( $token_var_name ),
				);
			}
		}
		return $out;
	}

	/**
	 * Groups field definitions by component_id for sectioned rendering.
	 *
	 * @return array<string, array<int, array{component_id: string, token_var_name: string, name_attr: string, label: string, value: string, max_length: int}>>
	 */
	public function get_fields_by_component(): array {
		$defs         = $this->get_field_definitions();
		$by_component = array();
		foreach ( $defs as $def ) {
			$c = $def['component_id'];
			if ( ! isset( $by_component[ $c ] ) ) {
				$by_component[ $c ] = array();
			}
			$by_component[ $c ][] = $def;
		}
		return $by_component;
	}

	/**
	 * Returns max length for a single token variable (inherit_from_core_spec).
	 *
	 * @param string $token_var_name
	 * @return int
	 */
	private function get_max_length_for_single_token_var( string $token_var_name ): int {
		if ( $this->token_registry === null || ! $this->token_registry->is_loaded() ) {
			return self::DEFAULT_MAX_LENGTH;
		}
		$group = $this->resolve_group_for_token_var( $token_var_name );
		if ( $group === '' ) {
			return self::DEFAULT_MAX_LENGTH;
		}
		$san = $this->token_registry->get_sanitization_for_group( $group );
		return isset( $san['max_length'] ) && is_numeric( $san['max_length'] ) ? (int) $san['max_length'] : self::DEFAULT_MAX_LENGTH;
	}

	/**
	 * Resolves token group from variable name (e.g. --aio-color-primary → color) for sanitization lookup.
	 *
	 * @param string $token_var_name
	 * @return string
	 */
	private function resolve_group_for_token_var( string $token_var_name ): string {
		if ( $this->token_registry === null || ! $this->token_registry->is_loaded() ) {
			return '';
		}
		foreach ( $this->token_registry->get_token_group_names() as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			foreach ( $this->token_registry->get_allowed_names_for_group( $group ) as $name ) {
				if ( $this->token_registry->get_token_variable_name( $group, $name ) === $token_var_name ) {
					return $group;
				}
			}
		}
		return '';
	}

	private function format_label( string $component_id, string $token_var_name ): string {
		$comp_label = \ucfirst( \str_replace( array( '-', '_' ), ' ', $component_id ) );
		$var_short  = \str_replace( '--aio-', '', $token_var_name );
		$var_short  = \str_replace( array( '-', '_' ), ' ', $var_short );
		return $comp_label . ' — ' . \ucwords( $var_short );
	}
}
