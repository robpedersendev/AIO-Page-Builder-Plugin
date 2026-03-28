<?php
/**
 * Persistence seam for scoped template-lab / planning chat UX (spec §10.5 session grouping, privacy).
 *
 * Chat rows are not canonical for templates or build plans. Approved structured payloads must still
 * be written through registry repositories after explicit user action. Session content may include
 * site context—extend Personal_Data_Exporter/Eraser when implementing concrete storage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\AI_Chat;

defined( 'ABSPATH' ) || exit;

/**
 * Repository surface for a future CPT/table implementation. No default registration in container yet.
 */
interface AI_Chat_Session_Repository_Interface {

	/**
	 * @param array<string, mixed> $context actor_user_id, purpose (e.g. template_lab), optional provider_thread_ref.
	 * @return string New session id or empty string on failure.
	 */
	public function create_session( array $context ): string;

	/**
	 * @param array<string, mixed> $message role, body_ref or redacted summary, optional ai_run_post_id.
	 */
	public function append_message( string $session_id, array $message ): bool;

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_session( string $session_id ): ?array;

	/**
	 * @param array<string, mixed> $approved_snapshot_ref References normalized artifact or registry write handle.
	 */
	public function link_approved_snapshot( string $session_id, array $approved_snapshot_ref ): bool;

	/**
	 * Updates session lifecycle status (_aio_status). Invalid statuses are rejected.
	 */
	public function update_status( string $session_id, string $status ): bool;

	/**
	 * Persists an external provider thread reference (opaque id only; no secrets).
	 */
	public function set_provider_thread_ref( string $session_id, string $ref ): bool;

	/**
	 * Replaces message previews and provider thread ref with placeholders; preserves session shell and approved snapshot ref.
	 * Idempotent when already anonymized. Does not delete canonical registry records.
	 */
	public function anonymize_transcript( string $session_id ): bool;

	/**
	 * Recent sessions for a WordPress user (owner), newest modified first.
	 *
	 * @return list<array<string, mixed>> Summary rows: session_id, post_id, status, task_type, message_count, post_modified_gmt.
	 */
	public function list_recent_for_owner( int $owner_user_id, int $limit = 20, int $offset = 0 ): array;

	/**
	 * Owner-scoped session list with optional filters (server-side; uses indexed meta when available).
	 *
	 * @param array{status?:string,task_type?:string,approved?:string,search?:string} $filters approved: '' (any), '1' (has approved ref), '0' (no approved ref).
	 * @return list<array<string, mixed>> Same row shape as {@see list_recent_for_owner()}.
	 */
	public function list_for_owner_with_filters( int $owner_user_id, array $filters, int $limit = 25, int $offset = 0 ): array;

	/**
	 * Post IDs owned by user (paginated) for privacy erase.
	 *
	 * @return list<int>
	 */
	public function list_post_ids_for_owner( int $owner_user_id, int $limit, int $offset ): array;

	/**
	 * Export-only: rows with non-empty approved_snapshot_ref; refs sanitized, no transcript bodies.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list_export_safe_approved_snapshot_rows( int $limit = 500 ): array;
}
