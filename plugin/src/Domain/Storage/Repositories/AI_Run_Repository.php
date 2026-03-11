<?php
/**
 * Data access for AI Run metadata/identity (spec §10.5, §29.6). Backing: CPT aio_ai_run; artifacts in meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::AI_RUN (CPT for metadata/identity).
 * Internal key: run_id (e.g. UUID). Status: pending_generation | completed | failed_validation | failed.
 * Run metadata and artifact payloads stored in post meta; raw vs normalized kept separate.
 */
final class AI_Run_Repository extends Abstract_CPT_Repository {

	/** Meta key for run metadata (actor, timestamps, provider, model, prompt_pack_ref, retry_count, build_plan_ref). */
	public const META_RUN_METADATA = '_aio_run_metadata';

	/** Meta key prefix for artifact payloads: _aio_artifact_{category}. */
	public const META_ARTIFACT_PREFIX = '_aio_artifact_';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::AI_RUN;
	}

	/**
	 * Returns run metadata for a run post (decoded from META_RUN_METADATA).
	 *
	 * @param int $post_id Run post ID.
	 * @return array<string, mixed> Keys: actor, created_at, completed_at, provider_id, model_used, prompt_pack_ref, retry_count, build_plan_ref, etc.
	 */
	public function get_run_metadata( int $post_id ): array {
		$raw = \get_post_meta( $post_id, self::META_RUN_METADATA, true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Saves run metadata for a run post.
	 *
	 * @param int                  $post_id Run post ID.
	 * @param array<string, mixed> $data    Run metadata (actor, timestamps, provider_id, model_used, etc.).
	 * @return bool Success.
	 */
	public function save_run_metadata( int $post_id, array $data ): bool {
		$json = wp_json_encode( $data );
		return $json !== false && \update_post_meta( $post_id, self::META_RUN_METADATA, $json ) !== false;
	}

	/**
	 * Returns artifact payload for a category (stored in meta _aio_artifact_{category}).
	 *
	 * @param int    $post_id  Run post ID.
	 * @param string $category Artifact_Category_Keys constant.
	 * @return mixed Decoded payload or null if absent.
	 */
	public function get_artifact_payload( int $post_id, string $category ): mixed {
		$key = self::META_ARTIFACT_PREFIX . $category;
		$raw = \get_post_meta( $post_id, $key, true );
		if ( $raw === '' || $raw === null ) {
			return null;
		}
		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			return $decoded;
		}
		return $raw;
	}

	/**
	 * Saves artifact payload for a category.
	 *
	 * @param int    $post_id  Run post ID.
	 * @param string $category Artifact_Category_Keys constant.
	 * @param mixed  $payload  Encodable payload (will be JSON-encoded).
	 * @return bool Success.
	 */
	public function save_artifact_payload( int $post_id, string $category, mixed $payload ): bool {
		$key  = self::META_ARTIFACT_PREFIX . $category;
		$json = wp_json_encode( $payload );
		return $json !== false && \update_post_meta( $post_id, $key, $json ) !== false;
	}

	/**
	 * Lists runs by most recent first (any status). For admin list screen.
	 *
	 * @param int $limit  Max items (default 50).
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		$limit  = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$query  = new \WP_Query(
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

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base = parent::get_meta( $post_id );
		$base['run_metadata'] = $this->get_run_metadata( $post_id );
		return $base;
	}

	/** @inheritdoc */
	protected function post_to_record( $post, array $meta ): array {
		$base = parent::post_to_record( $post, $meta );
		if ( isset( $meta['run_metadata'] ) && is_array( $meta['run_metadata'] ) ) {
			$base['run_metadata'] = $meta['run_metadata'];
		}
		return $base;
	}
}
