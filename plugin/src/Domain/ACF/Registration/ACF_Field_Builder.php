<?php
/**
 * Assembles ACF field arrays from normalized blueprint fields (spec §20.2–20.8).
 * Supports nested repeater/group structures. Does not register; produces arrays only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;

/**
 * Translates normalized blueprint fields into ACF-compatible field arrays.
 * Preserves deterministic keys, instructions, validation, and nested structures.
 */
final class ACF_Field_Builder {

	/** ACF field keys to pass through from blueprint (whitelist). */
	private const PASSTHROUGH_KEYS = array(
		'key',
		'label',
		'name',
		'type',
		'instructions',
		'required',
		'default_value',
		'placeholder',
		'conditional_logic',
		'wrapper',
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

	/**
	 * Builds ACF field array from normalized blueprint field.
	 *
	 * @param array<string, mixed> $field Normalized blueprint field.
	 * @param string|null         $parent_key Parent field/group key for subfields.
	 * @return array<string, mixed> ACF-compatible field array.
	 */
	public function build_field( array $field, ?string $parent_key = null ): array {
		$acf = $this->base_field( $field );

		if ( $parent_key !== null ) {
			$acf['parent'] = $parent_key;
		}

		if ( Field_Blueprint_Schema::type_requires_sub_fields( (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? '' ) ) ) {
			$sub = $field['sub_fields'] ?? array();
			if ( is_array( $sub ) && ! empty( $sub ) ) {
				$acf['sub_fields'] = $this->build_fields( $sub, (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' ) );
			}
		}

		$acf = $this->apply_validation( $acf, $field );
		return $this->sanitize_field_output( $acf );
	}

	/**
	 * Builds array of ACF fields from normalized blueprint fields.
	 *
	 * @param list<array<string, mixed>> $fields
	 * @param string|null                $parent_key
	 * @return list<array<string, mixed>>
	 */
	public function build_fields( array $fields, ?string $parent_key = null ): array {
		$out = array();
		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}
			$acf = $this->build_field( $f, $parent_key );
			if ( ! empty( $acf['key'] ) ) {
				$out[] = $acf;
			}
		}
		return $out;
	}

	/**
	 * Builds base ACF field from blueprint field.
	 *
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	private function base_field( array $field ): array {
		$acf = array();
		foreach ( self::PASSTHROUGH_KEYS as $k ) {
			if ( ! array_key_exists( $k, $field ) ) {
				continue;
			}
			$v = $field[ $k ];
			if ( $k === 'sub_fields' ) {
				continue;
			}
			if ( $k === 'required' ) {
				$acf[ $k ] = (int) ( $v ? 1 : 0 );
			} elseif ( in_array( $k, array( 'min', 'max', 'maxlength', 'rows', 'step' ), true ) && is_numeric( $v ) ) {
				$acf[ $k ] = (int) $v;
			} elseif ( in_array( $k, array( 'conditional_logic', 'wrapper', 'choices' ), true ) && is_array( $v ) ) {
				$acf[ $k ] = $v;
			} elseif ( in_array( $k, array( 'allow_null', 'multiple', 'media_upload', 'ui' ), true ) ) {
				$acf[ $k ] = (int) ( $v ? 1 : 0 );
			} else {
				$acf[ $k ] = $v;
			}
		}
		return $acf;
	}

	/**
	 * Applies validation metadata to ACF field where supported.
	 *
	 * @param array<string, mixed> $acf
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	private function apply_validation( array $acf, array $field ): array {
		$validation = $field['validation'] ?? null;
		if ( ! is_array( $validation ) ) {
			return $acf;
		}
		if ( isset( $validation['maxlength'] ) && is_numeric( $validation['maxlength'] ) ) {
			$acf['maxlength'] = (int) $validation['maxlength'];
		}
		if ( isset( $validation['min_rows'] ) && ( $field['type'] ?? '' ) === 'repeater' ) {
			$acf['min'] = (int) $validation['min_rows'];
		}
		if ( isset( $validation['max_rows'] ) && ( $field['type'] ?? '' ) === 'repeater' ) {
			$acf['max'] = (int) $validation['max_rows'];
		}
		return $acf;
	}

	/**
	 * Sanitizes output: removes empty optional values, ensures required shape.
	 *
	 * @param array<string, mixed> $acf
	 * @return array<string, mixed>
	 */
	private function sanitize_field_output( array $acf ): array {
		$required = array( 'key', 'label', 'name', 'type' );
		foreach ( $required as $r ) {
			if ( ! isset( $acf[ $r ] ) || $acf[ $r ] === '' ) {
				return array();
			}
		}
		if ( isset( $acf['wrapper'] ) && is_array( $acf['wrapper'] ) ) {
			$acf['wrapper'] = array_merge(
				array( 'width' => '', 'class' => '', 'id' => '' ),
				$acf['wrapper']
			);
		}
		return $acf;
	}
}
