<?php
/**
 * REST API for template-lab chat sessions (no provider HTTP in controller).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Rest;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Chat_Application_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class AI_Chat_REST_Controller {

	private const NS = 'aio-page-builder/v1';

	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	public function register_routes(): void {
		\register_rest_route(
			self::NS,
			'/chat-sessions',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_sessions' ),
					'permission_callback' => array( $this, 'can_manage_template_lab' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_session' ),
					'permission_callback' => array( $this, 'can_manage_template_lab' ),
					'args'                => array(
						'task_type' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);
		\register_rest_route(
			self::NS,
			'/chat-sessions/(?P<session_id>[\w-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_session' ),
					'permission_callback' => array( $this, 'can_manage_template_lab' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'append_message' ),
					'permission_callback' => array( $this, 'can_manage_template_lab' ),
					'args'                => array(
						'session_id'      => array(
							'type' => 'string',
						),
						'role'            => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
						'content_preview' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		\register_rest_route(
			self::NS,
			'/chat-sessions/(?P<session_id>[\w-]+)/approved-snapshot',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'link_snapshot' ),
				'permission_callback' => array( $this, 'can_manage_template_lab' ),
				'args'                => array(
					'session_id' => array( 'type' => 'string' ),
					'ref'        => array(
						'type'     => 'object',
						'required' => true,
					),
				),
			)
		);
		\register_rest_route(
			self::NS,
			'/chat-sessions/(?P<session_id>[\w-]+)/prompt',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'submit_prompt' ),
				'permission_callback' => array( $this, 'can_manage_template_lab' ),
				'args'                => array(
					'session_id' => array( 'type' => 'string' ),
					'text'       => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		\register_rest_route(
			self::NS,
			'/chat-sessions/(?P<session_id>[\w-]+)/status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'can_manage_template_lab' ),
				'args'                => array(
					'session_id' => array( 'type' => 'string' ),
					'status'     => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	public function can_manage_template_lab(): bool {
		return Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_COMPOSITIONS );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_sessions( $request ) {
		unset( $request );
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			return new \WP_Error( 'aio_chat_not_authenticated', __( 'Authentication required.', 'aio-page-builder' ), array( 'status' => 401 ) );
		}
		$repo = $this->get_chat_repo();
		$list = $repo->list_recent_for_owner( $uid, 50, 0 );
		return new \WP_REST_Response( array( 'sessions' => $list ), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_session( $request ) {
		$sid  = (string) $request['session_id'];
		$uid  = (int) \get_current_user_id();
		$repo = $this->get_chat_repo();
		$data = $repo->get_session( $sid );
		if ( $data === null ) {
			return new \WP_Error( 'aio_chat_not_found', __( 'Session not found.', 'aio-page-builder' ), array( 'status' => 404 ) );
		}
		if ( (int) ( $data['owner_user_id'] ?? 0 ) !== $uid && ! \current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'aio_chat_forbidden', __( 'You cannot access this session.', 'aio-page-builder' ), array( 'status' => 403 ) );
		}
		return new \WP_REST_Response( $this->sanitize_session_for_response( $data ), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_session( $request ) {
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			return new \WP_Error( 'aio_chat_not_authenticated', __( 'Authentication required.', 'aio-page-builder' ), array( 'status' => 401 ) );
		}
		$task = (string) $request->get_param( 'task_type' );
		$key  = $this->get_chat_repo()->create_session(
			array(
				'actor_user_id' => $uid,
				'task_type'     => $task,
			)
		);
		if ( $key === '' ) {
			return new \WP_Error( 'aio_chat_create_failed', __( 'Could not create session.', 'aio-page-builder' ), array( 'status' => 500 ) );
		}
		return new \WP_REST_Response( array( 'session_id' => $key ), 201 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function append_message( $request ) {
		$sid = (string) $request['session_id'];
		$uid = (int) \get_current_user_id();
		if ( ! $this->user_owns_session( $sid, $uid ) ) {
			return new \WP_Error( 'aio_chat_forbidden', __( 'You cannot modify this session.', 'aio-page-builder' ), array( 'status' => 403 ) );
		}
		$ok = $this->get_chat_repo()->append_message(
			$sid,
			array(
				'role'            => (string) $request->get_param( 'role' ),
				'content_preview' => (string) $request->get_param( 'content_preview' ),
				'ai_run_post_id'  => (int) $request->get_param( 'ai_run_post_id' ),
			)
		);
		if ( ! $ok ) {
			return new \WP_Error( 'aio_chat_append_failed', __( 'Could not append message.', 'aio-page-builder' ), array( 'status' => 400 ) );
		}
		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function link_snapshot( $request ) {
		$sid = (string) $request['session_id'];
		$uid = (int) \get_current_user_id();
		if ( ! $this->user_owns_session( $sid, $uid ) ) {
			return new \WP_Error( 'aio_chat_forbidden', __( 'You cannot modify this session.', 'aio-page-builder' ), array( 'status' => 403 ) );
		}
		$ref = $request->get_param( 'ref' );
		if ( ! is_array( $ref ) ) {
			return new \WP_Error( 'aio_chat_invalid_ref', __( 'Invalid snapshot reference.', 'aio-page-builder' ), array( 'status' => 400 ) );
		}
		/** @var array<string, mixed> $ref */
		$ok = $this->get_chat_repo()->link_approved_snapshot( $sid, $ref );
		if ( ! $ok ) {
			return new \WP_Error( 'aio_chat_snapshot_failed', __( 'Could not link snapshot.', 'aio-page-builder' ), array( 'status' => 400 ) );
		}
		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function submit_prompt( $request ) {
		$sid  = (string) $request['session_id'];
		$uid  = (int) \get_current_user_id();
		$text = (string) $request->get_param( 'text' );
		$svc  = $this->container->get( 'template_lab_chat_application_service' );
		if ( ! $svc instanceof Template_Lab_Chat_Application_Service ) {
			return new \WP_Error( 'aio_chat_service', __( 'Service unavailable.', 'aio-page-builder' ), array( 'status' => 500 ) );
		}
		$out = $svc->submit_prompt( $uid, $sid, $text );
		if ( ! ( $out['ok'] ?? false ) ) {
			$code = (string) ( $out['code'] ?? 'error' );
			$st   = $code === 'session_not_found' ? 404 : ( $code === 'forbidden' ? 403 : 400 );
			return new \WP_Error( 'aio_chat_prompt_' . $code, __( 'Prompt could not be recorded.', 'aio-page-builder' ), array( 'status' => $st ) );
		}
		return new \WP_REST_Response(
			array(
				'ok'          => true,
				'run_post_id' => (int) ( $out['run_post_id'] ?? 0 ),
			),
			200
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_status( $request ) {
		$sid = (string) $request['session_id'];
		$uid = (int) \get_current_user_id();
		if ( ! $this->user_owns_session( $sid, $uid ) ) {
			return new \WP_Error( 'aio_chat_forbidden', __( 'You cannot modify this session.', 'aio-page-builder' ), array( 'status' => 403 ) );
		}
		$st = (string) $request->get_param( 'status' );
		if ( ! $this->get_chat_repo()->update_status( $sid, $st ) ) {
			return new \WP_Error( 'aio_chat_status_failed', __( 'Invalid status or session.', 'aio-page-builder' ), array( 'status' => 400 ) );
		}
		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function get_chat_repo(): AI_Chat_Session_Repository_Interface {
		return $this->container->get( 'ai_chat_session_repository' );
	}

	private function user_owns_session( string $session_id, int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		$s = $this->get_chat_repo()->get_session( $session_id );
		if ( $s === null ) {
			return false;
		}
		if ( \current_user_can( 'manage_options' ) ) {
			return true;
		}
		return (int) ( $s['owner_user_id'] ?? 0 ) === $user_id;
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function sanitize_session_for_response( array $data ): array {
		$msgs = isset( $data['messages'] ) && is_array( $data['messages'] ) ? $data['messages'] : array();
		$safe = array();
		foreach ( $msgs as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			$safe[] = array(
				'role'            => (string) ( $m['role'] ?? '' ),
				'created_at'      => (string) ( $m['created_at'] ?? '' ),
				'content_preview' => (string) ( $m['content_preview'] ?? '' ),
				'ai_run_post_id'  => (int) ( $m['ai_run_post_id'] ?? 0 ),
			);
		}
		return array(
			'session_id'            => (string) ( $data['session_id'] ?? '' ),
			'status'                => (string) ( $data['status'] ?? '' ),
			'task_type'             => (string) ( $data['task_type'] ?? '' ),
			'post_modified_gmt'     => (string) ( $data['post_modified_gmt'] ?? '' ),
			'has_approved_snapshot' => is_array( $data['approved_snapshot_ref'] ?? null ) && $data['approved_snapshot_ref'] !== array(),
			'messages'              => $safe,
		);
	}
}
