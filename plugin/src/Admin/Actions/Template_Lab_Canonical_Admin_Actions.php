<?php
/**
 * Admin-post: approve template-lab snapshot and apply to canonical registry (nonce + capability; delegates to domain).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Apply_Service;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Template_Lab_Access;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class Template_Lab_Canonical_Admin_Actions {

	public const ACTION_APPROVE = 'aio_template_lab_approve_snapshot';

	public const ACTION_APPLY = 'aio_template_lab_apply_canonical';

	public const NONCE_APPROVE = 'aio_template_lab_approve_snapshot';

	public const NONCE_APPLY = 'aio_template_lab_apply_canonical';

	public const FIELD_SESSION = 'aio_tl_session_id';

	public const FIELD_TARGET = 'aio_tl_target_kind';

	public const QUERY_APPROVE = 'aio_tl_approve';

	public const QUERY_APPLY = 'aio_tl_apply';

	public static function handle_approve( Service_Container $container ): void {
		$url = self::redirect_base();
		if ( ! self::verify_nonce( self::NONCE_APPROVE ) ) {
			self::redirect( $url, self::QUERY_APPROVE, 'bad_nonce' );
		}
		$session_id = self::read_session_id();
		if ( $session_id === '' ) {
			self::redirect( $url, self::QUERY_APPROVE, 'bad_request' );
		}
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			self::redirect( $url, self::QUERY_APPROVE, 'unauthorized' );
		}
		$svc = self::apply_service( $container );
		if ( $svc === null ) {
			self::redirect( $url, self::QUERY_APPROVE, 'error' );
		}
		$chat = $container->get( 'ai_chat_session_repository' );
		if ( ! $chat instanceof AI_Chat_Session_Repository_Interface ) {
			self::redirect( $url, self::QUERY_APPROVE, 'error' );
		}
		$session = $chat->get_session( $session_id );
		if ( ! is_array( $session ) ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_APPROVE, 'session_missing' );
		}
		$ref = $session['approved_snapshot_ref'] ?? null;
		$tk  = is_array( $ref ) ? (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' ) : '';
		if ( ! Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $tk ) ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_APPROVE, 'bad_request' );
		}
		if ( ! Capabilities::current_user_can_for_route( Template_Lab_Access::capability_for_approved_target_kind( $tk ) ) ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_APPROVE, 'unauthorized' );
		}
		$out  = $svc->approve_pending_snapshot( $uid, $session_id );
		$code = ( $out['ok'] ?? false ) ? 'ok' : (string) ( $out['code'] ?? 'error' );
		self::redirect( self::url_with_session( $session_id ), self::QUERY_APPROVE, $code );
	}

	public static function handle_apply( Service_Container $container ): void {
		$url = self::redirect_base();
		if ( ! self::verify_nonce( self::NONCE_APPLY ) ) {
			self::redirect( $url, self::QUERY_APPLY, 'bad_nonce' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$session_id = self::read_session_id();
		$target     = isset( $_POST[ self::FIELD_TARGET ] ) && is_string( $_POST[ self::FIELD_TARGET ] )
			? \sanitize_key( \wp_unslash( $_POST[ self::FIELD_TARGET ] ) )
			: '';
		if ( $session_id === '' || ! Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target ) ) {
			self::redirect( $url, self::QUERY_APPLY, 'bad_request' );
		}
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			self::redirect( $url, self::QUERY_APPLY, 'unauthorized' );
		}
		if ( ! Capabilities::current_user_can_for_route( Template_Lab_Access::capability_for_approved_target_kind( $target ) ) ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_APPLY, 'unauthorized' );
		}
		$svc = self::apply_service( $container );
		if ( $svc === null ) {
			self::redirect( self::url_with_session( $session_id ), self::QUERY_APPLY, 'error' );
		}
		$res  = $svc->apply_approved_snapshot( $uid, $session_id, $target );
		$code = $res->is_success()
			? ( $res->is_already_applied() ? 'already_applied' : 'ok' )
			: $res->get_code();
		self::redirect( self::url_with_session( $session_id ), self::QUERY_APPLY, $code );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	private static function apply_service( Service_Container $container ): ?Template_Lab_Canonical_Apply_Service {
		if ( ! $container->has( 'template_lab_canonical_apply_service' ) ) {
			return null;
		}
		$s = $container->get( 'template_lab_canonical_apply_service' );
		return $s instanceof Template_Lab_Canonical_Apply_Service ? $s : null;
	}

	private static function verify_nonce( string $action ): bool {
		$field = $action === self::NONCE_APPROVE ? 'aio_tl_approve_nonce' : 'aio_tl_apply_nonce';
		$raw   = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] )
			? \sanitize_text_field( \wp_unslash( $_POST[ $field ] ) )
			: '';
		return $raw !== '' && \wp_verify_nonce( $raw, $action );
	}

	private static function read_session_id(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin-post handlers verify nonce before calling.
		if ( ! isset( $_POST[ self::FIELD_SESSION ] ) || ! is_string( $_POST[ self::FIELD_SESSION ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin-post handlers verify nonce before calling.
		return \sanitize_text_field( \wp_unslash( $_POST[ self::FIELD_SESSION ] ) );
	}

	private static function redirect_base(): string {
		return Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB );
	}

	private static function url_with_session( string $session_id ): string {
		return \add_query_arg(
			array( 'session_id' => rawurlencode( $session_id ) ),
			self::redirect_base()
		);
	}

	private static function redirect( string $base, string $query_key, string $value ): void {
		\wp_safe_redirect( \add_query_arg( $query_key, rawurlencode( $value ), $base ) );
		exit;
	}

	/**
	 * @param 'approve'|'apply' $which
	 */
	public static function nonce_field( string $which ): void {
		$action = $which === 'approve' ? self::NONCE_APPROVE : self::NONCE_APPLY;
		$name   = $which === 'approve' ? 'aio_tl_approve_nonce' : 'aio_tl_apply_nonce';
		\wp_nonce_field( $action, $name );
	}
}
