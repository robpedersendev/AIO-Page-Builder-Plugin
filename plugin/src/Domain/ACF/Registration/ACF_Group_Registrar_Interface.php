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
 * Registers a single field group from a normalized section blueprint.
 */
interface ACF_Group_Registrar_Interface {

	/**
	 * Registers one field group from a normalized blueprint.
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint from Section_Field_Blueprint_Service.
	 * @return bool True if registered, false if ACF unavailable or blueprint invalid.
	 */
	public function register_blueprint( array $blueprint ): bool;
}
