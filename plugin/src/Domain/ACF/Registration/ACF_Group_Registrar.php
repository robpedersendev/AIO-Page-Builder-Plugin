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

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Registers local field groups from normalized section blueprints.
 * Fail-safe when acf_add_local_field_group is not available.
 * Supports deterministic registration by section list (page-level) or by variation family (large-scale §6.2–6.3).
 */
final class ACF_Group_Registrar implements ACF_Group_Registrar_Interface {

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var ACF_Group_Builder */
	private ACF_Group_Builder $group_builder;

	/** @var Section_Template_Repository|null Used for register_by_family. */
	private ?Section_Template_Repository $section_repository;

	public function __construct(
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		ACF_Group_Builder $group_builder,
		?Section_Template_Repository $section_repository = null
	) {
		$this->blueprint_service  = $blueprint_service;
		$this->group_builder      = $group_builder;
		$this->section_repository = $section_repository;
	}

	/**
	 * Registers all section-owned field groups (bulk load via get_all_blueprints).
	 * Must not be called from the acf/init bootstrap path; use run_full_registration() only for explicit tooling. Normal request registration uses register_sections() with single-section lookup. See docs/qa/acf-blueprint-bulk-load-elimination-report.md.
	 *
	 * @return int Number of groups registered.
	 */
	public function register_all(): int {
		if ( ! $this->is_acf_available() ) {
			return 0;
		}
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$count      = 0;
		foreach ( $blueprints as $blueprint ) {
			if ( $this->register_blueprint( $blueprint ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Registers groups for the given section keys only (deterministic; single-section blueprint lookup per key).
	 * De-duplicates keys; skips invalid or missing section keys safely.
	 *
	 * @param array<int, string> $section_keys
	 * @return int Number of groups registered.
	 */
	public function register_sections( array $section_keys ): int {
		return $this->register_sections_with_result( $section_keys )->get_registered_count();
	}

	/**
	 * Registers groups for the given section keys and returns a result summary (registered count, skipped keys).
	 * De-duplicates keys; uses get_blueprint_for_section() per key (no full blueprint list load).
	 *
	 * @param array<int, string> $section_keys
	 * @return Section_Scoped_Group_Registration_Result
	 */
	public function register_sections_with_result( array $section_keys ): Section_Scoped_Group_Registration_Result {
		if ( ! $this->is_acf_available() ) {
			return new Section_Scoped_Group_Registration_Result( 0, array() );
		}
		$unique     = array_values( array_unique( array_map( 'strval', $section_keys ) ) );
		$registered = 0;
		$skipped    = array();
		foreach ( $unique as $key ) {
			$key = (string) $key;
			if ( $key === '' ) {
				continue;
			}
			$blueprint = $this->blueprint_service->get_blueprint_for_section( $key );
			if ( $blueprint === null ) {
				$skipped[] = $key;
				continue;
			}
			if ( $this->register_blueprint( $blueprint ) ) {
				++$registered;
			}
		}
		return new Section_Scoped_Group_Registration_Result( $registered, $skipped );
	}

	/**
	 * Registers groups only for sections that appear on the given page (deterministic; §6.2–6.3).
	 *
	 * @param array<int, string> $section_keys Section keys from page template composition.
	 * @return int Number of groups registered.
	 */
	public function register_sections_for_page( array $section_keys ): int {
		return $this->register_sections( $section_keys );
	}

	/**
	 * Registers groups for all sections in a variation family. No-op when section repository is not set.
	 *
	 * @param string $variation_family_key variation_family_key from section definitions.
	 * @return int Number of groups registered.
	 */
	public function register_by_family( string $variation_family_key ): int {
		if ( ! $this->is_acf_available() || $this->section_repository === null ) {
			return 0;
		}
		$family_key = \sanitize_key( $variation_family_key );
		if ( $family_key === '' ) {
			return 0;
		}
		$definitions  = $this->section_repository->list_all_definitions( 9999, 0 );
		$section_keys = array();
		foreach ( $definitions as $def ) {
			$def_family = \sanitize_key( (string) ( $def['variation_family_key'] ?? '' ) );
			if ( $def_family === $family_key ) {
				$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
				if ( $key !== '' ) {
					$section_keys[] = $key;
				}
			}
		}
		return $this->register_sections( $section_keys );
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
