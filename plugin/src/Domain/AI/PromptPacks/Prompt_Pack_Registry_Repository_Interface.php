<?php
/**
 * Minimal interface for prompt-pack lookup used by Prompt_Pack_Registry_Service (spec §26).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

/**
 * Allows registry to resolve packs from CPT or test doubles.
 */
interface Prompt_Pack_Registry_Repository_Interface {

	/**
	 * Returns full pack definition by internal_key and version.
	 *
	 * @param string $internal_key
	 * @param string $version
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key_and_version( string $internal_key, string $version ): ?array;

	/**
	 * Returns one pack definition by internal_key (prefers active).
	 *
	 * @param string $internal_key
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $internal_key ): ?array;

	/**
	 * Lists full pack definitions by status.
	 *
	 * @param string $status
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array;
}
