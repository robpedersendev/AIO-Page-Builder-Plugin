<?php
/**
 * Read-only token registry backed by core style spec (Prompt 244).
 * Exposes allowed token names, groups, and sanitization metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Token metadata and lookup from pb-style-core-spec.json.
 */
final class Style_Token_Registry {

	/** @var array<string, mixed>|null Loaded core spec or null if failed. */
	private ?array $core_spec = null;

	/** @var string Spec version when loaded. */
	private string $spec_version = '';

	/** @var string Spec schema when loaded. */
	private string $spec_schema = '';

	public function __construct( Style_Spec_Loader $loader ) {
		$spec = $loader->load_core_spec();
		if ( is_array( $spec ) ) {
			$this->core_spec    = $spec;
			$this->spec_version = Style_Spec_Loader::get_spec_version( $spec );
			$this->spec_schema  = Style_Spec_Loader::get_spec_schema( $spec );
		}
	}

	/**
	 * Whether the registry has valid core spec data.
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {
		return is_array( $this->core_spec ) && isset( $this->core_spec['token_groups'] );
	}

	/**
	 * Returns spec version string for cache/compatibility.
	 *
	 * @return string
	 */
	public function get_spec_version(): string {
		return $this->spec_version;
	}

	/**
	 * Returns spec_schema string.
	 *
	 * @return string
	 */
	public function get_spec_schema(): string {
		return $this->spec_schema;
	}

	/**
	 * Returns all token group names (e.g. color, typography, spacing).
	 *
	 * @return list<string>
	 */
	public function get_token_group_names(): array {
		if ( ! $this->is_loaded() ) {
			return array();
		}
		$groups = $this->core_spec['token_groups'] ?? array();
		if ( ! is_array( $groups ) ) {
			return array();
		}
		return array_keys( $groups );
	}

	/**
	 * Returns allowed token names for a group (short names, e.g. primary, heading).
	 *
	 * @param string $group Token group (e.g. color, typography).
	 * @return list<string>
	 */
	public function get_allowed_names_for_group( string $group ): array {
		if ( ! $this->is_loaded() ) {
			return array();
		}
		$groups = $this->core_spec['token_groups'] ?? array();
		if ( ! is_array( $groups ) || ! isset( $groups[ $group ] ) || ! is_array( $groups[ $group ] ) ) {
			return array();
		}
		$names = $groups[ $group ]['allowed_names'] ?? array();
		return is_array( $names ) ? array_values( array_filter( $names, 'is_string' ) ) : array();
	}

	/**
	 * Returns full CSS variable name for group + name (e.g. --aio-color-primary).
	 *
	 * @param string $group Token group.
	 * @param string $name  Token name (must be in allowed_names for group).
	 * @return string Empty if not allowed or not loaded.
	 */
	public function get_token_variable_name( string $group, string $name ): string {
		$allowed = $this->get_allowed_names_for_group( $group );
		if ( ! in_array( $name, $allowed, true ) ) {
			return '';
		}
		$prefix  = '--aio-';
		$map     = array(
			'color'      => 'color',
			'typography' => 'font',
			'spacing'    => 'space',
			'radius'     => 'radius',
			'shadow'     => 'shadow',
		);
		$segment = $map[ $group ] ?? $group;
		return $prefix . $segment . '-' . $name;
	}

	/**
	 * Returns sanitization metadata for a token group.
	 *
	 * @param string $group Token group.
	 * @return array{value_type?: string, allowed_formats?: array, max_length?: int} Empty array if not found.
	 */
	public function get_sanitization_for_group( string $group ): array {
		if ( ! $this->is_loaded() ) {
			return array();
		}
		$groups = $this->core_spec['token_groups'] ?? array();
		if ( ! is_array( $groups ) || ! isset( $groups[ $group ] ) || ! is_array( $groups[ $group ] ) ) {
			return array();
		}
		$sanitization = $groups[ $group ]['sanitization'] ?? array();
		return is_array( $sanitization ) ? $sanitization : array();
	}

	/**
	 * Returns whether the given token variable name (e.g. --aio-color-primary) is allowed by the spec.
	 *
	 * @param string $variable_name Full variable name.
	 * @return bool
	 */
	public function is_allowed_token_name( string $variable_name ): bool {
		if ( ! $this->is_loaded() || ! str_starts_with( $variable_name, '--aio-' ) ) {
			return false;
		}
		foreach ( $this->get_token_group_names() as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			foreach ( $this->get_allowed_names_for_group( $group ) as $name ) {
				if ( $this->get_token_variable_name( $group, $name ) === $variable_name ) {
					return true;
				}
			}
		}
		return false;
	}
}
