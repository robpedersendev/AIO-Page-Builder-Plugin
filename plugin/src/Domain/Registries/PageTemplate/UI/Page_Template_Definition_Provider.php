<?php
/**
 * Provides page template definition by key (spec §49.7). Allows test doubles without mocking final repositories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\UI;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for resolving a page template definition by internal key.
 */
interface Page_Template_Definition_Provider {

	/**
	 * Returns full page template definition by internal key, or null if not found.
	 *
	 * @param string $key Page template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get_definition_by_key( string $key ): ?array;
}
