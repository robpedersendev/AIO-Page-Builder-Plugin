<?php
/**
 * Normalizes model-provided strings that embed JSON arrays/objects so outer wp_json_encode stays valid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

/**
 * Decode+re-encode JSON-shaped strings (e.g. section_guidance) to a single canonical JSON text fragment.
 */
final class Build_Plan_Payload_Json_String_Normalizer {

	/**
	 * If the trimmed string looks like a JSON array or object, returns wp_json_encode( json_decode( … ) ); otherwise the original string.
	 *
	 * @param string $value Raw scalar from normalized AI output.
	 */
	public static function normalize_optional_json_fragment( string $value ): string {
		$t = trim( $value );
		if ( $t === '' || ( ! str_starts_with( $t, '[' ) && ! str_starts_with( $t, '{' ) ) ) {
			return $value;
		}
		$flags = JSON_BIGINT_AS_STRING;
		if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$decoded = \json_decode( $t, true, 512, $flags );
		if ( ! \is_array( $decoded ) ) {
			return $value;
		}
		$json = \function_exists( 'wp_json_encode' ) ? \wp_json_encode( $decoded ) : \json_encode( $decoded );
		return \is_string( $json ) ? $json : '';
	}

	/**
	 * Stores section_guidance as a native array when the model returns JSON-shaped text so the plan root encodes as valid JSON (never a string containing raw `[` / `{`).
	 *
	 * @return array<mixed>|string Plain non-JSON-looking strings pass through unchanged.
	 */
	public static function coerce_section_guidance_for_storage( mixed $value ): array|string {
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( ! is_string( $value ) ) {
			return '';
		}
		$t = trim( $value );
		if ( $t === '' ) {
			return '';
		}
		if ( ! str_starts_with( $t, '[' ) && ! str_starts_with( $t, '{' ) ) {
			return $value;
		}
		$flags = JSON_BIGINT_AS_STRING;
		if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$decoded = \json_decode( $t, true, 512, $flags );
		return \is_array( $decoded ) ? $decoded : $value;
	}
}
