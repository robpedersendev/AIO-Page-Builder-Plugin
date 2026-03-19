<?php
/**
 * Deterministic ACF group and field key generation (spec §20.4, acf-key-naming-contract).
 * Generates stable keys from section identity and field names. Does not register fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Generates group keys and field keys per acf-key-naming-contract.md.
 * Keys are deterministic, sanitized, and collision-free within scope.
 */
final class Field_Key_Generator {

	/** Group key prefix (ACF convention). */
	private const GROUP_PREFIX = 'group_';

	/** Plugin namespace for groups. */
	private const NAMESPACE = 'aio_';

	/** Field key prefix. */
	private const FIELD_PREFIX = 'field_';

	/** Max key length (ACF practical limit). */
	private const MAX_KEY_LENGTH = 64;

	/**
	 * Generates group key for a section.
	 *
	 * @param string $section_key Section internal_key.
	 * @return string
	 */
	public static function group_key( string $section_key ): string {
		$stem = self::GROUP_PREFIX . self::NAMESPACE . self::sanitize( $section_key );
		return self::truncate( $stem );
	}

	/**
	 * Generates top-level field key.
	 *
	 * @param string $section_key Section internal_key.
	 * @param string $field_name  Field name (from blueprint).
	 * @return string
	 */
	public static function field_key( string $section_key, string $field_name ): string {
		$section_key = self::sanitize( $section_key );
		$field_name  = self::sanitize( $field_name );
		$stem        = self::FIELD_PREFIX . $section_key . '_' . $field_name;
		return self::truncate( $stem );
	}

	/**
	 * Generates subfield key (repeater or group child).
	 *
	 * @param string $section_key  Section internal_key.
	 * @param string $parent_name  Parent field name.
	 * @param string $child_name   Child/subfield name.
	 * @return string
	 */
	public static function subfield_key( string $section_key, string $parent_name, string $child_name ): string {
		$section_key = self::sanitize( $section_key );
		$parent_name = self::sanitize( $parent_name );
		$child_name  = self::sanitize( $child_name );
		$stem        = self::FIELD_PREFIX . $section_key . '_' . $parent_name . '_' . $child_name;
		return self::truncate( $stem );
	}

	/**
	 * Sanitizes a key component to allowed pattern.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function sanitize( string $value ): string {
		$value = \sanitize_text_field( strtolower( $value ) );
		$value = preg_replace( '/[^a-z0-9_]/', '', $value );
		return $value === '' ? 'x' : substr( $value, 0, self::MAX_KEY_LENGTH - 1 );
	}

	/**
	 * Truncates key to max length, preserving rightmost segment where possible.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function truncate( string $key ): string {
		if ( strlen( $key ) <= self::MAX_KEY_LENGTH ) {
			return $key;
		}
		return substr( $key, 0, self::MAX_KEY_LENGTH );
	}

	/**
	 * Returns a key unique within existing set by appending numeric suffix if needed.
	 *
	 * @param string   $key     Proposed key.
	 * @param string[] $existing Existing keys (values or keys).
	 * @return string Unique key.
	 */
	public static function ensure_unique( string $key, array $existing ): string {
		$used      = array_flip( array_map( 'strval', array_values( $existing ) ) );
		$candidate = $key;
		$suffix    = 0;
		while ( isset( $used[ $candidate ] ) && $suffix < 9999 ) {
			++$suffix;
			$base      = strlen( $key ) + strlen( (string) $suffix ) + 1 <= self::MAX_KEY_LENGTH
				? $key
				: substr( $key, 0, self::MAX_KEY_LENGTH - strlen( (string) $suffix ) - 1 );
			$candidate = $base . '_' . $suffix;
		}
		return $candidate;
	}

	/**
	 * Validates that a key conforms to the contract pattern.
	 *
	 * @param string $key  Key to validate.
	 * @param string $kind 'group' or 'field'.
	 * @return bool
	 */
	public static function is_valid_key( string $key, string $kind = 'field' ): bool {
		if ( strlen( $key ) > self::MAX_KEY_LENGTH || strlen( $key ) === 0 ) {
			return false;
		}
		if ( $kind === 'group' ) {
			return str_starts_with( $key, self::GROUP_PREFIX . self::NAMESPACE )
				&& preg_match( '#^group_aio_[a-z0-9_]+$#', $key ) === 1;
		}
		return str_starts_with( $key, self::FIELD_PREFIX )
			&& preg_match( '#^field_[a-z0-9_]+$#', $key ) === 1;
	}
}
