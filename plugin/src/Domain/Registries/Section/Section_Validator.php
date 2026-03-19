<?php
/**
 * Validates section definitions against schema (spec §12, section-registry-schema.md §12).
 * Rejects required-structure violations; does not persist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Validates section definitions before persistence.
 * Requires Section_Definition_Normalizer for sanitized input; validates completeness and invariants.
 */
final class Section_Validator {

	/** @var Section_Definition_Normalizer */
	private Section_Definition_Normalizer $normalizer;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $repository;

	public function __construct(
		Section_Definition_Normalizer $normalizer,
		Section_Template_Repository $repository
	) {
		$this->normalizer = $normalizer;
		$this->repository = $repository;
	}

	/**
	 * Validates definition for create. Normalizes first, then checks completeness and uniqueness.
	 *
	 * @param array<string, mixed> $input Raw or partial section definition.
	 * @param int                  $exclude_post_id Post ID to exclude from key-uniqueness check (0 = none).
	 * @return Section_Validation_Result
	 */
	public function validate_for_create( array $input, int $exclude_post_id = 0 ): Section_Validation_Result {
		$normalized = $this->normalizer->normalize( $input );
		$errors     = $this->validate_completeness( $normalized );
		if ( ! empty( $errors ) ) {
			return Section_Validation_Result::failure( $errors, $normalized );
		}
		$key_errors = $this->validate_key_uniqueness( $normalized[ Section_Schema::FIELD_INTERNAL_KEY ], $exclude_post_id );
		if ( ! empty( $key_errors ) ) {
			return Section_Validation_Result::failure( array_merge( $errors, $key_errors ), $normalized );
		}
		return Section_Validation_Result::success( $normalized );
	}

	/**
	 * Validates definition for update. Enforces immutable internal_key; normalizes and checks completeness.
	 *
	 * @param array<string, mixed> $input Raw or partial section definition (must include internal_key).
	 * @param int                  $existing_post_id Post ID of existing section (for key immutability).
	 * @return Section_Validation_Result
	 */
	public function validate_for_update( array $input, int $existing_post_id ): Section_Validation_Result {
		$normalized = $this->normalizer->normalize( $input );
		$errors     = $this->validate_completeness( $normalized );
		if ( ! empty( $errors ) ) {
			return Section_Validation_Result::failure( $errors, $normalized );
		}
		$key_errors = $this->validate_key_immutability( $normalized, $existing_post_id );
		if ( ! empty( $key_errors ) ) {
			return Section_Validation_Result::failure( array_merge( $errors, $key_errors ), $normalized );
		}
		return Section_Validation_Result::success( $normalized );
	}

