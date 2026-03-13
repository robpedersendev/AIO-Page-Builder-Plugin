<?php
/**
 * Data access for Build Plan objects (spec §10.4, §30.3). Backing: CPT aio_build_plan; full definition in meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::BUILD_PLAN (CPT).
 * Internal key: plan_id (e.g. UUID). Status: pending_review | approved | rejected | in_progress | completed | superseded.
 * Full plan definition (steps, items, etc.) stored in _aio_plan_definition meta.
 * Implements Plan_State_For_Execution_Interface for single-action executor; Build_Plan_List_Provider_Interface for analytics.
 */
final class Build_Plan_Repository extends Abstract_CPT_Repository implements Build_Plan_Repository_Interface, Plan_State_For_Execution_Interface, Build_Plan_List_Provider_Interface {

	public const META_PLAN_DEFINITION = '_aio_plan_definition';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::BUILD_PLAN;
	}

	/**
	 * Lists plans by most recent first (any status). For admin list screen.
	 *
	 * @param int $limit  Max items (default 50).
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		$limit = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$query = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => $limit,
				'offset'                 => $offset,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			)
		);
		$out = array();
		foreach ( $query->get_posts() as $post ) {
			$meta = $this->get_meta( $post->ID );
			$out[] = $this->post_to_record( $post, $meta );
		}
		return $out;
	}

	/**
	 * Returns the full plan definition (root payload with steps) for a plan post.
	 *
	 * @param int $post_id Plan post ID.
	 * @return array<string, mixed> Decoded plan definition or empty array.
	 */
	public function get_plan_definition( int $post_id ): array {
		$raw = \get_post_meta( $post_id, self::META_PLAN_DEFINITION, true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Saves the full plan definition for a plan post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $definition Plan root payload (plan_id, status, steps, etc.).
	 * @return bool Success.
	 */
	public function save_plan_definition( int $post_id, array $definition ): bool {
		$json = \wp_json_encode( $definition );
		return $json !== false && \update_post_meta( $post_id, self::META_PLAN_DEFINITION, $json ) !== false;
	}

	/**
	 * Finds the step index that contains the given plan item id (for executor state updates).
	 *
	 * @param array<string, mixed> $definition Plan definition (steps array).
	 * @param string               $plan_item_id Item id to find.
	 * @return int|null Step index (0-based) or null if not found.
	 */
	public function find_step_index_for_item( array $definition, string $plan_item_id ): ?int {
		if ( $plan_item_id === '' ) {
			return null;
		}
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $idx => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $plan_item_id ) {
					return $idx;
				}
			}
		}
		return null;
	}

	/**
	 * Updates a single plan item's status and optionally execution artifact (spec §32.5, §37.6, plan history).
	 *
	 * @param int    $post_id    Plan post ID.
	 * @param int    $step_index Step index in steps array.
	 * @param string $item_id    Item id to update.
	 * @param string $new_status New status (e.g. approved, completed).
	 * @param array<string, mixed>|null $execution_artifact Optional artifact (e.g. post_id, target_post_id) for finalization publish.
	 * @return bool True if item was found and updated; false otherwise.
	 */
	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status, ?array $execution_artifact = null ): bool {
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return false;
		}
		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$updated = false;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $item_id ) {
				$items[ $i ]['status'] = $new_status;
				if ( $execution_artifact !== null ) {
					$items[ $i ]['execution_artifact'] = $execution_artifact;
				}
				$updated = true;
				break;
			}
		}
		if ( ! $updated ) {
			return false;
		}
		$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		return $this->save_plan_definition( $post_id, $definition );
	}

	/**
	 * Updates all items in a step that match a status predicate to a new status (e.g. bulk approve/deny).
	 *
	 * @param int    $post_id     Plan post ID.
	 * @param int    $step_index  Step index.
	 * @param string $from_status Only change items with this status (e.g. pending).
	 * @param string $to_status   New status to set.
	 * @return int Number of items updated.
	 */
	public function update_plan_step_items_by_status( int $post_id, int $step_index, string $from_status, string $to_status ): int {
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}
		$items  = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$count  = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) === $from_status ) {
				$items[ $i ]['status'] = $to_status;
				++$count;
			}
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->save_plan_definition( $post_id, $definition );
		}
		return $count;
	}

	/**
	 * Updates status for specific item IDs in a step (e.g. Build Selected for Step 2).
	 * Only items with from_status are updated. Ids not found or wrong status are skipped.
	 *
	 * @param int    $post_id     Plan post ID.
	 * @param int    $step_index  Step index.
	 * @param array  $item_ids    Item ids to update (e.g. selected).
	 * @param string $new_status  New status to set.
	 * @param string $from_status Only change items with this status (default pending).
	 * @return int Number of items updated.
	 */
	public function update_plan_items_by_ids( int $post_id, int $step_index, array $item_ids, string $new_status, string $from_status = 'pending' ): int {
		if ( empty( $item_ids ) ) {
			return 0;
		}
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}
		$items  = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$id_set = array_flip( array_map( 'strval', $item_ids ) );
		$count  = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = (string) ( $item['item_id'] ?? '' );
			if ( $id === '' || ! isset( $id_set[ $id ] ) ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) !== $from_status ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->save_plan_definition( $post_id, $definition );
		}
		return $count;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base = parent::get_meta( $post_id );
		$base['plan_definition'] = $this->get_plan_definition( $post_id );
		return $base;
	}

	/** @inheritdoc */
	protected function post_to_record( $post, array $meta ): array {
		$base = parent::post_to_record( $post, $meta );
		$p    = is_array( $post ) ? $post : (array) $post;
		$base['post_date'] = (string) ( $p['post_date'] ?? '' );
		if ( ! empty( $meta['plan_definition'] ) && is_array( $meta['plan_definition'] ) ) {
			$base = array_merge( $base, $meta['plan_definition'] );
		}
		return $base;
	}

	/** @inheritdoc */
	public function save( array $data ): int {
		$definition = isset( $data['plan_definition'] ) && is_array( $data['plan_definition'] ) ? $data['plan_definition'] : null;
		$data_for_parent = $data;
		if ( $definition !== null ) {
			$data_for_parent = array(
				'id'           => $data['id'] ?? 0,
				'internal_key' => $definition['plan_id'] ?? $data['internal_key'] ?? '',
				'post_title'   => $definition['plan_title'] ?? $data['post_title'] ?? 'Build Plan',
				'status'       => $definition['status'] ?? $data['status'] ?? 'pending_review',
			);
		}
		$id = parent::save( $data_for_parent );
		if ( $id > 0 && $definition !== null ) {
			$this->save_plan_definition( $id, $definition );
		}
		return $id;
	}
}
