<?php
/**
 * Builds ZIP archive from staging directory and computes file checksums (spec §52.2, §5).
 *
 * Paths in ZIP use forward slashes; only files are checksummed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;

/**
 * Creates ZIP from staging dir, writes to plugin exports path, returns checksum list and size.
 */
final class Export_Zip_Packager {

	/** Checksum algorithm for package_checksum_list (contract §5). */
	private const CHECKSUM_ALGO = 'sha256';

	/** @var Plugin_Path_Manager */
	private Plugin_Path_Manager $path_manager;

	public function __construct( Plugin_Path_Manager $path_manager ) {
		$this->path_manager = $path_manager;
	}

	/**
	 * Builds ZIP from staging directory and writes to destination path.
	 * Computes sha256 checksum for each file added. Directories are not checksummed.
	 *
	 * @param string        $staging_dir     Absolute path to directory containing category dirs (and optionally manifest.json).
	 * @param string        $destination_zip  Absolute path where ZIP will be written.
	 * @param callable|null $manifest_factory Optional. Receives (array $checksum_list), returns manifest JSON string. If set, manifest.json is added last; otherwise any existing manifest.json in staging is added as-is.
	 * @return array{checksum_list: array<string, string>, size_bytes: int, success: bool, error: string}
	 */
	public function pack( string $staging_dir, string $destination_zip, ?callable $manifest_factory = null ): array {
		$checksum_list = array();
		$size_bytes    = 0;
		$staging_dir   = rtrim( str_replace( '\\', '/', $staging_dir ), '/' );
		if ( ! is_dir( $staging_dir ) ) {
			return array(
				'checksum_list' => array(),
				'size_bytes'    => 0,
				'success'       => false,
				'error'         => 'Staging directory does not exist.',
			);
		}
		$zip = new \ZipArchive();
		if ( $zip->open( $destination_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
			return array(
				'checksum_list' => array(),
				'size_bytes'    => 0,
				'success'       => false,
				'error'         => 'Could not create ZIP file.',
			);
		}

		$files          = $this->list_files_in_dir( $staging_dir );
		$manifest_local = 'manifest.json';
		foreach ( $files as $absolute_path ) {
			$relative = $this->relative_path( $absolute_path, $staging_dir );
			if ( $relative === '' ) {
				continue;
			}
			$local_name = str_replace( '\\', '/', $relative );
			if ( $manifest_factory !== null && ( $local_name === $manifest_local || basename( $absolute_path ) === 'manifest.json' ) ) {
				continue;
			}
			$zip->addFile( $absolute_path, $local_name );
			$hash = $this->file_checksum( $absolute_path );
			if ( $hash !== '' ) {
				$checksum_list[ $local_name ] = self::CHECKSUM_ALGO . ':' . $hash;
			}
		}
		if ( $manifest_factory !== null ) {
			$manifest_json = $manifest_factory( $checksum_list );
			$zip->addFromString( $manifest_local, $manifest_json );
		}
		$zip->close();

		if ( is_file( $destination_zip ) ) {
			$size_bytes = (int) filesize( $destination_zip );
		}

		return array(
			'checksum_list' => $checksum_list,
			'size_bytes'    => $size_bytes,
			'success'       => true,
			'error'         => '',
		);
	}

	/**
	 * Returns a safe export package filename per contract §10.
	 *
	 * @param string $mode      Export mode key (e.g. full_operational_backup).
	 * @param string $site_slug Sanitized site identifier (hostname or slug; no credentials).
	 * @return string Filename like aio-export-full_operational_backup-20250715-120000-example.zip
	 */
	public function build_package_filename( string $mode, string $site_slug ): string {
		$mode_clean = preg_replace( '#[^a-z0-9_]#', '', strtolower( $mode ) );
		$slug_clean = preg_replace( '#[^a-zA-Z0-9_-]#', '', $site_slug );
		$mode_safe  = $mode_clean !== '' && $mode_clean !== null ? $mode_clean : 'export';
		$slug_safe  = $slug_clean !== '' && $slug_clean !== null ? $slug_clean : 'site';
		$date      = gmdate( 'Ymd' );
		$time      = gmdate( 'His' );
		return sprintf( 'aio-export-%s-%s-%s-%s.zip', $mode_safe, $date, $time, $slug_safe );
	}

	/**
	 * Lists all files in a directory recursively (no directories).
	 *
	 * @param string $dir Absolute path.
	 * @return array<int, string>
	 */
	private function list_files_in_dir( string $dir ): array {
		$out = array();
		if ( ! is_dir( $dir ) ) {
			return $out;
		}
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iter as $item ) {
			if ( $item->isFile() ) {
				$path = $item->getRealPath();
				if ( $path !== false ) {
					$out[] = $path;
				}
			}
		}
		return $out;
	}

	/**
	 * Returns path relative to base (forward slashes).
	 *
	 * @param string $absolute Full path.
	 * @param string $base     Base directory.
	 * @return string
	 */
	private function relative_path( string $absolute, string $base ): string {
		$absolute = str_replace( '\\', '/', $absolute );
		$base     = str_replace( '\\', '/', $base );
		if ( strpos( $absolute, $base ) !== 0 ) {
			return '';
		}
		$rel = substr( $absolute, strlen( $base ) );
		return trim( $rel, '/' );
	}

	/**
	 * Returns sha256 hex hash of file contents.
	 *
	 * @param string $path File path.
	 * @return string Empty if unreadable.
	 */
	private function file_checksum( string $path ): string {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return '';
		}
		$raw = @file_get_contents( $path );
		if ( $raw === false ) {
			return '';
		}
		$hash = hash( self::CHECKSUM_ALGO, $raw );
		return $hash !== false ? $hash : '';
	}
}
