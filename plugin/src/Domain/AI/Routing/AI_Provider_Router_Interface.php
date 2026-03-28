<?php
/**
 * Task-scoped AI provider routing (spec §25.1, §25.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves primary provider id and optional model override for a named task. No HTTP, no secrets.
 */
interface AI_Provider_Router_Interface {

	/**
	 * @param string               $task    One of AI_Routing_Task::*.
	 * @param array<string, mixed> $context Optional: preferred_provider_id (string), etc.
	 */
	public function resolve_route( string $task, array $context = array() ): AI_Provider_Route_Result;
}
