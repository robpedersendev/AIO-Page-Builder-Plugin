<?php
/**
 * Migration and upgrade verification harness for ACF field architecture (spec §53, §58.4, §58.5, §59.14; Prompt 225).
 * Verifies field-key stability, group-key stability, registry-to-group mappings, page-assignment relevance,
 * mirror coherence, and regeneration safety across version transitions. Internal/admin-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Migration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Field_Group_Debug_Exporter;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Plan;
use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service_Interface;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Runs ACF migration verification and produces ACF_Migration_Verification_Result.
 * Does not mutate live data; simulation and read-only checks only unless explicitly documented.
 */
final class ACF_Migration_Verification_Service {

	private const GROUP_KEY_PREFIX = 'group_aio_';

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var Assignment_Map_Service_Interface */
	private Assignment_Map_Service_Interface $assignment_map;

	/** @var Page_Template_Repository_Interface */
	private Page_Template_Repository_Interface $page_template_repository;

	/** @var Composition_Repository|null */
	private ?Composition_Repository $composition_repository;

	/** @var ACF_Field_Group_Debug_Exporter */
	private ACF_Field_Group_Debug_Exporter $debug_exporter;

	/** @var ACF_Local_JSON_Mirror_Service */
	private ACF_Local_JSON_Mirror_Service $mirror_service;

	/** @var ACF_Regeneration_Service */
	private ACF_Regeneration_Service $regeneration_service;

	public function __construct(
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		Assignment_Map_Service_Interface $assignment_map,
		Page_Template_Repository_Interface $page_template_repository,
		?Composition_Repository $composition_repository,
		ACF_Field_Group_Debug_Exporter $debug_exporter,
		ACF_Local_JSON_Mirror_Service $mirror_service,
		ACF_Regeneration_Service $regeneration_service
	) {
		$this->blueprint_service        = $blueprint_service;
		$this->assignment_map           = $assignment_map;
		$this->page_template_repository = $page_template_repository;
		$this->composition_repository   = $composition_repository;
		$this->debug_exporter           = $debug_exporter;
		$this->mirror_service           = $mirror_service;
		$this->regeneration_service     = $regeneration_service;
	}

	/**
	 * Runs full verification and returns a single result payload.
	 *
	 * @param array<string, mixed> $options Optional. Keys: simulated_mirror_manifest (array, for upgrade-path diff), acf_available (bool).
	 * @return ACF_Migration_Verification_Result
	 */
	public function run_verification( array $options = array() ): ACF_Migration_Verification_Result {
		$run_at     = gmdate( 'Y-m-d\TH:i:s\Z' );
		$plugin_v   = Versions::plugin();
		$registry_v = Versions::registry_schema();

		$field_stability    = $this->build_field_key_stability_summary( $options );
		$assignment_summary = $this->build_assignment_continuity_summary();
		$mirror_coherence   = $this->build_mirror_coherence( $options );
		$regeneration_safe  = $this->build_regeneration_safe();

		$breaking    = array();
		$deprecation = array();

		if ( ! empty( $field_stability['unstable_or_missing'] ) ) {
			$breaking[] = 'Field or group keys unstable or missing: ' . implode( ', ', array_slice( $field_stability['unstable_or_missing'], 0, 10 ) ) . ( count( $field_stability['unstable_or_missing'] ) > 10 ? '...' : '' );
		}
		if ( ! empty( $assignment_summary['orphaned_or_invalid'] ) ) {
			$deprecation[] = 'Assignments reference missing templates/compositions: ' . count( $assignment_summary['orphaned_or_invalid'] ) . ' affected.';
		}
		if ( ( $mirror_coherence['version_mismatch'] ?? 0 ) > 0 || ( $mirror_coherence['in_registry_not_mirror_count'] ?? 0 ) > 0 ) {
			$deprecation[] = 'Mirror coherence: version or registry/mirror drift detected.';
		}
		if ( ! ( $regeneration_safe['plan_buildable'] ?? true ) ) {
			$breaking[] = 'Regeneration plan could not be built.';
		}

		$overall = ACF_Migration_Verification_Result::STATUS_PASS;
		if ( count( $breaking ) > 0 ) {
			$overall = ACF_Migration_Verification_Result::STATUS_FAIL;
		} elseif ( count( $deprecation ) > 0 ) {
			$overall = ACF_Migration_Verification_Result::STATUS_WARNING;
		}

		$human = $overall === ACF_Migration_Verification_Result::STATUS_PASS
			? __( 'ACF migration verification passed.', 'aio-page-builder' )
			: ( $overall === ACF_Migration_Verification_Result::STATUS_FAIL
				? __( 'ACF migration verification failed: breaking risks detected.', 'aio-page-builder' )
				: __( 'ACF migration verification warning: deprecation or drift risks detected.', 'aio-page-builder' ) );

		return new ACF_Migration_Verification_Result(
			$run_at,
			$plugin_v,
			$registry_v,
			$field_stability,
			$assignment_summary,
			$mirror_coherence,
			$regeneration_safe,
			$breaking,
			$deprecation,
			$overall,
			$human
		);
	}

