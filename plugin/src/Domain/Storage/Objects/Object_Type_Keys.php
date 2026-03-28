<?php
/**
 * Stable internal post type keys for plugin object classes (spec §10, object-model-schema.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical CPT slugs. Do not rename; used in registration, queries, and references.
 * Built pages remain standard WordPress pages (post type 'page'); they are not registered here.
 */
final class Object_Type_Keys {

	private const PREFIX = 'aio_';

	/** Section Template (§10.1). */
	public const SECTION_TEMPLATE = self::PREFIX . 'section_template';

	/** Page Template (§10.2). */
	public const PAGE_TEMPLATE = self::PREFIX . 'page_template';

	/** Custom Template Composition (§10.3). */
	public const COMPOSITION = self::PREFIX . 'composition';

	/** Build Plan (§10.4). */
	public const BUILD_PLAN = self::PREFIX . 'build_plan';

	/** AI Run metadata/identity (§10.5); raw artifacts in custom table. */
	public const AI_RUN = self::PREFIX . 'ai_run';

	/** Prompt Pack (§10.6). */
	public const PROMPT_PACK = self::PREFIX . 'prompt_pack';

	/** Documentation Object (§10.7). */
	public const DOCUMENTATION = self::PREFIX . 'documentation';

	/** Version Snapshot (§10.8). */
	public const VERSION_SNAPSHOT = self::PREFIX . 'version_snapshot';

	/** Scoped template-lab / planning chat session (§10.5 UX grouping); not canonical registry state. */
	public const AI_CHAT_SESSION = self::PREFIX . 'ai_chat_session';

	/** @var array<int, string>|null */
	private static ?array $all = null;

	/**
	 * Returns all plugin CPT keys in stable order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::SECTION_TEMPLATE,
			self::PAGE_TEMPLATE,
			self::COMPOSITION,
			self::BUILD_PLAN,
			self::AI_RUN,
			self::PROMPT_PACK,
			self::DOCUMENTATION,
			self::VERSION_SNAPSHOT,
			self::AI_CHAT_SESSION,
		);
		return self::$all;
	}

	/**
	 * Returns whether the key is a plugin object CPT.
	 *
	 * @param string $post_type Post type key.
	 * @return bool
	 */
	public static function is_plugin_object( string $post_type ): bool {
		return in_array( $post_type, self::all(), true );
	}
}
