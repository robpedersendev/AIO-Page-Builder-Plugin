<?php
/**
 * Bounded promotion workflow from sandbox-validated candidates to release-ready artifact description (Prompt 454).
 * Does not mutate live state; does not auto-activate. Callers use output for review and explicit promotion steps.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Defines promotion prerequisites and returns release-ready candidate summary for audit. No live activation.
 */
final class Industry_Sandbox_Promotion_Service {

	/** Result key: whether all prerequisites are met. */
	public const RESULT_PREREQUISITES_MET = 'prerequisites_met';

	/** Result key: list of missing requirement descriptions. */
	public const RESULT_MISSING_REQUIREMENTS = 'missing_requirements';

	/** Result key: pack keys that would be in the release-ready set. */
	public const RESULT_PACK_KEYS = 'pack_keys';

	/** Result key: bundle keys that would be in the release-ready set. */
	public const RESULT_BUNDLE_KEYS = 'bundle_keys';

	/** Result key: human-readable summary for audit. */
	public const RESULT_SUMMARY = 'summary';

	/**
	 * Checks whether dry-run output meets promotion prerequisites. No mutation.
	 *
	 * @param array{summary?: array{lint_errors?: int, health_errors?: int}} $dry_run_result Output from Industry_Author_Sandbox_Service::run_dry_run().
	 * @return array{prerequisites_met: bool, missing_requirements: list<string>}
	 */
	public function check_prerequisites( array $dry_run_result ): array {
		$summary = isset( $dry_run_result['summary'] ) && \is_array( $dry_run_result['summary'] ) ? $dry_run_result['summary'] : array();
		$lint_errors   = (int) ( $summary['lint_errors'] ?? 0 );
		$health_errors = (int) ( $summary['health_errors'] ?? 0 );
		$missing = array();
		if ( $lint_errors > 0 ) {
			$missing[] = \sprintf( 'Fix definition lint errors (%d).', $lint_errors );
		}
		if ( $health_errors > 0 ) {
			$missing[] = \sprintf( 'Fix health check errors (%d).', $health_errors );
		}
		return array(
			self::RESULT_PREREQUISITES_MET => count( $missing ) === 0,
			self::RESULT_MISSING_REQUIREMENTS => $missing,
		);
	}

	/**
	 * Returns a release-ready candidate summary for the validated packs and bundles. No mutation; no activation.
	 * Use for audit and for operator-driven promotion (e.g. copy definitions to release location).
	 *
	 * @param array<int, array<string, mixed>> $candidate_packs   Same list passed to run_dry_run().
	 * @param array<int, array<string, mixed>> $candidate_bundles Same list passed to run_dry_run().
	 * @param array{summary?: array}          $dry_run_result    Output from run_dry_run().
	 * @return array{pack_keys: list<string>, bundle_keys: list<string>, summary: string, prerequisites_met: bool}
	 */
	public function get_release_ready_summary( array $candidate_packs, array $candidate_bundles, array $dry_run_result ): array {
		$prereq = $this->check_prerequisites( $dry_run_result );
		$pack_keys = array();
		foreach ( $candidate_packs as $pack ) {
			if ( ! \is_array( $pack ) ) {
				continue;
			}
			$key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && \is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? \trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $key !== '' ) {
				$pack_keys[] = $key;
			}
		}
		$pack_keys = array_values( array_unique( $pack_keys ) );
		$bundle_keys = array();
		foreach ( $candidate_bundles as $bundle ) {
			if ( ! \is_array( $bundle ) ) {
				continue;
			}
			$key = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
				? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
				: '';
			if ( $key !== '' ) {
				$bundle_keys[] = $key;
			}
		}
		$bundle_keys = array_values( array_unique( $bundle_keys ) );
		$summary = $prereq[ self::RESULT_PREREQUISITES_MET ]
			? \sprintf( 'Release-ready candidate: %d pack(s), %d bundle(s). Promotion does not auto-activate.', count( $pack_keys ), count( $bundle_keys ) )
			: \sprintf( 'Prerequisites not met. Fix errors before promotion. Packs: %d, Bundles: %d.', count( $pack_keys ), count( $bundle_keys ) );
		return array(
			self::RESULT_PACK_KEYS         => $pack_keys,
			self::RESULT_BUNDLE_KEYS      => $bundle_keys,
			'summary'                     => $summary,
			'prerequisites_met'           => $prereq[ self::RESULT_PREREQUISITES_MET ],
		);
	}
}
