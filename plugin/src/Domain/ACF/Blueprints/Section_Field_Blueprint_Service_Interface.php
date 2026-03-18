<?php
/**
 * Interface for section field blueprint retrieval (spec §20; Prompt 222).
 * Allows regeneration service to depend on abstraction for testing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Blueprint retrieval by section key; all normalized blueprints.
 */
interface Section_Field_Blueprint_Service_Interface {

	/**
	 * Returns normalized blueprint for a section by key.
	 *
	 * @param string      $section_key Section internal_key.
	 * @param string|null $version     Optional version filter; null = use section's current version.
	 * @return array<string, mixed>|null Normalized blueprint or null if not found/invalid.
	 */
	public function get_blueprint_for_section( string $section_key, ?string $version = null ): ?array;

	/**
	 * Returns all normalized blueprints from sections that have embedded field_blueprint.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all_blueprints(): array;
}
