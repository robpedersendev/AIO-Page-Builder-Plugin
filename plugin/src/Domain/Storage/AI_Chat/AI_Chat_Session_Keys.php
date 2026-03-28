<?php
/**
 * Meta keys and limits for AI chat session CPT (template-lab scoped UX; spec §10.5 grouping).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\AI_Chat;

defined( 'ABSPATH' ) || exit;

/**
 * JSON payload in META_PAYLOAD; owner indexed for privacy export/erase.
 */
final class AI_Chat_Session_Keys {

	public const META_PAYLOAD = '_aio_chat_session_payload';

	public const META_OWNER = '_aio_chat_owner_user_id';

	/** Queryable copy of payload task_type (synced on save). */
	public const META_TASK_TYPE = '_aio_chat_task_type';

	/** Queryable flag: 1 when approved_snapshot_ref is non-empty. */
	public const META_HAS_APPROVED_SNAPSHOT = '_aio_chat_has_approved_snapshot';

	public const MAX_MESSAGES = 200;

	public const MAX_CONTENT_PREVIEW = 500;

	/** JSON key: task / surface identifier (e.g. template_lab). */
	public const P_TASK_TYPE = 'task_type';

	/** JSON key: optional external thread ref (redacted in logs). */
	public const P_PROVIDER_THREAD_REF = 'provider_thread_ref';

	/** JSON key: approved structured snapshot reference (artifact/registry handle). */
	public const P_APPROVED_SNAPSHOT_REF = 'approved_snapshot_ref';

	/** JSON key: optional retention floor (unix). */
	public const P_RETENTION_NOT_BEFORE_UNIX = 'retention_not_before_unix';

	/** JSON key: list of message records. */
	public const P_MESSAGES = 'messages';

	/** JSON key: unix time when transcript was anonymized (idempotent erase). */
	public const P_TRANSCRIPT_ANONYMIZED_UNIX = 'transcript_anonymized_unix';
}
