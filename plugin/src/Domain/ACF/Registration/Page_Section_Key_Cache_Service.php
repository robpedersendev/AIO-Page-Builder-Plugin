<?php
/**
 * Optional bounded cache for resolved section keys per page ID or template/composition key (Prompt 290).
 * Reduces repeated assignment-map and derivation work on admin edit loads. Invalidated on assignment change.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Caches section-key lists only (no full definitions). Safe to miss; correctness over speed.
 * Multisite (Prompt 303): cache keys are site-scoped via blog_id suffix so no cross-site bleed.
 */
final class Page_Section_Key_Cache_Service {

	/** Transient key prefix for page-scoped section keys. */
	private const PREFIX_PAGE = 'aio_acf_sk_p_';

	/** Transient key prefix for template-scoped section keys. */
	private const PREFIX_TEMPLATE = 'aio_acf_sk_t_';

	/** Transient key prefix for composition-scoped section keys. */
	private const PREFIX_COMPOSITION = 'aio_acf_sk_c_';

	/** Default TTL in seconds (5 minutes). */
	private const DEFAULT_TTL = 300;

	/** @var int */
	private int $ttl;

	public function __construct( int $ttl = self::DEFAULT_TTL ) {
		$this->ttl = $ttl > 0 ? $ttl : self::DEFAULT_TTL;
	}

	/**
	 * Returns site-scoped suffix for transient keys (Prompt 303). Empty on single-site; blog id on multisite.
	 *
	 * @return string
	 */
	private function get_site_suffix(): string {
		if ( ! \function_exists( 'is_multisite' ) || ! is_multisite() ) {
			return '';
		}
		return \function_exists( 'get_current_blog_id' ) ? (string) get_current_blog_id() . '_' : '';
	}

	/**
	 * Gets cached section keys for a page. Returns null on miss or invalid.
	 *
	 * @param int $page_id
	 * @return list<string>|null
	 */
	public function get_for_page( int $page_id ): ?array {
		if ( $page_id <= 0 ) {
			return null;
		}
		$key = self::PREFIX_PAGE . $this->get_site_suffix() . (string) $page_id;
		$raw = \get_transient( $key );
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$out = array();
		foreach ( $raw as $sk ) {
			if ( is_string( $sk ) && $sk !== '' ) {
				$out[] = $sk;
			}
		}
		return $out;
	}

	/**
	 * Sets cached section keys for a page.
	 *
	 * @param int           $page_id
	 * @param list<string>  $section_keys
	 */
	public function set_for_page( int $page_id, array $section_keys ): void {
		if ( $page_id <= 0 ) {
			return;
		}
		$key = self::PREFIX_PAGE . (string) $page_id;
		\set_transient( $key, array_values( $section_keys ), $this->ttl );
	}

	/**
	 * Invalidates cache for a page (call when assignment changes).
	 *
	 * @param int $page_id
	 */
	public function invalidate_for_page( int $page_id ): void {
		if ( $page_id <= 0 ) {
			return;
		}
		\delete_transient( self::PREFIX_PAGE . $this->get_site_suffix() . (string) $page_id );
	}

	/**
	 * Gets cached section keys for a template. Returns null on miss.
	 *
	 * @param string $template_key
	 * @return list<string>|null
	 */
	public function get_for_template( string $template_key ): ?array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return null;
		}
		$key = self::PREFIX_TEMPLATE . $template_key;
		$raw = \get_transient( $key );
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$out = array();
		foreach ( $raw as $sk ) {
			if ( is_string( $sk ) && $sk !== '' ) {
				$out[] = $sk;
			}
		}
		return $out;
	}

	/**
	 * Sets cached section keys for a template.
	 *
	 * @param string        $template_key
	 * @param list<string>  $section_keys
	 */
	public function set_for_template( string $template_key, array $section_keys ): void {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return;
		}
		\set_transient( self::PREFIX_TEMPLATE . $this->get_site_suffix() . $template_key, array_values( $section_keys ), $this->ttl );
	}

	/**
	 * Invalidates cache for a template (call when template definition changes).
	 *
	 * @param string $template_key
	 */
	public function invalidate_for_template( string $template_key ): void {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return;
		}
		\delete_transient( self::PREFIX_TEMPLATE . $this->get_site_suffix() . $template_key );
	}

	/**
	 * Gets cached section keys for a composition. Returns null on miss.
	 *
	 * @param string $composition_id
	 * @return list<string>|null
	 */
	public function get_for_composition( string $composition_id ): ?array {
		$composition_id = \sanitize_key( $composition_id );
		if ( $composition_id === '' ) {
			return null;
		}
		$raw = \get_transient( self::PREFIX_COMPOSITION . $composition_id );
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$out = array();
		foreach ( $raw as $sk ) {
			if ( is_string( $sk ) && $sk !== '' ) {
				$out[] = $sk;
			}
		}
		return $out;
	}

	/**
	 * Sets cached section keys for a composition.
	 *
	 * @param string        $composition_id
	 * @param list<string>  $section_keys
	 */
	public function set_for_composition( string $composition_id, array $section_keys ): void {
		$composition_id = \sanitize_key( $composition_id );
		if ( $composition_id === '' ) {
			return;
		}
		\set_transient( self::PREFIX_COMPOSITION . $this->get_site_suffix() . $composition_id, array_values( $section_keys ), $this->ttl );
	}

	/**
	 * Invalidates cache for a composition.
	 *
	 * @param string $composition_id
	 */
	public function invalidate_for_composition( string $composition_id ): void {
		$composition_id = \sanitize_key( $composition_id );
		if ( $composition_id === '' ) {
			return;
		}
		\delete_transient( self::PREFIX_COMPOSITION . $this->get_site_suffix() . $composition_id );
	}

	/**
	 * Hooks into assignment change to invalidate page cache. Call from bootstrap or provider.
	 */
	public function listen_for_assignment_changes(): void {
		\add_action( 'aio_acf_assignment_changed', array( $this, 'invalidate_for_page' ), 10, 1 );
	}

	/**
	 * Hooks into template/composition definition saves to invalidate derived section-key caches (Prompt 300).
	 * Call from bootstrap or provider together with listen_for_assignment_changes.
	 */
	public function listen_for_definition_changes(): void {
		\add_action( 'aio_page_template_definition_saved', array( $this, 'invalidate_for_template' ), 10, 1 );
		\add_action( 'aio_composition_definition_saved', array( $this, 'invalidate_for_composition' ), 10, 1 );
	}
}
