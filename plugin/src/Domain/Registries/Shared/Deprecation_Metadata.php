<?php
/**
 * Shared deprecation metadata shape (spec §12.15, §13.13, §58.2).
 * Canonical field names for deprecation blocks; do not rename prior status keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Deprecation metadata fields. Section/page template use compatibility aliases.
 */
final class Deprecation_Metadata {

	/** Object is deprecated. */
	public const IS_DEPRECATED = 'is_deprecated';

	/** ISO 8601 datetime when deprecated. */
	public const DEPRECATED_AT = 'deprecated_at';

	/** Human-readable reason. */
	public const DEPRECATED_REASON = 'deprecated_reason';

	/** Replacement object key (section internal_key, template internal_key, etc.). */
	public const REPLACEMENT_KEY = 'replacement_key';

	/** Exclude from normal new-template / new-selection UI. */
	public const ELIGIBLE_FOR_NEW_USE = 'eligible_for_new_use';

	/** Historical references (plans, pages) may still reference this. */
	public const HISTORICAL_REFERENCE_ALLOWED = 'historical_reference_allowed';

	/**
	 * Builds normalized deprecation block for section (alias: replacement_section_key).
	 *
	 * @param string $reason Required.
	 * @param string $replacement_key Optional.
	 * @return array<string, mixed>
	 */
	public static function for_section( string $reason, string $replacement_key = '' ): array {
		$block                                       = array(
			'deprecated'                 => true,
			'reason'                     => \sanitize_text_field( $reason ),
			'replacement_section_key'    => self::sanitize( $replacement_key ),
			'retain_existing_references' => true,
			'exclude_from_new_selection' => true,
			'preserve_rendered_pages'    => true,
		);
		$block[ self::IS_DEPRECATED ]                = true;
		$block[ self::DEPRECATED_AT ]                = gmdate( 'Y-m-d\TH:i:s\Z' );
		$block[ self::DEPRECATED_REASON ]            = $block['reason'];
		$block[ self::REPLACEMENT_KEY ]              = $block['replacement_section_key'];
		$block[ self::ELIGIBLE_FOR_NEW_USE ]         = false;
		$block[ self::HISTORICAL_REFERENCE_ALLOWED ] = true;
		return $block;
	}

	/**
	 * Builds normalized deprecation block for page template (alias: replacement_template_key).
	 *
	 * @param string $reason Required.
	 * @param string $replacement_key Optional.
	 * @return array<string, mixed>
	 */
	public static function for_page_template( string $reason, string $replacement_key = '' ): array {
		$block                                       = array(
			'deprecated'                       => true,
			'reason'                           => \sanitize_text_field( $reason ),
			'replacement_template_key'         => self::sanitize( $replacement_key ),
			'interpretability_of_old_plans'    => true,
			'exclude_from_new_build_selection' => true,
		);
		$block[ self::IS_DEPRECATED ]                = true;
		$block[ self::DEPRECATED_AT ]                = gmdate( 'Y-m-d\TH:i:s\Z' );
		$block[ self::DEPRECATED_REASON ]            = $block['reason'];
		$block[ self::REPLACEMENT_KEY ]              = $block['replacement_template_key'];
		$block[ self::ELIGIBLE_FOR_NEW_USE ]         = false;
		$block[ self::HISTORICAL_REFERENCE_ALLOWED ] = true;
		return $block;
	}

	/**
	 * Returns whether definition is eligible for new selection (not deprecated or exclude_from_new_* false).
	 *
	 * @param array<string, mixed> $definition
	 * @return bool
	 */
	public static function is_eligible_for_new_use( array $definition ): bool {
		$status = (string) ( $definition['status'] ?? '' );
		if ( $status === 'deprecated' ) {
			return false;
		}
		$dep = $definition['deprecation'] ?? array();
		if ( (bool) ( $dep['deprecated'] ?? false ) ) {
			return false;
		}
		return true;
	}

	private static function sanitize( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		return substr( preg_replace( '/[^a-z0-9_]/', '', $key ), 0, 64 );
	}
}
