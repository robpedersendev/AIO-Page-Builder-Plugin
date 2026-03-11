<?php
/**
 * Data access for Prompt Pack objects (spec §26, §10.6). Backing: CPT aio_prompt_pack.
 * Full pack definition in meta _aio_prompt_pack_definition. Internal key and version in meta for lookup.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Repository_Interface;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

/**
 * Repository → storage: Object_Type_Keys::PROMPT_PACK (CPT).
 * Implements Prompt_Pack_Registry_Repository_Interface for registry and test doubles.
 */
final class Prompt_Pack_Repository extends Abstract_CPT_Repository implements Prompt_Pack_Registry_Repository_Interface {

	protected const META_DEFINITION  = '_aio_prompt_pack_definition';
	protected const META_PACK_VERSION = '_aio_pack_version';

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::PROMPT_PACK;
	}

	/**
	 * Returns full prompt pack definition by internal_key and version.
	 *
	 * @param string $internal_key Pack internal_key (e.g. aio/build-plan-draft).
	 * @param string $version      Semantic version (e.g. 1.0.0).
	 * @return array<string, mixed>|null Full pack definition or null.
	 */
	public function get_definition_by_key_and_version( string $internal_key, string $version ): ?array {
		$key = $this->sanitize_key( $internal_key );
		$version = trim( $version );
		if ( $key === '' || $version === '' ) {
			return null;
		}
		$query = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array( 'key' => self::META_INTERNAL_KEY, 'value' => $key, 'compare' => '=' ),
					array( 'key' => self::META_PACK_VERSION, 'value' => $version, 'compare' => '=' ),
				),
			)
		);
		$posts = $query->get_posts();
		if ( empty( $posts ) ) {
			return null;
		}
		$record = $this->get_by_id( $posts[0]->ID );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Returns one pack definition by internal_key (any status). Prefers active; otherwise first found.
	 *
	 * @param string $internal_key Pack internal_key.
	 * @return array<string, mixed>|null Full pack definition or null.
	 */
	public function get_definition_by_key( string $internal_key ): ?array {
		$key = $this->sanitize_key( $internal_key );
		if ( $key === '' ) {
			return null;
		}
		$query = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => 50,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array( 'key' => self::META_INTERNAL_KEY, 'value' => $key, 'compare' => '=' ),
				),
			)
		);
		$posts = $query->get_posts();
		if ( empty( $posts ) ) {
			return null;
		}
		foreach ( $posts as $post ) {
			$record = $this->get_by_id( $post->ID );
			if ( $record !== null && isset( $record['definition'] ) ) {
				$def = $record['definition'];
				$status = $def[ Prompt_Pack_Schema::ROOT_STATUS ] ?? '';
				if ( $status === Prompt_Pack_Schema::STATUS_ACTIVE ) {
					return $def;
				}
			}
		}
		$record = $this->get_by_id( $posts[0]->ID );
		return $record !== null && isset( $record['definition'] ) ? $record['definition'] : null;
	}

	/**
	 * Lists full pack definitions by status (e.g. active). Used by registry for planning selection.
	 *
	 * @param string $status Status (active, inactive, deprecated).
	 * @param int    $limit  Max items.
	 * @param int    $offset Offset.
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$status = $this->sanitize_status( $status );
		$limit  = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$records = $this->list_by_status( $status, $limit, $offset );
		$out = array();
		foreach ( $records as $r ) {
			if ( isset( $r['definition'] ) && is_array( $r['definition'] ) ) {
				$out[] = $r['definition'];
			}
		}
		return $out;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base = parent::get_meta( $post_id );
		$raw = \get_post_meta( $post_id, self::META_DEFINITION, true );
		if ( is_string( $raw ) && $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$base['definition'] = $decoded;
				$base[ self::META_INTERNAL_KEY ] = $base[ self::META_INTERNAL_KEY ] ?: ( $decoded[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
				$base[ self::META_STATUS ]       = $base[ self::META_STATUS ] ?: ( $decoded[ Prompt_Pack_Schema::ROOT_STATUS ] ?? '' );
				$base[ self::META_PACK_VERSION ] = $decoded[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '';
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

	/**
	 * Persists full prompt pack definition. Sets internal_key and version from definition.
	 *
	 * @param array<string, mixed> $definition Full pack (internal_key, version, status, segments, ...).
	 * @return int Post ID or 0 on failure.
	 */
	public function save_definition( array $definition ): int {
		$internal_key = (string) ( $definition[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
		$version      = (string) ( $definition[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
		$status       = (string) ( $definition[ Prompt_Pack_Schema::ROOT_STATUS ] ?? 'inactive' );
		$name         = (string) ( $definition[ Prompt_Pack_Schema::ROOT_NAME ] ?? $internal_key ?: 'Untitled' );

		$data = array(
			'internal_key' => $internal_key,
			'status'       => $status,
			'post_title'   => \sanitize_text_field( $name ),
		);

		$existing = $this->get_definition_by_key_and_version( $internal_key, $version );
		if ( $existing !== null ) {
			$record = $this->get_by_key( $internal_key . '|' . $version );
			if ( $record !== null && isset( $record['id'] ) ) {
				$data['id'] = (int) $record['id'];
			}
		}

		$id = parent::save( $data );
		if ( $id <= 0 ) {
			return 0;
		}

		\update_post_meta( $id, self::META_PACK_VERSION, $version );
		$json = wp_json_encode( $definition );
		if ( $json === false ) {
			return 0;
		}
		\update_post_meta( $id, self::META_DEFINITION, $json );
		return $id;
	}

	/** @inheritdoc */
	public function get_by_key( string $key ): ?array {
		if ( strpos( $key, '|' ) !== false ) {
			list( $internal_key, $version ) = array_map( 'trim', explode( '|', $key, 2 ) );
			$key = $this->sanitize_key( $internal_key );
			$version = trim( $version );
			if ( $key === '' || $version === '' ) {
				return null;
			}
			$query = new \WP_Query(
				array(
					'post_type'              => $this->get_post_type(),
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => true,
					'meta_query'             => array(
						array( 'key' => self::META_INTERNAL_KEY, 'value' => $key, 'compare' => '=' ),
						array( 'key' => self::META_PACK_VERSION, 'value' => $version, 'compare' => '=' ),
					),
				)
			);
			$posts = $query->get_posts();
			if ( empty( $posts ) ) {
				return null;
			}
			return $this->get_by_id( $posts[0]->ID );
		}
		$def = $this->get_definition_by_key( $key );
		if ( $def === null ) {
			return null;
		}
		$internal_key = (string) ( $def[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? $key );
		return array(
			'id'           => 0,
			'post_type'    => $this->get_post_type(),
			'post_title'   => $def[ Prompt_Pack_Schema::ROOT_NAME ] ?? '',
			'post_status'  => 'publish',
			'post_name'    => '',
			'internal_key' => $internal_key,
			'status'       => $def[ Prompt_Pack_Schema::ROOT_STATUS ] ?? '',
			'definition'   => $def,
		);
	}
}
