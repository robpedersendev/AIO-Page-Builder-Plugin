<?php
/**
 * Build Plan generation from normalized AI output and local context (spec §30.2, §30.3, §28.14).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Converts validated normalized output plus local context into a structured Build Plan.
 * Only normalized output is used; never raw provider output. Persists plan and returns result with omitted report.
 */
final class Build_Plan_Generator {

	/** Step type to title. */
	private const STEP_TITLES = array(
		Build_Plan_Schema::STEP_TYPE_OVERVIEW             => 'Overview',
		Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES => 'Existing page changes',
		Build_Plan_Schema::STEP_TYPE_NEW_PAGES            => 'New pages',
		Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW       => 'Hierarchy & flow',
		Build_Plan_Schema::STEP_TYPE_NAVIGATION           => 'Navigation',
		Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS        => 'Design tokens',
		Build_Plan_Schema::STEP_TYPE_SEO                  => 'SEO',
		Build_Plan_Schema::STEP_TYPE_CONFIRMATION         => 'Confirm',
		Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK        => 'Logs & rollback',
	);

	/** @var Build_Plan_Repository */
	private $repository;

	/** @var Build_Plan_Item_Generator */
	private $item_generator;

	/** @var Industry_Build_Plan_Scoring_Service|null */
	private $scoring_service;

	public function __construct(
		Build_Plan_Repository $repository,
		Build_Plan_Item_Generator $item_generator,
		?Industry_Build_Plan_Scoring_Service $scoring_service = null
	) {
		$this->repository      = $repository;
		$this->item_generator  = $item_generator;
		$this->scoring_service = $scoring_service;
	}

	/**
	 * Generates a Build Plan from validated normalized output and context. Persists and returns result.
	 *
	 * @param array<string, mixed> $normalized_output Validated normalized output (run_summary, site_purpose, existing_page_changes, etc.).
	 * @param string              $ai_run_ref        Source AI run id.
	 * @param string              $normalized_output_ref Reference to stored normalized output (e.g. run_id:normalized_output).
	 * @param array<string, mixed> $context           Optional: profile_context_ref, crawl_snapshot_ref, registry_snapshot_ref.
	 * @return Plan_Generation_Result
	 */
	public function generate( array $normalized_output, string $ai_run_ref, string $normalized_output_ref, array $context = array() ): Plan_Generation_Result {
		$errors = $this->validate_normalized_output( $normalized_output );
		if ( $errors !== array() ) {
			return Plan_Generation_Result::failure( $errors );
		}

		if ( $this->scoring_service !== null ) {
			$normalized_output = $this->scoring_service->enrich_output( $normalized_output, $context );
		}

		$plan_id = 'aio-plan-' . ( function_exists( 'wp_generate_uuid4' ) ? \wp_generate_uuid4() : uniqid( 'plan-', true ) );

		$run_summary   = $normalized_output[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ?? array();
		$site_purpose  = $normalized_output[ Build_Plan_Draft_Schema::KEY_SITE_PURPOSE ] ?? array();
		$site_structure = $normalized_output[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] ?? array();

		$plan_title  = $this->derive_plan_title( $run_summary );
		$plan_summary = is_array( $run_summary ) && isset( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ] )
			? (string) $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ]
			: '';
		$site_purpose_summary  = $this->derive_site_purpose_summary( $site_purpose );
		$site_flow_summary     = is_array( $site_structure ) && isset( $site_structure['navigation_summary'] )
			? (string) $site_structure['navigation_summary']
			: '';

		$built = $this->build_steps_in_order( $normalized_output, $plan_id, array() );
		$steps = $built['steps'];
		$all_omitted = $built['omitted'];

		// Confirmation (finalization) step.
		$steps[] = array(
			Build_Plan_Item_Schema::KEY_STEP_ID   => $plan_id . '_step_confirm',
			Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_CONFIRMATION,
			Build_Plan_Item_Schema::KEY_TITLE     => self::STEP_TITLES[ Build_Plan_Schema::STEP_TYPE_CONFIRMATION ],
			Build_Plan_Item_Schema::KEY_ORDER     => count( $steps ),
			Build_Plan_Item_Schema::KEY_ITEMS     => array(),
		);

		// Logs, history, and rollback step (shell only; no execution).
		$steps[] = array(
			Build_Plan_Item_Schema::KEY_STEP_ID   => $plan_id . '_step_logs_rollback',
			Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK,
			Build_Plan_Item_Schema::KEY_TITLE     => self::STEP_TITLES[ Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK ],
			Build_Plan_Item_Schema::KEY_ORDER     => count( $steps ),
			Build_Plan_Item_Schema::KEY_ITEMS     => array(),
		);

