<?php
/**
 * Uninstall cleanup: scheduled events and plugin-owned data only (spec §53.5, §53.6, §53.9). Built pages untouched.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler;

/**
 * Removes scheduled events and plugin-owned options, tables, CPTs. Does not delete built pages (post type 'page').
 */
final class Uninstall_Cleanup_Service {

	/** Cleanup scope: full plugin-owned (options, tables, CPTs, uploads subdir). */
	public const SCOPE_FULL = 'full_plugin_owned';

	/** @var \wpdb|null */
	private $wpdb;

	public function __construct( $wpdb = null ) {
		$this->wpdb = $wpdb ?? ( isset( $GLOBALS['wpdb'] ) && $GLOBALS['wpdb'] instanceof \wpdb ? $GLOBALS['wpdb'] : null );
	}

	/**
	 * Clears all plugin-registered scheduled events (spec §53.5).
	 *
	 * @return bool True if unschedule ran (or no-op).
	 */
	public function clear_scheduled_events(): bool {
		Heartbeat_Scheduler::unschedule();
		return true;
	}

	/**
	 * Removes plugin-owned data: options, custom tables, plugin CPT posts. Does not remove built pages.
	 *
	 * @param string $scope One of SCOPE_* (currently only SCOPE_FULL).
	 * @return array{scheduled_removed: bool, options_removed: int, tables_dropped: int, cpt_posts_removed: int, built_pages_preserved: true}
	 */
	public function cleanup_plugin_owned_data( string $scope = self::SCOPE_FULL ): array {
		$scheduled_removed = false;
		$options_removed   = 0;
		$tables_dropped    = 0;
		$cpt_posts_removed = 0;

		$this->clear_scheduled_events();
		$scheduled_removed = true;

		foreach ( Option_Names::all() as $option_key ) {
			if ( delete_option( $option_key ) ) {
				++$options_removed;
			}
		}

		if ( $this->wpdb instanceof \wpdb ) {
			$prefix = $this->wpdb->prefix;
			foreach ( Table_Names::all() as $suffix ) {
				$table = $prefix . $suffix;
				$this->wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
				if ( $this->wpdb->last_error === '' ) {
					++$tables_dropped;
				}
			}
		}

		foreach ( Object_Type_Keys::all() as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);
			foreach ( $posts as $post_id ) {
				wp_delete_post( (int) $post_id, true );
				++$cpt_posts_removed;
			}
		}

		return array(
			'scheduled_removed'       => $scheduled_removed,
			'options_removed'        => $options_removed,
			'tables_dropped'          => $tables_dropped,
			'cpt_posts_removed'       => $cpt_posts_removed,
			'built_pages_preserved'   => true,
		);
	}
}
