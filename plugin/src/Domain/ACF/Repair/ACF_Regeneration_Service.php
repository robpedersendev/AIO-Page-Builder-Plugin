<?php
/**
 * Controlled ACF field group and page-assignment regeneration/repair (spec §20, §20.13–20.15; Prompt 222).
 * Rebuilds code-defined field groups and page-assignment mappings from registry and blueprint authority.
 * Dry-run analysis, selective scope, mismatch detection; no destructive cleanup unless explicitly supported.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Repair;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service_Interface;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar_Interface;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service_Interface;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository_Interface;

/**
 * Builds regeneration plans (dry-run) and executes repair from registry authority.
 */
final class ACF_Regeneration_Service {

	/** Plugin group key prefix for identifying our groups. */
	private const GROUP_KEY_PREFIX = 'group_aio_';

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var ACF_Group_Registrar_Interface */
	private ACF_Group_Registrar_Interface $group_registrar;

	/** @var Page_Field_Group_Assignment_Service_Interface */
	private Page_Field_Group_Assignment_Service_Interface $page_assignment_service;

	/** @var Assignment_Map_Service_Interface */
	private Assignment_Map_Service_Interface $assignment_map;

	/** @var Section_Template_Repository_Interface */
	private Section_Template_Repository_Interface $section_repository;

	/** @var Page_Template_Repository_Interface */
	private Page_Template_Repository_Interface $page_template_repository;

	/** @var ACF_Local_JSON_Mirror_Service|null Optional mirror refresh after repair (Prompt 224). */
	private ?ACF_Local_JSON_Mirror_Service $mirror_service = null;

	/** @var string|null Path to refresh mirror after successful repair when mirror_service is set. */
	private ?string $mirror_refresh_path = null;

	public function __construct(
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		ACF_Group_Registrar_Interface $group_registrar,
		Page_Field_Group_Assignment_Service_Interface $page_assignment_service,
		Assignment_Map_Service_Interface $assignment_map,
		Section_Template_Repository_Interface $section_repository,
		Page_Template_Repository_Interface $page_template_repository,
		?ACF_Local_JSON_Mirror_Service $mirror_service = null,
		?string $mirror_refresh_path = null
	) {
		$this->blueprint_service        = $blueprint_service;
		$this->group_registrar          = $group_registrar;
		$this->page_assignment_service  = $page_assignment_service;
		$this->assignment_map           = $assignment_map;
		$this->section_repository       = $section_repository;
		$this->page_template_repository = $page_template_repository;
		$this->mirror_service           = $mirror_service;
		$this->mirror_refresh_path      = $mirror_refresh_path !== '' ? $mirror_refresh_path : null;
	}

	/**
	 * Builds a regeneration plan (dry-run analysis or executable). Detects missing groups, version-stale groups, and page-assignment repair candidates.
	 *
	 * @param bool   $dry_run                 When true, plan is for analysis only; execute_repair will not mutate.
	 * @param string $scope                   One of ACF_Regeneration_Plan::SCOPE_*.
	 * @param array  $options                 Optional. Keys: section_family_key (string), page_template_family_key (string), include_page_assignments (bool, default true).
	 * @return ACF_Regeneration_Plan
	 */
	public function build_plan( bool $dry_run, string $scope, array $options = array() ): ACF_Regeneration_Plan {
		$section_family_key       = isset( $options['section_family_key'] ) ? (string) $options['section_family_key'] : null;
		$page_template_family_key = isset( $options['page_template_family_key'] ) ? (string) $options['page_template_family_key'] : null;
		$include_page_assignments = ! isset( $options['include_page_assignments'] ) || ! empty( $options['include_page_assignments'] );

		$expected_section_keys = $this->resolve_expected_section_keys( $scope, $section_family_key, $page_template_family_key );
		$current_groups        = $this->get_plugin_field_groups();
		$mismatches            = $this->compute_field_group_mismatches( $expected_section_keys, $current_groups );
		$candidates            = $include_page_assignments ? $this->gather_page_assignment_candidates() : array();
		$refused_cleanup       = array( 'Destructive cleanup not supported; only regeneration from registry (spec §20.15).' );

		return new ACF_Regeneration_Plan(
			$dry_run,
			$scope,
			$section_family_key !== '' ? $section_family_key : null,
			$page_template_family_key !== '' ? $page_template_family_key : null,
			$include_page_assignments,
			$mismatches,
			$candidates,
			$refused_cleanup
		);
	}

