<?php
/**
 * Coordinates chat session rows with AI run records (no HTTP provider calls).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Keys;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;

final class Template_Lab_Chat_Application_Service {

	private AI_Chat_Session_Repository_Interface $chat;

	private AI_Run_Service $runs;

	public function __construct( AI_Chat_Session_Repository_Interface $chat, AI_Run_Service $runs ) {
		$this->chat = $chat;
		$this->runs = $runs;
	}

	/**
	 * @return array{ok: bool, run_post_id?: int, code?: string}
	 */
	public function submit_prompt( int $actor_user_id, string $session_key, string $prompt_preview ): array {
		$session = $this->chat->get_session( $session_key );
		if ( $session === null ) {
			return array(
				'ok'   => false,
				'code' => 'session_not_found',
			);
		}
		$owner_ok = (int) ( $session['owner_user_id'] ?? 0 ) === $actor_user_id
			|| \current_user_can( 'manage_options' );
		if ( ! $owner_ok ) {
			return array(
				'ok'   => false,
				'code' => 'forbidden',
			);
		}
		$prev = \sanitize_text_field( $prompt_preview );
		if ( strlen( $prev ) > AI_Chat_Session_Keys::MAX_CONTENT_PREVIEW ) {
			$prev = substr( $prev, 0, AI_Chat_Session_Keys::MAX_CONTENT_PREVIEW );
		}
		if ( ! $this->chat->append_message(
			$session_key,
			array(
				'role'            => 'user',
				'content_preview' => $prev,
			)
		) ) {
			return array(
				'ok'   => false,
				'code' => 'append_failed',
			);
		}
		$run_id  = \wp_generate_uuid4();
		$post_id = $this->runs->create_run(
			$run_id,
			array(
				'actor'              => (string) $actor_user_id,
				'chat_session_key'   => $session_key,
				'template_lab_shell' => true,
				'routing_task'       => AI_Routing_Task::TEMPLATE_LAB_CHAT,
			),
			'pending_generation'
		);
		if ( $post_id <= 0 ) {
			return array(
				'ok'   => false,
				'code' => 'run_create_failed',
			);
		}
		return array(
			'ok'          => true,
			'run_post_id' => $post_id,
		);
	}
}
