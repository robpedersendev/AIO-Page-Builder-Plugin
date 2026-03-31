#!/usr/bin/env php
<?php
/**
 * Diagnostics for `_aio_plan_definition` post-meta size discrepancies (e.g. 27034 vs 26390 bytes).
 *
 * ## Measurement basis (strlen vs SQL LENGTH)
 *
 * - `strlen( (string) get_post_meta( $post_id, '_aio_plan_definition', true ) )` reflects the logical
 *   meta string WordPress returns after normalizing stored slashes for PHP use.
 * - `SELECT LENGTH(meta_value) FROM wp_postmeta WHERE …` reflects the database cell as written.
 *   WordPress runs `wp_slash()` on meta values before INSERT/UPDATE, so JSON with many `"` characters
 *   is usually stored with extra backslashes and **raw SQL LENGTH is often greater** than the logical
 *   `strlen` from `get_post_meta`. If SQL length appears **smaller** than a separate 27034-byte source,
 *   compare the **same** representation (both logical or both raw) and rule out mixed baselines.
 *
 * ## Usage
 *
 * From the WordPress install root (WP-CLI), pass this file’s absolute path:
 *
 *   wp eval-file "/path/to/repo/tools/aio_plan_definition_meta_diagnostics.php" -- 1712
 *   wp eval-file "/path/to/repo/tools/aio_plan_definition_meta_diagnostics.php" -- 1712 /path/to/external.json
 *
 * The second form compares SHA-256 and JSON semantic equality (`==` on decoded arrays) between the
 * external file bytes and the stored meta string.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "aio_plan_definition_meta_diagnostics: load WordPress first (e.g. wp eval-file this script).\n" );
	exit( 1 );
}

/**
 * @param string $message Message.
 */
function aio_plan_def_diag_line( string $message ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\WP_CLI' ) ) {
		\WP_CLI::line( $message );
	} else {
		echo $message . "\n";
	}
}

/**
 * @return array{0: ?int, 1: ?string} Post ID and optional external JSON path.
 */
function aio_plan_def_diag_parse_cli_args(): array {
	$argv   = $GLOBALS['argv'] ?? array();
	$post_id = null;
	$path    = null;
	foreach ( $argv as $i => $arg ) {
		if ( $arg === '--' ) {
			$rest = array_slice( $argv, $i + 1 );
			if ( isset( $rest[0] ) && is_numeric( $rest[0] ) ) {
				$post_id = (int) $rest[0];
			}
			if ( isset( $rest[1] ) && is_string( $rest[1] ) && $rest[1] !== '' && is_readable( $rest[1] ) ) {
				$path = $rest[1];
			}
			break;
		}
	}
	if ( $post_id === null ) {
		foreach ( $argv as $arg ) {
			if ( is_numeric( $arg ) && (int) $arg > 0 ) {
				$post_id = (int) $arg;
				break;
			}
		}
	}
	return array( $post_id, $path );
}

/**
 * @param string $hook Hook name.
 */
function aio_plan_def_diag_dump_hook( string $hook ): void {
	global $wp_filter;
	if ( ! isset( $wp_filter[ $hook ] ) ) {
		aio_plan_def_diag_line( "Hook `{$hook}`: (no callbacks)" );
		return;
	}
	$bucket = $wp_filter[ $hook ];
	if ( ! is_object( $bucket ) || ! isset( $bucket->callbacks ) || ! is_array( $bucket->callbacks ) ) {
		aio_plan_def_diag_line( "Hook `{$hook}`: (non-standard bucket)" );
		return;
	}
	aio_plan_def_diag_line( "Hook `{$hook}`:" );
	foreach ( $bucket->callbacks as $priority => $cbs ) {
		if ( ! is_array( $cbs ) ) {
			continue;
		}
		foreach ( $cbs as $cb ) {
			$fn = $cb['function'] ?? null;
			$label = aio_plan_def_diag_callback_label( $fn );
			aio_plan_def_diag_line( '  priority=' . (string) $priority . ' ' . $label );
		}
	}
}

/**
 * @param mixed $fn Callback.
 */
function aio_plan_def_diag_callback_label( mixed $fn ): string {
	if ( is_string( $fn ) ) {
		return $fn;
	}
	if ( is_array( $fn ) && isset( $fn[0], $fn[1] ) ) {
		if ( is_object( $fn[0] ) ) {
			return get_class( $fn[0] ) . '::' . (string) $fn[1];
		}
		return (string) $fn[0] . '::' . (string) $fn[1];
	}
	if ( $fn instanceof \Closure ) {
		return 'Closure#' . spl_object_hash( $fn );
	}
	if ( is_object( $fn ) && is_callable( array( $fn, '__invoke' ) ) ) {
		return get_class( $fn ) . '::__invoke';
	}
	return 'callable';
}

$key = '_aio_plan_definition';

aio_plan_def_diag_line( '=== AIO _aio_plan_definition meta diagnostics ===' );
aio_plan_def_diag_line( '' );
aio_plan_def_diag_line( 'Measurement: strlen(get_post_meta) = logical; SQL LENGTH(meta_value) = slashed DB bytes (often larger for JSON).' );
aio_plan_def_diag_line( '' );

