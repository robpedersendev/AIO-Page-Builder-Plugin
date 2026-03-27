<?php
/**
 * Minimal normalized AI output shape for an onboarding-linked shell Build Plan before a real AI run exists.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;

/**
 * Produces a valid {@see Build_Plan_Draft_Schema} payload with empty sections so {@see Build_Plan_Generator} can persist a placeholder plan.
 */
final class Onboarding_Shell_Normalized_Output {

	/**
	 * Valid minimal normalized output for shell plan generation.
	 *
	 * @return array<string, mixed>
	 */
	public static function minimal_array(): array {
		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => '1',
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY      => array(
				Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT       => __( 'Onboarding — planning not run yet. Complete the wizard and request an AI plan to replace this placeholder.', 'aio-page-builder' ),
				Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE      => 'mixed',
				Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE => 'medium',
			),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array( 'summary' => '' ),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array(
				'navigation_summary'          => '',
				'recommended_top_level_pages' => array(),
			),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(
				array(
					'current_page_url'   => '/',
					'current_page_title' => 'Home',
					'action'             => 'keep',
					'reason'             => 'Placeholder until AI planning runs.',
					'risk_level'         => 'low',
					'confidence'         => 'medium',
				),
			),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE => array(),
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => array(),
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array(
				'overall'       => 'medium',
				'planning_mode' => 'mixed',
			),
		);
	}
}
