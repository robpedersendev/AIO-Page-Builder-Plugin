<?php
/**
 * Lightweight benchmark evidence for ACF conditional-registration retrofit (Prompt 301).
 * Captures registration mode and section-key counts from diagnostics for before/after comparison.
 * Admin/internal only; no sensitive data; no public routes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Bounded benchmark harness: snapshot last registration run from diagnostics.
 * Prompt 310: optional query/memory profile when called in controlled benchmark context.
 */
final class ACF_Registration_Benchmark_Service {

	/** @var ACF_Registration_Diagnostics_Service */
	private ACF_Registration_Diagnostics_Service $diagnostics;

	public function __construct( ACF_Registration_Diagnostics_Service $diagnostics ) {
		$this->diagnostics = $diagnostics;
	}

	/**
	 * Returns a snapshot of the last recorded registration for benchmark evidence.
	 * Only meaningful after an admin request that ran registration; front-end does not record.
	 * No sensitive field values or content; safe for internal QA.
	 *
	 * @return array{last_registration: array<string, mixed>|null, timestamp: int}
	 */
	public function get_evidence_snapshot(): array {
		return array(
			'last_registration' => $this->diagnostics->get_last_registration(),
			'timestamp'         => time(),
		);
	}

	/**
	 * Returns evidence snapshot plus query count and memory peak when called in a controlled benchmark run (Prompt 310).
	 * Call after the request under measurement; reads $wpdb->num_queries and memory_get_peak_usage at call time.
	 * Not always-on; no instrumentation on normal requests. Internal only; no sensitive data.
	 *
	 * @return array{last_registration: array<string, mixed>|null, timestamp: int, query_count: int|null, memory_peak_bytes: int}
	 */
	public function get_evidence_snapshot_with_profile(): array {
		$base = $this->get_evidence_snapshot();
		$query_count = null;
		if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) && isset( $GLOBALS['wpdb']->num_queries ) ) {
			$query_count = (int) $GLOBALS['wpdb']->num_queries;
		}
		$memory_peak = \function_exists( 'memory_get_peak_usage' ) ? (int) memory_get_peak_usage( true ) : 0;
		$base['query_count']       = $query_count;
		$base['memory_peak_bytes'] = $memory_peak;
		return $base;
	}
}
