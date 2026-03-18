<?php
/**
 * Subtype-aware Build Plan scoring layer (industry-build-plan-scoring-contract.md; industry-subtype-ai-overlay-contract.md; Prompt 431).
 * Resolves subtype from profile and passes subtype_definition and subtype_extender to the industry scoring service so page recommendations reflect subtype nuance.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Page_Template_Recommendation_Extender;

/**
 * Wraps Industry_Build_Plan_Scoring_Service and injects subtype context when profile has a valid subtype.
 * Parent-industry scoring remains the base; subtype influence is additive and explainable.
 */
final class Industry_Subtype_Build_Plan_Scoring_Service implements Build_Plan_Scoring_Interface {

	/** @var Build_Plan_Scoring_Interface */
	private Build_Plan_Scoring_Interface $inner;

	/** @var Industry_Subtype_Resolver */
	private Industry_Subtype_Resolver $subtype_resolver;

	/** @var Industry_Profile_Repository */
	private Industry_Profile_Repository $profile_repo;

	/** @var Industry_Subtype_Page_Template_Recommendation_Extender */
	private Industry_Subtype_Page_Template_Recommendation_Extender $subtype_extender;

	public function __construct(
		Build_Plan_Scoring_Interface $inner,
		Industry_Subtype_Resolver $subtype_resolver,
		Industry_Profile_Repository $profile_repo,
		Industry_Subtype_Page_Template_Recommendation_Extender $subtype_extender
	) {
		$this->inner            = $inner;
		$this->subtype_resolver = $subtype_resolver;
		$this->profile_repo     = $profile_repo;
		$this->subtype_extender = $subtype_extender;
	}

	/**
	 * Enriches normalized output with industry (and subtype) metadata. Resolves subtype from profile and passes subtype context to inner service.
	 *
	 * @param array<string, mixed> $normalized_output Validated Build_Plan_Draft_Schema-shaped output.
	 * @param array<string, mixed> $context          Optional: industry_profile, industry_primary_pack; subtype_definition and subtype_extender are added when valid subtype.
	 * @return array<string, mixed> Same structure with additive keys on page-related records.
	 */
	public function enrich_output( array $normalized_output, array $context = array() ): array {
		$profile  = isset( $context[ Industry_Build_Plan_Scoring_Service::CONTEXT_INDUSTRY_PROFILE ] ) && is_array( $context[ Industry_Build_Plan_Scoring_Service::CONTEXT_INDUSTRY_PROFILE ] )
			? $context[ Industry_Build_Plan_Scoring_Service::CONTEXT_INDUSTRY_PROFILE ]
			: $this->profile_repo->get_profile();
		$resolved = $this->subtype_resolver->resolve_from_profile( $profile );
		if ( ! empty( $resolved['has_valid_subtype'] ) && is_array( $resolved['resolved_subtype'] ?? null ) ) {
			$context['subtype_definition'] = $resolved['resolved_subtype'];
			$context['subtype_extender']   = $this->subtype_extender;
		}
		return $this->inner->enrich_output( $normalized_output, $context );
	}
}
