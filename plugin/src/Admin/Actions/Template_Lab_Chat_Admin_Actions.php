<?php
/**
 * Admin-post fallbacks for template-lab chat (session create, prompt submit); delegates to domain services.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Chat_Application_Service;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Template_Lab_Access;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class Template_Lab_Chat_Admin_Actions {

	public const ACTION_CREATE_SESSION = 'aio_template_lab_create_chat_session';

	public const ACTION_SUBMIT_PROMPT = 'aio_template_lab_submit_chat_prompt';

	public const NONCE_CREATE = 'aio_template_lab_create_chat_session';

	public const NONCE_PROMPT = 'aio_template_lab_submit_chat_prompt';

	public const QUERY_CREATE = 'aio_tl_chat_create';

	public const QUERY_PROMPT = 'aio_tl_chat_prompt';

	public static function handle_create_session( Service_Container $container ): void {
		$base = Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB );
		if ( ! self::verify_nonce( self::NONCE_CREATE, 'aio_tl_chat_create_nonce' ) ) {
			self::redirect( $base, self::QUERY_CREATE, 'bad_nonce' );
		}
		if ( ! Template_Lab_Access::can_create_template_lab_sessions_via_admin_post() ) {
			self::redirect( $base, self::QUERY_CREATE, 'unauthorized' );
		}
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			self::redirect( $base, self::QUERY_CREATE, 'unauthorized' );
		}
		$chat = self::chat_repo( $container );
		if ( $chat === null ) {
			self::redirect( $base, self::QUERY_CREATE, 'error' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$task = isset( $_POST['aio_tl_task_type'] ) && is_string( $_POST['aio_tl_task_type'] )
			? \sanitize_key( \wp_unslash( $_POST['aio_tl_task_type'] ) )
			: 'template_lab';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$sid = $chat->create_session(
			array(
				'actor_user_id' => $uid,
				'purpose'       => $task,
			)
		);
		if ( $sid === '' ) {
			self::redirect( $base, self::QUERY_CREATE, 'error' );
		}
		$url = \add_query_arg( array( 'session_id' => rawurlencode( $sid ) ), $base );
		self::redirect( $url, self::QUERY_CREATE, 'ok' );
	}

	public static function handle_submit_prompt( Service_Container $container ): void {
		$base = Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB );
		if ( ! self::verify_nonce( self::NONCE_PROMPT, 'aio_tl_chat_prompt_nonce' ) ) {
			self::redirect( $base, self::QUERY_PROMPT, 'bad_nonce' );
		}
		if ( ! Template_Lab_Access::can_submit_template_lab_prompts() ) {
			self::redirect( $base, self::QUERY_PROMPT, 'unauthorized' );
		}
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			self::redirect( $base, self::QUERY_PROMPT, 'unauthorized' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$session_id = isset( $_POST['aio_tl_prompt_session'] ) && is_string( $_POST['aio_tl_prompt_session'] )
			? \sanitize_text_field( \wp_unslash( $_POST['aio_tl_prompt_session'] ) )
			: '';
		$text       = isset( $_POST['aio_tl_prompt_text'] ) && is_string( $_POST['aio_tl_prompt_text'] )
			? \sanitize_text_field( \wp_unslash( $_POST['aio_tl_prompt_text'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $session_id === '' || $text === '' ) {
			self::redirect( $base, self::QUERY_PROMPT, 'bad_request' );
		}
		if ( ! $container->has( 'template_lab_chat_application_service' ) ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_PROMPT, 'error' );
		}
		$app = $container->get( 'template_lab_chat_application_service' );
		if ( ! $app instanceof Template_Lab_Chat_Application_Service ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_PROMPT, 'error' );
		}
		$out  = $app->submit_prompt( $uid, $session_id, $text );
		$code = ! empty( $out['ok'] ) ? 'ok' : (string) ( $out['code'] ?? 'error' );
		self::redirect( self::url_with_session( $session_id ), self::QUERY_PROMPT, $code );
	}

	private static function chat_repo( Service_Container $container ): ?AI_Chat_Session_Repository_Interface {
		if ( ! $container->has( 'ai_chat_session_repository' ) ) {
			return null;
		}
		$r = $container->get( 'ai_chat_session_repository' );
		return $r instanceof AI_Chat_Session_Repository_Interface ? $r : null;
	}

	private static function verify_nonce( string $action, string $field ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reads nonce field only; sanitized for wp_verify_nonce().
		$raw = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] )
			? \sanitize_text_field( \wp_unslash( $_POST[ $field ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $raw !== '' && \wp_verify_nonce( $raw, $action );
	}

	private static function url_with_session( string $session_id ): string {
		return \add_query_arg(
			array( 'session_id' => rawurlencode( $session_id ) ),
			Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB )
		);
	}

	private static function redirect( string $base, string $query_key, string $value ): void {
		\wp_safe_redirect( \add_query_arg( $query_key, rawurlencode( $value ), $base ) );
		exit;
	}

	/**
	 * @param 'create'|'prompt' $which
	 */
	public static function nonce_field( string $which ): void {
		$action = $which === 'create' ? self::NONCE_CREATE : self::NONCE_PROMPT;
		$name   = $which === 'create' ? 'aio_tl_chat_create_nonce' : 'aio_tl_chat_prompt_nonce';
		\wp_nonce_field( $action, $name );
	}
}
