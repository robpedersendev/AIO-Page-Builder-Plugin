<?php
/**
 * Authoritative list of plugin-owned site options for confirmed uninstall cleanup (spec §53.6).
 * Dynamic keys (e.g. per-bundle industry payloads) are removed in {@see Uninstall_Cleanup_Service::remove_industry_bundle_apply_storage_options()}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Single source of truth for which declared option keys uninstall attempts to delete.
 * Does not include: built page post meta, non-option storage, or foreign theme options.
 */
final class Uninstall_Option_Registry {

	/**
	 * Option keys deleted in the same cleanup pass, but only after dynamic bundle payload keys
	 * are resolved in {@see Uninstall_Cleanup_Service::remove_industry_bundle_apply_storage_options()}.
	 * Listed so audits do not assume these are missing from the registry.
	 *
	 * @return list<string>
	 */
	public static function industry_bundle_keys_removed_in_prerequisite_step(): array {
		return array(
			Option_Names::PB_INDUSTRY_BUNDLE_REGISTRY,
			Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE,
		);
	}

	/**
	 * Prefix pattern for dynamic per-bundle options (not static constants); cleaned in prerequisite step.
	 */
	public const INDUSTRY_BUNDLE_PAYLOAD_PREFIX   = 'aio_pb_industry_bundle_payload_';
	public const INDUSTRY_BUNDLE_CONFLICTS_PREFIX = 'aio_pb_industry_bundle_conflicts_';

	/**
	 * Site option keys to remove on confirmed uninstall (stable keys from {@see Option_Names::declared_option_keys()}).
	 * Excludes nothing here — full declared set; prerequisite step runs first for bundle-specific keys.
	 *
	 * @return list<string>
	 */
	public static function removable_declared_option_keys(): array {
		return Option_Names::declared_option_keys();
	}

	/**
	 * Non-static option keys stored with a bounded prefix (per-provider spend caps, crawl sessions, etc.).
	 * Each prefix is plugin-owned; removal uses a LIKE match on `wp_options.option_name` only for these literals.
	 *
	 * @return list<string>
	 */
	public static function removable_dynamic_option_prefixes(): array {
		return array(
			'aio_pb_monthly_spend_',
			'aio_pb_spend_cap_',
			'aio_page_builder_crawl_session_',
			'aio_page_builder_crawl_lock_',
		);
	}
}
