<?php
/**
 * Builds form field definitions for per-entity style payloads (Prompt 253).
 * Token overrides and component overrides for one entity; spec-driven; no raw CSS.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Forms;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;

/**
 * Produces field definitions for per-entity token_overrides and component_overrides from registries and current payload.
 */
final class Entity_Style_Form_Builder {

	/** POST key root: aio_entity_style[token_overrides][group][name], aio_entity_style[component_overrides][component_id][var]. */
	public const FORM_KEY = 'aio_entity_style';

	/** Default max length when spec does not define one. */
	private const DEFAULT_MAX_LENGTH = 256;

	/** @var Style_Token_Registry */
	private Style_Token_Registry $token_registry;

	/** @var Component_Override_Registry */
	private Component_Override_Registry $component_registry;

	/** @var Entity_Style_Payload_Repository */
	private Entity_Style_Payload_Repository $payload_repository;

	public function __construct(
		Style_Token_Registry $token_registry,
		Component_Override_Registry $component_registry,
		Entity_Style_Payload_Repository $payload_repository
	) {
		$this->token_registry     = $token_registry;
		$this->component_registry = $component_registry;
		$this->payload_repository = $payload_repository;
	}

	/**
	 * Returns token override field definitions for the entity. Only allowed groups/names; values from payload.
	 *
	 * @param string $entity_type One of Entity_Style_Payload_Schema::ENTITY_TYPES.
	 * @param string $entity_key  Entity key (e.g. section_key, template_key).
	 * @return array<int, array{group: string, name: string, name_attr: string, label: string, value: string, value_type: string, max_length: int}>
	 */
	public function get_token_field_definitions( string $entity_type, string $entity_key ): array {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) || ! $this->token_registry->is_loaded() ) {
			return array();
		}
		$payload = $this->payload_repository->get_payload( $entity_type, $entity_key );
		$current = $payload[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ];
		$groups  = $this->token_registry->get_token_group_names();
		$out     = array();
		foreach ( $groups as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			$names      = $this->token_registry->get_allowed_names_for_group( $group );
			$san        = $this->token_registry->get_sanitization_for_group( $group );
			$max        = isset( $san['max_length'] ) ? (int) $san['max_length'] : self::DEFAULT_MAX_LENGTH;
			$value_type = isset( $san['value_type'] ) ? $san['value_type'] : 'text';
			foreach ( $names as $name ) {
				$value = isset( $current[ $group ][ $name ] ) && is_string( $current[ $group ][ $name ] )
					? $current[ $group ][ $name ]
					: '';
				$out[] = array(
					'group'      => $group,
					'name'       => $name,
					'name_attr'  => self::FORM_KEY . '[token_overrides][' . \esc_attr( $group ) . '][' . \esc_attr( $name ) . ']',
					'label'      => $this->format_token_label( $group, $name ),
					'value'      => $value,
					'value_type' => $value_type,
					'max_length' => $max,
				);
			}
		}
		return $out;
	}

	/**
	 * Returns component override field definitions for the entity.
	 *
	 * @param string $entity_type Entity type (e.g. section, page).
	 * @param string $entity_key  Entity identifier (internal key).
	 * @return array<int, array{component_id: string, token_var_name: string, name_attr: string, label: string, value: string, max_length: int}>
	 */
	public function get_component_field_definitions( string $entity_type, string $entity_key ): array {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) || ! $this->component_registry->is_loaded() ) {
			return array();
		}
		$payload       = $this->payload_repository->get_payload( $entity_type, $entity_key );
		$current       = $payload[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ];
		$out           = array();
		$component_ids = $this->component_registry->get_component_ids();
		foreach ( $component_ids as $component_id ) {
			$allowed = $this->component_registry->get_allowed_token_overrides( $component_id );
			foreach ( $allowed as $token_var_name ) {
				$value = isset( $current[ $component_id ][ $token_var_name ] ) && is_string( $current[ $component_id ][ $token_var_name ] )
					? $current[ $component_id ][ $token_var_name ]
					: '';
				$out[] = array(
					'component_id'   => $component_id,
					'token_var_name' => $token_var_name,
					'name_attr'      => self::FORM_KEY . '[component_overrides][' . \esc_attr( $component_id ) . '][' . \esc_attr( $token_var_name ) . ']',
					'label'          => $this->format_component_label( $component_id, $token_var_name ),
					'value'          => $value,
					'max_length'     => self::DEFAULT_MAX_LENGTH,
				);
			}
		}
		return $out;
	}

	/**
	 * Token fields grouped by token group for sectioned rendering.
	 *
	 * @param string $entity_type Entity type (e.g. section, page).
	 * @param string $entity_key  Entity identifier (internal key).
	 * @return array<string, array<int, array{group: string, name: string, name_attr: string, label: string, value: string, value_type: string, max_length: int}>>
	 */
	public function get_token_fields_by_group( string $entity_type, string $entity_key ): array {
		$defs   = $this->get_token_field_definitions( $entity_type, $entity_key );
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

	/**
	 * Component override fields grouped by component_id.
	 *
	 * @param string $entity_type Entity type (e.g. section, page).
	 * @param string $entity_key  Entity identifier (internal key).
	 * @return array<string, array<int, array{component_id: string, token_var_name: string, name_attr: string, label: string, value: string, max_length: int}>>
	 */
	public function get_component_fields_by_component( string $entity_type, string $entity_key ): array {
		$defs         = $this->get_component_field_definitions( $entity_type, $entity_key );
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

	private function format_token_label( string $group, string $name ): string {
		$group_label = \ucfirst( $group );
		$name_label  = \str_replace( array( '-', '_' ), ' ', $name );
		$name_label  = \ucwords( $name_label );
		return $group_label . ' — ' . $name_label;
	}

	private function format_component_label( string $component_id, string $token_var_name ): string {
		$comp_label = \ucfirst( \str_replace( array( '-', '_' ), ' ', $component_id ) );
		$var_short  = \str_replace( '--aio-', '', $token_var_name );
		$var_short  = \str_replace( array( '-', '_' ), ' ', $var_short );
		return $comp_label . ' — ' . \ucwords( $var_short );
	}
}
