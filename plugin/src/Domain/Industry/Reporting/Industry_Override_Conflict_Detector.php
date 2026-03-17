<?php
/**
 * Advisory detector for overrides that may be stale or risky due to pack deprecation,
 * subtype/bundle changes, or removed refs (Prompt 464, industry-override-conflict-contract.md).
 * Read-only; no override mutation. Admin/support surfacing only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;

/**
 * Detects override conflicts: missing targets, deprecated refs, stale context.
 * Advisory only; results are for display and suggested review actions.
 */
final class Industry_Override_Conflict_Detector {

	public const CONFLICT_TYPE_MISSING_TARGET   = 'missing_target';
	public const CONFLICT_TYPE_DEPRECATED_REF   = 'deprecated_ref';
	public const CONFLICT_TYPE_REMOVED_REF      = 'removed_ref';
	public const CONFLICT_TYPE_SUBTYPE_STALE    = 'subtype_context_stale';

	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_INFO    = 'info';

	/** Max conflicts returned per run to keep output bounded. */
	private const MAX_CONFLICTS = 100;

	/** @var Industry_Override_Read_Model_Builder */
	private $read_model_builder;

	/** @var Build_Plan_Repository_Interface|null */
	private $plan_repository;

	public function __construct(
		?Industry_Override_Read_Model_Builder $read_model_builder = null,
		?Build_Plan_Repository_Interface $plan_repository = null
	) {
		$this->read_model_builder = $read_model_builder ?? new Industry_Override_Read_Model_Builder();
		$this->plan_repository   = $plan_repository;
	}

	/**
	 * Runs conflict detection on all overrides. Returns bounded list of conflict results.
	 *
	 * @return list<array{
	 *   override_ref: string,
	 *   conflict_type: string,
	 *   severity: string,
	 *   target_type: string,
	 *   target_key: string,
	 *   plan_id: string|null,
	 *   related_refs: list<string>,
	 *   suggested_review_action: string
	 * }>
	 */
	public function detect(): array {
		$rows    = $this->read_model_builder->build( array() );
		$results = array();
		foreach ( $rows as $row ) {
			if ( count( $results ) >= self::MAX_CONFLICTS ) {
				break;
			}
			$override_ref = (string) ( $row['row_id'] ?? '' );
			if ( $override_ref === '' ) {
				continue;
			}
			$target_type = (string) ( $row['target_type'] ?? '' );
			$target_key  = (string) ( $row['target_key'] ?? '' );
			$plan_id     = isset( $row['plan_id'] ) && is_string( $row['plan_id'] ) ? $row['plan_id'] : null;
			if ( $plan_id !== null && $plan_id === '' ) {
				$plan_id = null;
			}

			if ( $target_type === Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM && $this->plan_repository !== null ) {
				$conflict = $this->check_build_plan_item_override( $override_ref, $target_key, $plan_id );
				if ( $conflict !== null ) {
					$results[] = $conflict;
				}
			}
			// * Section and page_template: optional resolvers can be added later for deprecated_ref / missing_target.
		}
		return $results;
	}

	/**
	 * Checks one build_plan_item override: plan exists and item exists in plan.
	 *
	 * @param string      $override_ref
	 * @param string      $item_id
	 * @param string|null $plan_id
	 * @return array<string, mixed>|null Conflict result or null if no conflict.
	 */
	private function check_build_plan_item_override( string $override_ref, string $item_id, ?string $plan_id ): ?array {
		if ( $plan_id === null || $plan_id === '' ) {
			return array(
				'override_ref'             => $override_ref,
				'conflict_type'            => self::CONFLICT_TYPE_MISSING_TARGET,
				'severity'                 => self::SEVERITY_WARNING,
				'target_type'              => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
				'target_key'               => $item_id,
				'plan_id'                  => null,
				'related_refs'             => array(),
				'suggested_review_action'  => __( 'Override has no plan_id; consider removing it.', 'aio-page-builder' ),
			);
		}
		$record = $this->plan_repository->get_by_key( $plan_id );
		if ( $record === null ) {
			return array(
				'override_ref'             => $override_ref,
				'conflict_type'            => self::CONFLICT_TYPE_MISSING_TARGET,
				'severity'                 => self::SEVERITY_WARNING,
				'target_type'              => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
				'target_key'               => $item_id,
				'plan_id'                  => $plan_id,
				'related_refs'             => array( $plan_id ),
				'suggested_review_action'  => __( 'Plan no longer exists; consider removing this override.', 'aio-page-builder' ),
			);
		}
		$post_id = isset( $record['id'] ) ? (int) $record['id'] : 0;
		if ( $post_id <= 0 ) {
			return array(
				'override_ref'             => $override_ref,
				'conflict_type'            => self::CONFLICT_TYPE_MISSING_TARGET,
				'severity'                 => self::SEVERITY_WARNING,
				'target_type'              => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
				'target_key'               => $item_id,
				'plan_id'                  => $plan_id,
				'related_refs'             => array(),
				'suggested_review_action'  => __( 'Plan record invalid; consider removing this override.', 'aio-page-builder' ),
			);
		}
		$definition = $this->plan_repository->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$item_found = false;
		foreach ( $steps as $step ) {
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				$cid = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
				if ( $cid === $item_id ) {
					$item_found = true;
					break 2;
				}
			}
		}
		if ( ! $item_found ) {
			return array(
				'override_ref'             => $override_ref,
				'conflict_type'            => self::CONFLICT_TYPE_MISSING_TARGET,
				'severity'                 => self::SEVERITY_WARNING,
				'target_type'              => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
				'target_key'               => $item_id,
				'plan_id'                  => $plan_id,
				'related_refs'             => array( $plan_id ),
				'suggested_review_action'  => __( 'Item no longer in plan; consider removing this override.', 'aio-page-builder' ),
			);
		}
		return null;
	}
}
