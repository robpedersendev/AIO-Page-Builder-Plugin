<?php
/**
 * Data access for Custom Template Composition objects (spec §10.3). Backing: CPT aio_composition.
 * Persists full normalized composition definition in meta _aio_composition_definition.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::COMPOSITION (CPT).
 * Internal key: composition_id (e.g. comp_xxx). Status: draft | active | archived.
 * Full definition stored in _aio_composition_definition meta (JSON).
 */
final class Composition_Repository extends Abstract_CPT_Repository {

	/** Meta key for full composition definition (JSON-encoded). */
	protected const META_DEFINITION = '_aio_composition_definition';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::COMPOSITION;
	}

	/**
	 * Persists full normalized composition definition.
	 *
	 * @param array<string, mixed> $definition Normalized composition definition.
	 * @return int Post ID on success; 0 on failure.
	 */
	public function save_definition( array $definition ): int {
		$comp_id = (string) ( $definition[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		$status  = (string) ( $definition[ Composition_Schema::FIELD_STATUS ] ?? 'draft' );
		$name_raw = $definition[ Composition_Schema::FIELD_NAME ] ?? $comp_id;
		$name    = (string) ( $name_raw !== '' && $name_raw !== null ? $name_raw : 'Untitled' );

		$data = array(
			'internal_key' => $comp_id,
			'status'       => $status,
			'post_title'   => \sanitize_text_field( $name ),
		);

		$existing = $comp_id !== '' ? $this->get_by_key( $comp_id ) : null;
		if ( $existing !== null ) {
			$data['id'] = (int) ( $existing['id'] ?? 0 );
		}

		$id = parent::save( $data );
		if ( $id <= 0 ) {
			return 0;
		}

		$json = wp_json_encode( $definition );
		if ( $json === false ) {
			return 0;
		}
		\update_post_meta( $id, self::META_DEFINITION, $json );
		if ( $comp_id !== '' ) {
			do_action( 'aio_composition_definition_saved', $comp_id );
		}
		return $id;
	}

	/**
	 * Returns full composition definition by composition_id (internal key).
	 *
	 * @param string $composition_id
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $composition_id ): ?array {
		$record = $this->get_by_key( $composition_id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Returns full composition definition by post ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_id( int $id ): ?array {
		$record = $this->get_by_id( $id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Lists composition definitions by status.
	 *
	 * @param string $status
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$records = $this->list_by_status( $status, $limit, $offset );
		$out     = array();
		foreach ( $records as $r ) {
			if ( isset( $r['definition'] ) && is_array( $r['definition'] ) ) {
				$out[] = $r['definition'];
			}
		}
		return $out;
	}

	/**
	 * Lists all composition definitions (any status). For export and diagnostics.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_all_definitions( int $limit = 0, int $offset = 0 ): array {
		$limit = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$query = new \WP_Query(
			array(
				'post_type'      => $this->get_post_type(),
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'no_found_rows'  => true,
				'post_status'    => 'any',
			)
		);
		$out   = array();
		foreach ( $query->get_posts() as $post ) {
			$record = $this->post_to_record( $post, $this->get_meta( $post->ID ) );
			if ( isset( $record['definition'] ) && is_array( $record['definition'] ) ) {
				$out[] = $record['definition'];
			}
		}
		return $out;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base = parent::get_meta( $post_id );
		$raw  = \get_post_meta( $post_id, self::META_DEFINITION, true );
		if ( is_string( $raw ) && $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$base['definition']              = $decoded;
				$ik = $base[ self::META_INTERNAL_KEY ];
				$st = $base[ self::META_STATUS ];
				$base[ self::META_INTERNAL_KEY ] = ( $ik !== '' && $ik !== null && $ik !== false ) ? $ik : ( $decoded[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
				$base[ self::META_STATUS ]       = ( $st !== '' && $st !== null && $st !== false ) ? $st : ( $decoded[ Composition_Schema::FIELD_STATUS ] ?? '' );
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
