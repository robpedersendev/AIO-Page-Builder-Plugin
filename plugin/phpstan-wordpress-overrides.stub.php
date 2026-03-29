<?php
/**
 * PHPStan stub overrides for WordPress core functions whose vendor/php-stubs signatures are incomplete.
 * Loaded via phpstan.neon.dist stubFiles (does not run in WordPress).
 *
 * @package AIOPageBuilder
 */

namespace {
	function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool {}
	function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false {}
	function update_post_meta( int $post_id, string $meta_key, mixed $meta_value, mixed $prev_value = null ): int|bool {}
}
