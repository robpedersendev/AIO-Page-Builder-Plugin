<?php
/**
 * Profile Snapshot History admin screen (v2-scope-backlog.md §3).
 *
 * Renders a list table of versioned profile snapshots, a per-snapshot diff summary vs current
 * profile, and capability + nonce protected restore forms. Restore uses admin-post for
 * WordPress-native request handling: redirect + admin notice.
 *
 * Route: admin.php?page=aio-page-builder-profile-snapshots
 * Restore: admin-post action aio_restore_profile_snapshot
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Diff_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders profile snapshot history list and restore actions.
 * Handles admin-post restore requests. No direct AI integration.
 */
final class Profile_Snapshot_History_Panel {

	public const SLUG = 'aio-page-builder-profile-snapshots';

	/** Admin-post action name for restore. */
	public const ACTION_RESTORE = 'aio_restore_profile_snapshot';

	/** Nonce action for restore. */
	public const NONCE_ACTION_RESTORE = 'aio_restore_profile_snapshot_action';

	/** Number of snapshots shown per page. */
	private const PER_PAGE = 20;

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Profile Snapshot History', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	/**
	 * Registers the admin-post restore handler. Must be called during admin_init or init.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'admin_post_' . self::ACTION_RESTORE, array( $this, 'handle_restore' ) );
	}

	// -------------------------------------------------------------------------
	// Restore handler
	// -------------------------------------------------------------------------

	/**
	 * Handles the admin-post restore request. Validates capability, nonce, and snapshot_id;
	 * restores profile via Profile_Store::set_full_profile(); audits result; redirects with notice.
	 *
	 * @return void
	 */
	public function handle_restore(): void {
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_die( \esc_html__( 'You do not have permission to restore profile snapshots.', 'aio-page-builder' ), 403 );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
		if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_RESTORE ) ) {
			\wp_die( \esc_html__( 'Security check failed. Please try again.', 'aio-page-builder' ), 403 );
		}

		$snapshot_id = isset( $_POST['snapshot_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['snapshot_id'] ) ) : '';
		$redirect    = \admin_url( 'admin.php?page=' . self::SLUG );

		if ( $snapshot_id === '' ) {
			\wp_safe_redirect( \add_query_arg( 'restore_error', 'missing_id', $redirect ) );
			exit;
		}

		if ( ! $this->container
			|| ! $this->container->has( 'profile_snapshot_repository' )
			|| ! $this->container->has( 'profile_store' )
			|| ! $this->container->has( 'profile_snapshot_factory' )
		) {
			\wp_safe_redirect( \add_query_arg( 'restore_error', 'service_unavailable', $redirect ) );
			exit;
		}

		/** @var Profile_Snapshot_Repository $repo */
		$repo = $this->container->get( 'profile_snapshot_repository' );
		/** @var Profile_Store $store */
		$store    = $this->container->get( 'profile_store' );
		$snapshot = $repo->get_by_id( $snapshot_id );

		if ( $snapshot === null ) {
			\wp_safe_redirect( \add_query_arg( 'restore_error', 'not_found', $redirect ) );
			exit;
		}

		// * Capture a pre-restore snapshot before overwriting the current profile.
		/** @var Profile_Snapshot_Factory $factory */
		$factory       = $this->container->get( 'profile_snapshot_factory' );
		$pre_restore   = $factory->build( $store, 'pre_restore_backup', 'other', $snapshot_id );
		$repo->save( $pre_restore );

		// Full overwrite restore — sets brand + business profile from snapshot.
		$store->set_full_profile(
			array(
				'brand_profile'    => $snapshot->brand_profile,
				'business_profile' => $snapshot->business_profile,
			)
		);

		// * Capture a post-restore snapshot to confirm what was applied.
		/** @var Profile_Snapshot_Repository $repo */
		$post_restore = $factory->build( $store, 'restore_event', 'other', $snapshot_id );
		$repo->save( $post_restore );

		\error_log(
			'[AIO Page Builder] ' . \wp_json_encode(
				array(
					'event'            => 'profile_snapshot_restore',
					'actor_id'         => (string) \get_current_user_id(),
					'source_snapshot'  => $snapshot_id,
					'pre_backup_id'    => $pre_restore->snapshot_id,
					'post_snapshot_id' => $post_restore->snapshot_id,
					'restored_at'      => \gmdate( 'c' ),
				)
			)
		); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		\wp_safe_redirect( \add_query_arg( 'restore_success', '1', $redirect ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * Renders the admin panel with snapshot list, diff summaries, and restore forms.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ) );
		}

		$snapshots    = $this->get_snapshots();
		$current_store = $this->get_profile_store();
		$diff_service  = $this->get_diff_service();

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', \esc_html( $this->get_title() ) );

		$this->render_admin_notices();

		if ( empty( $snapshots ) ) {
			echo '<p>' . \esc_html__( 'No profile snapshots have been captured yet. Snapshots are created automatically when your brand or business profile is saved or when an AI planning run completes.', 'aio-page-builder' ) . '</p>';
			echo '</div>';
			return;
		}

		printf(
			'<p>%s</p>',
			\esc_html(
				sprintf(
					/* translators: %d: snapshot count */
					\_n( '%d snapshot on record.', '%d snapshots on record.', count( $snapshots ), 'aio-page-builder' ),
					count( $snapshots )
				)
			)
		);

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . \esc_html__( 'Snapshot ID', 'aio-page-builder' ) . '</th>';
		echo '<th>' . \esc_html__( 'Captured', 'aio-page-builder' ) . '</th>';
		echo '<th>' . \esc_html__( 'Source', 'aio-page-builder' ) . '</th>';
		echo '<th>' . \esc_html__( 'Changed Fields vs Current', 'aio-page-builder' ) . '</th>';
		echo '<th>' . \esc_html__( 'Restore', 'aio-page-builder' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $snapshots as $snapshot ) {
			$summary_text = $this->build_diff_summary_text( $snapshot, $current_store, $diff_service );
			echo '<tr>';
			printf( '<td><code>%s</code></td>', \esc_html( $snapshot->snapshot_id ) );
			printf( '<td>%s</td>', \esc_html( $snapshot->created_at ) );
			printf( '<td>%s</td>', \esc_html( $this->format_source( $snapshot->source ) ) );
			printf( '<td>%s</td>', \esc_html( $summary_text ) );
			echo '<td>';
			$this->render_restore_form( $snapshot->snapshot_id );
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// Render helpers
	// -------------------------------------------------------------------------

	/**
	 * Renders a restore form for a single snapshot.
	 *
	 * @param string $snapshot_id
	 * @return void
	 */
	private function render_restore_form( string $snapshot_id ): void {
		echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . \esc_attr( self::ACTION_RESTORE ) . '">';
		echo '<input type="hidden" name="snapshot_id" value="' . \esc_attr( $snapshot_id ) . '">';
		\wp_nonce_field( self::NONCE_ACTION_RESTORE );
		printf(
			'<button type="submit" class="button button-secondary" onclick="return confirm(%s);">%s</button>',
			\esc_attr(
				'"' . \esc_js( __( 'Restore this snapshot? This will overwrite your current brand and business profile. A backup snapshot will be saved first.', 'aio-page-builder' ) ) . '"'
			),
			\esc_html__( 'Restore', 'aio-page-builder' )
		);
		echo '</form>';
	}

	/**
	 * Renders query-string-based admin notices for restore result feedback.
	 *
	 * @return void
	 */
	private function render_admin_notices(): void {
		if ( isset( $_GET['restore_success'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Profile snapshot restored successfully. A pre-restore backup was captured automatically.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		$error_map = array(
			'missing_id'         => __( 'Restore failed: snapshot ID is missing.', 'aio-page-builder' ),
			'not_found'          => __( 'Restore failed: snapshot not found.', 'aio-page-builder' ),
			'service_unavailable' => __( 'Restore failed: profile snapshot services are not available.', 'aio-page-builder' ),
		);
		$error_key = isset( $_GET['restore_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['restore_error'] ) ) : '';
		if ( $error_key !== '' && isset( $error_map[ $error_key ] ) ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', \esc_html( $error_map[ $error_key ] ) );
		}
	}

	/**
	 * Produces a plain-text diff summary comparing a snapshot to the current profile.
	 *
	 * @param Profile_Snapshot_Data  $snapshot
	 * @param Profile_Store|null     $store
	 * @param Profile_Snapshot_Diff_Service|null $diff_service
	 * @return string
	 */
	private function build_diff_summary_text(
		Profile_Snapshot_Data $snapshot,
		?Profile_Store $store,
		?Profile_Snapshot_Diff_Service $diff_service
	): string {
		if ( $store === null || $diff_service === null ) {
			return __( 'N/A', 'aio-page-builder' );
		}
		$summary = $diff_service->summary( $snapshot, $store );
		if ( $summary['changed'] === 0 ) {
			return __( 'No differences', 'aio-page-builder' );
		}
		return sprintf(
			/* translators: 1: changed field count 2: total field count */
			__( '%1$d / %2$d fields differ', 'aio-page-builder' ),
			$summary['changed'],
			$summary['total']
		);
	}

	/**
	 * Returns human-readable label for a source string.
	 *
	 * @param string $source
	 * @return string
	 */
	private function format_source( string $source ): string {
		$labels = array(
			'brand_profile_merge'    => __( 'Brand profile saved', 'aio-page-builder' ),
			'business_profile_merge' => __( 'Business profile saved', 'aio-page-builder' ),
			'onboarding_completion'  => __( 'Onboarding AI run', 'aio-page-builder' ),
			'restore_event'          => __( 'Restore applied', 'aio-page-builder' ),
			'pre_restore_backup'     => __( 'Pre-restore backup', 'aio-page-builder' ),
			'manual'                 => __( 'Manual', 'aio-page-builder' ),
		);
		return $labels[ $source ] ?? \ucwords( str_replace( '_', ' ', $source ) );
	}

	// -------------------------------------------------------------------------
	// Service resolution helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns all snapshots newest-first. Returns empty array when repository is unavailable.
	 *
	 * @return array<int, Profile_Snapshot_Data>
	 */
	private function get_snapshots(): array {
		if ( ! $this->container || ! $this->container->has( 'profile_snapshot_repository' ) ) {
			return array();
		}
		/** @var Profile_Snapshot_Repository $repo */
		$repo = $this->container->get( 'profile_snapshot_repository' );
		return $repo->get_all( self::PER_PAGE );
	}

	/**
	 * Returns the Profile_Store from the container, or null.
	 *
	 * @return Profile_Store|null
	 */
	private function get_profile_store(): ?Profile_Store {
		if ( ! $this->container || ! $this->container->has( 'profile_store' ) ) {
			return null;
		}
		$store = $this->container->get( 'profile_store' );
		return $store instanceof Profile_Store ? $store : null;
	}

	/**
	 * Returns the diff service from the container, or null.
	 *
	 * @return Profile_Snapshot_Diff_Service|null
	 */
	private function get_diff_service(): ?Profile_Snapshot_Diff_Service {
		if ( ! $this->container || ! $this->container->has( 'profile_snapshot_diff_service' ) ) {
			return null;
		}
		$svc = $this->container->get( 'profile_snapshot_diff_service' );
		return $svc instanceof Profile_Snapshot_Diff_Service ? $svc : null;
	}
}
