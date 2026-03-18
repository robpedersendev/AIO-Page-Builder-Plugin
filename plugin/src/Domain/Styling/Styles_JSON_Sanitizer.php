<?php
/**
 * Whitelist-based sanitization for styles_json and structured style payloads (Prompt 252).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Validates token keys, component override keys, value types/units, and rejects dangerous CSS-like patterns.
 */
final class Styles_JSON_Sanitizer {

	/** Default max length when spec does not define it. */
	private const DEFAULT_MAX_LENGTH = 256;

	/** Prohibited substrings in any style value (case-insensitive where relevant). */
	private const PROHIBITED_PATTERNS = array(
		'url(',
		'expression(',
		'javascript:',
		'vbscript:',
		'data:',
		'<',
		'>',
		'{',
		'}',
	);

	/** @var Style_Token_Registry */
	private Style_Token_Registry $token_registry;

	/** @var Component_Override_Registry */
	private Component_Override_Registry $component_registry;

	/** @var Styles_JSON_Normalizer */
	private Styles_JSON_Normalizer $normalizer;

	public function __construct(
		Style_Token_Registry $token_registry,
		Component_Override_Registry $component_registry,
		Styles_JSON_Normalizer $normalizer
	) {
		$this->token_registry     = $token_registry;
		$this->component_registry = $component_registry;
		$this->normalizer         = $normalizer;
	}

	/**
	 * Sanitizes normalized global token overrides. Returns result with sanitized payload or errors.
	 *
	 * @param array<string, array<string, string>> $normalized [ group => [ name => value ] ]
	 * @return Style_Validation_Result
	 */
	public function sanitize_global_tokens( array $normalized ): Style_Validation_Result {
		$errors      = array();
		$sanitized   = array();
		$group_names = $this->token_registry->get_token_group_names();
		foreach ( $normalized as $group => $names ) {
			if ( ! is_string( $group ) || ! in_array( $group, $group_names, true ) ) {
				$errors[] = sprintf( 'Invalid token group: %s', $this->short_display( (string) $group ) );
				continue;
			}
			if ( $group === 'component' ) {
				continue;
			}
			$allowed_names       = $this->token_registry->get_allowed_names_for_group( $group );
			$meta                = $this->token_registry->get_sanitization_for_group( $group );
			$max_len             = isset( $meta['max_length'] ) && is_int( $meta['max_length'] ) ? $meta['max_length'] : self::DEFAULT_MAX_LENGTH;
			$sanitized[ $group ] = array();
			foreach ( $names as $name => $value ) {
				if ( ! is_string( $name ) || ! is_string( $value ) ) {
					continue;
				}
				if ( ! in_array( $name, $allowed_names, true ) ) {
					$errors[] = sprintf( 'Invalid token name in group %s: %s', $group, $this->short_display( $name ) );
					continue;
				}
				$err = $this->validate_value( $value, $max_len );
				if ( $err !== '' ) {
					$errors[] = sprintf( 'Token %s.%s: %s', $group, $name, $err );
					continue;
				}
				$sanitized[ $group ][ $name ] = $value;
			}
		}
		return new Style_Validation_Result( count( $errors ) === 0, $errors, $sanitized );
	}

	/**
	 * Sanitizes normalized global component overrides.
	 *
	 * @param array<string, array<string, string>> $normalized [ component_id => [ token_var => value ] ]
	 * @return Style_Validation_Result
	 */
	public function sanitize_global_component_overrides( array $normalized ): Style_Validation_Result {
		$errors                = array();
		$sanitized             = array();
		$allowed_component_ids = $this->component_registry->get_component_ids();
		foreach ( $normalized as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! in_array( $component_id, $allowed_component_ids, true ) ) {
				$errors[] = sprintf( 'Invalid component id: %s', $this->short_display( (string) $component_id ) );
				continue;
			}
			$allowed_vars               = $this->component_registry->get_allowed_token_overrides( $component_id );
			$sanitized[ $component_id ] = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( ! is_string( $var_name ) || ! is_string( $value ) ) {
					continue;
				}
				if ( ! in_array( $var_name, $allowed_vars, true ) ) {
					$errors[] = sprintf( 'Invalid override for component %s: %s', $component_id, $this->short_display( $var_name ) );
					continue;
				}
				$err = $this->validate_value( $value, self::DEFAULT_MAX_LENGTH );
				if ( $err !== '' ) {
					$errors[] = sprintf( 'Component override %s.%s: %s', $component_id, $var_name, $err );
					continue;
				}
				$sanitized[ $component_id ][ $var_name ] = $value;
			}
		}
		return new Style_Validation_Result( count( $errors ) === 0, $errors, $sanitized );
	}

	/**
	 * Sanitizes normalized entity payload (token_overrides + component_overrides).
	 *
	 * @param array{version: string, token_overrides: array, component_overrides: array} $normalized
	 * @return Style_Validation_Result Result's sanitized array is full entity payload shape.
	 */
	public function sanitize_entity_payload( array $normalized ): Style_Validation_Result {
		$token_result = $this->sanitize_global_tokens( $normalized[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] ?? array() );
		$comp_result  = $this->sanitize_global_component_overrides( $normalized[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] ?? array() );
		$errors       = array_merge( $token_result->get_errors(), $comp_result->get_errors() );
		$valid        = $token_result->is_valid() && $comp_result->is_valid();
		$sanitized    = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => $normalized[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] ?? Entity_Style_Payload_Schema::PAYLOAD_VERSION,
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => $token_result->get_sanitized(),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => $comp_result->get_sanitized(),
		);
		return new Style_Validation_Result( $valid, $errors, $sanitized );
	}

	/**
	 * Validates a single value for prohibited patterns and length.
	 *
	 * @param string $value
	 * @param int    $max_length
	 * @return string Empty if valid; otherwise short error message.
	 */
	public function validate_value( string $value, int $max_length = self::DEFAULT_MAX_LENGTH ): string {
		if ( strlen( $value ) > $max_length ) {
			return sprintf( 'Value exceeds max length %d', $max_length );
		}
		$lower = strtolower( $value );
		foreach ( self::PROHIBITED_PATTERNS as $pattern ) {
			$needle   = strpos( $pattern, ':' ) !== false || strpos( $pattern, '(' ) !== false
				? strtolower( $pattern )
				: $pattern;
			$haystack = ( $needle === $pattern ) ? $value : $lower;
			if ( str_contains( $haystack, $needle ) ) {
				return 'Value contains prohibited pattern';
			}
		}
		return '';
	}

	/**
	 * Short string for error messages (bounded, no raw user input explosion).
	 *
	 * @param string $s
	 * @return string
	 */
	private function short_display( string $s ): string {
		$len = 32;
		if ( strlen( $s ) <= $len ) {
			return $s;
		}
		return substr( $s, 0, $len ) . '…';
	}
}
