<?php
/**
 * Schema for industry recommendation override objects (industry-override-contract.md, Prompt 366).
 * Defines target types, states, reason bounds, and validation. No storage implementation in this class.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Override object shape and validation for section, page_template, and build_plan_item overrides.
 */
final class Industry_Override_Schema {

	/** Override target type: section template. */
	public const TARGET_TYPE_SECTION = 'section';

	/** Override target type: page template. */
	public const TARGET_TYPE_PAGE_TEMPLATE = 'page_template';

	/** Override target type: Build Plan item. */
	public const TARGET_TYPE_BUILD_PLAN_ITEM = 'build_plan_item';

	/** @var array<int, string> Allowed target types. */
	public const TARGET_TYPES = array(
		self::TARGET_TYPE_SECTION,
		self::TARGET_TYPE_PAGE_TEMPLATE,
		self::TARGET_TYPE_BUILD_PLAN_ITEM,
	);

	/** Override state: operator accepted the choice despite recommendation/warning. */
	public const STATE_ACCEPTED = 'accepted';

	/** Override state: operator rejected the recommendation. */
	public const STATE_REJECTED = 'rejected';

	/** @var array<int, string> Allowed override states. */
	public const STATES = array(
		self::STATE_ACCEPTED,
		self::STATE_REJECTED,
	);

	/** Field: target type. */
	public const FIELD_TARGET_TYPE = 'target_type';

	/** Field: target key (section_key, template_key, or item_id). */
	public const FIELD_TARGET_KEY = 'target_key';

	/** Field: optional plan_id for build_plan_item scope. */
	public const FIELD_PLAN_ID = 'plan_id';

	/** Field: override state. */
	public const FIELD_STATE = 'state';

	/** Field: reason (sanitized text). */
	public const FIELD_REASON = 'reason';

	/** Field: optional created timestamp (Unix). */
	public const FIELD_CREATED_AT = 'created_at';

	/** Field: optional updated timestamp (Unix). */
	public const FIELD_UPDATED_AT = 'updated_at';

	/** Field: optional industry context snapshot ref (e.g. primary_industry_key at override time). */
	public const FIELD_INDUSTRY_CONTEXT_REF = 'industry_context_ref';

	/** Max length for target_key. */
	public const TARGET_KEY_MAX_LENGTH = 255;

	/** Max length for reason. */
	public const REASON_MAX_LENGTH = 500;

	/** Pattern for target_key (alphanumeric, hyphen, underscore). */
	private const TARGET_KEY_PATTERN = '#^[a-zA-Z0-9_-]+$#';

	/**
	 * Validates an override array. Returns list of error codes; empty when valid.
	 *
	 * @param array<string, mixed> $override Override record.
	 * @return array<int, string> Error codes (e.g. invalid_target_type, missing_target_key, reason_too_long).
	 */
	public static function validate( array $override ): array {
		$errors = array();

		$target_type = isset( $override[ self::FIELD_TARGET_TYPE ] ) ? (string) $override[ self::FIELD_TARGET_TYPE ] : '';
		if ( ! in_array( $target_type, self::TARGET_TYPES, true ) ) {
			$errors[] = 'invalid_target_type';
		}

		$target_key = isset( $override[ self::FIELD_TARGET_KEY ] ) ? (string) $override[ self::FIELD_TARGET_KEY ] : '';
		if ( $target_key === '' ) {
			$errors[] = 'missing_target_key';
		} elseif ( strlen( $target_key ) > self::TARGET_KEY_MAX_LENGTH ) {
			$errors[] = 'target_key_too_long';
		} elseif ( ! preg_match( self::TARGET_KEY_PATTERN, $target_key ) ) {
			$errors[] = 'invalid_target_key';
		}

		$state = isset( $override[ self::FIELD_STATE ] ) ? (string) $override[ self::FIELD_STATE ] : '';
		if ( $state !== '' && ! in_array( $state, self::STATES, true ) ) {
			$errors[] = 'invalid_state';
		}

		$reason = isset( $override[ self::FIELD_REASON ] ) ? (string) $override[ self::FIELD_REASON ] : '';
		if ( strlen( $reason ) > self::REASON_MAX_LENGTH ) {
			$errors[] = 'reason_too_long';
		}

		return $errors;
	}

	/**
	 * Sanitizes reason for storage: strip tags, trim, enforce max length.
	 *
	 * @param string $reason Raw reason input.
	 * @return string Sanitized reason.
	 */
	public static function sanitize_reason( string $reason ): string {
		$reason = \function_exists( 'wp_strip_all_tags' ) ? \wp_strip_all_tags( $reason ) : \strip_tags( $reason );
		$reason = trim( $reason );
		if ( strlen( $reason ) > self::REASON_MAX_LENGTH ) {
			$reason = substr( $reason, 0, self::REASON_MAX_LENGTH );
		}
		return $reason;
	}

	/**
	 * Returns whether the override array is valid (no validation errors).
	 *
	 * @param array<string, mixed> $override
	 * @return bool
	 */
	public static function is_valid( array $override ): bool {
		return self::validate( $override ) === array();
	}
}
