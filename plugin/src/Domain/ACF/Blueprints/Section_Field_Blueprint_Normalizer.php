<?php
/**
 * Normalizes section field blueprints for deterministic ACF registration (spec §20.2–20.6, §20.8).
 * Applies key generation, schema defaults, and structure enforcement. Does not register fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Produces validated, normalized blueprint arrays suitable for later ACF registration.
 * Integrates Field_Key_Generator for deterministic keys. Preserves editorial metadata.
 */
final class Section_Field_Blueprint_Normalizer {

	/** Allowed root keys (whitelist; unknown keys stripped). */
	private const ROOT_KEYS = array(
		'blueprint_id',
		'section_key',
		'section_version',
		'label',
		'description',
		'fields',
		'location_rules_hint',
		'variant_overrides',
	);

	/** Allowed field keys (whitelist). */
	private const FIELD_KEYS = array(
		'key',
		'name',
		'label',
		'type',
		'required',
		'instructions',
		'default_value',
		'placeholder',
		'validation',
		'conditional_logic',
		'lpagery',
		'wrapper',
		'sub_fields',
		'layout',
		'min',
		'max',
		'button_label',
		'choices',
		'allow_null',
		'multiple',
		'return_format',
		'preview_size',
		'library',
		'toolbar',
		'media_upload',
		'ui',
		'step',
		'post_type',
		'filters',
		'maxlength',
		'rows',
	);

	/** @var Section_Field_Blueprint_Validator */
	private Section_Field_Blueprint_Validator $validator;

	public function __construct( Section_Field_Blueprint_Validator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Normalizes a validated blueprint. Fails if validation fails.
	 *
	 * @param array<string, mixed> $blueprint Raw blueprint.
	 * @param string|null          $section_key Expected section key (for alignment).
	 * @param string|null          $field_blueprint_ref Expected blueprint_id from section.
	 * @return array{normalized: array<string, mixed>, errors: array<int, string>}
	 */
	public function normalize( array $blueprint, ?string $section_key = null, ?string $field_blueprint_ref = null ): array {
		$section_key = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? $section_key ?? '' );
		$blueprint   = $this->prefill_missing_keys( $blueprint, $section_key );

		$errors = $this->validator->validate( $blueprint, $section_key !== '' ? $section_key : null, $field_blueprint_ref );
		if ( ! empty( $errors ) ) {
			return array(
				'normalized' => array(),
				'errors'     => $errors,
			);
		}
		$normalized           = $this->normalize_root( $blueprint );
		$normalized['fields'] = $this->normalize_fields(
			(array) ( $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? array() ),
			$section_key,
			null
		);

		return array(
			'normalized' => $normalized,
			'errors'     => array(),
		);
	}

