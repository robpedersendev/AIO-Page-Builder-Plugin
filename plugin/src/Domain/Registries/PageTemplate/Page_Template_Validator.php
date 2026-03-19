<?php
/**
 * Validates page template definitions against schema (spec §13, page-template-registry-schema.md §12).
 * Validates section references against the section registry.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Validates page templates before persistence. Section references must exist in section registry.
 */
final class Page_Template_Validator {

	/** @var Page_Template_Normalizer */
	private Page_Template_Normalizer $normalizer;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $repository;

	/** @var Section_Registry_Service */
	private Section_Registry_Service $section_registry;

	public function __construct(
		Page_Template_Normalizer $normalizer,
		Page_Template_Repository $repository,
		Section_Registry_Service $section_registry
	) {
		$this->normalizer       = $normalizer;
		$this->repository       = $repository;
		$this->section_registry = $section_registry;
	}

	/**
	 * Validates definition for create.
	 *
	 * @param array<string, mixed> $input
	 * @param int                  $exclude_post_id
	 * @return Page_Template_Validation_Result
	 */
	public function validate_for_create( array $input, int $exclude_post_id = 0 ): Page_Template_Validation_Result {
		$normalized = $this->normalizer->normalize( $input );
		$errors     = $this->validate_completeness( $normalized );
		if ( ! empty( $errors ) ) {
			return Page_Template_Validation_Result::failure( $errors, $normalized );
		}
		$section_errors = $this->validate_section_references( $normalized );
		if ( ! empty( $section_errors ) ) {
			return Page_Template_Validation_Result::failure( array_merge( $errors, $section_errors ), $normalized );
		}
		$key_errors = $this->validate_key_uniqueness( $normalized[ Page_Template_Schema::FIELD_INTERNAL_KEY ], $exclude_post_id );
		if ( ! empty( $key_errors ) ) {
			return Page_Template_Validation_Result::failure( array_merge( $errors, $key_errors ), $normalized );
		}
		return Page_Template_Validation_Result::success( $normalized );
	}

	/**
	 * Validates definition for update. Enforces immutable internal_key.
	 *
	 * @param array<string, mixed> $input
	 * @param int                  $existing_post_id
	 * @return Page_Template_Validation_Result
	 */
	public function validate_for_update( array $input, int $existing_post_id ): Page_Template_Validation_Result {
		$normalized = $this->normalizer->normalize( $input );
		$errors     = $this->validate_completeness( $normalized );
		if ( ! empty( $errors ) ) {
			return Page_Template_Validation_Result::failure( $errors, $normalized );
		}
		$section_errors = $this->validate_section_references( $normalized );
		if ( ! empty( $section_errors ) ) {
			return Page_Template_Validation_Result::failure( array_merge( $errors, $section_errors ), $normalized );
		}
		$key_errors = $this->validate_key_immutability( $normalized, $existing_post_id );
		if ( ! empty( $key_errors ) ) {
			return Page_Template_Validation_Result::failure( array_merge( $errors, $key_errors ), $normalized );
		}
		return Page_Template_Validation_Result::success( $normalized );
	}

	/**
	 * Validates completeness per page-template-registry-schema §12.
	 *
	 * @param array<string, mixed> $normalized
	 * @return array<int, string>
	 */
	public function validate_completeness( array $normalized ): array {
		$errors   = array();
		$required = Page_Template_Schema::get_required_fields();

		foreach ( $required as $field ) {
			if ( ! array_key_exists( $field, $normalized ) ) {
				$errors[] = "Missing required field: {$field}";
				continue;
			}
			$v = $normalized[ $field ];
			if ( $field !== Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS
				&& $field !== Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES
				&& ( $v === '' || $v === null ) ) {
				$errors[] = "Required field empty: {$field}";
				continue;
			}
			if ( $field === Page_Template_Schema::FIELD_ORDERED_SECTIONS ) {
				if ( ! is_array( $v ) || empty( $v ) ) {
					$errors[] = 'ordered_sections must be non-empty';
				} else {
					foreach ( $v as $i => $item ) {
						if ( ! is_array( $item ) || empty( (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' ) ) ) {
							$errors[] = "ordered_sections[{$i}] must have valid section_key";
						}
					}
				}
			}
			if ( $field === Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ) {
				if ( ! is_array( $v ) ) {
					$errors[] = 'section_requirements must be an object';
				}
			}
			if ( $field === Page_Template_Schema::FIELD_ONE_PAGER ) {
				if ( ! is_array( $v ) || empty( (string) ( $v['page_purpose_summary'] ?? '' ) ) ) {
					$errors[] = 'one_pager.page_purpose_summary is required and non-empty';
				}
			}
			if ( $field === Page_Template_Schema::FIELD_VERSION ) {
				if ( ! is_array( $v ) || empty( (string) ( $v['version'] ?? '' ) ) ) {
					$errors[] = 'version.version is required';
				}
			}
		}

		$key = (string) ( $normalized[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key !== '' && ! preg_match( Page_Template_Schema::INTERNAL_KEY_PATTERN, $key ) ) {
			$errors[] = 'internal_key must match pattern ^[a-z0-9_]+$';
		}

		$status  = (string) ( $normalized[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
		$allowed = array( 'draft', 'active', 'inactive', 'deprecated' );
		if ( $status !== '' && ! in_array( $status, $allowed, true ) ) {
			$errors[] = 'status must be one of: ' . implode( ', ', $allowed );
		}

		$arch = (string) ( $normalized[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' );
		if ( $arch !== '' && ! Page_Template_Schema::is_allowed_archetype( $arch ) ) {
			$errors[] = 'archetype is not in allowed list';
		}

		$ordered = $normalized[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$reqs    = $normalized[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ] ?? array();
		if ( is_array( $ordered ) && is_array( $reqs ) ) {
			foreach ( $ordered as $item ) {
				$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				if ( $sk !== '' && ! isset( $reqs[ $sk ] ) ) {
					$errors[] = "section_requirements missing entry for section_key: {$sk}";
				}
			}
		}

		return $errors;
	}

	/**
	 * Validates that every section_key in ordered_sections exists in section registry.
	 *
	 * @param array<string, mixed> $normalized
	 * @return array<int, string>
	 */
	public function validate_section_references( array $normalized ): array {
		$errors  = array();
		$ordered = $normalized[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		if ( ! is_array( $ordered ) ) {
			return array();
		}
		foreach ( $ordered as $item ) {
			$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$section = $this->section_registry->get_by_key( $key );
			if ( $section === null ) {
				$errors[] = "Section key does not exist in registry: {$key}";
			}
		}
		return $errors;
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
		$new_key    = (string) ( $normalized[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$stored_key = (string) ( $existing['internal_key'] ?? '' );
		if ( $stored_key === '' && isset( $existing['definition'] ) && is_array( $existing['definition'] ) ) {
			$stored_key = (string) ( $existing['definition'][ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		}
		if ( $new_key !== $stored_key ) {
			return array( 'internal_key is immutable; cannot change from ' . $stored_key . ' to ' . $new_key );
		}
		return array();
	}
}
