<?php
/**
 * Deterministically stages and zips a production WordPress plugin package.
 *
 * Usage:
 *   php tools/build-release-package.php [repo-root] [output-dir]
 *
 * Defaults:
 *   repo-root  = repository root (parent of this script)
 *   output-dir = <repo-root>/dist/release
 *
 * The staged plugin is written to:
 *   <output-dir>/stage/aio-page-builder
 *
 * The ZIP is written to:
 *   <output-dir>/aio-page-builder.zip
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

if ( PHP_SAPI !== 'cli' ) {
	echo "CLI only.\n";
	exit( 1 );
}

$repo_root  = isset( $argv[1] ) && is_string( $argv[1] ) && $argv[1] !== ''
	? rtrim( $argv[1], DIRECTORY_SEPARATOR )
	: dirname( __DIR__ );
$output_dir = isset( $argv[2] ) && is_string( $argv[2] ) && $argv[2] !== ''
	? rtrim( $argv[2], DIRECTORY_SEPARATOR )
	: $repo_root . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'release';
$plugin_src = $repo_root . DIRECTORY_SEPARATOR . 'plugin';
$stage_root = $output_dir . DIRECTORY_SEPARATOR . 'stage';
$stage_dir  = $stage_root . DIRECTORY_SEPARATOR . 'aio-page-builder';
$zip_path   = $output_dir . DIRECTORY_SEPARATOR . 'aio-page-builder.zip';

if ( ! is_dir( $plugin_src ) ) {
	fwrite( STDERR, "Plugin source directory not found: {$plugin_src}\n" );
	exit( 1 );
}

$exclude_prefixes = array(
	'.git',
	'.github',
	'.cursor',
	'.vscode',
	'docs',
	'legacy',
	'node_modules',
	'tests',
	'vendor',
	'phpstan-fixtures',
);
$exclude_exact    = array(
	'.gitignore',
	'.gitattributes',
	'.phpunit.result.cache',
	'.wp-env.json',
	'composer.json',
	'composer.lock',
	'package-lock.json',
	'package.json',
	'phpcs.xml.dist',
	'phpstan-baseline.neon',
	'phpstan-bootstrap.php',
	'phpstan-wordpress-overrides.stub.php',
	'phpstan.neon',
	'phpstan.neon.dist',
	'phpunit.xml',
	'phpunit.xml.dist',
);

$delete_tree = static function ( string $path ) use ( &$delete_tree ): void {
	if ( ! file_exists( $path ) ) {
		return;
	}
	if ( is_file( $path ) || is_link( $path ) ) {
		unlink( $path );
		return;
	}
	$items = scandir( $path );
	if ( false === $items ) {
		return;
	}
	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}
		$delete_tree( $path . DIRECTORY_SEPARATOR . $item );
	}
	rmdir( $path );
};

$should_exclude = static function ( string $relative_path ) use ( $exclude_prefixes, $exclude_exact ): bool {
	$normalized = str_replace( '\\', '/', ltrim( $relative_path, '/\\' ) );

	if ( '' === $normalized ) {
		return false;
	}

	foreach ( $exclude_exact as $exact ) {
		if ( $normalized === $exact ) {
			return true;
		}
	}

	foreach ( $exclude_prefixes as $prefix ) {
		if ( $normalized === $prefix || str_starts_with( $normalized, $prefix . '/' ) ) {
			return true;
		}
	}

	return false;
};

$copy_tree = static function ( string $source, string $destination ) use ( &$copy_tree, $plugin_src, $should_exclude ): void {
	if ( ! is_dir( $destination ) ) {
		mkdir( $destination, 0777, true );
	}

	$items = scandir( $source );
	if ( false === $items ) {
		throw new RuntimeException( "Unable to read directory: {$source}" );
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		$source_path   = $source . DIRECTORY_SEPARATOR . $item;
		$relative_path = ltrim( str_replace( '\\', '/', substr( $source_path, strlen( $plugin_src ) ) ), '/' );

		if ( $should_exclude( $relative_path ) ) {
			continue;
		}

		$destination_path = $destination . DIRECTORY_SEPARATOR . $item;

		if ( is_dir( $source_path ) ) {
			$copy_tree( $source_path, $destination_path );
			continue;
		}

		if ( ! copy( $source_path, $destination_path ) ) {
			throw new RuntimeException( "Unable to copy file: {$relative_path}" );
		}
	}
};

$zip_stage = static function ( string $source_dir, string $zip_file, string $root_dir_name ): void {
	if ( ! class_exists( 'ZipArchive' ) ) {
		throw new RuntimeException( 'ZipArchive is not available; install the zip extension or package the staged directory manually.' );
	}

	$zip = new ZipArchive();

	if ( true !== $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		throw new RuntimeException( "Unable to create ZIP: {$zip_file}" );
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $source_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$item_path     = $item->getPathname();
		$relative_path = str_replace( '\\', '/', substr( $item_path, strlen( $source_dir ) + 1 ) );
		$archive_path  = $root_dir_name . '/' . $relative_path;

		if ( $item->isDir() ) {
			$zip->addEmptyDir( $archive_path );
			continue;
		}

		$zip->addFile( $item_path, $archive_path );
	}

	$zip->close();
};

$delete_tree( $stage_root );
if ( file_exists( $zip_path ) ) {
	unlink( $zip_path );
}
if ( ! is_dir( $output_dir ) ) {
	mkdir( $output_dir, 0777, true );
}

$copy_tree( $plugin_src, $stage_dir );

$preflight_cmd = escapeshellarg( PHP_BINARY ) . ' ' .
	escapeshellarg( $repo_root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'release_preflight_check.php' ) . ' ' .
	escapeshellarg( $stage_dir );

passthru( $preflight_cmd, $preflight_exit_code );

if ( 0 !== $preflight_exit_code ) {
	fwrite( STDERR, "Preflight failed for staged package: {$stage_dir}\n" );
	exit( $preflight_exit_code );
}

$zip_stage( $stage_dir, $zip_path, 'aio-page-builder' );

echo "Staged package: {$stage_dir}\n";
echo "ZIP package: {$zip_path}\n";
