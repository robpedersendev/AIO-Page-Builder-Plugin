<?php
/**
 * Bounded what-if simulation for pack, subtype, and bundle selections (Prompt 466, industry-what-if-simulation-contract).
 * Previews recommendation/comparison differences under alternate industry config without mutating live state.
 * Admin-only; read-only; no persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Runs a single what-if simulation: build simulated profile, validate refs, optionally run comparison. No live state mutation.
 */
final class Industry_What_If_Simulation_Service {

	public const PARAM_ALTERNATE_PRIMARY   = 'alternate_primary_industry_key';
	public const PARAM_ALTERNATE_SUBTYPE  = 'alternate_subtype_key';
	public const PARAM_ALTERNATE_BUNDLE   = 'alternate_starter_bundle_key';
	public const PARAM_ALTERNATE_CONVERSION_GOAL = 'alternate_conversion_goal_key';

	/** @var Industry_Profile_Repository */
	private $profile_repo;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $bundle_registry;

	/** @var Industry_Subtype_Comparison_Service|null */
	private $comparison_service;

	public function __construct(
		Industry_Profile_Repository $profile_repo,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null,
		?Industry_Starter_Bundle_Registry $bundle_registry = null,
		?Industry_Subtype_Comparison_Service $comparison_service = null
	) {
		$this->profile_repo       = $profile_repo;
		$this->pack_registry      = $pack_registry;
		$this->subtype_registry   = $subtype_registry;
		$this->bundle_registry    = $bundle_registry;
		$this->comparison_service = $comparison_service;
	}

	/**
	 * Runs one what-if simulation. Does not mutate live state. Returns bounded result with validity, invalid_refs, and optional comparison data.
	 *
	 * @param array<string, mixed> $params Optional PARAM_ALTERNATE_* keys; omitted or null = keep live value; empty string = clear that slot for simulation.
	 * @return array{
	 *   valid: bool,
	 *   invalid_refs: list<array{type: string, key: string}>,
	 *   simulated_profile_summary: array{primary: string, subtype: string, bundle: string},
	 *   live_profile_summary: array{primary: string, subtype: string, bundle: string},
	 *   comparison_simulated: array<string, mixed>|null,
	 *   comparison_live: array<string, mixed>|null,
	 *   warnings: list<string>
	 * }
	 */
	public function run_simulation( array $params = array() ): array {
		$live   = $this->profile_repo->get_profile();
		$simulated = $this->build_simulated_profile( $live, $params );
		$invalid_refs = $this->validate_simulated_refs( $simulated, $params );
		$warnings = array();

		$live_summary = $this->profile_summary( $live );
		$sim_summary  = $this->profile_summary( $simulated );

		$comparison_simulated = null;
		$comparison_live     = null;
		$valid = empty( $invalid_refs );
		if ( $valid && $this->comparison_service !== null ) {
			$comparison_simulated = $this->comparison_service->get_comparison(
				$sim_summary['primary'],
				$sim_summary['subtype']
			);
			$comparison_live = $this->comparison_service->get_comparison(
				$live_summary['primary'],
				$live_summary['subtype']
			);
			if ( ! empty( $comparison_simulated['pack_found'] ) && $sim_summary['primary'] !== '' && $this->pack_registry !== null ) {
				$pack = $this->pack_registry->get( $sim_summary['primary'] );
				if ( $pack !== null && ( ( $pack['status'] ?? '' ) === 'deprecated' ) ) {
					$warnings[] = 'simulated_primary_is_deprecated';
				}
			}
		}

		return array(
			'valid'                     => $valid,
			'invalid_refs'              => $invalid_refs,
			'simulated_profile_summary'  => $sim_summary,
			'live_profile_summary'      => $live_summary,
			'comparison_simulated'      => $comparison_simulated,
			'comparison_live'           => $comparison_live,
			'warnings'                  => $warnings,
		);
	}

	/**
	 * Builds simulated profile from live with overrides. No validation; no persistence.
	 *
	 * @param array<string, mixed> $live
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function build_simulated_profile( array $live, array $params ): array {
		$out = $live;
		if ( array_key_exists( self::PARAM_ALTERNATE_PRIMARY, $params ) ) {
			$out[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = is_string( $params[ self::PARAM_ALTERNATE_PRIMARY ] )
				? trim( $params[ self::PARAM_ALTERNATE_PRIMARY ] )
				: '';
			$out[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] = array();
		}
		if ( array_key_exists( self::PARAM_ALTERNATE_SUBTYPE, $params ) ) {
			$v = $params[ self::PARAM_ALTERNATE_SUBTYPE ];
			$out[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] = is_string( $v ) ? trim( $v ) : '';
			$out[ Industry_Profile_Schema::FIELD_SUBTYPE ] = $out[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ];
		}
		if ( array_key_exists( self::PARAM_ALTERNATE_BUNDLE, $params ) ) {
			$v = $params[ self::PARAM_ALTERNATE_BUNDLE ];
			$out[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] = is_string( $v ) ? trim( $v ) : '';
		}
		if ( array_key_exists( self::PARAM_ALTERNATE_CONVERSION_GOAL, $params ) ) {
			$v = $params[ self::PARAM_ALTERNATE_CONVERSION_GOAL ];
			$goal = is_string( $v ) ? trim( $v ) : '';
			if ( $goal === '' || ( strlen( $goal ) <= 64 && preg_match( '#^[a-z0-9_-]+$#', $goal ) ) ) {
				$out[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] = $goal;
			}
		}
		return $out;
	}

	/**
	 * Validates simulated profile refs against registries. Returns list of invalid refs.
	 *
	 * @param array<string, mixed> $simulated
	 * @param array<string, mixed> $params
	 * @return list<array{type: string, key: string}>
	 */
	private function validate_simulated_refs( array $simulated, array $params ): array {
		$invalid = array();
		$primary = trim( (string) ( $simulated[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '' ) );
		$subtype = trim( (string) ( $simulated[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ?? '' ) );
		$bundle  = trim( (string) ( $simulated[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ?? '' ) );

		if ( $primary !== '' && $this->pack_registry !== null && $this->pack_registry->get( $primary ) === null ) {
			$invalid[] = array( 'type' => 'primary_industry', 'key' => $primary );
		}
		if ( $subtype !== '' ) {
			if ( $this->subtype_registry === null ) {
				$invalid[] = array( 'type' => 'subtype', 'key' => $subtype );
			} else {
				$def = $this->subtype_registry->get( $subtype );
				if ( $def === null ) {
					$invalid[] = array( 'type' => 'subtype', 'key' => $subtype );
				} else {
					$parent = trim( (string) ( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
					if ( $parent !== $primary ) {
						$invalid[] = array( 'type' => 'subtype_parent_mismatch', 'key' => $subtype );
					}
				}
			}
		}
		if ( $bundle !== '' && $this->bundle_registry !== null && $this->bundle_registry->get( $bundle ) === null ) {
			$invalid[] = array( 'type' => 'starter_bundle', 'key' => $bundle );
		}
		return $invalid;
	}

	/**
	 * @param array<string, mixed> $profile
	 * @return array{primary: string, subtype: string, bundle: string, goal: string}
	 */
	private function profile_summary( array $profile ): array {
		return array(
			'primary'  => trim( (string) ( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '' ) ),
			'subtype'  => trim( (string) ( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ?? '' ) ),
			'bundle'   => trim( (string) ( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ?? '' ) ),
			'goal'     => trim( (string) ( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ?? '' ) ),
		);
	}
}
