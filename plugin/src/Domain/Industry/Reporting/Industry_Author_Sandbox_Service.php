<?php
/**
 * Dry-run validation for pack authors (Prompt 444).
 * Loads candidate definitions into an in-memory context and runs linting and health checks without persisting to live state.
 * No mutation of live registries or profile; internal-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Runs validation against candidate pack/bundle definitions. Results are for review only; no promotion to live state.
 */
final class Industry_Author_Sandbox_Service {

	/**
	 * Runs dry-run validation: lint and health check against candidate definitions only. No live state is read or written.
	 *
	 * @param array<int, array<string, mixed>> $candidate_packs    List of pack definitions (industry_key, name, status, ...).
	 * @param array<int, array<string, mixed>> $candidate_bundles  List of starter bundle definitions (bundle_key, industry_key, ...).
	 * @return array{
	 *   lint_result: array{errors: list, warnings: list, summary: array},
	 *   health_result: array{errors: list, warnings: list},
	 *   summary: array{lint_errors: int, lint_warnings: int, health_errors: int, health_warnings: int}
	 * }
	 */
	public function run_dry_run( array $candidate_packs = array(), array $candidate_bundles = array() ): array {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( $candidate_packs );

		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( $candidate_bundles );

		$health_check = new Industry_Health_Check_Service(
			null,
			$pack_registry,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$bundle_registry,
			null
		);
		$health_result = $health_check->run();
		$health_errors  = isset( $health_result['errors'] ) && \is_array( $health_result['errors'] ) ? $health_result['errors'] : array();
		$health_warnings = isset( $health_result['warnings'] ) && \is_array( $health_result['warnings'] ) ? $health_result['warnings'] : array();

		$linter = new Industry_Definition_Linter(
			$pack_registry,
			null,
			$health_check,
			$bundle_registry,
			null
		);
		$lint_result = $linter->lint();
		$lint_errors   = isset( $lint_result['errors'] ) && \is_array( $lint_result['errors'] ) ? $lint_result['errors'] : array();
		$lint_warnings = isset( $lint_result['warnings'] ) && \is_array( $lint_result['warnings'] ) ? $lint_result['warnings'] : array();
		$summary_lint  = isset( $lint_result['summary'] ) && \is_array( $lint_result['summary'] ) ? $lint_result['summary'] : array( 'error_count' => count( $lint_errors ), 'warning_count' => count( $lint_warnings ) );

		return array(
			'lint_result'   => $lint_result,
			'health_result' => $health_result,
			'summary'       => array(
				'lint_errors'    => $summary_lint['error_count'] ?? count( $lint_errors ),
				'lint_warnings'  => $summary_lint['warning_count'] ?? count( $lint_warnings ),
				'health_errors'  => count( $health_errors ),
				'health_warnings' => count( $health_warnings ),
			),
		);
	}
}