	/**
	 * Validates completeness per section-registry-schema §12 incompleteness rules.
	 *
	 * @param array<string, mixed> $normalized Normalized definition from Section_Definition_Normalizer.
	 * @return array<int, string> Error messages.
	 */
	public function validate_completeness( array $normalized ): array {
		$errors   = array();
		$required = Section_Schema::get_required_fields();

		foreach ( $required as $field ) {
			if ( ! array_key_exists( $field, $normalized ) ) {
				$errors[] = "Missing required field: {$field}";
				continue;
			}
			$v = $normalized[ $field ];
			if ( $v === '' || $v === null ) {
				$errors[] = "Required field empty: {$field}";
				continue;
			}
			if ( $field === Section_Schema::FIELD_VARIANTS ) {
				if ( ! is_array( $v ) || empty( $v ) ) {
					$errors[] = 'variants must be non-empty';
				}
			}
			if ( $field === Section_Schema::FIELD_COMPATIBILITY ) {
				if ( ! is_array( $v ) ) {
					$errors[] = 'compatibility must be an object';
				}
			}
			if ( $field === Section_Schema::FIELD_VERSION ) {
				if ( ! is_array( $v ) || empty( (string) ( $v['version'] ?? '' ) ) ) {
					$errors[] = 'version.version is required';
				}
			}
			if ( $field === Section_Schema::FIELD_ASSET_DECLARATION ) {
				if ( ! is_array( $v ) ) {
					$errors[] = 'asset_declaration must be an object';
				} else {
					$ad_err = $this->validate_asset_declaration( $v );
					if ( ! empty( $ad_err ) ) {
						$errors[] = $ad_err;
					}
				}
			}
		}

		$key = (string) ( $normalized[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key !== '' && ! preg_match( Section_Schema::INTERNAL_KEY_PATTERN, $key ) ) {
			$errors[] = 'internal_key must match pattern ^[a-z0-9_]+$';
		}
		if ( strlen( $key ) > Section_Schema::INTERNAL_KEY_MAX_LENGTH ) {
			$errors[] = 'internal_key exceeds max length';
		}

		$status  = (string) ( $normalized[ Section_Schema::FIELD_STATUS ] ?? '' );
		$allowed = array( 'draft', 'active', 'inactive', 'deprecated' );
		if ( $status !== '' && ! in_array( $status, $allowed, true ) ) {
			$errors[] = 'status must be one of: ' . implode( ', ', $allowed );
		}

		$cat = (string) ( $normalized[ Section_Schema::FIELD_CATEGORY ] ?? '' );
		if ( $cat !== '' && ! Section_Schema::is_allowed_category( $cat ) ) {
			$errors[] = 'category is not in allowed list';
		}

		$mode = (string) ( $normalized[ Section_Schema::FIELD_RENDER_MODE ] ?? '' );
		if ( $mode !== '' && ! Section_Schema::is_allowed_render_mode( $mode ) ) {
			$errors[] = 'render_mode is not in allowed list';
		}

		$default_var = (string) ( $normalized[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? '' );
		$variants    = $normalized[ Section_Schema::FIELD_VARIANTS ] ?? array();
		if ( is_array( $variants ) && $default_var !== '' && ! isset( $variants[ $default_var ] ) ) {
			$errors[] = 'default_variant must be a key in variants';
		}

		if ( (string) $status === 'deprecated' ) {
			$dep = $normalized['deprecation'] ?? array();
			if ( is_array( $dep ) && ! empty( $dep['reason'] ) ) {
				// * Reason recommended but not strictly required by schema for traceability. No validation error.
			}
		}

		return $errors;
	}

	/**
	 * @param array<string, mixed> $asset
	 * @return string Empty if valid.
	 */
	private function validate_asset_declaration( array $asset ): string {
		if ( ! empty( $asset['none'] ) ) {
			return '';
		}
		$has = false;
		foreach ( array( 'frontend_css', 'admin_css', 'frontend_js', 'admin_js', 'icons', 'media_patterns' ) as $k ) {
			if ( ! empty( $asset[ $k ] ) ) {
				$has = true;
				break;
			}
		}
		if ( ! $has && empty( $asset['shared_resources'] ) ) {
			return 'asset_declaration: if none is false, at least one asset flag or shared_resources must be set';
		}
		return '';
	}

	/**
	 * @param string $key
	 * @param int    $exclude_post_id
	 * @return array<int, string>
	 */
	private function validate_key_uniqueness( string $key, int $exclude_post_id ): array {
		if ( $key === '' ) {
			return array( 'internal_key is required and must be non-empty' );
		}
		$existing = $this->repository->get_by_key( $key );
		if ( $existing === null ) {
			return array();
		}
		$existing_id = (int) ( $existing['id'] ?? 0 );
		if ( $exclude_post_id > 0 && $existing_id === $exclude_post_id ) {
			return array();
		}
		return array( 'internal_key already exists: ' . $key );
	}

	/**
	 * @param array<string, mixed> $normalized
	 * @param int                  $existing_post_id
	 * @return array<int, string>
	 */
	private function validate_key_immutability( array $normalized, int $existing_post_id ): array {
		$existing = $this->repository->get_by_id( $existing_post_id );
		if ( $existing === null ) {
			return array();
		}
		$new_key    = (string) ( $normalized[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$stored_key = (string) ( $existing['internal_key'] ?? $existing['definition'][ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );

		// * Load from definition if repository returns it in that shape.
		if ( $stored_key === '' && isset( $existing['definition'] ) && is_array( $existing['definition'] ) ) {
			$stored_key = (string) ( $existing['definition'][ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		}

		if ( $new_key !== $stored_key ) {
			return array( 'internal_key is immutable; cannot change from ' . $stored_key . ' to ' . $new_key );
		}
		return array();
	}
}
