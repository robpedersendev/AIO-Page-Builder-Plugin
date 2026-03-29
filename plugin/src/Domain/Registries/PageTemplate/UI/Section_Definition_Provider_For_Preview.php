<?php
/**
 * Provides section template definition by key for preview assembly (spec §49.7). Allows test doubles without mocking final repositories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\UI;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for resolving a section template definition by internal key (used by page template detail preview).
 */
interface Section_Definition_Provider_For_Preview {

	/**
	 * Returns full section template definition by internal key, or null if not found.
	 *
	 * @param string $key Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $key ): ?array;
}