	/**
	 * Executes repair from the plan. When plan is dry_run, returns a result with zero mutations and plan summary in warnings.
	 *
	 * @param ACF_Regeneration_Plan $plan
	 * @return ACF_Regeneration_Result
	 */
	public function execute_repair( ACF_Regeneration_Plan $plan ): ACF_Regeneration_Result {
		if ( $plan->is_dry_run() ) {
			return $this->result_for_dry_run( $plan );
		}

		$section_keys_to_register = array();
		foreach ( $plan->get_field_group_mismatches() as $m ) {
			$status = $m['status'] ?? '';
			if ( $status === ACF_Regeneration_Plan::MISMATCH_STATUS_MISSING || $status === ACF_Regeneration_Plan::MISMATCH_STATUS_VERSION_STALE ) {
				$section_keys_to_register[] = $m['section_key'];
			}
		}
		$section_keys_to_register = array_values( array_unique( $section_keys_to_register ) );

		$groups_regenerated = 0;
		$groups_skipped     = array();
		foreach ( $section_keys_to_register as $section_key ) {
			$blueprint = $this->blueprint_service->get_blueprint_for_section( $section_key );
			if ( $blueprint === null ) {
				$groups_skipped[] = Field_Key_Generator::group_key( $section_key );
				continue;
			}
			if ( $this->group_registrar->register_blueprint( $blueprint ) ) {
				++$groups_regenerated;
			} else {
				$groups_skipped[] = Field_Key_Generator::group_key( $section_key );
			}
		}

		$page_repaired = 0;
		$page_failed   = 0;
		$page_skipped  = 0;
		if ( $plan->get_include_page_assignments() ) {
			foreach ( $plan->get_page_assignment_repair_candidates() as $c ) {
				$page_id = (int) ( $c['page_id'] ?? 0 );
				if ( $page_id <= 0 ) {
					++$page_skipped;
					continue;
				}
				$out = $this->page_assignment_service->reassign_from_stored_source( $page_id );
				if ( ( $out['assigned'] ?? 0 ) > 0 || empty( $out['errors'] ) ) {
					++$page_repaired;
				} else {
					++$page_failed;
				}
			}
		}

		$missing_count       = 0;
		$version_stale_count = 0;
		foreach ( $plan->get_field_group_mismatches() as $m ) {
			if ( ( $m['status'] ?? '' ) === ACF_Regeneration_Plan::MISMATCH_STATUS_MISSING ) {
				++$missing_count;
			} elseif ( ( $m['status'] ?? '' ) === ACF_Regeneration_Plan::MISMATCH_STATUS_VERSION_STALE ) {
				++$version_stale_count;
			}
		}

		if ( $this->mirror_service !== null && $this->mirror_refresh_path !== null && ( $groups_regenerated > 0 || $page_repaired > 0 ) ) {
			$this->mirror_service->generate_mirror_to_directory( $this->mirror_refresh_path );
		}

		return new ACF_Regeneration_Result(
			$groups_regenerated,
			$groups_skipped,
			$page_repaired,
			$page_failed,
			array(),
			array(),
			array(
				'missing'       => $missing_count,
				'version_stale' => $version_stale_count,
				'repaired'      => $groups_regenerated,
			),
			array(
				'repaired' => $page_repaired,
				'failed'   => $page_failed,
				'skipped'  => $page_skipped,
			)
		);
	}

