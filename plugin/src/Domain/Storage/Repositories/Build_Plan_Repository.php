<?php
/**
 * Data access for Build Plan objects (spec §10.4, §30.3). Backing: CPT aio_build_plan; full definition in meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::BUILD_PLAN (CPT).
 * Internal key: plan_id (e.g. UUID). Status: pending_review | approved | rejected | in_progress | completed | superseded.
 * Full plan definition (steps, items, etc.) stored in _aio_plan_definition meta.
 */
final class Build_Plan_Repository extends Abstract_CPT_Repository {

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
		$json = wp_json_encode( $definition );
		return $json !== false && \update_post_meta( $post_id, self::META_PLAN_DEFINITION, $json ) !== false;
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
