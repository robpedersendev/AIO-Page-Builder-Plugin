<?php
/**
 * Data access for Version Snapshot objects (spec §10.8). Backing: CPT aio_version_snapshot.
 * Persists full normalized snapshot definition in meta _aio_snapshot_definition.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::VERSION_SNAPSHOT (CPT).
 * Internal key: snapshot_id. Meta keys: _aio_scope_type, _aio_scope_id for querying.
 */
final class Version_Snapshot_Repository extends Abstract_CPT_Repository {

	/** Meta key for full snapshot definition (JSON-encoded). */
	protected const META_DEFINITION = '_aio_snapshot_definition';

	/** Meta key for scope_type (for list_by_scope_type queries). */
	protected const META_SCOPE_TYPE = '_aio_scope_type';

	/** Meta key for scope_id (for list_by_scope_id queries). */
	protected const META_SCOPE_ID = '_aio_scope_id';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::VERSION_SNAPSHOT;
	}

	/**
	 * Persists full normalized snapshot definition.
	 *
	 * @param array<string, mixed> $definition Normalized snapshot definition.
	 * @return int Post ID on success; 0 on failure.
	 */
	public function save_definition( array $definition ): int {
		$snapshot_id = (string) ( $definition[ Version_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '' );
		$scope_type  = (string) ( $definition[ Version_Snapshot_Schema::FIELD_SCOPE_TYPE ] ?? '' );
		$scope_id    = (string) ( $definition[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] ?? '' );
		$status      = (string) ( $definition[ Version_Snapshot_Schema::FIELD_STATUS ] ?? Version_Snapshot_Schema::STATUS_ACTIVE );
		$title       = \sanitize_text_field( (string) ( $definition['post_title'] ?? $snapshot_id ?: 'Snapshot' ) );

		$snapshot_id = $this->sanitize_key( $snapshot_id );
		if ( $snapshot_id === '' ) {
			return 0;
		}
		$status = Version_Snapshot_Schema::is_valid_status( $status ) ? $status : Version_Snapshot_Schema::STATUS_ACTIVE;

		$data = array(
			'internal_key' => $snapshot_id,
			'status'       => $status,
			'post_title'   => $title,
		);

		$existing = $this->get_by_key( $snapshot_id );
		if ( $existing !== null ) {
			$data['id'] = (int) ( $existing['id'] ?? 0 );
		}

		$id = parent::save( $data );
		if ( $id <= 0 ) {
			return 0;
		}

		\update_post_meta( $id, self::META_SCOPE_TYPE, $scope_type );
		\update_post_meta( $id, self::META_SCOPE_ID, $scope_id );

		$json = wp_json_encode( $definition );
		if ( $json === false ) {
			return 0;
		}
		\update_post_meta( $id, self::META_DEFINITION, $json );
		return $id;
	}

	/**
	 * Returns full snapshot definition by post ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_id( int $id ): ?array {
		$record = $this->get_by_id( $id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Returns full snapshot definition by snapshot_id (internal key).
	 *
	 * @param string $snapshot_id
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $snapshot_id ): ?array {
		$record = $this->get_by_key( $snapshot_id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Lists snapshot definitions by scope_type.
	 *
	 * @param string $scope_type Version_Snapshot_Schema::SCOPE_* constant.
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_scope_type( string $scope_type, int $limit = 0, int $offset = 0 ): array {
		return $this->list_definitions_by_meta( self::META_SCOPE_TYPE, $scope_type, $limit, $offset );
	}

	/**
	 * Lists snapshot definitions by scope_id.
	 *
	 * @param string $scope_id
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_scope_id( string $scope_id, int $limit = 0, int $offset = 0 ): array {
		return $this->list_definitions_by_meta( self::META_SCOPE_ID, $scope_id, $limit, $offset );
	}

	/**
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	private function list_definitions_by_meta( string $meta_key, string $meta_value, int $limit, int $offset ): array {
		$limit  = $limit > 0 ? min( self::DEFAULT_LIST_LIMIT, $limit ) : self::DEFAULT_LIST_LIMIT;
		$offset = max( 0, $offset );
		$query  = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => $limit,
				'offset'                 => $offset,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'     => $meta_key,
						'value'   => $meta_value,
						'compare' => '=',
					),
				),
			)
		);
		$out    = array();
		foreach ( $query->get_posts() as $post ) {
			$meta = $this->get_meta( $post->ID );
			if ( isset( $meta['definition'] ) && is_array( $meta['definition'] ) ) {
				$out[] = $meta['definition'];
			}
		}
		return $out;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base                          = parent::get_meta( $post_id );
		$base[ self::META_SCOPE_TYPE ] = \get_post_meta( $post_id, self::META_SCOPE_TYPE, true ) ?: '';
		$base[ self::META_SCOPE_ID ]   = \get_post_meta( $post_id, self::META_SCOPE_ID, true ) ?: '';
		$raw                           = \get_post_meta( $post_id, self::META_DEFINITION, true );
		if ( is_string( $raw ) && $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$base['definition']              = $decoded;
				$base[ self::META_INTERNAL_KEY ] = $base[ self::META_INTERNAL_KEY ] ?: ( $decoded[ Version_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '' );
				$base[ self::META_STATUS ]       = $base[ self::META_STATUS ] ?: ( $decoded[ Version_Snapshot_Schema::FIELD_STATUS ] ?? '' );
			}
		}
		return $base;
	}

	/** @inheritdoc */
	protected function post_to_record( $post, array $meta ): array {
		$base = parent::post_to_record( $post, $meta );
		if ( isset( $meta['definition'] ) && is_array( $meta['definition'] ) ) {
			$base['definition'] = $meta['definition'];
		}
		return $base;
	}
}
