<?php
/**
 * Artifact storage/retrieval and review payloads with redaction and access gating (spec §29, §29.8, §29.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Runs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;

/**
 * Stores and retrieves artifacts by run and category. Builds review-safe payloads with redaction.
 * Access to raw artifacts is gated by caller/capability; this service does not check caps.
 */
final class AI_Run_Artifact_Service {

	/** Placeholder shown when content is redacted. */
	private const REDACTED_PLACEHOLDER = '[redacted]';

	/** @var AI_Run_Repository */
	private $run_repository;

	public function __construct( AI_Run_Repository $run_repository ) {
		$this->run_repository = $run_repository;
	}

	/**
	 * Stores an artifact payload for a run (by post ID) and category.
	 *
	 * @param int    $run_post_id Run post ID.
	 * @param string $category   Artifact_Category_Keys constant.
	 * @param mixed  $payload    Encodable payload.
	 * @return bool Success.
	 */
	public function store( int $run_post_id, string $category, mixed $payload ): bool {
		if ( ! Artifact_Category_Keys::is_valid( $category ) ) {
			return false;
		}
		return $this->run_repository->save_artifact_payload( $run_post_id, $category, $payload );
	}

	/**
	 * Retrieves raw artifact payload for a run and category (no redaction).
	 *
	 * @param int    $run_post_id Run post ID.
	 * @param string $category   Artifact_Category_Keys constant.
	 * @return mixed Payload or null.
	 */
	public function get( int $run_post_id, string $category ): mixed {
		if ( ! Artifact_Category_Keys::is_valid( $category ) ) {
			return null;
		}
		return $this->run_repository->get_artifact_payload( $run_post_id, $category );
	}

	/**
	 * Returns artifact summary payload for admin review: category => present + redacted summary.
	 * Sensitive categories are replaced with REDACTED_PLACEHOLDER unless $include_raw is true.
	 *
	 * @param int  $run_post_id  Run post ID.
	 * @param bool $include_raw  If true, include raw content for sensitive categories (caller must gate by cap).
	 * @return array<string, array{present: bool, summary: string|array, redacted: bool}>
	 */
	public function get_artifact_summary_for_review( int $run_post_id, bool $include_raw = false ): array {
		$out = array();
		foreach ( Artifact_Category_Keys::all() as $cat ) {
			$payload     = $this->get( $run_post_id, $cat );
			$present     = $payload !== null && $payload !== '';
			$redact      = in_array( $cat, Artifact_Category_Keys::REDACT_BEFORE_DISPLAY, true );
			$summary     = $present ? $this->summarize_payload( $payload, $redact && ! $include_raw ) : '';
			$out[ $cat ] = array(
				'present'  => $present,
				'summary'  => $summary,
				'redacted' => $redact && ! $include_raw,
			);
		}
		return $out;
	}

	/**
	 * Summarizes a payload for display: short string or structure; redacts if requested.
	 *
	 * @param mixed $payload  Artifact payload.
	 * @param bool  $redact   If true, return redacted placeholder for sensitive content.
	 * @return string|array
	 */
	private function summarize_payload( mixed $payload, bool $redact ) {
		if ( $redact ) {
			return self::REDACTED_PLACEHOLDER;
		}
		if ( is_scalar( $payload ) ) {
			$s = (string) $payload;
			return strlen( $s ) > 200 ? substr( $s, 0, 200 ) . '…' : $s;
		}
		if ( is_array( $payload ) ) {
			$keys = array_keys( $payload );
			return array(
				'keys'  => $keys,
				'count' => count( $payload ),
			);
		}
		return '[non-scalar]';
	}

	/**
	 * Redacts sensitive values in an array (e.g. api_key, secret, token) for safe display.
	 * Used when building review payloads that must not expose secrets.
	 *
	 * @param array<string, mixed> $data Associative array (e.g. usage_metadata, run_metadata).
	 * @return array<string, mixed>
	 */
	public static function redact_sensitive_values( array $data ): array {
		$sensitive_keys = array( 'api_key', 'secret', 'token', 'password', 'authorization' );
		$out            = array();
		foreach ( $data as $k => $v ) {
			$lower  = strtolower( (string) $k );
			$redact = false;
			foreach ( $sensitive_keys as $sk ) {
				if ( str_contains( $lower, $sk ) ) {
					$redact = true;
					break;
				}
			}
			$out[ $k ] = $redact ? self::REDACTED_PLACEHOLDER : ( is_array( $v ) ? self::redact_sensitive_values( $v ) : $v );
		}
		return $out;
	}
}
