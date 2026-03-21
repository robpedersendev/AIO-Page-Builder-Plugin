<?php
/**
 * Minimal contract for AI run artifact persistence (spec §29, §29.8). Decouples AI_Run_Artifact_Service from the
 * concrete repository so test doubles and alternate storage backends can be wired without requiring WordPress.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Runs;

defined( 'ABSPATH' ) || exit;

/**
 * Read/write contract for artifact payloads keyed by run post ID and category.
 */
interface AI_Artifact_Repository_Interface {

	/**
	 * Retrieves a stored artifact payload.
	 *
	 * @param int    $post_id  Run post ID.
	 * @param string $category Artifact category key.
	 * @return mixed Payload or null when absent.
	 */
	public function get_artifact_payload( int $post_id, string $category ): mixed;

	/**
	 * Stores an artifact payload.
	 *
	 * @param int    $post_id  Run post ID.
	 * @param string $category Artifact category key.
	 * @param mixed  $payload  Encodable payload.
	 * @return bool True on success.
	 */
	public function save_artifact_payload( int $post_id, string $category, mixed $payload ): bool;
}
