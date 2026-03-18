<?php
/**
 * Provides section template definition by key for detail screen and preview (spec §49.6). Allows test doubles without mocking final repositories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\UI;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for resolving a section template definition by internal key.
 */
interface Section_Definition_Provider {

	/**
	 * Returns full section template definition by internal key, or null if not found.
	 *
	 * @param string $key Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $key ): ?array;
}
