<?php
/**
 * Data access for AI Run metadata/identity (spec §10.5, §29.6). Backing: CPT aio_ai_run; artifacts in meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Artifact_Repository_Interface;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Infrastructure\Db\Wpdb_Prepared_Results;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Repository → storage: Object_Type_Keys::AI_RUN (CPT for metadata/identity).
 * Internal key: run_id (e.g. UUID). Status: pending_generation | completed | failed_validation | failed.
 * Run metadata and artifact payloads stored in post meta; raw vs normalized kept separate.
 */
final class AI_Run_Repository extends Abstract_CPT_Repository implements AI_Artifact_Repository_Interface, AI_Run_Template_Lab_Apply_State_Port {

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
		if ( $json === false || \update_post_meta( $post_id, self::META_RUN_METADATA, $json ) === false ) {
			return false;
		}
		// * Indexed for privacy exporter/eraser (actor-linked queries).
		$actor = isset( $data['actor'] ) ? (string) $data['actor'] : '';
		\update_post_meta( $post_id, '_aio_run_actor', $actor );
		$surface = self::infer_run_surface( $data );
		if ( $surface !== null ) {
			\update_post_meta( $post_id, self::META_RUN_SURFACE, $surface );
		}
		return true;
	}

	/** Post meta: coarse surface for diagnostics (template_lab | build_plan | other). Secrets-free. */
	public const META_RUN_SURFACE = '_aio_run_surface';

	/**
	 * @param array<string, mixed> $data Run metadata being saved.
	 */
	private static function infer_run_surface( array $data ): ?string {
		if ( isset( $data['template_lab'] ) && is_array( $data['template_lab'] ) ) {
			return 'template_lab';
		}
		if ( ! empty( $data['template_lab_shell'] ) ) {
			return 'template_lab';
		}
		$bp = $data['build_plan_ref'] ?? null;
		if ( is_string( $bp ) && $bp !== '' ) {
			return 'build_plan';
		}
		if ( is_array( $bp ) && $bp !== array() ) {
			return 'build_plan';
		}
		return null;
	}

	/**
	 * Lists recent run post IDs whose surface meta matches (empty $surface returns recent ignoring surface).
	 *
	 * @return list<int>
	 */
	public function list_recent_post_ids_by_surface( string $surface, int $limit = 50, int $offset = 0 ): array {
		$limit  = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$offset = max( 0, $offset );
		$args   = array(
			'post_type'              => $this->get_post_type(),
			'posts_per_page'         => $limit,
			'offset'                 => $offset,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
		);
		if ( $surface !== '' ) {
			$args['meta_key']   = self::META_RUN_SURFACE;
			$args['meta_value'] = $surface;
		}
		$query = new \WP_Query( $args );
		$ids   = array();
		foreach ( $query->get_posts() as $id ) {
			$ids[] = (int) $id;
		}
		return $ids;
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
		$key   = self::META_ARTIFACT_PREFIX . $category;
		$clean = self::sanitize_payload_for_meta_json( $payload );
		$flags = defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
		$depth = 2048;
		$json  = \wp_json_encode( $clean, $flags, $depth );
		if ( $json === false ) {
			$json = \wp_json_encode( $clean, $flags | ( defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0 ), $depth );
		}
		return $json !== false && \update_post_meta( $post_id, $key, $json ) !== false;
	}

	/**
	 * Recursively replaces non-finite floats so json_encode can persist provider payloads (PHP JSON rejects INF/NAN).
	 *
	 * @param mixed $value Payload fragment.
	 * @return mixed
	 */
	private static function sanitize_payload_for_meta_json( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = self::sanitize_payload_for_meta_json( $v );
			}
			return $out;
		}
		if ( is_string( $value ) && \function_exists( 'wp_check_invalid_utf8' ) ) {
			return \wp_check_invalid_utf8( $value, true );
		}
		if ( is_float( $value ) && ( ! is_finite( $value ) ) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Lists runs for a given actor (user ID). For privacy exporter/eraser.
	 *
	 * @param int $user_id WordPress user ID (stored in run metadata as actor).
	 * @param int $limit   Max items (default 50).
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_recent_by_actor( int $user_id, int $limit = 50, int $offset = 0 ): array {
		$limit  = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$offset = $offset >= 0 ? $offset : 0;
		global $wpdb;
		if ( $wpdb instanceof \wpdb ) {
			$ids = Wpdb_Prepared_Results::find_post_ids_by_post_type_meta_key_value(
				$wpdb,
				$this->get_post_type(),
				'_aio_run_actor',
				(string) $user_id,
				$limit,
				$offset,
				'post_date',
				'DESC'
			);
			if ( $ids !== array() ) {
				$out = array();
				foreach ( $ids as $post_id ) {
					$post = \get_post( $post_id );
					if ( ! $post instanceof \WP_Post ) {
						continue;
					}
					$meta  = $this->get_meta( $post->ID );
					$out[] = $this->post_to_record( $post, $meta );
				}
				return $out;
			}
		}
		// phpcs:disable WordPress.DB.SlowDBQuery -- Unreachable in real WordPress when global $wpdb exists; used by PHPUnit stubs.
		$query = new \WP_Query(
			array(
				'post_type'      => $this->get_post_type(),
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_key'       => '_aio_run_actor',
				'meta_value'     => (string) $user_id,
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery
		$out = array();
		foreach ( $query->get_posts() as $post ) {
			$meta  = $this->get_meta( $post->ID );
			$out[] = $this->post_to_record( $post, $meta );
		}
		return $out;
	}

	/**
	 * Lists runs by most recent first (any status). For admin list screen.
	 *
	 * @param int $limit  Max items (default 50).
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0, string $surface = '' ): array {
		$limit = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$args  = array(
			'post_type'              => $this->get_post_type(),
			'posts_per_page'         => $limit,
			'offset'                 => $offset,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
		);
		if ( $surface !== '' ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin AI run list filter; paginated.
			$args['meta_key']    = self::META_RUN_SURFACE;
			$args['meta_value']  = $surface;
		}
		$query = new \WP_Query( $args );
		$out   = array();
		foreach ( $query->get_posts() as $post ) {
			$meta  = $this->get_meta( $post->ID );
			$out[] = $this->post_to_record( $post, $meta );
		}
		return $out;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base                 = parent::get_meta( $post_id );
		$base['run_metadata'] = $this->get_run_metadata( $post_id );
		return $base;
	}

	/** @inheritdoc */
	protected function post_to_record( $post, array $meta ): array {
		$base = parent::post_to_record( $post, $meta );
		if ( isset( $meta['run_metadata'] ) && is_array( $meta['run_metadata'] ) ) {
			$base['run_metadata'] = $meta['run_metadata'];
		}
		$pid = (int) ( $base['id'] ?? 0 );
		if ( $pid > 0 ) {
			$base['run_surface'] = (string) \get_post_meta( $pid, self::META_RUN_SURFACE, true );
		} else {
			$base['run_surface'] = '';
		}
		return $base;
	}

	/** Meta: last template-lab canonical apply (idempotency; secrets-free). */
	public const META_TEMPLATE_LAB_CANONICAL_APPLY = '_aio_template_lab_canonical_apply';

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_template_lab_canonical_apply_record( int $post_id ): ?array {
		$raw = \get_post_meta( $post_id, self::META_TEMPLATE_LAB_CANONICAL_APPLY, true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * @param array<string, mixed> $record
	 */
	public function save_template_lab_canonical_apply_record( int $post_id, array $record ): bool {
		$json = wp_json_encode( $record );
		return $json !== false && \update_post_meta( $post_id, self::META_TEMPLATE_LAB_CANONICAL_APPLY, $json ) !== false;
	}
}
