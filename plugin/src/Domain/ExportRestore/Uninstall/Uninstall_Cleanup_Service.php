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
use AIOPageBuilder\Infrastructure\Db\Wpdb_Prepared_Results;

/**
 * Removes scheduled events and plugin-owned options, tables, CPTs, and ACF section-key cache transients.
 * Does not delete built pages (post type 'page'), post meta, or ACF field groups. Non-destructive for ACF values and handed-off groups (spec §53.5, §53.6, acf-uninstall-retention-contract, acf-uninstall-retained-data-matrix).
 */
final class Uninstall_Cleanup_Service {

	/** Cleanup scope: full plugin-owned (options, tables, CPTs, ACF cache transients). */
	public const SCOPE_FULL = 'full_plugin_owned';

	/** Transient key prefixes for ACF section-key cache only (safe to remove; docs/operations/acf-uninstall-retained-data-matrix.md). */
	private const ACF_CACHE_TRANSIENT_PREFIXES = array( 'aio_acf_sk_p_', 'aio_acf_sk_t_', 'aio_acf_sk_c_' );

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
	 * Removes plugin-owned data: options, custom tables, plugin CPT posts, ACF section-key cache transients.
	 * Does not remove built pages, post meta, or ACF field groups. See acf-uninstall-retained-data-matrix.md.
	 *
	 * @param string $scope One of SCOPE_* (currently only SCOPE_FULL).
	 * @return array{scheduled_removed: bool, options_removed: int, tables_dropped: int, cpt_posts_removed: int, acf_transients_removed: int, built_pages_preserved: true}
	 */
	public function cleanup_plugin_owned_data( string $scope = self::SCOPE_FULL ): array {
		$scheduled_removed      = false;
		$options_removed        = 0;
		$tables_dropped         = 0;
		$cpt_posts_removed      = 0;
		$acf_transients_removed = 0;

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
				$suffix = (string) $suffix;
				if ( ! preg_match( '/^[a-z0-9_]+$/', $suffix ) ) {
					continue;
				}
				$table = $prefix . $suffix;
				Wpdb_Prepared_Results::query( $this->wpdb, 'DROP TABLE IF EXISTS %i', array( $table ) );
				if ( $this->wpdb->last_error === '' ) {
					++$tables_dropped;
				}
			}

			$acf_transients_removed = $this->clear_acf_section_key_cache_transients();
		}

		foreach ( Object_Type_Keys::all() as $post_type ) {
			$posts = \get_posts(
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
			'scheduled_removed'      => $scheduled_removed,
			'options_removed'        => $options_removed,
			'tables_dropped'         => $tables_dropped,
			'cpt_posts_removed'      => $cpt_posts_removed,
			'acf_transients_removed' => $acf_transients_removed,
			'built_pages_preserved'  => true,
		);
	}

	/**
	 * Deletes only ACF section-key cache transients (aio_acf_sk_*). Does not touch post meta or ACF field groups.
	 *
	 * @return int Number of transient options removed.
	 */
	private function clear_acf_section_key_cache_transients(): int {
		if ( ! $this->wpdb instanceof \wpdb ) {
			return 0;
		}
		$table = $this->wpdb->options;
		$count = 0;
		foreach ( self::ACF_CACHE_TRANSIENT_PREFIXES as $prefix ) {
			$like = $this->wpdb->esc_like( '_transient_' . $prefix ) . '%';
			$ids  = Wpdb_Prepared_Results::get_col( $this->wpdb, 'SELECT option_id FROM %i WHERE option_name LIKE %s', array( $table, $like ) );
			foreach ( $ids as $id ) {
				$this->wpdb->delete( $table, array( 'option_id' => $id ) );
				if ( $this->wpdb->last_error === '' ) {
					++$count;
				}
			}
			$like_timeout = $this->wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
			$ids_timeout  = Wpdb_Prepared_Results::get_col( $this->wpdb, 'SELECT option_id FROM %i WHERE option_name LIKE %s', array( $table, $like_timeout ) );
			foreach ( $ids_timeout as $id ) {
				$this->wpdb->delete( $table, array( 'option_id' => $id ) );
				if ( $this->wpdb->last_error === '' ) {
					++$count;
				}
			}
		}
		return $count;
	}

	/**
	 * Runs cleanup only when uninstall cleanup mode has been explicitly confirmed.
	 * Default behavior is to preserve plugin-owned data on uninstall when no confirmation exists.
	 *
	 * @return array{cleanup_ran: bool, mode: string, cleanup_result?: array<string, mixed>}
	 */
	public function cleanup_if_confirmed(): array {
		$mode = \get_option( Option_Names::PB_UNINSTALL_CLEANUP_MODE, '' );
		$mode = is_string( $mode ) ? trim( $mode ) : '';
		if ( $mode !== 'confirmed_cleanup' ) {
			return array(
				'cleanup_ran' => false,
				'mode'        => $mode !== '' ? $mode : 'preserve_default',
			);
		}
		return array(
			'cleanup_ran'    => true,
			'mode'           => $mode,
			'cleanup_result' => $this->cleanup_plugin_owned_data( self::SCOPE_FULL ),
		);
	}
}
