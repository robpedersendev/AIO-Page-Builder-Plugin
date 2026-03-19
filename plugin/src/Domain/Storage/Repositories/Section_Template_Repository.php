<?php
/**
 * Data access for Section Template objects (spec §10.1). Backing: CPT aio_section_template.
 * Persists full normalized section definition in meta _aio_section_definition.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::SECTION_TEMPLATE (CPT).
 * Internal key: stable slug (e.g. st01_hero). Status: draft | active | inactive | deprecated.
 * Full definition stored in _aio_section_definition meta (JSON).
 */
final class Section_Template_Repository extends Abstract_CPT_Repository implements Section_Template_Repository_Interface {

	/** Meta key for full section definition (JSON-encoded). */
	protected const META_DEFINITION = '_aio_section_definition';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::SECTION_TEMPLATE;
	}

	/**
	 * Persists full normalized section definition. Creates or updates post; syncs internal_key and status to meta.
	 *
	 * @param array<string, mixed> $definition Normalized section definition (schema shape).
	 * @return int Post ID on success; 0 on failure.
	 */
	public function save_definition( array $definition ): int {
		$key    = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$status = (string) ( $definition[ Section_Schema::FIELD_STATUS ] ?? 'draft' );
		$name_raw = $definition[ Section_Schema::FIELD_NAME ] ?? $key;
		$name   = (string) ( $name_raw !== '' && $name_raw !== null ? $name_raw : 'Untitled' );

		$data = array(
			'internal_key' => $key,
			'status'       => $status,
			'post_title'   => \sanitize_text_field( $name ),
		);

		$existing = $key !== '' ? $this->get_by_key( $key ) : null;
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
		return $id;
	}

	/**
	 * Returns full section definition by internal key, or null if not found.
	 *
	 * @param string $key Internal section key.
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $key ): ?array {
		$record = $this->get_by_key( $key );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Returns full section definition by post ID.
	 *
	 * @param int $id Post ID.
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_id( int $id ): ?array {
		$record = $this->get_by_id( $id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Lists section definitions by category.
	 *
	 * @param string $category Category slug (Section_Schema allowed).
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_by_category( string $category, int $limit = 0, int $offset = 0 ): array {
		$all = $this->list_all_definitions( $limit, $offset );
		if ( $category === '' ) {
			return $all;
		}
		return array_values(
			array_filter(
				$all,
				function ( $def ) use ( $category ) {
					return ( (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ) ) === $category;
				}
			)
		);
	}

	/**
	 * Lists section definitions by status.
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
	 * Lists all section definitions (any status). Excludes records without valid definition.
	 * Not used from ACF registration bootstrap (conditional registration uses get_definition_by_key per section). See docs/qa/acf-blueprint-bulk-load-elimination-report.md.
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

	/**
	 * Returns total count of section template posts (any status). Used for large-library pagination.
	 *
	 * @return int
	 */
	public function count_definitions(): int {
		$query = new \WP_Query(
			array(
				'post_type'      => $this->get_post_type(),
				'posts_per_page' => 1,
				'no_found_rows'  => false,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Lists all section definitions up to a cap (for in-memory filtering; spec §55.8).
	 *
	 * @param int $max Maximum definitions to load (bounded for large libraries).
	 * @return list<array<string, mixed>>
	 */
	public function list_all_definitions_capped( int $max = 1000 ): array {
		$chunk_size = min( 200, max( 1, $max ) );
		$out        = array();
		$offset     = 0;
		while ( count( $out ) < $max ) {
			$limit = min( $chunk_size, $max - count( $out ) );
			$batch = $this->list_all_definitions( $limit, $offset );
			if ( empty( $batch ) ) {
				break;
			}
			$out     = array_merge( $out, $batch );
			$offset += $limit;
			if ( count( $batch ) < $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Returns all unique internal keys in the registry (for uniqueness checks).
	 *
	 * @return list<string>
	 */
	public function get_all_internal_keys(): array {
		$defs = $this->list_all_definitions( 9999, 0 );
		$keys = array();
		foreach ( $defs as $d ) {
			$k = (string) ( $d[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $k !== '' ) {
				$keys[] = $k;
			}
		}
		return array_values( array_unique( $keys ) );
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
				$base[ self::META_INTERNAL_KEY ] = ( $ik !== '' && $ik !== null && $ik !== false ) ? $ik : ( $decoded[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
				$base[ self::META_STATUS ]       = ( $st !== '' && $st !== null && $st !== false ) ? $st : ( $decoded[ Section_Schema::FIELD_STATUS ] ?? '' );
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
