<?php
/**
 * Programmatic ACF field group registration (spec §7.3, §20.6, §20.8, §59.5).
 * Registers section-owned groups at acf/init with deterministic keys.
 * No-op when ACF is unavailable. Does not implement page-level visibility.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
/**
 * Registers local field groups from normalized section blueprints.
 * Fail-safe when acf_add_local_field_group is not available.
 */
final class ACF_Group_Registrar {

	/** @var Section_Field_Blueprint_Service */
	private Section_Field_Blueprint_Service $blueprint_service;

	/** @var ACF_Group_Builder */
	private ACF_Group_Builder $group_builder;

	public function __construct(
		Section_Field_Blueprint_Service $blueprint_service,
		ACF_Group_Builder $group_builder
	) {
		$this->blueprint_service = $blueprint_service;
		$this->group_builder     = $group_builder;
	}

	/**
	 * Registers all section-owned field groups that have embedded blueprints.
	 * No-op when ACF is unavailable.
	 *
	 * @return int Number of groups registered.
	 */
	public function register_all(): int {
		if ( ! $this->is_acf_available() ) {
			return 0;
		}
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$count = 0;
		foreach ( $blueprints as $blueprint ) {
			if ( $this->register_blueprint( $blueprint ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Registers groups for the given section keys only.
	 *
	 * @param list<string> $section_keys
	 * @return int Number of groups registered.
	 */
	public function register_sections( array $section_keys ): int {
		if ( ! $this->is_acf_available() ) {
			return 0;
		}
		$count = 0;
		foreach ( $section_keys as $key ) {
			$blueprint = $this->blueprint_service->get_blueprint_for_section( (string) $key );
			if ( $blueprint !== null && $this->register_blueprint( $blueprint ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Registers a single normalized blueprint as an ACF field group.
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint.
	 * @return bool True if registered.
	 */
	public function register_blueprint( array $blueprint ): bool {
		if ( ! $this->is_acf_available() ) {
			return false;
		}
		$group = $this->group_builder->build_group( $blueprint );
		if ( $group === null ) {
			return false;
		}
		acf_add_local_field_group( $group );
		return true;
	}

	/**
	 * Builds group array without registering. For verification and testing.
	 *
	 * @param array<string, mixed> $blueprint
	 * @return array<string, mixed>|null
	 */
	public function assemble_group( array $blueprint ): ?array {
		return $this->group_builder->build_group( $blueprint );
	}

	/**
	 * Returns whether ACF registration functions are available.
	 *
	 * @return bool
	 */
	public function is_acf_available(): bool {
		return function_exists( 'acf_add_local_field_group' );
	}
}
