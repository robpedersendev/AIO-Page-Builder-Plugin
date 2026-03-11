<?php
/**
 * ACF field blueprint schema constants and helpers (spec §7.3, §20, §21).
 * Defines supported field types, required properties, and ineligibility rules.
 * Does not register fields or create ACF groups.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Schema contract for field blueprints. Use for deterministic validation before ACF registration.
 */
final class Field_Blueprint_Schema {

	/** Root blueprint fields (required). */
	public const BLUEPRINT_ID    = 'blueprint_id';
	public const SECTION_KEY    = 'section_key';
	public const SECTION_VERSION = 'section_version';
	public const LABEL          = 'label';
	public const FIELDS         = 'fields';

	/** Per-field properties (required). */
	public const FIELD_KEY   = 'key';
	public const FIELD_NAME  = 'name';
	public const FIELD_LABEL = 'label';
	public const FIELD_TYPE  = 'type';

	/** Supported ACF field types (spec §20.5, acf-field-blueprint-schema §5). */
	public const TYPE_TEXT        = 'text';
	public const TYPE_TEXTAREA    = 'textarea';
	public const TYPE_NUMBER      = 'number';
	public const TYPE_URL         = 'url';
	public const TYPE_EMAIL       = 'email';
	public const TYPE_WYSIWYG     = 'wysiwyg';
	public const TYPE_IMAGE       = 'image';
	public const TYPE_GALLERY     = 'gallery';
	public const TYPE_LINK        = 'link';
	public const TYPE_SELECT      = 'select';
	public const TYPE_TRUE_FALSE  = 'true_false';
	public const TYPE_RELATIONSHIP = 'relationship';
	public const TYPE_REPEATER    = 'repeater';
	public const TYPE_GROUP       = 'group';
	public const TYPE_COLOR_PICKER = 'color_picker';

	/** Field types that require sub_fields. */
	public const TYPES_WITH_SUBFIELDS = array( self::TYPE_REPEATER, self::TYPE_GROUP );

	/** LPagery token-compatible types (spec §21.3). */
	public const LPAGERY_SUPPORTED_TYPES = array(
		self::TYPE_TEXT,
		self::TYPE_TEXTAREA,
		self::TYPE_URL,
		self::TYPE_EMAIL,
		self::TYPE_LINK,
		self::TYPE_WYSIWYG,
	);

	/** LPagery unsupported types (spec §21.4). */
	public const LPAGERY_UNSUPPORTED_TYPES = array(
		self::TYPE_RELATIONSHIP,
		self::TYPE_GALLERY,
		self::TYPE_IMAGE, // Unsupported for attachment ref; URL-only may be partial.
	);

	/** @var list<string> */
	private static ?array $supported_types = null;

	/** @var list<string> */
	private static ?array $required_blueprint_fields = null;

	/** @var list<string> */
	private static ?array $required_field_properties = null;

	/**
	 * Returns all supported field types.
	 *
	 * @return list<string>
	 */
	public static function get_supported_types(): array {
		if ( self::$supported_types !== null ) {
			return self::$supported_types;
		}
		self::$supported_types = array(
			self::TYPE_TEXT,
			self::TYPE_TEXTAREA,
			self::TYPE_NUMBER,
			self::TYPE_URL,
			self::TYPE_EMAIL,
			self::TYPE_WYSIWYG,
			self::TYPE_IMAGE,
			self::TYPE_GALLERY,
			self::TYPE_LINK,
			self::TYPE_SELECT,
			self::TYPE_TRUE_FALSE,
			self::TYPE_RELATIONSHIP,
			self::TYPE_REPEATER,
			self::TYPE_GROUP,
			self::TYPE_COLOR_PICKER,
		);
		return self::$supported_types;
	}

	/**
	 * Returns required root blueprint field names.
	 *
	 * @return list<string>
	 */
	public static function get_required_blueprint_fields(): array {
		if ( self::$required_blueprint_fields !== null ) {
			return self::$required_blueprint_fields;
		}
		self::$required_blueprint_fields = array(
			self::BLUEPRINT_ID,
			self::SECTION_KEY,
			self::SECTION_VERSION,
			self::LABEL,
			self::FIELDS,
		);
		return self::$required_blueprint_fields;
	}

	/**
	 * Returns required per-field property names.
	 *
	 * @return list<string>
	 */
	public static function get_required_field_properties(): array {
		if ( self::$required_field_properties !== null ) {
			return self::$required_field_properties;
		}
		self::$required_field_properties = array(
			self::FIELD_KEY,
			self::FIELD_NAME,
			self::FIELD_LABEL,
			self::FIELD_TYPE,
		);
		return self::$required_field_properties;
	}

	/**
	 * Returns whether the field type is supported.
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function is_supported_type( string $type ): bool {
		return in_array( $type, self::get_supported_types(), true );
	}

	/**
	 * Returns whether the type requires sub_fields.
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function type_requires_sub_fields( string $type ): bool {
		return in_array( $type, self::TYPES_WITH_SUBFIELDS, true );
	}

	/**
	 * Returns whether the type is LPagery token-compatible by default.
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function is_lpagery_supported_type( string $type ): bool {
		return in_array( $type, self::LPAGERY_SUPPORTED_TYPES, true );
	}

	/**
	 * Returns whether the type is LPagery-unsupported.
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function is_lpagery_unsupported_type( string $type ): bool {
		return in_array( $type, self::LPAGERY_UNSUPPORTED_TYPES, true );
	}

	/**
	 * Validates that a blueprint has all required root fields.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return list<string> Empty if valid; otherwise error messages.
	 */
	public static function validate_blueprint_required_fields( array $blueprint ): array {
		$errors = array();
		foreach ( self::get_required_blueprint_fields() as $field ) {
			if ( ! array_key_exists( $field, $blueprint ) ) {
				$errors[] = "Missing required blueprint field: {$field}";
				continue;
			}
			$val = $blueprint[ $field ];
			if ( $field === self::FIELDS ) {
				if ( ! is_array( $val ) || empty( $val ) ) {
					$errors[] = 'fields must be a non-empty array';
				}
			} elseif ( $val === '' || $val === null ) {
				$errors[] = "Required blueprint field is empty: {$field}";
			}
		}
		return $errors;
	}

	/**
	 * Validates that a field definition has all required properties.
	 *
	 * @param array<string, mixed> $field
	 * @return list<string> Empty if valid; otherwise error messages.
	 */
	public static function validate_field_required_properties( array $field ): array {
		$errors = array();
		foreach ( self::get_required_field_properties() as $prop ) {
			if ( ! array_key_exists( $prop, $field ) ) {
				$errors[] = "Missing required field property: {$prop}";
				continue;
			}
			$val = $field[ $prop ];
			if ( $val === '' || $val === null ) {
				$errors[] = "Required field property is empty: {$prop}";
			}
		}
		$type = (string) ( $field[ self::FIELD_TYPE ] ?? '' );
		if ( $type !== '' && ! self::is_supported_type( $type ) ) {
			$errors[] = "Unsupported field type: {$type}";
		}
		if ( $type !== '' && self::type_requires_sub_fields( $type ) ) {
			$sub = $field['sub_fields'] ?? array();
			if ( ! is_array( $sub ) || empty( $sub ) ) {
				$errors[] = "Field type {$type} requires non-empty sub_fields";
			}
		}
		return $errors;
	}
}