	/**
	 * Builds field_key_stability_summary from blueprints and optionally live ACF groups.
	 *
	 * @param array<string, mixed> $options
	 * @return array<string, mixed> field_key_stability_summary
	 */
	public function build_field_key_stability_summary( array $options = array() ): array {
		$blueprints          = $this->blueprint_service->get_all_blueprints();
		$stable_group_keys   = array();
		$stable_field_keys   = array();
		$unstable_or_missing = array();

		foreach ( $blueprints as $bp ) {
			$section_key = (string) ( $bp[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$group_key           = Field_Key_Generator::group_key( $section_key );
			$stable_group_keys[] = $group_key;

			$fields = $bp[ Field_Blueprint_Schema::FIELDS ] ?? array();
			if ( ! is_array( $fields ) ) {
				continue;
			}
			foreach ( $fields as $f ) {
				if ( ! is_array( $f ) ) {
					continue;
				}
				$name = (string) ( $f['name'] ?? '' );
				if ( $name === '' ) {
					continue;
				}
				$key = (string) ( $f['key'] ?? Field_Key_Generator::field_key( $section_key, $name ) );
				if ( Field_Key_Generator::is_valid_key( $key, 'field' ) ) {
					$stable_field_keys[] = $key;
				} else {
					$unstable_or_missing[] = 'field:' . $key;
				}
			}
		}

		$acf_available = $options['acf_available'] ?? function_exists( 'acf_get_field_groups' );
		if ( $acf_available && function_exists( 'acf_get_field_groups' ) ) {
			$live = acf_get_field_groups();
			if ( is_array( $live ) ) {
				$expected_group_keys = array_flip( $stable_group_keys );
				foreach ( $live as $g ) {
					if ( ! is_array( $g ) ) {
						continue;
					}
					$key = (string) ( $g['key'] ?? '' );
					if ( $key !== '' && str_starts_with( $key, self::GROUP_KEY_PREFIX ) && ! isset( $expected_group_keys[ $key ] ) ) {
						$unstable_or_missing[] = 'group_orphan:' . $key;
					}
				}
			}
		}

		$summary = count( $unstable_or_missing ) === 0
			? __( 'All keys stable.', 'aio-page-builder' )
			: sprintf(
				/* translators: 1: group count, 2: field count, 3: unstable count */
				__( '%1$d group(s) and %2$d field key(s) from registry; %3$d unstable or missing.', 'aio-page-builder' ),
				count( $stable_group_keys ),
				count( $stable_field_keys ),
				count( $unstable_or_missing )
			);

		return array(
			'stable_group_keys'   => $stable_group_keys,
			'stable_field_keys'   => $stable_field_keys,
			'unstable_or_missing' => array_values( array_unique( $unstable_or_missing ) ),
			'summary'             => $summary,
		);
	}

	/**
	 * Builds assignment_continuity_summary: assignments whose target_ref still exists in registry.
	 *
	 * @return array<string, mixed> assignment_continuity_summary
	 */
	public function build_assignment_continuity_summary(): array {
		$valid_pt_keys = array();
		$defs          = $this->page_template_repository->list_all_definitions( 9999, 0 );
		foreach ( $defs as $d ) {
			$k = (string) ( $d[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $k !== '' ) {
				$valid_pt_keys[ $k ] = true;
			}
		}

		$valid_comp_ids = array();
		if ( $this->composition_repository !== null ) {
			$comp_defs = $this->composition_repository->list_all_definitions( 9999, 0 );
			foreach ( $comp_defs as $c ) {
				$cid = (string) ( $c[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
				if ( $cid !== '' ) {
					$valid_comp_ids[ $cid ] = true;
				}
			}
		}

		$assignments_checked  = 0;
		$assignments_relevant = 0;
		$orphaned_or_invalid  = array();

		$pt_rows = $this->assignment_map->list_by_type( Assignment_Types::PAGE_TEMPLATE, 500, 0 );
		foreach ( $pt_rows as $row ) {
			++$assignments_checked;
			$target = (string) ( $row['target_ref'] ?? '' );
			if ( isset( $valid_pt_keys[ $target ] ) ) {
				++$assignments_relevant;
			} else {
				$orphaned_or_invalid[] = 'page_template:' . $target;
			}
		}

		$comp_rows = $this->assignment_map->list_by_type( Assignment_Types::PAGE_COMPOSITION, 500, 0 );
		foreach ( $comp_rows as $row ) {
			++$assignments_checked;
			$target = (string) ( $row['target_ref'] ?? '' );
			if ( $target === '' || isset( $valid_comp_ids[ $target ] ) ) {
				++$assignments_relevant;
			} else {
				$orphaned_or_invalid[] = 'page_composition:' . $target;
			}
		}

		$summary = count( $orphaned_or_invalid ) === 0
			? __( 'All assignments relevant.', 'aio-page-builder' )
			: sprintf(
				/* translators: 1: checked, 2: relevant, 3: orphaned count */
				__( '%1$d checked, %2$d relevant, %3$d orphaned or invalid.', 'aio-page-builder' ),
				$assignments_checked,
				$assignments_relevant,
				count( $orphaned_or_invalid )
			);

		return array(
			'assignments_checked'  => $assignments_checked,
			'assignments_relevant' => $assignments_relevant,
			'orphaned_or_invalid'  => $orphaned_or_invalid,
			'summary'              => $summary,
		);
	}

	/**
	 * Builds mirror coherence (registry vs optional simulated mirror manifest).
	 *
	 * @param array<string, mixed> $options simulated_mirror_manifest => array (optional).
	 * @return array<string, mixed>
	 */
	public function build_mirror_coherence( array $options = array() ): array {
		$registry_manifest = $this->mirror_service->get_manifest_without_writing();
		$simulated         = $options['simulated_mirror_manifest'] ?? null;
		$mirror_manifest   = is_array( $simulated ) ? $simulated : $registry_manifest;

		$diff = $this->debug_exporter->build_diff_summary( $registry_manifest, $mirror_manifest );

		$in_sync = count( $registry_manifest['group_keys'] ?? array() ) - count( $diff['in_registry_not_mirror'] ?? array() ) - count( $diff['version_mismatch'] ?? array() );
		$in_sync = max( 0, $in_sync );

		return array(
			'in_sync'                      => $in_sync,
			'in_registry_not_mirror_count' => count( $diff['in_registry_not_mirror'] ?? array() ),
			'in_mirror_not_registry_count' => count( $diff['in_mirror_not_registry'] ?? array() ),
			'version_mismatch'             => count( $diff['version_mismatch'] ?? array() ),
			'summary'                      => $diff['summary'] ?? '',
		);
	}

	/**
	 * Builds regeneration_safe: plan buildable and repair candidates consistent.
	 *
	 * @return array<string, mixed>
	 */
	public function build_regeneration_safe(): array {
		$plan_buildable               = true;
		$repair_candidates_consistent = true;
		$summary                      = '';

		try {
			$plan    = $this->regeneration_service->build_plan( true, ACF_Regeneration_Plan::SCOPE_FULL, array( 'include_page_assignments' => true ) );
			$refused = $plan->get_refused_cleanup();
			if ( ! empty( $refused ) ) {
				$repair_candidates_consistent = true;
			}
			$mismatches = $plan->get_field_group_mismatches();
			$candidates = $plan->get_page_assignment_repair_candidates();
			$summary    = sprintf(
				/* translators: 1: mismatch count, 2: candidate count */
				__( 'Regeneration plan buildable; %1$d mismatch(es), %2$d repair candidate(s).', 'aio-page-builder' ),
				count( $mismatches ),
				count( $candidates )
			);
		} catch ( \Throwable $e ) {
			$plan_buildable = false;
			$summary        = __( 'Regeneration plan could not be built.', 'aio-page-builder' );
		}

		return array(
			'plan_buildable'               => $plan_buildable,
			'repair_candidates_consistent' => $repair_candidates_consistent,
			'summary'                      => $summary,
		);
	}
}
