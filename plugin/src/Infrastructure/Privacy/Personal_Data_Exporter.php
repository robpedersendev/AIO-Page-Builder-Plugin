<?php
/**
 * WordPress personal data exporter for AIO Page Builder (spec §47, SPR-004).
 * Exports actor-linked data: AI run metadata, job queue records, template compare lists, bundle preview transient.
 * No secrets or credentials are ever included.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Privacy;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Chat_Session_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Exports personal data for a user (by email) for the WordPress Tools → Export Personal Data flow.
 */
final class Personal_Data_Exporter {

	private const PER_PAGE = 50;

	/** Exporter group: AI run records. */
	public const GROUP_AI_RUNS = 'aio-ai-runs';

	/** Exporter group: Job queue records. */
	public const GROUP_JOB_QUEUE = 'aio-job-queue';

	/** Exporter group: User preferences (compare lists, preview cache). */
	public const GROUP_USER_PREFS = 'aio-user-prefs';

	/** Exporter group: Template-lab chat sessions (bounded summaries; no full transcripts). */
	public const GROUP_CHAT_SESSIONS = 'aio-chat-sessions';

	/**
	 * Exports personal data for the given email address (paginated).
	 *
	 * @param string $email_address User email (WordPress resolves to user ID).
	 * @param int    $page         Page number (1-based).
	 * @return array{data: array<int, array{group_id: string, group_label: string, item_id: string, data: array<int, array{name: string, value: string}>}>, done: bool}
	 */
	public static function export( string $email_address, int $page = 1 ): array {
		$user         = \get_user_by( 'email', $email_address );
		$page         = max( 1, $page );
		$export_items = array();

		if ( ! $user instanceof \WP_User ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$user_id   = (int) $user->ID;
		$actor_ref = 'user:' . $user_id;
		$offset    = ( $page - 1 ) * self::PER_PAGE;

		if ( $page === 1 ) {
			$export_items = array_merge( $export_items, self::export_user_prefs( $user_id ) );
		}

		$ai_run_repo = new AI_Run_Repository();
		$runs        = $ai_run_repo->list_recent_by_actor( $user_id, self::PER_PAGE, $offset );
		foreach ( $runs as $run ) {
			$export_items[] = self::run_to_export_item( $run );
		}

		global $wpdb;
		$job_repo = new Job_Queue_Repository( $wpdb );
		$jobs     = $job_repo->list_by_actor_ref( $actor_ref, self::PER_PAGE, $offset );
		foreach ( $jobs as $job ) {
			$export_items[] = self::job_to_export_item( $job );
		}

		$chat_repo = new AI_Chat_Session_Repository();
		$sessions  = $chat_repo->list_recent_for_owner( $user_id, self::PER_PAGE, $offset );
		foreach ( $sessions as $row ) {
			$export_items[] = self::chat_session_to_export_item( $row, $chat_repo );
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::PRIVACY_CHAT_EXPORT_SUMMARY,
			'page=' . (string) $page . ' chat_items=' . (string) count( $sessions )
		);

		$runs_done  = count( $runs ) < self::PER_PAGE;
		$jobs_done  = count( $jobs ) < self::PER_PAGE;
		$chats_done = count( $sessions ) < self::PER_PAGE;
		$done       = $runs_done && $jobs_done && $chats_done;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * @param int $user_id
	 * @return list<array{group_id: string, group_label: string, item_id: string, data: array<int, array{name: string, value: string}>}>
	 */
	private static function export_user_prefs( int $user_id ): array {
		$items        = array();
		$section_key  = Template_Compare_Screen::get_compare_meta_key( 'section' );
		$page_key     = Template_Compare_Screen::get_compare_meta_key( 'page' );
		$section_list = \get_user_meta( $user_id, $section_key, true );
		$page_list    = \get_user_meta( $user_id, $page_key, true );
		if ( is_array( $section_list ) && ! empty( $section_list ) ) {
			$items[] = array(
				'group_id'    => self::GROUP_USER_PREFS,
				'group_label' => __( 'AIO Page Builder – Template compare & preferences', 'aio-page-builder' ),
				'item_id'     => 'compare-list-section',
				'data'        => array(
					array(
						'name'  => __( 'Section template compare list', 'aio-page-builder' ),
						'value' => implode( ', ', array_map( 'sanitize_key', $section_list ) ),
					),
				),
			);
		}
		if ( is_array( $page_list ) && ! empty( $page_list ) ) {
			$items[] = array(
				'group_id'    => self::GROUP_USER_PREFS,
				'group_label' => __( 'AIO Page Builder – Template compare & preferences', 'aio-page-builder' ),
				'item_id'     => 'compare-list-page',
				'data'        => array(
					array(
						'name'  => __( 'Page template compare list', 'aio-page-builder' ),
						'value' => implode( ', ', array_map( 'sanitize_key', $page_list ) ),
					),
				),
			);
		}
		$transient_key = 'aio_industry_bundle_preview_' . $user_id;
		$preview       = \get_transient( $transient_key );
		if ( $preview !== false ) {
			$items[] = array(
				'group_id'    => self::GROUP_USER_PREFS,
				'group_label' => __( 'AIO Page Builder – Template compare & preferences', 'aio-page-builder' ),
				'item_id'     => 'bundle-preview-cache',
				'data'        => array(
					array(
						'name'  => __( 'Industry bundle import preview cache', 'aio-page-builder' ),
						'value' => __( 'Temporary cache (up to 15 minutes). Contains bundle structure and conflict summary; no secrets.', 'aio-page-builder' ),
					),
				),
			);
		}
		return $items;
	}

	/**
	 * @param array<string, mixed> $run
	 * @return array{group_id: string, group_label: string, item_id: string, data: array<int, array{name: string, value: string}>}
	 */
	private static function run_to_export_item( array $run ): array {
		$internal_key = $run['internal_key'] ?? (string) ( $run['id'] ?? '' );
		$meta         = $run['run_metadata'] ?? array();
		$data         = array(
			array(
				'name'  => __( 'Run ID', 'aio-page-builder' ),
				'value' => (string) $internal_key,
			),
			array(
				'name'  => __( 'Status', 'aio-page-builder' ),
				'value' => (string) ( $run['status'] ?? '' ),
			),
			array(
				'name'  => __( 'Created', 'aio-page-builder' ),
				'value' => (string) ( $meta['created_at'] ?? '' ),
			),
			array(
				'name'  => __( 'Provider', 'aio-page-builder' ),
				'value' => (string) ( $meta['provider_id'] ?? '' ),
			),
			array(
				'name'  => __( 'Model', 'aio-page-builder' ),
				'value' => (string) ( $meta['model_used'] ?? '' ),
			),
		);
		return array(
			'group_id'    => self::GROUP_AI_RUNS,
			'group_label' => __( 'AIO Page Builder – AI run records', 'aio-page-builder' ),
			'item_id'     => 'ai-run-' . $internal_key,
			'data'        => $data,
		);
	}

	/**
	 * @param array<string, mixed> $job
	 * @return array{group_id: string, group_label: string, item_id: string, data: array<int, array{name: string, value: string}>}
	 */
	/**
	 * @param array<string, mixed> $row From AI_Chat_Session_Repository::list_recent_for_owner.
	 */
	private static function chat_session_to_export_item( array $row, AI_Chat_Session_Repository $repo ): array {
		$session_id = (string) ( $row['session_id'] ?? '' );
		$detail     = $session_id !== '' ? $repo->get_session( $session_id ) : null;
		$has_snap   = is_array( $detail ) && isset( $detail['approved_snapshot_ref'] ) && is_array( $detail['approved_snapshot_ref'] ) && $detail['approved_snapshot_ref'] !== array();
		$data       = array(
			array(
				'name'  => __( 'Session ID', 'aio-page-builder' ),
				'value' => $session_id,
			),
			array(
				'name'  => __( 'Status', 'aio-page-builder' ),
				'value' => (string) ( $row['status'] ?? '' ),
			),
			array(
				'name'  => __( 'Task type', 'aio-page-builder' ),
				'value' => (string) ( $row['task_type'] ?? '' ),
			),
			array(
				'name'  => __( 'Message count', 'aio-page-builder' ),
				'value' => (string) (int) ( $row['message_count'] ?? 0 ),
			),
			array(
				'name'  => __( 'Last modified (GMT)', 'aio-page-builder' ),
				'value' => (string) ( $row['post_modified_gmt'] ?? '' ),
			),
			array(
				'name'  => __( 'Has approved snapshot reference', 'aio-page-builder' ),
				'value' => $has_snap ? __( 'Yes', 'aio-page-builder' ) : __( 'No', 'aio-page-builder' ),
			),
		);
		return array(
			'group_id'    => self::GROUP_CHAT_SESSIONS,
			'group_label' => __( 'AIO Page Builder – Template-lab chat sessions', 'aio-page-builder' ),
			'item_id'     => 'chat-session-' . ( $session_id !== '' ? $session_id : (string) ( $row['post_id'] ?? '0' ) ),
			'data'        => $data,
		);
	}

	private static function job_to_export_item( array $job ): array {
		$job_ref = (string) ( $job['job_ref'] ?? '' );
		$data    = array(
			array(
				'name'  => __( 'Job reference', 'aio-page-builder' ),
				'value' => $job_ref,
			),
			array(
				'name'  => __( 'Job type', 'aio-page-builder' ),
				'value' => (string) ( $job['job_type'] ?? '' ),
			),
			array(
				'name'  => __( 'Status', 'aio-page-builder' ),
				'value' => (string) ( $job['queue_status'] ?? '' ),
			),
			array(
				'name'  => __( 'Created', 'aio-page-builder' ),
				'value' => (string) ( $job['created_at'] ?? '' ),
			),
		);
		return array(
			'group_id'    => self::GROUP_JOB_QUEUE,
			'group_label' => __( 'AIO Page Builder – Job queue records', 'aio-page-builder' ),
			'item_id'     => 'job-' . $job_ref,
			'data'        => $data,
		);
	}
}