		$warnings   = isset( $normalized_output[ Build_Plan_Draft_Schema::KEY_WARNINGS ] ) && is_array( $normalized_output[ Build_Plan_Draft_Schema::KEY_WARNINGS ] )
			? $normalized_output[ Build_Plan_Draft_Schema::KEY_WARNINGS ]
			: array();
		$assumptions = isset( $normalized_output[ Build_Plan_Draft_Schema::KEY_ASSUMPTIONS ] ) && is_array( $normalized_output[ Build_Plan_Draft_Schema::KEY_ASSUMPTIONS ] )
			? $normalized_output[ Build_Plan_Draft_Schema::KEY_ASSUMPTIONS ]
			: array();
		$confidence = isset( $normalized_output[ Build_Plan_Draft_Schema::KEY_CONFIDENCE ] ) && is_array( $normalized_output[ Build_Plan_Draft_Schema::KEY_CONFIDENCE ] )
			? $normalized_output[ Build_Plan_Draft_Schema::KEY_CONFIDENCE ]
			: array( 'overall' => 'medium', 'planning_mode' => 'mixed' );

		$definition = array(
			Build_Plan_Schema::KEY_PLAN_ID               => $plan_id,
			Build_Plan_Schema::KEY_STATUS                => Build_Plan_Statuses::ROOT_PENDING_REVIEW,
			Build_Plan_Schema::KEY_AI_RUN_REF            => $ai_run_ref,
			Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF => $normalized_output_ref,
			Build_Plan_Schema::KEY_PLAN_TITLE            => $plan_title,
			Build_Plan_Schema::KEY_PLAN_SUMMARY          => $plan_summary,
			Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY  => $site_purpose_summary,
			Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY     => $site_flow_summary,
			Build_Plan_Schema::KEY_STEPS                 => $steps,
			Build_Plan_Schema::KEY_CREATED_AT            => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Build_Plan_Schema::KEY_APPROVAL_DENIAL_STATE  => 'pending',
			Build_Plan_Schema::KEY_REMAINING_WORK_STATUS => 'review',
			Build_Plan_Schema::KEY_WARNINGS              => $warnings,
			Build_Plan_Schema::KEY_ASSUMPTIONS           => $assumptions,
			Build_Plan_Schema::KEY_CONFIDENCE            => $confidence,
			Build_Plan_Schema::KEY_SCHEMA_VERSION        => Build_Plan_Schema::SCHEMA_VERSION_DEFAULT,
		);
		if ( ! empty( $context['profile_context_ref'] ) ) {
			$definition[ Build_Plan_Schema::KEY_PROFILE_CONTEXT_REF ] = (string) $context['profile_context_ref'];
		}
		if ( ! empty( $context['crawl_snapshot_ref'] ) ) {
			$definition[ Build_Plan_Schema::KEY_CRAWL_SNAPSHOT_REF ] = (string) $context['crawl_snapshot_ref'];
		}
		if ( ! empty( $context['registry_snapshot_ref'] ) ) {
			$definition[ Build_Plan_Schema::KEY_REGISTRY_SNAPSHOT_REF ] = (string) $context['registry_snapshot_ref'];
		}
		if ( ! empty( $context['source_starter_bundle_key'] ) && is_string( $context['source_starter_bundle_key'] ) ) {
			$definition[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] = trim( $context['source_starter_bundle_key'] );
		}

		$post_id = $this->repository->save( array( 'plan_definition' => $definition ) );
		if ( $post_id === 0 ) {
			return Plan_Generation_Result::failure( array( 'Failed to persist Build Plan.' ) );
		}

