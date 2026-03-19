<?php
/**
 * Provider interface for scaffold completeness report data (Prompt 565 testability).
 * Implemented by Industry_Scaffold_Completeness_Report_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Allows promotion-readiness and other consumers to depend on report shape without coupling to the final class.
 */
interface Industry_Scaffold_Completeness_Report_Provider_Interface {

	/**
	 * Returns scaffold completeness report with at least 'scaffold_results' (list), 'readable_summary', 'warnings', 'generated_at'.
	 *
	 * @param array<string, mixed> $options Report options (e.g. scaffold_industry_keys, include_draft_packs).
	 * @return array{scaffold_results: array<int, array>, readable_summary: string, warnings: list, generated_at: string}
	 */
	public function generate_report( array $options = array() ): array;
}
