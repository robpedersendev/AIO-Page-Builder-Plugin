<?php
/**
 * Validates option names before prefix-based uninstall deletion. Conservative: only shapes this plugin writes.
 *
 * Wildcard DELETE is limited to rows whose full option_name matches a known pattern for that prefix
 * (see {@see Uninstall_Option_Registry::removable_dynamic_option_prefixes()}). Names that merely share
 * a substring with a prefix but do not match the plugin’s key shape are not removed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

/**
 * Ownership rules for dynamic (non-declared-constant) option keys removed on confirmed uninstall.
 */
final class Uninstall_Dynamic_Option_Ownership {

	/**
	 * Whether the option name is a plugin-owned dynamic key for the given prefix (declared in the registry).
	 *
	 * @param string $option_name Full wp_options.option_name.
	 * @param string $prefix      One of {@see Uninstall_Option_Registry::removable_dynamic_option_prefixes()}.
	 */
	public static function matches_plugin_owned_dynamic_key( string $option_name, string $prefix ): bool {
		$prefix = (string) $prefix;
		if ( $prefix === '' || ! str_starts_with( $option_name, $prefix ) ) {
			return false;
		}
		switch ( $prefix ) {
			case 'aio_pb_monthly_spend_':
				// * Provider_Monthly_Spend_Service: prefix + sanitize_key(provider) + '_' + gmdate('Y_m').
				return 1 === preg_match( '/^aio_pb_monthly_spend_[a-z0-9_-]+_\d{4}_\d{2}$/', $option_name );

			case 'aio_pb_spend_cap_':
				// * Provider_Spend_Cap_Settings: prefix + sanitize_key(provider_id) only.
				return 1 === preg_match( '/^aio_pb_spend_cap_[a-z0-9_-]+$/', $option_name );

			case 'aio_page_builder_crawl_session_':
				// * Crawl_Snapshot_Service / Crawl_Enqueue_Service: run id alnum, max 64.
				return 1 === preg_match( '/^aio_page_builder_crawl_session_[a-zA-Z0-9_-]{1,64}$/', $option_name );

			case 'aio_page_builder_crawl_lock_':
				// * Crawl_Enqueue_Service: LOCK_PREFIX + md5( host ) — 32 hex chars.
				return 1 === preg_match( '/^aio_page_builder_crawl_lock_[a-f0-9]{32}$/', $option_name );

			default:
				return false;
		}
	}
}
