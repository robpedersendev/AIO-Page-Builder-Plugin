<?php
/**
 * Preview snapshot cache for section and page template previews (Prompt 184, spec §55.5, §55.8).
 * Bounded storage, deterministic invalidation by template/version, and asset-budget-aware usage.
 * Caching is an optimization layer; preview content remains from the real renderer.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Manages cached preview HTML for section and page templates. Invalidates on template/version change.
 */
final class Preview_Cache_Service {

	/** Option key for stored cache entries. */
	private const OPTION_KEY = 'aio_preview_snapshot_cache';

	/** Default maximum number of cache entries (list + detail; spec §55.8). */
	private const DEFAULT_MAX_ENTRIES = 800;

	/** @var int Maximum cache entries before eviction (LRU by created_at). */
	private int $max_entries;

	public function __construct( int $max_entries = self::DEFAULT_MAX_ENTRIES ) {
		$this->max_entries = $max_entries > 0 ? $max_entries : self::DEFAULT_MAX_ENTRIES;
	}

	/**
	 * Builds a deterministic cache key from context and definition version.
	 *
	 * @param Synthetic_Preview_Context $context
	 * @param array<string, mixed>     $definition Section or page definition (must include version and identity).
	 * @return string
	 */
	public function get_cache_key( Synthetic_Preview_Context $context, array $definition ): string {
		$version_hash = $this->definition_version_hash( $definition, $context->get_type() );
		$parts = array(
			$context->get_type(),
			$context->get_key(),
			$context->get_purpose_family(),
			$context->get_template_category_class(),
			$context->get_variant(),
			$context->is_reduced_motion() ? '1' : '0',
			$context->get_animation_tier(),
			$context->get_omission_case(),
			$version_hash,
		);
		return 'aio_preview_' . \md5( \implode( "\n", $parts ) );
	}

	/**
	 * Returns a hash of definition fields that affect preview output (for invalidation).
	 *
	 * @param array<string, mixed> $definition
	 * @param string               $type TYPE_SECTION | TYPE_PAGE
	 * @return string
	 */
	public function definition_version_hash( array $definition, string $type ): string {
		if ( $type === Synthetic_Preview_Context::TYPE_SECTION ) {
			$key    = (string) ( $definition['internal_key'] ?? '' );
			$ver    = $definition['version'] ?? array();
			$bp_ref = (string) ( $definition['field_blueprint_ref'] ?? '' );
			return \md5( $key . \wp_json_encode( $ver ) . $bp_ref );
		}
		$key    = (string) ( $definition['internal_key'] ?? '' );
		$ver    = $definition['version'] ?? array();
		$ordered = $definition['ordered_sections'] ?? array();
		$section_keys = array();
		foreach ( $ordered as $item ) {
			if ( \is_array( $item ) && isset( $item['section_key'] ) ) {
				$section_keys[] = $item['section_key'];
			}
		}
		return \md5( $key . \wp_json_encode( $ver ) . \implode( ',', $section_keys ) );
	}

	/**
	 * Gets a cached preview record by cache key.
	 *
	 * @param string $cache_key
	 * @return Preview_Cache_Record|null
	 */
	public function get( string $cache_key ): ?Preview_Cache_Record {
		if ( $cache_key === '' ) {
			return null;
		}
		$store = $this->load_store();
		if ( ! isset( $store[ $cache_key ] ) || ! \is_array( $store[ $cache_key ] ) ) {
			return null;
		}
		return Preview_Cache_Record::from_array( $store[ $cache_key ] );
	}

	/**
	 * Stores a preview record and evicts oldest entries if over budget.
	 *
	 * @param Preview_Cache_Record $record
	 * @return bool True if stored.
	 */
	public function set( Preview_Cache_Record $record ): bool {
		$key = $record->get_cache_key();
		if ( $key === '' ) {
			return false;
		}
		$store = $this->load_store();
		$store[ $key ] = $record->to_array();
		$this->evict_if_over_budget( $store );
		return \update_option( self::OPTION_KEY, $store, false ) !== false;
	}

	/**
	 * Invalidates all cache entries for a given template (e.g. after template update).
	 *
	 * @param string $type TYPE_SECTION | TYPE_PAGE
	 * @param string $template_key Section or page internal_key.
	 * @return int Number of entries removed.
	 */
	public function invalidate_for_template( string $type, string $template_key ): int {
		if ( $template_key === '' ) {
			return 0;
		}
		$store = $this->load_store();
		$removed = 0;
		foreach ( \array_keys( $store ) as $k ) {
			$entry = $store[ $k ];
			if ( \is_array( $entry ) && ( (string) ( $entry['type'] ?? '' ) ) === $type && ( (string) ( $entry['template_key'] ?? '' ) ) === $template_key ) {
				unset( $store[ $k ] );
				++$removed;
			}
		}
		if ( $removed > 0 ) {
			\update_option( self::OPTION_KEY, $store, false );
		}
		return $removed;
	}

	/**
	 * Clears all preview cache entries (e.g. for QA or debugging).
	 *
	 * @return void
	 */
	public function invalidate_all(): void {
		\delete_option( self::OPTION_KEY );
	}

	/**
	 * Returns whether the cache has a stored entry for the given key (for staleness/debugging).
	 *
	 * @param string $cache_key
	 * @return bool
	 */
	public function has( string $cache_key ): bool {
		return $this->get( $cache_key ) !== null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function load_store(): array {
		$raw = \get_option( self::OPTION_KEY, array() );
		return \is_array( $raw ) ? $raw : array();
	}

	/**
	 * Evicts oldest entries (by created_at) until count <= max_entries.
	 *
	 * @param array<string, array<string, mixed>> $store Modified in place.
	 * @return void
	 */
	private function evict_if_over_budget( array &$store ): void {
		if ( \count( $store ) <= $this->max_entries ) {
			return;
		}
		$by_time = array();
		foreach ( $store as $k => $entry ) {
			$created = isset( $entry['created_at'] ) ? (int) $entry['created_at'] : 0;
			$by_time[] = array( 'key' => $k, 'created_at' => $created );
		}
		\usort( $by_time, function ( $a, $b ) {
			return $a['created_at'] <=> $b['created_at'];
		} );
		$to_remove = \count( $store ) - $this->max_entries;
		for ( $i = 0; $i < $to_remove && $i < \count( $by_time ); $i++ ) {
			unset( $store[ $by_time[ $i ]['key'] ] );
		}
	}
}
