<?php
/**
 * Interface for Build Plan scoring services (industry-build-plan-scoring-contract; Prompt 431).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Enriches normalized Build Plan draft output with industry (and optionally subtype) metadata.
 */
interface Build_Plan_Scoring_Interface {

	/**
	 * Enriches normalized output with scoring metadata.
	 *
	 * @param array<string, mixed> $normalized_output Validated Build_Plan_Draft_Schema-shaped output.
	 * @param array<string, mixed> $context          Optional context (e.g. industry_profile, industry_primary_pack).
	 * @return array<string, mixed> Enriched output.
	 */
	public function enrich_output( array $normalized_output, array $context = array() ): array;
}