	/**
	 * Resolves section keys to consider for field group repair based on scope.
	 *
	 * @param string      $scope
	 * @param string|null $section_family_key
	 * @param string|null $page_template_family_key
	 * @return list<string>
	 */
	private function resolve_expected_section_keys( string $scope, ?string $section_family_key, ?string $page_template_family_key ): array {
		if ( $scope === ACF_Regeneration_Plan::SCOPE_SECTION_FAMILY && $section_family_key !== null && $section_family_key !== '' ) {
			$definitions = $this->section_repository->list_all_definitions( 9999, 0 );
			$keys        = array();
			foreach ( $definitions as $def ) {
				$fam = (string) ( $def['variation_family_key'] ?? '' );
				if ( $fam === $section_family_key ) {
					$k = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
					if ( $k !== '' ) {
						$keys[] = $k;
					}
				}
			}
			return array_values( array_unique( $keys ) );
		}

		if ( $scope === ACF_Regeneration_Plan::SCOPE_PAGE_TEMPLATE_FAMILY && $page_template_family_key !== null && $page_template_family_key !== '' ) {
			$definitions  = $this->page_template_repository->list_all_definitions( 9999, 0 );
			$section_keys = array();
			foreach ( $definitions as $def ) {
				$fam = (string) ( $def[ Page_Template_Schema::FIELD_TEMPLATE_FAMILY ] ?? '' );
				if ( $fam !== $page_template_family_key ) {
					continue;
				}
				$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
				foreach ( $ordered as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
					if ( $sk !== '' ) {
						$section_keys[] = $sk;
					}
				}
			}
			return array_values( array_unique( $section_keys ) );
		}

		// Full: all sections that have a blueprint.
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$keys       = array();
		foreach ( $blueprints as $bp ) {
			$k = (string) ( $bp[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $k !== '' ) {
				$keys[] = $k;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Returns currently registered ACF field groups that belong to the plugin (key prefix group_aio_).
	 *
	 * @return array<string, array> Map of group_key => group array (with key, _aio_section_key, _aio_section_version when present).
	 */
	private function get_plugin_field_groups(): array {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array();
		}
		$all = acf_get_field_groups();
		if ( ! is_array( $all ) ) {
			return array();
		}
		$out = array();
		foreach ( $all as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			$key = (string) ( $group['key'] ?? '' );
			if ( $key !== '' && str_starts_with( $key, self::GROUP_KEY_PREFIX ) ) {
				$out[ $key ] = $group;
			}
		}
		return $out;
	}

	/**
	 * Computes mismatch list: missing, version_stale, or ok.
	 *
	 * @param list<string>         $expected_section_keys
	 * @param array<string, array> $current_groups
	 * @return list<array{section_key: string, group_key: string, status: string}>
	 */
	private function compute_field_group_mismatches( array $expected_section_keys, array $current_groups ): array {
		$out = array();
		foreach ( $expected_section_keys as $section_key ) {
			$group_key        = Field_Key_Generator::group_key( $section_key );
			$current          = $current_groups[ $group_key ] ?? null;
			$blueprint        = $this->blueprint_service->get_blueprint_for_section( $section_key );
			$expected_version = $blueprint !== null ? (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '1' ) : '';

			if ( $current === null ) {
				$out[] = array(
					'section_key' => $section_key,
					'group_key'   => $group_key,
					'status'      => ACF_Regeneration_Plan::MISMATCH_STATUS_MISSING,
				);
				continue;
			}
			$current_version = (string) ( $current['_aio_section_version'] ?? '' );
			if ( $expected_version !== '' && $current_version !== $expected_version ) {
				$out[] = array(
					'section_key' => $section_key,
					'group_key'   => $group_key,
					'status'      => ACF_Regeneration_Plan::MISMATCH_STATUS_VERSION_STALE,
				);
			} else {
				$out[] = array(
					'section_key' => $section_key,
					'group_key'   => $group_key,
					'status'      => ACF_Regeneration_Plan::MISMATCH_STATUS_OK,
				);
			}
		}
		return $out;
	}

	/**
	 * Gathers page assignment repair candidates from assignment map (pages with template or composition source).
	 *
	 * @return list<array{page_id: int, type: string, key: string}>
	 */
	private function gather_page_assignment_candidates(): array {
		$candidates    = array();
		$template_rows = $this->assignment_map->list_by_type( Assignment_Types::PAGE_TEMPLATE, 500, 0 );
		foreach ( $template_rows as $row ) {
			$source = (string) ( $row['source_ref'] ?? '' );
			$target = (string) ( $row['target_ref'] ?? '' );
			if ( $source !== '' && $target !== '' && is_numeric( $source ) ) {
				$candidates[] = array(
					'page_id' => (int) $source,
					'type'    => 'page_template',
					'key'     => $target,
				);
			}
		}
		$composition_rows = $this->assignment_map->list_by_type( Assignment_Types::PAGE_COMPOSITION, 500, 0 );
		foreach ( $composition_rows as $row ) {
			$source = (string) ( $row['source_ref'] ?? '' );
			$target = (string) ( $row['target_ref'] ?? '' );
			if ( $source !== '' && $target !== '' && is_numeric( $source ) ) {
				$candidates[] = array(
					'page_id' => (int) $source,
					'type'    => 'page_composition',
					'key'     => $target,
				);
			}
		}
		return $candidates;
	}

	private function result_for_dry_run( ACF_Regeneration_Plan $plan ): ACF_Regeneration_Result {
		$missing       = 0;
		$version_stale = 0;
		foreach ( $plan->get_field_group_mismatches() as $m ) {
			if ( ( $m['status'] ?? '' ) === ACF_Regeneration_Plan::MISMATCH_STATUS_MISSING ) {
				++$missing;
			} elseif ( ( $m['status'] ?? '' ) === ACF_Regeneration_Plan::MISMATCH_STATUS_VERSION_STALE ) {
				++$version_stale;
			}
		}
		return new ACF_Regeneration_Result(
			0,
			array(),
			0,
			0,
			array( 'Dry run: no mutations performed.' ),
			array(),
			array(
				'missing'       => $missing,
				'version_stale' => $version_stale,
				'repaired'      => 0,
			),
			array(
				'repaired' => 0,
				'failed'   => 0,
				'skipped'  => count( $plan->get_page_assignment_repair_candidates() ),
			)
		);
	}
}
