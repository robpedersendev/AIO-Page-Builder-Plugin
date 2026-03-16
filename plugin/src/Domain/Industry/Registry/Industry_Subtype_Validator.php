<?php
/**
 * Validates industry subtype definitions (industry-subtype-schema.md; Prompt 421).
 * Single-definition validation; optional parent-industry key check. Returns list of error strings; empty means valid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Validates a single subtype definition. Schema and optional parent-industry reference check.
 */
final class Industry_Subtype_Validator {

	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LEN = 64;
	private const LABEL_MAX_LEN = 256;
	private const SUMMARY_MAX_LEN = 1024;

	/**
	 * Validates a single subtype definition. Returns list of error messages; empty means valid.
	 *
	 * @param array<string, mixed> $def Subtype definition (subtype_key, parent_industry_key, label, summary, status, version_marker, optional refs).
	 * @param array<string, true>|null $valid_parent_keys Optional set of allowed parent_industry_key values (e.g. from Industry_Pack_Registry). If provided, parent_industry_key must be in this set.
	 * @return list<string> Error messages; empty if valid.
	 */
	public function validate( array $def, ?array $valid_parent_keys = null ): array {
		$errors = array();

		$key = isset( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] )
			: '';
		if ( $key === '' ) {
			$errors[] = 'subtype_key is required and must be a non-empty string.';
		} else {
			if ( strlen( $key ) > self::KEY_MAX_LEN ) {
				$errors[] = 'subtype_key must not exceed ' . self::KEY_MAX_LEN . ' characters.';
			}
			if ( ! preg_match( self::KEY_PATTERN, $key ) ) {
				$errors[] = 'subtype_key must match pattern ^[a-z0-9_-]+$.';
			}
		}

		$parent = isset( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
			: '';
		if ( $parent === '' ) {
			$errors[] = 'parent_industry_key is required and must be a non-empty string.';
		} elseif ( $valid_parent_keys !== null && ! isset( $valid_parent_keys[ $parent ] ) ) {
			$errors[] = 'parent_industry_key must reference an existing industry pack.';
		}

		$label = isset( $def[ Industry_Subtype_Registry::FIELD_LABEL ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
			: '';
		if ( $label !== '' && strlen( $label ) > self::LABEL_MAX_LEN ) {
			$errors[] = 'label must not exceed ' . self::LABEL_MAX_LEN . ' characters.';
		}

		$summary = isset( $def[ Industry_Subtype_Registry::FIELD_SUMMARY ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_SUMMARY ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_SUMMARY ] )
			: '';
		if ( $summary !== '' && strlen( $summary ) > self::SUMMARY_MAX_LEN ) {
			$errors[] = 'summary must not exceed ' . self::SUMMARY_MAX_LEN . ' characters.';
		}

		$status = isset( $def[ Industry_Subtype_Registry::FIELD_STATUS ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_STATUS ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_STATUS ] )
			: '';
		if ( ! in_array( $status, array( Industry_Subtype_Registry::STATUS_ACTIVE, Industry_Subtype_Registry::STATUS_DRAFT, Industry_Subtype_Registry::STATUS_DEPRECATED ), true ) ) {
			$errors[] = 'status must be one of: active, draft, deprecated.';
		}

		$version = isset( $def[ Industry_Subtype_Registry::FIELD_VERSION_MARKER ] ) && is_string( $def[ Industry_Subtype_Registry::FIELD_VERSION_MARKER ] )
			? trim( $def[ Industry_Subtype_Registry::FIELD_VERSION_MARKER ] )
			: '';
		if ( $version !== '' && $version !== Industry_Subtype_Registry::SUPPORTED_VERSION ) {
			$errors[] = 'version_marker must be supported version (e.g. 1).';
		}

		return array_values( $errors );
	}
}