		$omitted_report = Omitted_Recommendation_Report::report( $all_omitted );
		return new Plan_Generation_Result( true, $plan_id, $post_id, $definition, $omitted_report, array() );
	}

	/**
	 * Builds steps in schema order: overview, existing_page_changes, new_pages, hierarchy_flow, navigation, design_tokens, seo, confirmation.
	 *
	 * @param array<string, mixed> $normalized_output
	 * @param string               $plan_id
	 * @param array<int, array<string, mixed>> $omitted_so_far
	 * @return array{steps: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function build_steps_in_order( array $normalized_output, string $plan_id, array $omitted_so_far ): array {
		$run_summary   = $normalized_output[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ?? array();
		$site_purpose  = $normalized_output[ Build_Plan_Draft_Schema::KEY_SITE_PURPOSE ] ?? array();
		$site_structure = $normalized_output[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] ?? array();
		$plan_summary  = is_array( $run_summary ) && isset( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ] )
			? (string) $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ]
			: '';
		$site_flow_summary = is_array( $site_structure ) && isset( $site_structure['navigation_summary'] )
			? (string) $site_structure['navigation_summary']
			: '';

		$all_omitted = $omitted_so_far;
		$steps = array();
		$order = 0;

		$overview_item = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => $plan_id . '_overview_0',
			Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE,
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
				'summary_text'       => $plan_summary,
				'planning_mode'      => is_array( $run_summary ) ? (string) ( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE ] ?? 'mixed' ) : 'mixed',
				'overall_confidence' => is_array( $run_summary ) ? (string) ( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE ] ?? 'medium' ) : 'medium',
			),
			Build_Plan_Item_Schema::KEY_STATUS   => Build_Plan_Item_Statuses::PENDING,
		);
		$steps[] = array(
			Build_Plan_Item_Schema::KEY_STEP_ID    => $plan_id . '_step_overview',
			Build_Plan_Item_Schema::KEY_STEP_TYPE  => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
			Build_Plan_Item_Schema::KEY_TITLE      => self::STEP_TITLES[ Build_Plan_Schema::STEP_TYPE_OVERVIEW ],
			Build_Plan_Item_Schema::KEY_ORDER      => $order++,
			Build_Plan_Item_Schema::KEY_ITEMS      => array( $overview_item ),
		);

		// Order per schema §3.1: existing, new, hierarchy_flow, navigation, design_tokens, seo.
		$sections_order = array(
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES,
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE,
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN,
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS,
		);
		$step_type_by_section = array(
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE   => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN     => Build_Plan_Schema::STEP_TYPE_NAVIGATION,
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS,
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS   => Build_Plan_Schema::STEP_TYPE_SEO,
		);

		foreach ( $sections_order as $idx => $section_key ) {
			// Insert hierarchy step after new_pages (index 1).
			if ( $idx === 2 ) {
				$hierarchy_items = $this->derive_hierarchy_items( $site_structure, $plan_id );
				$steps[] = array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => $plan_id . '_step_hierarchy',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW,
					Build_Plan_Item_Schema::KEY_TITLE     => self::STEP_TITLES[ Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW ],
					Build_Plan_Item_Schema::KEY_ORDER     => $order++,
					Build_Plan_Item_Schema::KEY_ITEMS     => $hierarchy_items,
				);
			}

			$records = isset( $normalized_output[ $section_key ] ) && is_array( $normalized_output[ $section_key ] )
				? $normalized_output[ $section_key ]
				: array();
			$result = $this->item_generator->generate_for_section( $section_key, $records, $plan_id );
			$all_omitted = array_merge( $all_omitted, $result['omitted'] );
			$step_type = $step_type_by_section[ $section_key ];
			$steps[] = array(
				Build_Plan_Item_Schema::KEY_STEP_ID   => $plan_id . '_step_' . $step_type,
				Build_Plan_Item_Schema::KEY_STEP_TYPE => $step_type,
				Build_Plan_Item_Schema::KEY_TITLE     => self::STEP_TITLES[ $step_type ],
				Build_Plan_Item_Schema::KEY_ORDER     => $order++,
				Build_Plan_Item_Schema::KEY_ITEMS     => $result['items'],
			);
		}

		return array( 'steps' => $steps, 'omitted' => $all_omitted );
	}

	/**
	 * @param array<string, mixed> $normalized_output
	 * @return array<int, string>
	 */
	private function validate_normalized_output( array $normalized_output ): array {
		$errors = array();
		foreach ( Build_Plan_Draft_Schema::REQUIRED_TOP_LEVEL_KEYS as $key ) {
			if ( ! array_key_exists( $key, $normalized_output ) ) {
				$errors[] = "Normalized output missing required key: {$key}";
			}
		}
		if ( $errors !== array() ) {
			return $errors;
		}
		$run_summary = $normalized_output[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ?? null;
		if ( ! is_array( $run_summary ) ) {
			$errors[] = 'run_summary must be an array';
		}
		return $errors;
	}

	/**
	 * @param array<string, mixed> $run_summary
	 * @return string
	 */
	private function derive_plan_title( array $run_summary ): string {
		$text = isset( $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ] )
			? (string) $run_summary[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ]
			: '';
		if ( $text !== '' && strlen( $text ) <= 255 ) {
			return $text;
		}
		if ( $text !== '' ) {
			return substr( $text, 0, 252 ) . '...';
		}
		return 'Build Plan – ' . gmdate( 'Y-m-d' );
	}

	/**
	 * @param array<string, mixed>|mixed $site_purpose
	 * @return string
	 */
	private function derive_site_purpose_summary( $site_purpose ): string {
		if ( ! is_array( $site_purpose ) ) {
			return '';
		}
		if ( isset( $site_purpose['summary'] ) && is_string( $site_purpose['summary'] ) ) {
			return substr( $site_purpose['summary'], 0, 1024 );
		}
		return '';
	}

	/**
	 * @param array<string, mixed>|mixed $site_structure
	 * @param string                     $plan_id
	 * @return array<int, array<string, mixed>>
	 */
	private function derive_hierarchy_items( $site_structure, string $plan_id ): array {
		if ( ! is_array( $site_structure ) ) {
			return array();
		}
		$items = array();
		if ( ! empty( $site_structure['recommended_top_level_pages'] ) && is_array( $site_structure['recommended_top_level_pages'] ) ) {
			$items[] = array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => $plan_id . '_hierarchy_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array( 'recommended_top_level_pages' => $site_structure['recommended_top_level_pages'] ),
				Build_Plan_Item_Schema::KEY_STATUS   => Build_Plan_Item_Statuses::PENDING,
			);
		}
		return $items;
	}
}