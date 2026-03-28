<?php
/**
 * WordPress personal data eraser for AIO Page Builder (spec §47, SPR-004).
 * Anonymizes or removes actor-linked data. Preserves audit trail by redacting actor references
 * in AI runs and job queue; removes user meta and transients.
 * Secrets are never stored in these records; none are exposed.
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
 * Erases or anonymizes personal data for a user (by email) for the WordPress Tools → Erase Personal Data flow.
 */
final class Personal_Data_Eraser {

	private const PER_PAGE = 50;

	/** Redacted actor value for preserved records (audit trail). */
	private const ANONYMIZED_ACTOR = '0';

	/** Redacted actor_ref for job queue (preserves row for audit). */
	private const ANONYMIZED_ACTOR_REF = 'user:0';

	/**
	 * Erases or anonymizes personal data for the given email address (paginated).
	 *
	 * @param string $email_address User email.
	 * @param int    $page         Page number (1-based).
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public static function erase( string $email_address, int $page = 1 ): array {
		$user           = \get_user_by( 'email', $email_address );
		$page           = max( 1, $page );
		$items_removed  = false;
		$items_retained = false;
		$messages       = array();

		if ( ! $user instanceof \WP_User ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$user_id   = (int) $user->ID;
		$actor_ref = 'user:' . $user_id;
		$offset    = ( $page - 1 ) * self::PER_PAGE;

		if ( $page === 1 ) {
			$pref_removed = self::erase_user_prefs( $user_id );
			if ( $pref_removed ) {
				$items_removed = true;
				$messages[]    = __( 'Template compare lists and bundle preview cache removed.', 'aio-page-builder' );
			}
		}

		$ai_run_repo = new AI_Run_Repository();
		$runs        = $ai_run_repo->list_recent_by_actor( $user_id, self::PER_PAGE, $offset );
		foreach ( $runs as $run ) {
			$post_id = isset( $run['id'] ) ? (int) $run['id'] : 0;
			if ( $post_id > 0 ) {
				$meta          = $run['run_metadata'] ?? array();
				$meta['actor'] = self::ANONYMIZED_ACTOR;
				$ai_run_repo->save_run_metadata( $post_id, $meta );
				\update_post_meta( $post_id, '_aio_run_actor', self::ANONYMIZED_ACTOR );
				$items_removed = true;
			}
		}
		if ( count( $runs ) > 0 ) {
			$items_retained = true;
			if ( $page === 1 && ! in_array( __( 'AI run and job queue records anonymized (actor reference redacted) for audit trail.', 'aio-page-builder' ), $messages, true ) ) {
				$messages[] = __( 'AI run and job queue records anonymized (actor reference redacted) for audit trail.', 'aio-page-builder' );
			}
		}

		global $wpdb;
		$job_repo = new Job_Queue_Repository( $wpdb );
		$jobs     = $job_repo->list_by_actor_ref( $actor_ref, self::PER_PAGE, $offset );
		foreach ( $jobs as $job ) {
			$id = isset( $job['id'] ) ? (int) $job['id'] : 0;
			if ( $id > 0 ) {
				$job_repo->update_actor_ref( $id, self::ANONYMIZED_ACTOR_REF );
				$items_removed = true;
			}
		}

		$chat_repo = new AI_Chat_Session_Repository();
		$chat_ids  = $chat_repo->list_post_ids_for_owner( $user_id, self::PER_PAGE, $offset );
		foreach ( $chat_ids as $cid ) {
			if ( $cid > 0 && \wp_delete_post( $cid, true ) ) {
				$items_removed = true;
			}
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::PRIVACY_CHAT_ERASE_SUMMARY,
			'page=' . (string) $page . ' chat_deleted=' . (string) count( $chat_ids )
		);

		$runs_done  = count( $runs ) < self::PER_PAGE;
		$jobs_done  = count( $jobs ) < self::PER_PAGE;
		$chats_done = count( $chat_ids ) < self::PER_PAGE;
		$done       = $runs_done && $jobs_done && $chats_done;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Removes user meta (compare lists) and transient (bundle preview). Returns true if any was removed.
	 */
	private static function erase_user_prefs( int $user_id ): bool {
		$removed     = false;
		$section_key = Template_Compare_Screen::get_compare_meta_key( 'section' );
		$page_key    = Template_Compare_Screen::get_compare_meta_key( 'page' );
		if ( \get_user_meta( $user_id, $section_key, true ) !== '' ) {
			\delete_user_meta( $user_id, $section_key );
			$removed = true;
		}
		if ( \get_user_meta( $user_id, $page_key, true ) !== '' ) {
			\delete_user_meta( $user_id, $page_key );
			$removed = true;
		}
		$transient_key = 'aio_industry_bundle_preview_' . $user_id;
		if ( \get_transient( $transient_key ) !== false ) {
			\delete_transient( $transient_key );
			$removed = true;
		}
		return $removed;
	}
}
