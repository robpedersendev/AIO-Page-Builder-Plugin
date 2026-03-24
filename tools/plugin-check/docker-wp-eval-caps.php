<?php
/**
 * WordPress capability snapshot for plugin-check Docker (run via wp eval-file).
 *
 * @package AIOPageBuilder
 */

$r = get_role( 'administrator' );
if ( ! $r ) {
	echo "administrator role missing\n";
	exit( 1 );
}
$n = 0;
foreach ( array_keys( $r->capabilities ) as $k ) {
	if ( strpos( (string) $k, 'aio_' ) === 0 ) {
		++$n;
	}
}
echo 'aio_cap_count=' . $n . "\n";
echo 'manage_options_on_role=' . ( $r->has_cap( 'manage_options' ) ? 'yes' : 'no' ) . "\n";
echo 'user1_manage_options=' . ( user_can( 1, 'manage_options' ) ? 'yes' : 'no' ) . "\n";
foreach ( array( 'aio_view_logs', 'aio_access_template_library', 'aio_manage_section_templates' ) as $c ) {
	echo $c . '_on_role=' . ( $r->has_cap( $c ) ? 'yes' : 'no' ) . "\n";
}
