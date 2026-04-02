<?php
/**
 * Repairs legacy invalid plan JSON where section_guidance was embedded as a quoted string containing raw `[` / `{` JSON (broken outer quotes).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

/**
 * Transforms invalid `"section_guidance":"[...]"` segments into valid `"section_guidance":[...]` so json_decode succeeds.
 */
final class Build_Plan_Definition_Json_Legacy_Repair {

	private const MARKER = '"section_guidance":"';

	/**
	 * Returns repaired JSON text, or the original string when nothing changes / repair is unsafe.
	 */
	public static function try_repair_corrupt_section_guidance( string $raw ): string {
		if ( $raw === '' || ! str_contains( $raw, self::MARKER ) ) {
			return $raw;
		}
		$out    = $raw;
		$offset = 0;
		// * Bounded iterations: one pass per occurrence; offset advances to avoid infinite loops.
		for ( $guard = 0; $guard < 500; $guard++ ) {
			$p = strpos( $out, self::MARKER, $offset );
			if ( $p === false ) {
				break;
			}
			$bracket_start = $p + strlen( self::MARKER );
			if ( ! isset( $out[ $bracket_start ] ) ) {
				$offset = $p + 1;
				continue;
			}
			$open = $out[ $bracket_start ];
			if ( $open !== '[' && $open !== '{' ) {
				$offset = $p + 1;
				continue;
			}
			$close_idx = '[' === $open
				? self::find_matching_close( $out, $bracket_start, '[', ']' )
				: self::find_matching_close( $out, $bracket_start, '{', '}' );
			if ( $close_idx === null ) {
				$offset = $p + 1;
				continue;
			}
			$fragment = substr( $out, $bracket_start, $close_idx - $bracket_start + 1 );
			$flags    = JSON_BIGINT_AS_STRING;
			if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
				$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
			}
			\json_decode( $fragment, true, 512, $flags );
			if ( \JSON_ERROR_NONE !== \json_last_error() ) {
				$offset = $p + 1;
				continue;
			}
			$after = $close_idx + 1;
			if ( ! isset( $out[ $after ] ) || $out[ $after ] !== '"' ) {
				$offset = $p + 1;
				continue;
			}
			$replace_from = $p;
			$replace_to   = $after;
			$before       = substr( $out, 0, $replace_from );
			$after_part   = substr( $out, $replace_to + 1 );
			$out          = $before . '"section_guidance":' . $fragment . $after_part;
			$offset       = strlen( $before ) + strlen( '"section_guidance":' ) + strlen( $fragment );
		}
		return $out;
	}

	/**
	 * @param string $s       Full document.
	 * @param int    $open_idx Byte index of opening bracket/brace.
	 * @param string $open_char `[` or `{`.
	 * @param string $close_char `]` or `}`.
	 * @return int|null Byte index of matching closer, or null.
	 */
	private static function find_matching_close( string $s, int $open_idx, string $open_char, string $close_char ): ?int {
		$len = strlen( $s );
		if ( $open_idx >= $len || $s[ $open_idx ] !== $open_char ) {
			return null;
		}
		$depth  = 0;
		$in_str = false;
		$esc    = false;
		for ( $i = $open_idx; $i < $len; $i++ ) {
			$c = $s[ $i ];
			if ( $in_str ) {
				if ( $esc ) {
					$esc = false;
				} elseif ( '\\' === $c ) {
					$esc = true;
				} elseif ( '"' === $c ) {
					$in_str = false;
				}
				continue;
			}
			if ( '"' === $c ) {
				$in_str = true;
				continue;
			}
			if ( $c === $open_char ) {
				++$depth;
			} elseif ( $c === $close_char ) {
				--$depth;
				if ( $depth === 0 ) {
					return $i;
				}
			}
		}
		return null;
	}
}
