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
}
