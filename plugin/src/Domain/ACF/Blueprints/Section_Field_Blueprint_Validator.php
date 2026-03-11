<?php
/**
 * Validates section field blueprints against acf-field-blueprint-schema (spec §20.1–20.6, §20.8).
 * Rejects invalid or incomplete blueprint definitions. Does not register fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Validates raw blueprint arrays per acf-field-blueprint-schema. Returns structured errors.
 * Prohibits executable callbacks, arbitrary code references, and undocumented attributes.
 */
final class Section_Field_Blueprint_Validator {

	/** Blueprint key pattern per acf-key-naming-contract. */
	private const BLUEPRINT_ID_PATTERN = '#^[a-z0-9_]+$#';

	/** Field key pattern per contract. */
	private const FIELD_KEY_PATTERN = '#^field_[a-z0-9_]+$#';

	/** Field name pattern. */
	private const FIELD_NAME_PATTERN = '#^[a-z0-9_]+$#';

	/** Max lengths. */
	private const MAX_BLUEPRINT_ID = 64;
	private const MAX_SECTION_KEY = 64;
	private const MAX_SECTION_VERSION = 32;
	private const MAX_LABEL = 255;
	private const MAX_KEY = 64;
	private const MAX_NAME = 64;

	/**
	 * Validates full blueprint. Returns empty array if valid; otherwise error messages.
	 *
	 * @param array<string, mixed> $blueprint Raw blueprint definition.
	 * @param string|null          $section_key Expected section key (for alignment check).
	 * @param string|null          $field_blueprint_ref Expected blueprint_id from section (for alignment check).
	 * @return list<string>
	 */
	public function validate( array $blueprint, ?string $section_key = null, ?string $field_blueprint_ref = null ): array {
		$errors = array();

		$errors = array_merge( $errors, $this->validate_root( $blueprint ) );
		$errors = array_merge( $errors, $this->validate_alignment( $blueprint, $section_key, $field_blueprint_ref ) );
		$errors = array_merge( $errors, $this->validate_fields( $blueprint ) );
		$errors = array_merge( $errors, $this->validate_security( $blueprint ) );

		return $errors;
	}

	/**
	 * Validates root structure and required properties.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return list<string>
	 */
	public function validate_root( array $blueprint ): array {
		$errors = Field_Blueprint_Schema::validate_blueprint_required_fields( $blueprint );
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		$err = array();

		$bid = (string) ( $blueprint[ Field_Blueprint_Schema::BLUEPRINT_ID ] ?? '' );
		if ( ! preg_match( self::BLUEPRINT_ID_PATTERN, $bid ) ) {
			$err[] = 'blueprint_id must match ^[a-z0-9_]+$';
		}
		if ( strlen( $bid ) > self::MAX_BLUEPRINT_ID ) {
			$err[] = 'blueprint_id exceeds max length';
		}

		$sk = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
		if ( ! preg_match( self::FIELD_NAME_PATTERN, $sk ) || strlen( $sk ) > self::MAX_SECTION_KEY ) {
			$err[] = 'section_key must match ^[a-z0-9_]+$ and max 64 chars';
		}

		$sv = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '' );
		if ( $sv === '' || strlen( $sv ) > self::MAX_SECTION_VERSION ) {
			$err[] = 'section_version must be non-empty and max 32 chars';
		}

		$label = (string) ( $blueprint[ Field_Blueprint_Schema::LABEL ] ?? '' );
		if ( strlen( $label ) > self::MAX_LABEL ) {
			$err[] = 'label exceeds max 255 chars';
		}