aio_plan_def_diag_line( '--- Core metadata hooks ---' );
foreach ( array( 'update_post_metadata', 'add_post_metadata', 'added_post_meta', 'updated_post_meta' ) as $hook ) {
	aio_plan_def_diag_dump_hook( $hook );
}
aio_plan_def_diag_line( '' );

aio_plan_def_diag_line( '--- sanitize_post_meta_* for this key ---' );
aio_plan_def_diag_dump_hook( 'sanitize_post_meta_' . $key );

aio_plan_def_diag_line( '' );
aio_plan_def_diag_line( '--- Any hook name containing aio_plan_definition ---' );
global $wp_filter;
if ( isset( $wp_filter ) && is_array( $wp_filter ) ) {
	foreach ( array_keys( $wp_filter ) as $hook_name ) {
		if ( ! is_string( $hook_name ) ) {
			continue;
		}
		if ( str_contains( $hook_name, 'aio_plan_definition' ) ) {
			aio_plan_def_diag_dump_hook( $hook_name );
		}
	}
} else {
	aio_plan_def_diag_line( '(wp_filter not available)' );
}

aio_plan_def_diag_line( '' );
aio_plan_def_diag_line( '--- Registered post meta (if API exists) ---' );
if ( function_exists( 'get_registered_meta_keys' ) ) {
	$types            = function_exists( 'get_post_types' ) ? get_post_types( array(), 'names' ) : array( 'post' );
	$found_registered = false;
	foreach ( $types as $pt ) {
		$reg_keys = get_registered_meta_keys( 'post', $pt );
		if ( is_array( $reg_keys ) && array_key_exists( $key, $reg_keys ) ) {
			aio_plan_def_diag_line( "Registered for post_type={$pt}: " . wp_json_encode( $reg_keys[ $key ] ) );
			$found_registered = true;
		}
	}
	if ( ! $found_registered ) {
		aio_plan_def_diag_line( "Key `{$key}` not registered via register_post_meta for checked post types." );
	}
} else {
	aio_plan_def_diag_line( 'get_registered_meta_keys unavailable.' );
}

list( $post_id, $external_path ) = aio_plan_def_diag_parse_cli_args();

if ( $post_id === null || $post_id <= 0 ) {
	aio_plan_def_diag_line( '' );
	aio_plan_def_diag_line( 'No post ID parsed. Re-run with: wp eval-file ... -- <post_id> [external.json]' );
	exit( 0 );
}

aio_plan_def_diag_line( '' );
aio_plan_def_diag_line( '--- Post ' . (string) $post_id . ' ---' );
$raw = get_post_meta( $post_id, $key, true );
if ( ! is_string( $raw ) || $raw === '' ) {
	aio_plan_def_diag_line( 'Stored meta: (empty or non-string)' );
} else {
	aio_plan_def_diag_line( 'strlen(get_post_meta): ' . (string) strlen( $raw ) );
	aio_plan_def_diag_line( 'sha256(stored): ' . hash( 'sha256', $raw ) );
	global $wpdb;
	if ( $wpdb instanceof \wpdb ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Diagnostic read only.
		$db_len = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT LENGTH(meta_value) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
				$post_id,
				$key
			)
		);
		if ( $db_len !== null ) {
			aio_plan_def_diag_line( 'SQL LENGTH(meta_value) latest row: ' . (string) $db_len );
		}
	}
}

if ( is_string( $external_path ) && $external_path !== '' ) {
	$ext = file_get_contents( $external_path );
	if ( ! is_string( $ext ) ) {
		aio_plan_def_diag_line( 'External file: could not read.' );
		exit( 1 );
	}
	aio_plan_def_diag_line( '' );
	aio_plan_def_diag_line( '--- External file ---' );
	aio_plan_def_diag_line( 'path: ' . $external_path );
	aio_plan_def_diag_line( 'strlen(file): ' . (string) strlen( $ext ) );
	aio_plan_def_diag_line( 'sha256(file): ' . hash( 'sha256', $ext ) );
	if ( is_string( $raw ) && $raw !== '' ) {
		$same_hash = hash_equals( hash( 'sha256', $raw ), hash( 'sha256', $ext ) );
		aio_plan_def_diag_line( 'sha256 match: ' . ( $same_hash ? 'yes' : 'no' ) );
		$flags = defined( 'JSON_BIGINT_AS_STRING' ) ? JSON_BIGINT_AS_STRING : 0;
		$d1    = is_string( $raw ) ? json_decode( $raw, true, 512, $flags ) : null;
		$d2    = json_decode( $ext, true, 512, $flags );
		$json_ok = is_array( $d1 ) && is_array( $d2 ) && ( $d1 == $d2 );
		aio_plan_def_diag_line( 'json_decode loose array equality: ' . ( $json_ok ? 'yes' : 'no' ) );
	}
}

aio_plan_def_diag_line( '' );
aio_plan_def_diag_line( 'Done.' );
