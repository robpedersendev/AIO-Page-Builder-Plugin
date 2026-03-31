<?php
/**
 * Scans PHP sources for admin URL / redirect emissions and literal aio page= slugs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\AdminRouting;

/**
 * Supports drift checks: unknown literal `aio-page-builder*` page slugs in strings should match {@see Admin_Route_Inventory}.
 */
final class Admin_Url_Emission_Scanner {

	/**
	 * Per-file counts of emission patterns (line may match multiple categories).
	 *
	 * @return array<string, array<string, int>>
	 */
	public static function classify_emissions_by_file( string $absolute_dir ): array {
		$absolute_dir = rtrim( $absolute_dir, "/\\\r\n" );
		$by_file      = array();
		if ( $absolute_dir === '' || ! is_dir( $absolute_dir ) ) {
			return $by_file;
		}
		$patterns = array(
			'admin_url'         => '/\badmin_url\s*\(/',
			'wp_safe_redirect'  => '/\bwp_safe_redirect\s*\(/',
			'wp_redirect'       => '/\bwp_redirect\s*\(/',
			'tab_url'           => '/\btab_url\s*\(/',
			'subtab_url'        => '/\bsubtab_url\s*\(/',
			'admin_post_action' => '/[\'"]admin-post\.php[\'"]/',
			'router_url'        => '/->\s*url\s*\(\s*[\'"]/',
		);
		$it       = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $absolute_dir, \FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
				continue;
			}
			if ( strtolower( (string) $file->getExtension() ) !== 'php' ) {
				continue;
			}
			$path = $file->getPathname();
			$raw  = file_get_contents( $path );
			if ( false === $raw ) {
				continue;
			}
			$rel = self::relative_path( $absolute_dir, $path );
			foreach ( $patterns as $label => $re ) {
				$n = preg_match_all( $re, $raw );
				if ( is_int( $n ) && $n > 0 ) {
					if ( ! isset( $by_file[ $rel ] ) ) {
						$by_file[ $rel ] = array();
					}
					$by_file[ $rel ][ $label ] = ( $by_file[ $rel ][ $label ] ?? 0 ) + $n;
				}
			}
		}
		return $by_file;
	}

	/**
	 * Literal aio admin page slugs found in PHP strings (best-effort; misses ::SLUG concatenation).
	 *
	 * @return list<array{file: string, line: int, slug: string}>
	 */
	public static function literal_aio_page_slugs( string $absolute_dir ): array {
		$absolute_dir = rtrim( $absolute_dir, "/\\\r\n" );
		$hits         = array();
		if ( $absolute_dir === '' || ! is_dir( $absolute_dir ) ) {
			return $hits;
		}
		$resolvers = array(
			// * Array shape: page key plus aio-prefixed slug string (see screen SLUG constants).
			"/['\"]page['\"]\\s*=>\\s*['\"](aio-page-builder[a-z0-9-]*)['\"]/",
			// * admin.php?page=aio-...
			"/admin\\.php\\?page=(aio-page-builder[a-z0-9-]*)\b/",
			// * add_query_arg( array( 'page' => 'aio-...' — already covered by first pattern
		);
		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $absolute_dir, \FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
				continue;
			}
			if ( strtolower( (string) $file->getExtension() ) !== 'php' ) {
				continue;
			}
			$path = $file->getPathname();
			$raw  = file_get_contents( $path );
			if ( false === $raw ) {
				continue;
			}
			$rel   = self::relative_path( $absolute_dir, $path );
			$lines = preg_split( "/\r\n|\n|\r/", $raw );
			if ( ! is_array( $lines ) ) {
				continue;
			}
			foreach ( $lines as $idx => $line ) {
				$line_no = $idx + 1;
				foreach ( $resolvers as $re ) {
					if ( preg_match_all( $re, $line, $m ) && ! empty( $m[1] ) ) {
						foreach ( $m[1] as $slug ) {
							$hits[] = array(
								'file' => $rel,
								'line' => $line_no,
								'slug' => (string) $slug,
							);
						}
					}
				}
			}
		}
		return $hits;
	}

	/**
	 * Slugs from literals that are not in the route inventory allowlist.
	 *
	 * @return list<string>
	 */
	public static function unknown_literal_slugs( string $absolute_dir ): array {
		$allowed = array_flip( Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS );
		$unknown = array();
		foreach ( self::literal_aio_page_slugs( $absolute_dir ) as $row ) {
			$s = (string) ( $row['slug'] ?? '' );
			if ( $s === '' || isset( $allowed[ $s ] ) ) {
				continue;
			}
			$unknown[] = $s;
		}
		return array_values( array_unique( $unknown ) );
	}

	private static function relative_path( string $root, string $absolute ): string {
		$root = rtrim( str_replace( '\\', '/', $root ), '/' );
		$abs  = str_replace( '\\', '/', $absolute );
		if ( str_starts_with( $abs, $root . '/' ) ) {
			return substr( $abs, strlen( $root ) + 1 );
		}
		return basename( $absolute );
	}
}
