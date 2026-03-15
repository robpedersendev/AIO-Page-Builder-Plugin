<?php
/**
 * Data access for Page Template objects (spec §10.2). Backing: CPT aio_page_template.
 * Persists full normalized page template definition in meta _aio_page_template_definition.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::PAGE_TEMPLATE (CPT).
 * Internal key: stable slug (e.g. pt_landing_contact). Status: draft | active | inactive | deprecated.
 * Full definition stored in _aio_page_template_definition meta (JSON).
 */
final class Page_Template_Repository extends Abstract_CPT_Repository implements Page_Template_Repository_Interface {

	/** Meta key for full page template definition (JSON-encoded). */
	protected const META_DEFINITION = '_aio_page_template_definition';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::PAGE_TEMPLATE;
	}

	/**
	 * Persists full normalized page template definition.
	 *
	 * @param array<string, mixed> $definition Normalized page template definition.
	 * @return int Post ID on success; 0 on failure.
	 */
	public function save_definition( array $definition ): int {
		$key    = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$status = (string) ( $definition[ Page_Template_Schema::FIELD_STATUS ] ?? 'draft' );
		$name   = (string) ( $definition[ Page_Template_Schema::FIELD_NAME ] ?? $key ?: 'Untitled' );

		$data = array(
			'internal_key' => $key,
			'status'      => $status,
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
		if ( $key !== '' ) {
			do_action( 'aio_page_template_definition_saved', $key );
		}
		return $id;
	}

	/**
	 * Returns full page template definition by internal key.
	 *
	 * @param string $key
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $key ): ?array {
		$record = $this->get_by_key( $key );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Returns full page template definition by post ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_id( int $id ): ?array {
		$record = $this->get_by_id( $id );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Lists page template definitions by archetype.
	 *
	 * @param string $archetype
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_by_archetype( string $archetype, int $limit = 0, int $offset = 0 ): array {
		$all = $this->list_all_definitions( $limit, $offset );
		if ( $archetype === '' ) {
			return $all;
		}
		return array_values( array_filter( $all, function ( $def ) use ( $archetype ) {
			return ( (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' ) ) === $archetype;
		} ) );
	}

	/**
	 * Lists page template definitions by status.
	 *
	 * @param string $status
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$records = $this->list_by_status( $status, $limit, $offset );
		$out = array();
		foreach ( $records as $r ) {
			if ( isset( $r['definition'] ) && is_array( $r['definition'] ) ) {
				$out[] = $r['definition'];
			}
		}
		return $out;
	}

	/**
	 * Returns total count of page template posts (any status). Used for large-library pagination.
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
	 * Lists all page template definitions up to a cap (for in-memory filtering; spec §55.8).
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
			$out = array_merge( $out, $batch );
			$offset += $limit;
			if ( count( $batch ) < $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Lists all page template definitions (any status).
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
		$out = array();
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
				$base['definition'] = $decoded;
				$base[ self::META_INTERNAL_KEY ] = $base[ self::META_INTERNAL_KEY ] ?: ( $decoded[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
				$base[ self::META_STATUS ] = $base[ self::META_STATUS ] ?: ( $decoded[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
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
