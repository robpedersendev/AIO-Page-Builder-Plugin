<?php
/**
 * Contract for programmatic ACF field group registration (spec §20.6, §20.8).
 * Allows regeneration/repair and tests to depend on a stable interface.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for ACF field group registration (single blueprint, all sections, or section-scoped).
 */
interface ACF_Group_Registrar_Interface {

	/**
	 * Registers one field group from a normalized blueprint.
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint from Section_Field_Blueprint_Service.
	 * @return bool True if registered, false if ACF unavailable or blueprint invalid.
	 */
	public function register_blueprint( array $blueprint ): bool;

	/**
	 * Registers all section-owned field groups (full registration). Used by bootstrap or explicit tooling.
	 *
	 * @return int Number of groups registered.
	 */
	public function register_all(): int;

	/**
	 * Registers only the given section keys (page-scoped registration).
	 *
	 * @param array<int, string> $section_keys
	 * @return int Number of groups registered.
	 */
	public function register_sections( array $section_keys ): int;
}
