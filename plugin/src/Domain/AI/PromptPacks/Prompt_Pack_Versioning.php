<?php
/**
 * Prompt pack version comparison and compatibility (prompt-pack-schema.md §3, §12, spec §26.8, §58.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

/**
 * Semantic version parsing and comparison for prompt packs. Purely declarative; no I/O.
 */
final class Prompt_Pack_Versioning {

	/**
	 * Parses a semantic version string into [ major, minor, patch ]. Non-semver strings return [0, 0, 0].
	 *
	 * @param string $version Version string (e.g. 1.2.0).
	 * @return array{0: int, 1: int, 2: int}
	 */
	public static function parse( string $version ): array {
		$version = trim( $version );
		if ( preg_match( '/^(\d+)\.(\d+)\.(\d+)(?:-|$)/', $version, $m ) ) {
			return array( (int) $m[1], (int) $m[2], (int) $m[3] );
		}
		if ( preg_match( '/^(\d+)\.(\d+)(?:-|$)/', $version, $m ) ) {
			return array( (int) $m[1], (int) $m[2], 0 );
		}
		if ( preg_match( '/^(\d+)(?:-|$)/', $version, $m ) ) {
			return array( (int) $m[1], 0, 0 );
		}
		return array( 0, 0, 0 );
	}

	/**
	 * Compares two version strings. Returns -1 if $a < $b, 0 if equal, 1 if $a > $b.
	 *
	 * @param string $a First version.
	 * @param string $b Second version.
	 * @return int
	 */
	public static function compare( string $a, string $b ): int {
		$pa = self::parse( $a );
		$pb = self::parse( $b );
		if ( $pa[0] !== $pb[0] ) {
			return $pa[0] < $pb[0] ? -1 : 1;
		}
		if ( $pa[1] !== $pb[1] ) {
			return $pa[1] < $pb[1] ? -1 : 1;
		}
		if ( $pa[2] !== $pb[2] ) {
			return $pa[2] < $pb[2] ? -1 : 1;
		}
		return 0;
	}

	/**
	 * Whether version string looks like a valid semver (major.minor.patch).
	 *
	 * @param string $version Version string.
	 * @return bool
	 */
	public static function is_valid_semver( string $version ): bool {
		return (bool) preg_match( '/^\d+\.\d+\.\d+$/', trim( $version ) );
	}
}
