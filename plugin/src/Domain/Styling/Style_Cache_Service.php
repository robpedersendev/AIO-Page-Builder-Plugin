<?php
/**
 * Style cache versioning and invalidation (Prompt 256). Coordinates style output version and preview cache invalidation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;

/**
 * Holds style output version marker and invalidates preview cache when global or per-entity style data changes.
 * Cached output must contain only sanitized style data; invalidation is not triggerable by unauthenticated users.
 */
final class Style_Cache_Service {

	/** Option key for the style cache version (bumped on invalidation). */
	public const OPTION_VERSION = 'aio_style_cache_version';

	/** @var Preview_Cache_Service|null When set, invalidate() also clears preview cache. */
	private ?Preview_Cache_Service $preview_cache;

	public function __construct( ?Preview_Cache_Service $preview_cache = null ) {
		$this->preview_cache = $preview_cache;
	}

	/**
	 * Returns the current style output version (for asset versioning / cache busting).
	 *
	 * @return string Non-empty version string.
	 */
	public function get_version(): string {
		$ver = \get_option( self::OPTION_VERSION, '' );
		if ( \is_string( $ver ) && $ver !== '' ) {
			return $ver;
		}
		return (string) \time();
	}

	/**
	 * Invalidates style caches: bumps version and clears preview cache when available.
	 * Call after global or per-entity style saves, restore, or migration.
	 *
	 * @return void
	 */
	public function invalidate(): void {
		\update_option( self::OPTION_VERSION, (string) \time(), false );
		if ( $this->preview_cache !== null ) {
			$this->preview_cache->invalidate_all();
		}
	}
}
