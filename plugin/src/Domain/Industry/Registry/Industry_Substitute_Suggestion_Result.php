<?php
/**
 * Immutable substitute suggestion result (industry-substitute-suggestion-contract.md).
 * One suggested replacement for a discouraged or weak-fit section/template.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Single substitute suggestion: original key, suggested key, reason, score delta, optional warning flags.
 */
final class Industry_Substitute_Suggestion_Result {

	public const KEY_ORIGINAL_KEY             = 'original_key';
	public const KEY_SUGGESTED_REPLACEMENT_KEY = 'suggested_replacement_key';
	public const KEY_SUBSTITUTE_REASON        = 'substitute_reason';
	public const KEY_FIT_SCORE_DELTA          = 'fit_score_delta';
	public const KEY_WARNING_FLAGS            = 'warning_flags';

	public const REASON_SAME_FAMILY_BETTER_FIT = 'same_family_better_fit';
	public const REASON_RECOMMENDED_ALTERNATIVE = 'recommended_alternative';
	public const REASON_SAME_CATEGORY_BETTER_FIT = 'same_category_better_fit';

	/**
	 * Builds a result array. All parameters required for a valid suggestion.
	 *
	 * @param string   $original_key             Section or template key that was discouraged/weak-fit.
	 * @param string   $suggested_replacement_key Suggested alternative key.
	 * @param string   $substitute_reason        Reason code (e.g. REASON_SAME_FAMILY_BETTER_FIT).
	 * @param int      $fit_score_delta          Score difference (suggested minus original).
	 * @param array    $warning_flags            Optional warning flags for context.
	 * @return array<string, mixed>
	 */
	public static function create(
		string $original_key,
		string $suggested_replacement_key,
		string $substitute_reason,
		int $fit_score_delta,
		array $warning_flags = array()
	): array {
		return array(
			self::KEY_ORIGINAL_KEY              => $original_key,
			self::KEY_SUGGESTED_REPLACEMENT_KEY => $suggested_replacement_key,
			self::KEY_SUBSTITUTE_REASON         => $substitute_reason,
			self::KEY_FIT_SCORE_DELTA           => $fit_score_delta,
			self::KEY_WARNING_FLAGS             => array_values( array_filter( array_map( 'strval', $warning_flags ) ) ),
		);
	}

	/**
	 * Normalizes an array to the result shape. Fills defaults for missing keys.
	 *
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	public static function from_array( array $raw ): array {
		return array(
			self::KEY_ORIGINAL_KEY              => isset( $raw[ self::KEY_ORIGINAL_KEY ] ) && is_string( $raw[ self::KEY_ORIGINAL_KEY ] ) ? trim( $raw[ self::KEY_ORIGINAL_KEY ] ) : '',
			self::KEY_SUGGESTED_REPLACEMENT_KEY => isset( $raw[ self::KEY_SUGGESTED_REPLACEMENT_KEY ] ) && is_string( $raw[ self::KEY_SUGGESTED_REPLACEMENT_KEY ] ) ? trim( $raw[ self::KEY_SUGGESTED_REPLACEMENT_KEY ] ) : '',
			self::KEY_SUBSTITUTE_REASON         => isset( $raw[ self::KEY_SUBSTITUTE_REASON ] ) && is_string( $raw[ self::KEY_SUBSTITUTE_REASON ] ) ? trim( $raw[ self::KEY_SUBSTITUTE_REASON ] ) : self::REASON_RECOMMENDED_ALTERNATIVE,
			self::KEY_FIT_SCORE_DELTA           => isset( $raw[ self::KEY_FIT_SCORE_DELTA ] ) && is_numeric( $raw[ self::KEY_FIT_SCORE_DELTA ] ) ? (int) $raw[ self::KEY_FIT_SCORE_DELTA ] : 0,
			self::KEY_WARNING_FLAGS             => isset( $raw[ self::KEY_WARNING_FLAGS ] ) && is_array( $raw[ self::KEY_WARNING_FLAGS ] ) ? array_values( array_filter( array_map( 'strval', $raw[ self::KEY_WARNING_FLAGS ] ) ) ) : array(),
		);
	}
}
