<?php
/**
 * Internal benchmark harness for industry recommendation quality (Prompt 392).
 * Runs scenarios per launch industry; captures top recommendations and fit distribution for human review.
 * No live user data; deterministic; internal-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;

/**
 * Runs benchmark scenarios for industry recommendation quality. Produces a report structure for human evaluation.
 */
final class Industry_Recommendation_Benchmark_Service {

	/** Launch industries used in benchmark scenarios. */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/** Default cap for page templates and sections per scenario. */
	private const DEFAULT_CAP = 200;

	/** Default top-N to capture per scenario. */
	private const DEFAULT_TOP_N = 15;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Page_Template_Recommendation_Resolver|null */
	private $page_resolver;

	/** @var Industry_Section_Recommendation_Resolver|null */
	private $section_resolver;

	/** @var Page_Template_Repository_Interface|null */
	private $page_repo;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var callable(): array<int, array<string, mixed>>|null Optional section list provider for benchmark. */
	private $section_list_provider;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Page_Template_Recommendation_Resolver $page_resolver = null,
		?Industry_Section_Recommendation_Resolver $section_resolver = null,
		?Page_Template_Repository_Interface $page_repo = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?callable $section_list_provider = null
	) {
		$this->pack_registry           = $pack_registry;
		$this->page_resolver           = $page_resolver;
		$this->section_resolver        = $section_resolver;
		$this->page_repo               = $page_repo;
		$this->starter_bundle_registry = $starter_bundle_registry;
		$this->section_list_provider   = $section_list_provider;
	}

	/**
	 * Runs benchmark scenarios for each launch industry. Returns report suitable for human review.
	 *
	 * @param int $template_cap Max page templates to load per scenario (0 = use default).
	 * @param int $top_n        Top-N recommendation keys to capture per scenario (0 = use default).
	 * @return array{scenarios: array<int, array<string, mixed>>, run_at: string, launch_industries: array<int, string>}
	 */
	public function run( int $template_cap = 0, int $top_n = 0 ): array {
		$template_cap = $template_cap > 0 ? $template_cap : self::DEFAULT_CAP;
		$top_n        = $top_n > 0 ? $top_n : self::DEFAULT_TOP_N;
		$scenarios    = array();

		foreach ( self::LAUNCH_INDUSTRIES as $industry_key ) {
			$scenarios[] = $this->run_scenario( $industry_key, $template_cap, $top_n );
		}

		return array(
			'scenarios'         => $scenarios,
			'run_at'            => gmdate( 'c' ),
			'launch_industries' => self::LAUNCH_INDUSTRIES,
		);
	}

	/**
	 * Runs a single scenario for the given industry key.
	 *
	 * @param string $industry_key Launch industry key.
	 * @param int    $template_cap Cap for page templates.
	 * @param int    $top_n        Top-N keys to capture.
	 * @return array<string, mixed>
	 */
	private function run_scenario( string $industry_key, int $template_cap, int $top_n ): array {
		$profile      = array(
			'primary_industry_key'    => $industry_key,
			'secondary_industry_keys' => array(),
		);
		$primary_pack = $this->pack_registry !== null ? $this->pack_registry->get( $industry_key ) : null;

		$page_result = array(
			'top_template_keys' => array(),
			'fit_distribution'  => array(
				'recommended'      => 0,
				'neutral'          => 0,
				'discouraged'      => 0,
				'allowed_weak_fit' => 0,
			),
			'total_evaluated'   => 0,
		);
		if ( $this->page_resolver !== null && $this->page_repo !== null ) {
			$templates                      = $this->page_repo->list_all_definitions( $template_cap, 0 );
			$result                         = $this->page_resolver->resolve( $profile, $primary_pack, $templates, array() );
			$page_result['total_evaluated'] = count( $templates );
			$items                          = $result->get_items();
			foreach ( $items as $item ) {
				$status = $item['fit_classification'] ?? 'neutral';
				if ( isset( $page_result['fit_distribution'][ $status ] ) ) {
					++$page_result['fit_distribution'][ $status ];
				} else {
					$page_result['fit_distribution']['neutral'] = ( $page_result['fit_distribution']['neutral'] ?? 0 ) + 1;
				}
			}
			$ordered_keys                     = $result->get_ranked_keys();
			$page_result['top_template_keys'] = array_slice( array_values( $ordered_keys ), 0, $top_n );
		}

		$section_result = array(
			'top_section_keys' => array(),
			'fit_distribution' => array(
				'recommended'      => 0,
				'neutral'          => 0,
				'discouraged'      => 0,
				'allowed_weak_fit' => 0,
			),
			'total_evaluated'  => 0,
		);
		if ( $this->section_resolver !== null && $this->section_list_provider !== null ) {
			$sections                          = ( $this->section_list_provider )();
			$section_result['total_evaluated'] = count( $sections );
			$result                            = $this->section_resolver->resolve( $profile, $primary_pack, $sections, array() );
			$items                             = $result->get_items();
			foreach ( $items as $item ) {
				$status = $item['fit_classification'] ?? 'neutral';
				if ( isset( $section_result['fit_distribution'][ $status ] ) ) {
					++$section_result['fit_distribution'][ $status ];
				} else {
					$section_result['fit_distribution']['neutral'] = ( $section_result['fit_distribution']['neutral'] ?? 0 ) + 1;
				}
			}
			$ordered_keys                       = $result->get_ranked_keys();
			$section_result['top_section_keys'] = array_slice( array_values( $ordered_keys ), 0, $top_n );
		}

		$starter_bundles = array();
		if ( $this->starter_bundle_registry !== null ) {
			$bundles = $this->starter_bundle_registry->get_for_industry( $industry_key );
			foreach ( $bundles as $bundle ) {
				$key = $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '';
				if ( $key !== '' ) {
					$starter_bundles[] = $key;
				}
			}
		}

		$metadata_gaps = array();
		if ( $primary_pack !== null && $this->pack_registry !== null ) {
			$token_ref = isset( $primary_pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ) && is_string( $primary_pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
				? trim( $primary_pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
				: '';
			if ( $token_ref === '' ) {
				$metadata_gaps[] = 'no_token_preset_ref';
			}
		}

		return array(
			'industry_key'            => $industry_key,
			'pack_found'              => $primary_pack !== null,
			'page_recommendations'    => $page_result,
			'section_recommendations' => $section_result,
			'starter_bundle_keys'     => $starter_bundles,
			'metadata_gaps'           => $metadata_gaps,
		);
	}
}
