<?php
/**
 * Read-only component override registry backed by components style spec (Prompt 244).
 * Exposes component ids, element roles, selector patterns, and allowed token overrides.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Component override rules from pb-style-components-spec.json.
 */
final class Component_Override_Registry {

	/** @var array<string, mixed>|null Loaded components spec or null if failed. */
	private ?array $components_spec = null;

	/** @var string Spec version when loaded. */
	private string $spec_version = '';

	/** @var array<string, array> Indexed by component id for lookup. */
	private array $by_id = array();

	public function __construct( Style_Spec_Loader $loader ) {
		$spec = $loader->load_components_spec();
		if ( is_array( $spec ) && isset( $spec['components'] ) && is_array( $spec['components'] ) ) {
			$this->components_spec = $spec;
			$this->spec_version    = Style_Spec_Loader::get_spec_version( $spec );
			foreach ( $spec['components'] as $component ) {
				if ( is_array( $component ) && isset( $component['id'] ) && is_string( $component['id'] ) ) {
					$this->by_id[ $component['id'] ] = $component;
				}
			}
		}
	}

	/**
	 * Whether the registry has valid components spec data.
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {
		return is_array( $this->components_spec );
	}

	/**
	 * Returns spec version string.
	 *
	 * @return string
	 */
	public function get_spec_version(): string {
		return $this->spec_version;
	}

	/**
	 * Returns all component ids.
	 *
	 * @return list<string>
	 */
	public function get_component_ids(): array {
		return array_keys( $this->by_id );
	}

	/**
	 * Returns component data by id.
	 *
	 * @param string $component_id Component id (e.g. card, cta, badge).
	 * @return array{id?: string, element_role?: string, selector_pattern?: string, allowed_token_overrides?: list<string>} Empty array if not found.
	 */
	public function get_component( string $component_id ): array {
		if ( isset( $this->by_id[ $component_id ] ) && is_array( $this->by_id[ $component_id ] ) ) {
			return $this->by_id[ $component_id ];
		}
		return array();
	}

	/**
	 * Returns element role for a component (e.g. card, cta).
	 *
	 * @param string $component_id
	 * @return string
	 */
	public function get_element_role( string $component_id ): string {
		$c = $this->get_component( $component_id );
		$r = $c['element_role'] ?? null;
		return is_string( $r ) ? $r : '';
	}

	/**
	 * Returns selector pattern for a component (e.g. aio-s-{section_key}__card).
	 *
	 * @param string $component_id
	 * @return string
	 */
	public function get_selector_pattern( string $component_id ): string {
		$c = $this->get_component( $component_id );
		$s = $c['selector_pattern'] ?? null;
		return is_string( $s ) ? $s : '';
	}

	/**
	 * Returns list of allowed token variable names for overrides (e.g. --aio-color-primary).
	 *
	 * @param string $component_id
	 * @return list<string>
	 */
	public function get_allowed_token_overrides( string $component_id ): array {
		$c     = $this->get_component( $component_id );
		$list  = $c['allowed_token_overrides'] ?? array();
		if ( ! is_array( $list ) ) {
			return array();
		}
		return array_values( array_filter( $list, 'is_string' ) );
	}

	/**
	 * Returns whether a token variable name is allowed for the given component.
	 *
	 * @param string $component_id
	 * @param string $token_variable_name
	 * @return bool
	 */
	public function is_token_allowed_for_component( string $component_id, string $token_variable_name ): bool {
		return in_array( $token_variable_name, $this->get_allowed_token_overrides( $component_id ), true );
	}
}
