<?php
/**
 * Loads machine-readable style specs from plugin-owned paths (Prompt 244).
 * Read-only; safe failure on missing or invalid files; no path leakage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Loads pb-style-core, pb-style-components, and pb-style-render-surfaces JSON specs.
 */
final class Style_Spec_Loader {

	/** Core token spec filename. */
	public const CORE_SPEC_FILE = 'pb-style-core-spec.json';

	/** Component override spec filename. */
	public const COMPONENTS_SPEC_FILE = 'pb-style-components-spec.json';

	/** Render surfaces spec filename. */
	public const RENDER_SURFACES_SPEC_FILE = 'pb-style-render-surfaces-spec.json';

	/** @var string Base directory (absolute path, trailing slash) for spec files. */
	private string $specs_base_path;

	/**
	 * @param string $specs_base_path Base path to specs directory (plugin-owned; no user input).
	 */
	public function __construct( string $specs_base_path ) {
		$this->specs_base_path = \rtrim( str_replace( '\\', '/', $specs_base_path ), '/' ) . '/';
	}

	/**
	 * Loads a single spec file. Returns decoded array or null on missing/invalid; does not leak paths.
	 *
	 * @param string $filename One of CORE_SPEC_FILE, COMPONENTS_SPEC_FILE, RENDER_SURFACES_SPEC_FILE.
	 * @return array<string, mixed>|null Decoded JSON or null on failure.
	 */
	public function load_spec( string $filename ): ?array {
		$allowed = array( self::CORE_SPEC_FILE, self::COMPONENTS_SPEC_FILE, self::RENDER_SURFACES_SPEC_FILE );
		if ( ! in_array( $filename, $allowed, true ) ) {
			return null;
		}
		$path = $this->specs_base_path . $filename;
		if ( ! \is_readable( $path ) || ! \is_file( $path ) ) {
			return null;
		}
		$raw = @\file_get_contents( $path );
		if ( $raw === false || $raw === '' ) {
			return null;
		}
		$decoded = \json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}
		return $decoded;
	}

	/**
	 * Loads core token spec.
	 *
	 * @return array<string, mixed>|null
	 */
	public function load_core_spec(): ?array {
		return $this->load_spec( self::CORE_SPEC_FILE );
	}

	/**
	 * Loads component override spec.
	 *
	 * @return array<string, mixed>|null
	 */
	public function load_components_spec(): ?array {
		return $this->load_spec( self::COMPONENTS_SPEC_FILE );
	}

	/**
	 * Loads render surfaces spec.
	 *
	 * @return array<string, mixed>|null
	 */
	public function load_render_surfaces_spec(): ?array {
		return $this->load_spec( self::RENDER_SURFACES_SPEC_FILE );
	}

	/**
	 * Returns the spec version string from a loaded spec array, or empty string.
	 *
	 * @param array<string, mixed> $spec Loaded spec.
	 * @return string
	 */
	public static function get_spec_version( array $spec ): string {
		$v = $spec['spec_version'] ?? null;
		return is_string( $v ) ? $v : '';
	}

	/**
	 * Returns the spec_schema string from a loaded spec array, or empty string.
	 *
	 * @param array<string, mixed> $spec Loaded spec.
	 * @return string
	 */
	public static function get_spec_schema( array $spec ): string {
		$s = $spec['spec_schema'] ?? null;
		return is_string( $s ) ? $s : '';
	}
}
