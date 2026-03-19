<?php
/**
 * Base for CPT-backed repositories. Uses WP_Query and post meta; object status in meta (spec §9.1, §10).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Status_Families;

/**
 * Object lifecycle status is stored in meta _aio_status; internal key in _aio_internal_key.
 * Subclasses define post_type and optional key meta key. No permission checks; callers must be authorized.
 */
abstract class Abstract_CPT_Repository implements Repository_Interface {

	/** Meta key for stable internal key (slug or id). */
	protected const META_INTERNAL_KEY = '_aio_internal_key';

	/** Meta key for object lifecycle status (draft, active, pending_review, etc.). */
	protected const META_STATUS = '_aio_status';

	/** Default list limit when not specified. */
	protected const DEFAULT_LIST_LIMIT = 100;

	/**
	 * Returns the post type for this repository (Object_Type_Keys constant).
	 */
	abstract protected function get_post_type(): string;

	/**
	 * Converts a WP_Post (or post array) and meta to a normalized record array. Subclasses may override.
	 *
	 * @param \WP_Post|array<string, mixed> $post Post object or array.
	 * @param array<string, mixed>          $meta Meta key => value.
	 * @return array<string, mixed>
	 */
	protected function post_to_record( $post, array $meta ): array {
		$p = is_array( $post ) ? $post : (array) $post;
		return array_merge(
			array(
				'id'          => (int) ( $p['ID'] ?? 0 ),
				'post_type'   => $p['post_type'] ?? '',
				'post_title'  => $p['post_title'] ?? '',
				'post_status' => $p['post_status'] ?? '',
				'post_name'   => $p['post_name'] ?? '',
			),
			array(
				'internal_key' => $meta[ self::META_INTERNAL_KEY ] ?? '',
				'status'       => $meta[ self::META_STATUS ] ?? '',
			)
		);
	}

	/** @inheritdoc */
	public function get_by_id( int $id ): ?array {
		$post = \get_post( $id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== $this->get_post_type() ) {
			return null;
		}
		$meta = $this->get_meta( $id );
		return $this->post_to_record( $post, $meta );
	}

	/** @inheritdoc */
	public function get_by_key( string $key ): ?array {
		$key = $this->sanitize_key( $key );
		if ( $key === '' ) {
			return null;
		}
		$query = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'     => self::META_INTERNAL_KEY,
						'value'   => $key,
						'compare' => '=',
					),
				),
			)
		);
		$posts = $query->get_posts();
		if ( empty( $posts ) ) {
			return null;
		}
		$post = $posts[0];
		$meta = $this->get_meta( $post->ID );
		return $this->post_to_record( $post, $meta );
	}

	/** @inheritdoc */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$status = $this->sanitize_status( $status );
		$limit  = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$query  = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => $limit,
				'offset'                 => $offset,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'     => self::META_STATUS,
						'value'   => $status,
						'compare' => '=',
					),
				),
			)
		);
		$out    = array();
		foreach ( $query->get_posts() as $post ) {
			$meta  = $this->get_meta( $post->ID );
			$out[] = $this->post_to_record( $post, $meta );
		}
		return $out;
	}

	/** @inheritdoc */
	public function save( array $data ): int {
		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$key    = $this->sanitize_key( (string) ( $data['internal_key'] ?? $data['post_name'] ?? '' ) );
		$status = $this->sanitize_status( (string) ( $data['status'] ?? 'draft' ) );
		$title  = isset( $data['post_title'] ) ? \sanitize_text_field( (string) $data['post_title'] ) : ( $key !== '' && $key !== null ? $key : 'Untitled' );

		if ( $id > 0 ) {
			$updated = \wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $title,
					'post_type'  => $this->get_post_type(),
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				return 0;
			}
			$id = (int) $updated;
		} else {
			$inserted = \wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => $this->get_post_type(),
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $inserted ) || $inserted === 0 ) {
				return 0;
			}
			$id = (int) $inserted;
		}

		\update_post_meta( $id, self::META_INTERNAL_KEY, $key );
		\update_post_meta( $id, self::META_STATUS, $status );
		return $id;
	}

	/** @inheritdoc */
	public function exists( $key_or_id ): bool {
		if ( is_int( $key_or_id ) ) {
			return $this->get_by_id( $key_or_id ) !== null;
		}
		return $this->get_by_key( (string) $key_or_id ) !== null;
	}

	/**
	 * Loads meta for a post (internal_key and status).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_meta( int $post_id ): array {
		return array(
			self::META_INTERNAL_KEY => ( ( $meta_ik = \get_post_meta( $post_id, self::META_INTERNAL_KEY, true ) ) !== false && $meta_ik !== '' ) ? $meta_ik : '',
			self::META_STATUS       => ( ( $meta_st = \get_post_meta( $post_id, self::META_STATUS, true ) ) !== false && $meta_st !== '' ) ? $meta_st : '',
		);
	}

	protected function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( $key );
		return substr( $key, 0, 255 );
	}

	protected function sanitize_status( string $status ): string {
		$status  = \sanitize_text_field( $status );
		$allowed = Object_Status_Families::get_statuses_for( $this->get_post_type() );
		return in_array( $status, $allowed, true ) ? $status : '';
	}
}
