<?php
/**
 * Read-only render surface registry backed by render-surfaces spec (Prompt 244).
 * Exposes allowed surfaces (root, page, section) for style emission.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Render surfaces from pb-style-render-surfaces-spec.json.
 */
final class Render_Surface_Style_Registry {

	/** @var array<string, mixed>|null Loaded render surfaces spec or null if failed. */
	private ?array $surfaces_spec = null;

	/** @var string Spec version when loaded. */
	private string $spec_version = '';

	/** @var list<array> Ordered list of surface definitions. */
	private array $surfaces = array();

	public function __construct( Style_Spec_Loader $loader ) {
		$spec = $loader->load_render_surfaces_spec();
		if ( is_array( $spec ) && isset( $spec['render_surfaces'] ) && is_array( $spec['render_surfaces'] ) ) {
			$this->surfaces_spec = $spec;
			$this->spec_version  = Style_Spec_Loader::get_spec_version( $spec );
			foreach ( $spec['render_surfaces'] as $surface ) {
				if ( is_array( $surface ) && isset( $surface['id'] ) && is_string( $surface['id'] ) ) {
					$this->surfaces[] = $surface;
				}
			}
		}
	}

	/**
	 * Whether the registry has valid render surfaces spec data.
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {
		return is_array( $this->surfaces_spec );
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
	 * Returns all render surface definitions (id, selector, scope, allowed_output).
	 *
	 * @return list<array{id?: string, selector?: string, scope?: string, allowed_output?: array}>
	 */
	public function get_surfaces(): array {
		return $this->surfaces;
	}

	/**
	 * Returns surface by id.
	 *
	 * @param string $surface_id One of root, page, section.
	 * @return array{id?: string, selector?: string, scope?: string, allowed_output?: array} Empty array if not found.
	 */
	public function get_surface( string $surface_id ): array {
		foreach ( $this->surfaces as $s ) {
			if ( ( $s['id'] ?? '' ) === $surface_id ) {
				return $s;
			}
		}
		return array();
	}

	/**
	 * Returns CSS selector for a surface (e.g. :root, .aio-page).
	 *
	 * @param string $surface_id
	 * @return string
	 */
	public function get_selector_for_surface( string $surface_id ): string {
		$s   = $this->get_surface( $surface_id );
		$sel = $s['selector'] ?? null;
		return is_string( $sel ) ? $sel : '';
	}

	/**
	 * Returns scope for a surface (global, page, section).
	 *
	 * @param string $surface_id
	 * @return string
	 */
	public function get_scope_for_surface( string $surface_id ): string {
		$s     = $this->get_surface( $surface_id );
		$scope = $s['scope'] ?? null;
		return is_string( $scope ) ? $scope : '';
	}
}
