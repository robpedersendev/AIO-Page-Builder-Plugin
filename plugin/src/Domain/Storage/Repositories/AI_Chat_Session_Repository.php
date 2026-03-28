<?php
/**
 * CPT-backed chat session persistence (Object_Type_Keys::AI_CHAT_SESSION). No provider calls.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Keys;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Objects\Object_Status_Families;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Internal key: stable session id (acs_{uuid}). Status family: active|idle|archived|closed.
 * Message bodies are stored as bounded content_preview only; use artifacts for structured drafts.
 */
final class AI_Chat_Session_Repository extends Abstract_CPT_Repository implements AI_Chat_Session_Repository_Interface {

	/** @var list<string> */
	private const MESSAGE_ROLES = array( 'user', 'assistant', 'system' );

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::AI_CHAT_SESSION;
	}

	public function create_session( array $context ): string {
		$owner = (int) ( $context['actor_user_id'] ?? $context['owner_user_id'] ?? 0 );
		if ( $owner <= 0 ) {
			return '';
		}
		$task_type = isset( $context['purpose'] ) ? (string) $context['purpose'] : (string) ( $context['task_type'] ?? '' );
		$task_type = \sanitize_key( $task_type );
		if ( $task_type === '' ) {
			$task_type = 'template_lab';
		}
		$thread = isset( $context['provider_thread_ref'] ) ? \sanitize_text_field( (string) $context['provider_thread_ref'] ) : '';
		$thread = substr( $thread, 0, 512 );

		$key = 'acs_' . \wp_generate_uuid4();
		$id  = $this->save(
			array(
				'internal_key' => $key,
				'status'       => 'active',
				'post_title'   => $key,
			)
		);
		if ( $id <= 0 ) {
			return '';
		}
		$payload                                      = $this->default_payload();
		$payload[ AI_Chat_Session_Keys::P_TASK_TYPE ] = $task_type;
		$payload[ AI_Chat_Session_Keys::P_PROVIDER_THREAD_REF ] = $thread;
		if ( ! $this->persist_payload_and_owner( $id, $payload, $owner ) ) {
			return '';
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::CHAT_SESSION_CREATED,
			'post_id=' . (string) $id . ' owner_user_id=' . (string) $owner . ' task_type=' . $task_type
		);
		return $key;
	}

	public function append_message( string $session_id, array $message ): bool {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_MESSAGE_APPEND_FAILED, 'reason=missing_session' );
			return false;
		}
		$id = (int) ( $row['id'] ?? 0 );
		if ( $id <= 0 ) {
			return false;
		}
		$role = \sanitize_key( (string) ( $message['role'] ?? '' ) );
		if ( ! in_array( $role, self::MESSAGE_ROLES, true ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_MESSAGE_APPEND_FAILED, 'reason=invalid_role' );
			return false;
		}
		$preview = '';
		if ( isset( $message['content_preview'] ) ) {
			$preview = \sanitize_text_field( (string) $message['content_preview'] );
		} elseif ( isset( $message['body_ref'] ) ) {
			$preview = \sanitize_text_field( (string) $message['body_ref'] );
		}
		if ( strlen( $preview ) > AI_Chat_Session_Keys::MAX_CONTENT_PREVIEW ) {
			$preview = substr( $preview, 0, AI_Chat_Session_Keys::MAX_CONTENT_PREVIEW );
		}
		$ai_run_post_id = isset( $message['ai_run_post_id'] ) ? (int) $message['ai_run_post_id'] : 0;
		$meta_in        = isset( $message['meta'] ) && is_array( $message['meta'] ) ? $message['meta'] : array();
		$safe_meta      = $this->sanitize_message_meta( $meta_in );

		$payload = $this->load_payload( $id );
		$msgs    = $payload[ AI_Chat_Session_Keys::P_MESSAGES ];
		if ( ! is_array( $msgs ) ) {
			$msgs = array();
		}
		$msgs[] = array(
			'role'            => $role,
			'created_at'      => \gmdate( 'c' ),
			'content_preview' => $preview,
			'ai_run_post_id'  => $ai_run_post_id,
			'meta'            => $safe_meta,
			'has_body_ref'    => isset( $message['body_ref'] ) && (string) $message['body_ref'] !== '',
		);
		if ( count( $msgs ) > AI_Chat_Session_Keys::MAX_MESSAGES ) {
			$msgs = array_slice( $msgs, -AI_Chat_Session_Keys::MAX_MESSAGES );
		}
		$payload[ AI_Chat_Session_Keys::P_MESSAGES ] = $msgs;
		if ( ! $this->persist_payload_and_owner( $id, $payload, (int) \get_post_meta( $id, AI_Chat_Session_Keys::META_OWNER, true ) ) ) {
			return false;
		}
		$this->touch_modified( $id );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::CHAT_SESSION_MESSAGE_APPENDED,
			'post_id=' . (string) $id . ' role=' . $role . ' ai_run_post_id=' . (string) $ai_run_post_id . ' msg_count=' . (string) count( $msgs )
		);
		return true;
	}

	public function get_session( string $session_id ): ?array {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			return null;
		}
		$id      = (int) ( $row['id'] ?? 0 );
		$payload = $this->load_payload( $id );
		$owner   = (int) \get_post_meta( $id, AI_Chat_Session_Keys::META_OWNER, true );
		$post    = \get_post( $id );
		$mod_gmt = ( $post instanceof \WP_Post ) ? (string) $post->post_modified_gmt : '';
		return array(
			'post_id'                   => $id,
			'session_id'                => $session_id,
			'owner_user_id'             => $owner,
			'task_type'                 => (string) ( $payload[ AI_Chat_Session_Keys::P_TASK_TYPE ] ?? '' ),
			'status'                    => (string) ( $row['status'] ?? '' ),
			'provider_thread_ref'       => (string) ( $payload[ AI_Chat_Session_Keys::P_PROVIDER_THREAD_REF ] ?? '' ),
			'approved_snapshot_ref'     => $payload[ AI_Chat_Session_Keys::P_APPROVED_SNAPSHOT_REF ] ?? null,
			'retention_not_before_unix' => (int) ( $payload[ AI_Chat_Session_Keys::P_RETENTION_NOT_BEFORE_UNIX ] ?? 0 ),
			'messages'                  => is_array( $payload[ AI_Chat_Session_Keys::P_MESSAGES ] ?? null ) ? $payload[ AI_Chat_Session_Keys::P_MESSAGES ] : array(),
			'post_modified_gmt'         => $mod_gmt,
		);
	}

	public function link_approved_snapshot( string $session_id, array $approved_snapshot_ref ): bool {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			return false;
		}
		$id      = (int) ( $row['id'] ?? 0 );
		$payload = $this->load_payload( $id );
		$clean   = $this->sanitize_snapshot_ref( $approved_snapshot_ref );
		$payload[ AI_Chat_Session_Keys::P_APPROVED_SNAPSHOT_REF ] = $clean;
		$owner = (int) \get_post_meta( $id, AI_Chat_Session_Keys::META_OWNER, true );
		if ( ! $this->persist_payload_and_owner( $id, $payload, $owner ) ) {
			return false;
		}
		$this->touch_modified( $id );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::CHAT_SESSION_SNAPSHOT_LINKED,
			'post_id=' . (string) $id . ' keys=' . implode( ',', array_keys( $clean ) )
		);
		return true;
	}

	public function update_status( string $session_id, string $status ): bool {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			return false;
		}
		$status = $this->sanitize_status( $status );
		if ( $status === '' || ! Object_Status_Families::is_valid_status( $this->get_post_type(), $status ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_STATUS_UPDATE_FAILED, 'reason=invalid_status' );
			return false;
		}
		$id = (int) ( $row['id'] ?? 0 );
		$ok = parent::save(
			array(
				'id'           => $id,
				'internal_key' => $session_id,
				'post_title'   => (string) ( $row['post_title'] ?? $session_id ),
				'status'       => $status,
			)
		);
		if ( $ok <= 0 ) {
			return false;
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_STATUS_UPDATED, 'post_id=' . (string) $id . ' status=' . $status );
		return true;
	}

	public function anonymize_transcript( string $session_id ): bool {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_TRANSCRIPT_ANONYMIZED, 'result=no_match' );
			return true;
		}
		$id      = (int) ( $row['id'] ?? 0 );
		$payload = $this->load_payload( $id );
		if ( (int) ( $payload[ AI_Chat_Session_Keys::P_TRANSCRIPT_ANONYMIZED_UNIX ] ?? 0 ) > 0 ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_TRANSCRIPT_ANONYMIZED, 'post_id=' . (string) $id . ' result=idempotent' );
			return true;
		}
		$payload[ AI_Chat_Session_Keys::P_PROVIDER_THREAD_REF ]           = '';
		$payload[ AI_Chat_Session_Keys::P_MESSAGES ]                      = array(
			array(
				'role'            => 'system',
				'created_at'      => \gmdate( 'c' ),
				'content_preview' => '[erased]',
				'ai_run_post_id'  => 0,
				'meta'            => array(),
				'has_body_ref'    => false,
			),
		);
		$payload[ AI_Chat_Session_Keys::P_TRANSCRIPT_ANONYMIZED_UNIX ] = time();
		$owner = (int) \get_post_meta( $id, AI_Chat_Session_Keys::META_OWNER, true );
		if ( ! $this->persist_payload_and_owner( $id, $payload, $owner ) ) {
			return false;
		}
		$this->touch_modified( $id );
		Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_TRANSCRIPT_ANONYMIZED, 'post_id=' . (string) $id . ' result=ok' );
		return true;
	}

	public function set_provider_thread_ref( string $session_id, string $ref ): bool {
		$row = $this->get_by_key( $session_id );
		if ( $row === null ) {
			return false;
		}
		$id      = (int) ( $row['id'] ?? 0 );
		$ref     = substr( \sanitize_text_field( $ref ), 0, 512 );
		$payload = $this->load_payload( $id );
		$payload[ AI_Chat_Session_Keys::P_PROVIDER_THREAD_REF ] = $ref;
		$owner = (int) \get_post_meta( $id, AI_Chat_Session_Keys::META_OWNER, true );
		if ( ! $this->persist_payload_and_owner( $id, $payload, $owner ) ) {
			return false;
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::CHAT_SESSION_PROVIDER_THREAD_LINKED, 'post_id=' . (string) $id . ' len=' . (string) strlen( $ref ) );
		return true;
	}

	public function list_recent_for_owner( int $owner_user_id, int $limit = 20, int $offset = 0 ): array {
		$owner_user_id = max( 0, $owner_user_id );
		$limit         = max( 1, min( 100, $limit ) );
		$offset        = max( 0, $offset );
		$query         = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'post_status'            => 'any',
				'posts_per_page'         => $limit,
				'offset'                 => $offset,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_key'               => AI_Chat_Session_Keys::META_OWNER,
				'meta_value'             => (string) $owner_user_id,
			)
		);
		$out           = array();
		foreach ( $query->get_posts() as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$sid   = (string) \get_post_meta( $post->ID, self::META_INTERNAL_KEY, true );
			$pl    = $this->load_payload( (int) $post->ID );
			$mc    = is_array( $pl[ AI_Chat_Session_Keys::P_MESSAGES ] ?? null ) ? count( $pl[ AI_Chat_Session_Keys::P_MESSAGES ] ) : 0;
			$st    = (string) \get_post_meta( $post->ID, self::META_STATUS, true );
			$out[] = array(
				'session_id'        => $sid,
				'post_id'           => (int) $post->ID,
				'status'            => $st,
				'task_type'         => (string) ( $pl[ AI_Chat_Session_Keys::P_TASK_TYPE ] ?? '' ),
				'message_count'     => $mc,
				'post_modified_gmt' => (string) $post->post_modified_gmt,
			);
		}
		return $out;
	}

	public function list_post_ids_for_owner( int $owner_user_id, int $limit, int $offset ): array {
		$rows = $this->list_recent_for_owner( $owner_user_id, $limit, $offset );
		$ids  = array();
		foreach ( $rows as $r ) {
			if ( isset( $r['post_id'] ) ) {
				$ids[] = (int) $r['post_id'];
			}
		}
		return $ids;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function default_payload(): array {
		return array(
			AI_Chat_Session_Keys::P_TASK_TYPE             => '',
			AI_Chat_Session_Keys::P_PROVIDER_THREAD_REF   => '',
			AI_Chat_Session_Keys::P_APPROVED_SNAPSHOT_REF => null,
			AI_Chat_Session_Keys::P_RETENTION_NOT_BEFORE_UNIX => 0,
			AI_Chat_Session_Keys::P_MESSAGES              => array(),
			AI_Chat_Session_Keys::P_TRANSCRIPT_ANONYMIZED_UNIX => 0,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function load_payload( int $post_id ): array {
		$raw = \get_post_meta( $post_id, AI_Chat_Session_Keys::META_PAYLOAD, true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return $this->default_payload();
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return $this->default_payload();
		}
		return array_merge( $this->default_payload(), $decoded );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function persist_payload_and_owner( int $post_id, array $payload, int $owner_user_id ): bool {
		$json = \wp_json_encode( $payload );
		if ( $json === false ) {
			return false;
		}
		\update_post_meta( $post_id, AI_Chat_Session_Keys::META_PAYLOAD, $json );
		if ( $owner_user_id > 0 ) {
			\update_post_meta( $post_id, AI_Chat_Session_Keys::META_OWNER, (string) $owner_user_id );
		}
		return true;
	}

	private function touch_modified( int $post_id ): void {
		\wp_update_post(
			array(
				'ID'                => $post_id,
				'post_modified'     => \current_time( 'mysql' ),
				'post_modified_gmt' => \current_time( 'mysql', true ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $meta_in
	 * @return array<string, string|int|bool>
	 */
	private function sanitize_message_meta( array $meta_in ): array {
		$out = array();
		$n   = 0;
		foreach ( $meta_in as $k => $v ) {
			if ( $n >= 20 ) {
				break;
			}
			$ks = \sanitize_key( (string) $k );
			if ( $ks === '' ) {
				continue;
			}
			if ( is_bool( $v ) ) {
				$out[ $ks ] = $v;
			} elseif ( is_int( $v ) ) {
				$out[ $ks ] = $v;
			} else {
				$out[ $ks ] = substr( \sanitize_text_field( (string) $v ), 0, 200 );
			}
			++$n;
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $ref
	 * @return array<string, string|int>
	 */
	private function sanitize_snapshot_ref( array $ref ): array {
		$out = array();
		foreach ( $ref as $k => $v ) {
			$ks = \sanitize_key( (string) $k );
			if ( $ks === '' ) {
				continue;
			}
			if ( is_int( $v ) ) {
				$out[ $ks ] = $v;
			} else {
				$out[ $ks ] = substr( \sanitize_text_field( (string) $v ), 0, 512 );
			}
			if ( count( $out ) >= 24 ) {
				break;
			}
		}
		return $out;
	}
}