		return $err;
	}

	/**
	 * Validates alignment with section (section_key, blueprint_id vs field_blueprint_ref).
	 *
	 * @param array<string, mixed> $blueprint
	 * @param string|null         $section_key
	 * @param string|null         $field_blueprint_ref
	 * @return list<string>
	 */
	public function validate_alignment( array $blueprint, ?string $section_key, ?string $field_blueprint_ref ): array {
		$err = array();
		if ( $section_key !== null && $section_key !== '' ) {
			$bp_sk = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $bp_sk !== $section_key ) {
				$err[] = "blueprint section_key ({$bp_sk}) does not match section internal_key ({$section_key})";
			}
		}
		if ( $field_blueprint_ref !== null && $field_blueprint_ref !== '' ) {
			$bp_id = (string) ( $blueprint[ Field_Blueprint_Schema::BLUEPRINT_ID ] ?? '' );
			if ( $bp_id !== $field_blueprint_ref ) {
				$err[] = "blueprint blueprint_id ({$bp_id}) does not match section field_blueprint_ref ({$field_blueprint_ref})";
			}
		}
		return $err;
	}

	/**
	 * Validates all fields and nested sub_fields.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return list<string>
	 */
	public function validate_fields( array $blueprint ): array {
		$fields = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? array();
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return array( 'fields must be a non-empty array' );
		}

		$err = array();
		$seen_keys = array();

		foreach ( $fields as $i => $field ) {
			if ( ! is_array( $field ) ) {
				$err[] = "fields[{$i}] must be an object";
				continue;
			}
			$field_err = $this->validate_field( $field, $seen_keys, 'fields' );
			$err = array_merge( $err, array_map( function ( $e ) use ( $i ) {
				return "fields[{$i}]: {$e}";
			}, $field_err ) );
		}

		return $err;
	}

	/**
	 * Validates a single field (and sub_fields when type is repeater/group).
	 *
	 * @param array<string, mixed> $field
	 * @param array<string, true>  $seen_keys Keys already used in this scope.
	 * @param string               $path Path for error messages.
	 * @return list<string>
	 */
	private function validate_field( array $field, array &$seen_keys, string $path ): array {
		$err = Field_Blueprint_Schema::validate_field_required_properties( $field );
		if ( ! empty( $err ) ) {
			return $err;
		}

		$key = (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
		if ( isset( $seen_keys[ $key ] ) ) {
			$err[] = "duplicate field key: {$key}";
		}
		$seen_keys[ $key ] = true;

		if ( ! preg_match( self::FIELD_KEY_PATTERN, $key ) ) {
			$err[] = "field key must match ^field_[a-z0-9_]+$";
		}
		if ( strlen( $key ) > self::MAX_KEY ) {
			$err[] = 'field key exceeds 64 chars';
		}

		$name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' );
		if ( ! preg_match( self::FIELD_NAME_PATTERN, $name ) ) {
			$err[] = "field name must match ^[a-z0-9_]+$";
		}
		if ( strlen( $name ) > self::MAX_NAME ) {
			$err[] = 'field name exceeds 64 chars';
		}

		$type = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? '' );
		if ( Field_Blueprint_Schema::type_requires_sub_fields( $type ) ) {
			$sub = $field['sub_fields'] ?? array();
			if ( ! is_array( $sub ) || empty( $sub ) ) {
				$err[] = "{$type} requires non-empty sub_fields";
			} else {
				$sub_seen = array();
				foreach ( $sub as $j => $subfield ) {
					if ( ! is_array( $subfield ) ) {
						$err[] = "sub_fields[{$j}] must be an object";
						continue;
					}
					$sub_err = $this->validate_field( $subfield, $sub_seen, "{$path}.sub_fields[{$j}]" );
					$err = array_merge( $err, array_map( function ( $e ) use ( $j ) {
						return "sub_fields[{$j}]: {$e}";
					}, $sub_err ) );
				}
			}
		}

		return $err;
	}

	/**
	 * Validates that blueprint contains no executable/code references (security).
	 *
	 * @param array<string, mixed> $blueprint
	 * @return list<string>
	 */
	private function validate_security( array $blueprint ): array {
		$err = array();
		$this->reject_callable_references( $blueprint, 'blueprint', $err );
		return $err;
	}

	/**
	 * Recursively rejects callable/code references in arrays.
	 * Skips string values (e.g. field type 'link') that happen to be PHP function names.
	 *
	 * @param mixed       $val
	 * @param string      $path
	 * @param list<string> $errors
	 */
	private function reject_callable_references( $val, string $path, array &$errors ): void {
		if ( is_array( $val ) ) {
			$forbidden = array( 'callback', 'callable', 'exec', 'eval', 'code', 'php', 'script' );
			foreach ( $val as $k => $v ) {
				$k_lower = is_string( $k ) ? strtolower( $k ) : '';
				foreach ( $forbidden as $f ) {
					if ( str_contains( $k_lower, $f ) && ( $this->is_dangerous_callable( $v ) || ( is_string( $v ) && str_contains( strtolower( $v ), 'eval' ) ) ) ) {
						$errors[] = "{$path}: executable or code reference not allowed";
						return;
					}
				}
				$this->reject_callable_references( $v, "{$path}.{$k}", $errors );
			}
			return;
		}
		if ( $this->is_dangerous_callable( $val ) ) {
			$errors[] = "{$path}: callable not allowed";
		}
	}

	/**
	 * Returns true if value is a dangerous callable (Closure, invokable, array callback).
	 * Excludes plain strings like 'link' that are field type names.
	 *
	 * @param mixed $val
	 * @return bool
	 */
	private function is_dangerous_callable( $val ): bool {
		if ( is_string( $val ) ) {
			return false;
		}
		if ( $val instanceof \Closure ) {
			return true;
		}
		if ( is_array( $val ) && count( $val ) >= 2 && is_callable( $val ) ) {
			return true;
		}
		if ( is_object( $val ) && is_callable( $val ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns whether the blueprint is valid and eligible for normalization.
	 *
	 * @param array<string, mixed> $blueprint
	 * @param string|null          $section_key
	 * @param string|null          $field_blueprint_ref
	 * @return bool
	 */
	public function is_valid( array $blueprint, ?string $section_key = null, ?string $field_blueprint_ref = null ): bool {
		return empty( $this->validate( $blueprint, $section_key, $field_blueprint_ref ) );
	}
}