	/**
	 * Pre-fills missing field keys before validation so valid blueprints with omitted keys can pass.
	 *
	 * @param array<string, mixed> $blueprint
	 * @param string               $section_key
	 * @return array<string, mixed>
	 */
	private function prefill_missing_keys( array $blueprint, string $section_key ): array {
		$fields = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? array();
		if ( ! is_array( $fields ) ) {
			return $blueprint;
		}
		$sanitized = Field_Key_Generator::sanitize( $section_key );
		$section_key                                 = $sanitized !== '' ? $sanitized : 'x';
		$filled                                      = $this->prefill_fields_keys( $fields, $section_key, null );
		$blueprint[ Field_Blueprint_Schema::FIELDS ] = $filled;
		return $blueprint;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @param string                           $section_key
	 * @param string|null                      $parent_name
	 * @return array<int, array<string, mixed>>
	 */
	private function prefill_fields_keys( array $fields, string $section_key, ?string $parent_name ): array {
		$out = array();
		foreach ( $fields as $i => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$sanitized_name = Field_Key_Generator::sanitize( (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' ) );
			$name           = $sanitized_name !== '' ? $sanitized_name : 'field_' . ( $i + 1 );
			$existing = (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			if ( $existing === '' || ! Field_Key_Generator::is_valid_key( $existing, 'field' ) ) {
				if ( $parent_name !== null ) {
					$field[ Field_Blueprint_Schema::FIELD_KEY ] = Field_Key_Generator::subfield_key( $section_key, $parent_name, $name );
				} else {
					$field[ Field_Blueprint_Schema::FIELD_KEY ] = Field_Key_Generator::field_key( $section_key, $name );
				}
			}
			$type = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? 'text' );
			if ( Field_Blueprint_Schema::type_requires_sub_fields( $type ) ) {
				$sub                 = $field['sub_fields'] ?? array();
				$field['sub_fields'] = $this->prefill_fields_keys( is_array( $sub ) ? $sub : array(), $section_key, $name );
			}
			$out[] = $field;
		}
		return $out;
	}

	/**
	 * Normalizes root blueprint properties.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return array<string, mixed>
	 */
	private function normalize_root( array $blueprint ): array {
		$out = array();
		foreach ( self::ROOT_KEYS as $k ) {
			if ( ! array_key_exists( $k, $blueprint ) ) {
				continue;
			}
			$v = $blueprint[ $k ];
			if ( $k === 'description' && ( $v === '' || $v === null ) ) {
				continue;
			}
			if ( $k === 'location_rules_hint' && ( $v === '' || $v === null ) ) {
				continue;
			}
			if ( $k === 'variant_overrides' && ( ! is_array( $v ) || empty( $v ) ) ) {
				continue;
			}
			if ( in_array( $k, array( 'blueprint_id', 'section_key', 'section_version', 'label' ), true ) ) {
				$out[ $k ] = \sanitize_text_field( (string) $v );
			} elseif ( $k === 'description' ) {
				$out[ $k ] = \sanitize_textarea_field( (string) $v );
			} elseif ( $k === 'location_rules_hint' ) {
				$out[ $k ] = \sanitize_text_field( (string) $v );
			} elseif ( $k === 'variant_overrides' ) {
				$out[ $k ] = is_array( $v ) ? $v : array();
			} elseif ( $k === 'fields' ) {
				continue;
			} else {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	/**
	 * Normalizes field array; generates deterministic keys when missing or invalid.
	 *
	 * @param array<int, array<string, mixed>> $fields
	 * @param string                           $section_key
	 * @param string|null                      $parent_name For subfields.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_fields( array $fields, string $section_key, ?string $parent_name ): array {
		$out         = array();
		$used_keys   = array();
		$section_key = Field_Key_Generator::sanitize( $section_key );
		if ( $section_key === '' ) {
			$section_key = 'x';
		}

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' );
			$name = Field_Key_Generator::sanitize( $name );
			if ( $name === '' ) {
				$name = 'field_' . ( count( $out ) + 1 );
			}

			$type = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? 'text' );
			if ( ! Field_Blueprint_Schema::is_supported_type( $type ) ) {
				$type = Field_Blueprint_Schema::TYPE_TEXT;
			}

			if ( $parent_name !== null ) {
				$proposed_key = Field_Key_Generator::subfield_key( $section_key, $parent_name, $name );
				$key          = Field_Key_Generator::ensure_unique( $proposed_key, array_keys( $used_keys ) );
			} else {
				$proposed_key = Field_Key_Generator::field_key( $section_key, $name );
				$existing_key = (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
				if ( $existing_key !== '' && Field_Key_Generator::is_valid_key( $existing_key, 'field' ) ) {
					$key = Field_Key_Generator::ensure_unique( $existing_key, array_keys( $used_keys ) );
				} else {
					$key = Field_Key_Generator::ensure_unique( $proposed_key, array_keys( $used_keys ) );
				}
			}
			$used_keys[ $key ] = true;

			$normalized = $this->normalize_field( $field, $key, $name, $type );
			if ( Field_Blueprint_Schema::type_requires_sub_fields( $type ) ) {
				$sub                      = $field['sub_fields'] ?? array();
				$normalized['sub_fields'] = $this->normalize_fields( is_array( $sub ) ? $sub : array(), $section_key, $name );
			}
			$out[] = $normalized;
		}
		return $out;
	}

	/**
	 * Normalizes a single field, preserving allowed metadata.
	 *
	 * @param array<string, mixed> $field
	 * @param string               $key Assigned key.
	 * @param string               $name Assigned name.
	 * @param string               $type Field type.
	 * @return array<string, mixed>
	 */
	private function normalize_field( array $field, string $key, string $name, string $type ): array {
		$out = array(
			'key'   => $key,
			'name'  => $name,
			'label' => \sanitize_text_field( (string) ( $field[ Field_Blueprint_Schema::FIELD_LABEL ] ?? $name ) ),
			'type'  => $type,
		);

		foreach ( self::FIELD_KEYS as $k ) {
			if ( in_array( $k, array( 'key', 'name', 'label', 'type' ), true ) ) {
				continue;
			}
			if ( ! array_key_exists( $k, $field ) ) {
				continue;
			}
			$v = $field[ $k ];
			if ( $k === 'sub_fields' ) {
				continue;
			}
			if ( $k === 'required' ) {
				$out[ $k ] = (bool) $v;
			} elseif ( $k === 'instructions' || $k === 'placeholder' || $k === 'button_label' ) {
				$out[ $k ] = \sanitize_textarea_field( (string) $v );
			} elseif ( in_array( $k, array( 'min', 'max', 'maxlength', 'rows', 'step' ), true ) ) {
				$out[ $k ] = is_numeric( $v ) ? (int) $v : ( (float) $v );
			} elseif ( $k === 'validation' && is_array( $v ) ) {
				$out[ $k ] = $this->sanitize_validation( $v );
			} elseif ( $k === 'lpagery' && is_array( $v ) ) {
				$out[ $k ] = $this->sanitize_lpagery( $v );
			} elseif ( $k === 'wrapper' && is_array( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'conditional_logic' && is_array( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'choices' && ( is_array( $v ) || is_object( $v ) ) ) {
				$out[ $k ] = (array) $v;
			} else {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	/**
	 * Sanitizes validation metadata object.
	 *
	 * @param array<string, mixed> $v
	 * @return array<string, mixed>
	 */
	private function sanitize_validation( array $v ): array {
		$allowed = array( 'required', 'url', 'number', 'pattern', 'maxlength', 'min_rows', 'max_rows', 'warning_if_empty', 'variant_dependent' );
		$out     = array();
		foreach ( $allowed as $k ) {
			if ( array_key_exists( $k, $v ) ) {
				if ( $k === 'required' || $k === 'url' || $k === 'warning_if_empty' ) {
					$out[ $k ] = (bool) $v[ $k ];
				} elseif ( $k === 'number' && is_array( $v[ $k ] ) ) {
					$out[ $k ] = $v[ $k ];
				} elseif ( $k === 'pattern' || $k === 'variant_dependent' ) {
					$out[ $k ] = \sanitize_text_field( (string) $v[ $k ] );
				} elseif ( in_array( $k, array( 'maxlength', 'min_rows', 'max_rows' ), true ) ) {
					$out[ $k ] = is_numeric( $v[ $k ] ) ? (int) $v[ $k ] : 0;
				}
			}
		}
		return $out;
	}

	/**
	 * Sanitizes LPagery metadata object.
	 *
	 * @param array<string, mixed> $v
	 * @return array<string, mixed>
	 */
	private function sanitize_lpagery( array $v ): array {
		$allowed = array( 'token_compatible', 'token_name', 'injection_notes', 'fallback_behavior', 'unsupported_reason' );
		$out     = array();
		foreach ( $allowed as $k ) {
			if ( array_key_exists( $k, $v ) ) {
				if ( $k === 'token_compatible' ) {
					$out[ $k ] = (bool) $v[ $k ];
				} else {
					$out[ $k ] = \sanitize_text_field( (string) $v[ $k ] );
				}
			}
		}
		return $out;
	}
}
