<?php
/**
 * Discovers admin page slugs from PHP sources (public const SLUG / HUB_PAGE_SLUG).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\AdminRouting;

/**
 * Static scan used with {@see Admin_Route_Inventory} to fail CI when a new admin slug is added without updating the inventory.
 */
final class Admin_Page_Slug_Scanner {

	/**
	 * Collects unique slugs under a directory tree.
	 *
	 * @param string $absolute_dir Absolute path to scan (e.g. plugin src/).
	 * @return list<string> Sorted unique slugs.
	 */
	public static function discover_slugs( string $absolute_dir ): array {
		$absolute_dir = rtrim( $absolute_dir, "/\\\r\n" );
		if ( $absolute_dir === '' || ! is_dir( $absolute_dir ) ) {
			return array();
		}
		$found = array();
		$it    = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $absolute_dir, \FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
				continue;
			}
			if ( strtolower( (string) $file->getExtension() ) !== 'php' ) {
				continue;
			}
			$contents = file_get_contents( $file->getPathname() );
			if ( false === $contents ) {
				continue;
			}
			if ( preg_match_all( "/public const SLUG = '([a-z0-9-]+)';/", $contents, $m ) ) {
				foreach ( $m[1] as $slug ) {
					$found[] = $slug;
				}
			}
			if ( preg_match_all( "/public const HUB_PAGE_SLUG = '([a-z0-9-]+)';/", $contents, $m2 ) ) {
				foreach ( $m2[1] as $slug ) {
					$found[] = $slug;
				}
			}
		}
		$found = array_values( array_unique( $found ) );
		sort( $found, SORT_STRING );
		return $found;
	}
}
