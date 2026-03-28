<?php
/**
 * Safe, non-authoritative template-lab linkage for build plans (ids and kinds only; no chat or provider payloads).
 *
 * Build plans remain canonical: review, approval, and execution follow the structured pipeline. This object only
 * records that a plan was informed by an approved template-lab artifact or derived canonical template key.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Allowed keys for {@see \AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT}.
 */
final class Build_Plan_Template_Lab_Context {

	public const FIELD_RUN_POST_ID = 'run_post_id';

	public const FIELD_TARGET_KIND = 'target_kind';

	public const FIELD_CANONICAL_INTERNAL_KEY = 'canonical_internal_key';

	public const FIELD_ARTIFACT_FINGERPRINT = 'artifact_fingerprint';

	public const FIELD_SCHEMA_REF = 'schema_ref';

	public const FIELD_CHAT_SESSION_ID = 'chat_session_id';

	public const FIELD_LINKED_AT_UNIX = 'linked_at_unix';

	/**
	 * @var list<string>
	 */
	private const ALLOWED_KEYS = array(
		self::FIELD_RUN_POST_ID,
		self::FIELD_TARGET_KIND,
		self::FIELD_CANONICAL_INTERNAL_KEY,
		self::FIELD_ARTIFACT_FINGERPRINT,
		self::FIELD_SCHEMA_REF,
		self::FIELD_CHAT_SESSION_ID,
		self::FIELD_LINKED_AT_UNIX,
	);

	/**
	 * @param array<string, mixed>|null $raw
	 * @return array<string, int|string>
	 */
	public static function sanitize( ?array $raw ): array {
		if ( $raw === null || $raw === array() ) {
			return array();
		}
		$out = array();
		foreach ( self::ALLOWED_KEYS as $k ) {
			if ( ! array_key_exists( $k, $raw ) ) {
				continue;
			}
			$v = $raw[ $k ];
			if ( $k === self::FIELD_RUN_POST_ID || $k === self::FIELD_LINKED_AT_UNIX ) {
				$out[ $k ] = max( 0, (int) $v );
				continue;
			}
			$s = is_string( $v ) ? trim( $v ) : ( is_scalar( $v ) ? trim( (string) $v ) : '' );
			if ( $s === '' ) {
				continue;
			}
			if ( $k === self::FIELD_SCHEMA_REF ) {
				$out[ $k ] = substr( \sanitize_text_field( $s ), 0, 512 );
				continue;
			}
			if ( $k === self::FIELD_CHAT_SESSION_ID ) {
				$out[ $k ] = substr( \sanitize_text_field( $s ), 0, 128 );
				continue;
			}
			if ( $k === self::FIELD_CANONICAL_INTERNAL_KEY || $k === self::FIELD_ARTIFACT_FINGERPRINT ) {
				$out[ $k ] = substr( \sanitize_text_field( $s ), 0, 255 );
				continue;
			}
			$out[ $k ] = substr( \sanitize_key( $s ), 0, 255 );
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $definition
	 * @param array<string, mixed> $context_raw
	 * @return array<string, mixed>
	 */
	public static function merge_into_definition( array $definition, array $context_raw ): array {
		$clean = self::sanitize( $context_raw );
		$definition[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ] = $clean;
		return $definition;
	}
}
