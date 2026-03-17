<?php
/**
 * Bounded migration executor for deprecated-to-replacement industry pack transitions (Prompt 412).
 * Updates profile refs and starter bundle refs only; does not rewrite Build Plan or approval snapshots.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Runs controlled migrations from a deprecated pack key to an approved replacement.
 * Explicit, auditable, reversible where practical. No automatic run without operator approval.
 */
final class Industry_Pack_Migration_Executor {

	/** @var Industry_Profile_Repository */
	private Industry_Profile_Repository $profile_repo;

	/** @var Industry_Pack_Registry */
	private Industry_Pack_Registry $pack_registry;

	/** @var Industry_Starter_Bundle_Registry */
	private Industry_Starter_Bundle_Registry $bundle_registry;

	public function __construct(
		Industry_Profile_Repository $profile_repo,
		Industry_Pack_Registry $pack_registry,
		Industry_Starter_Bundle_Registry $bundle_registry
	) {
		$this->profile_repo   = $profile_repo;
		$this->pack_registry  = $pack_registry;
		$this->bundle_registry = $bundle_registry;
	}

	/**
	 * Returns the replacement pack key from a pack definition, or null if not set/invalid.
	 *
	 * @param string $pack_key Industry pack key (may be deprecated).
	 * @return string|null Replacement industry_key, or null.
	 */
	public function get_replacement_pack_ref( string $pack_key ): ?string {
		$pack_key = trim( $pack_key );
		if ( $pack_key === '' ) {
			return null;
		}
		$pack = $this->pack_registry->get( $pack_key );
		if ( $pack === null ) {
			return null;
		}
		$ref = isset( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] )
			? trim( $pack[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] )
			: '';
		if ( $ref === '' || $ref === $pack_key ) {
			return null;
		}
		return $this->pack_registry->get( $ref ) !== null ? $ref : null;
	}

	/**
	 * Returns the replacement bundle key from a bundle definition, or null if not set/invalid.
	 *
	 * @param string $bundle_key Starter bundle key.
	 * @return string|null Replacement bundle_key, or null.
	 */
	public function get_replacement_bundle_ref( string $bundle_key ): ?string {
		$bundle_key = trim( $bundle_key );
		if ( $bundle_key === '' ) {
			return null;
		}
		$bundle = $this->bundle_registry->get( $bundle_key );
		if ( $bundle === null ) {
			return null;
		}
		$ref = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] )
			? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] )
			: '';
		if ( $ref === '' || $ref === $bundle_key ) {
			return null;
		}
		return $this->bundle_registry->get( $ref ) !== null ? $ref : null;
	}

	/**
	 * Runs migration from one industry pack key to another. Updates only profile and related settings; never rewrites Build Plan snapshots.
	 *
	 * @param string $from_pack_key Current (e.g. deprecated) industry pack key.
	 * @param string $to_pack_key   Replacement industry pack key (must exist and be active).
	 * @return Industry_Pack_Migration_Result
	 */
	public function run_migration( string $from_pack_key, string $to_pack_key ): Industry_Pack_Migration_Result {
		$from_pack_key = trim( $from_pack_key );
		$to_pack_key   = trim( $to_pack_key );
		$migrated_refs = array();
		$warnings      = array();
		$errors        = array();

		if ( $from_pack_key === '' || $to_pack_key === '' ) {
			$errors[] = __( 'From and to pack keys are required.', 'aio-page-builder' );
			return new Industry_Pack_Migration_Result( false, $migrated_refs, $warnings, $errors, '' );
		}
		if ( $from_pack_key === $to_pack_key ) {
			$errors[] = __( 'From and to pack keys must differ.', 'aio-page-builder' );
			return new Industry_Pack_Migration_Result( false, $migrated_refs, $warnings, $errors, '' );
		}

		$from_pack = $this->pack_registry->get( $from_pack_key );
		$to_pack   = $this->pack_registry->get( $to_pack_key );
		if ( $from_pack === null ) {
			$errors[] = sprintf( /* translators: %s: pack key */ __( 'Source pack not found: %s.', 'aio-page-builder' ), $from_pack_key );
			return new Industry_Pack_Migration_Result( false, $migrated_refs, $warnings, $errors, '' );
		}
		if ( $to_pack === null ) {
			$errors[] = sprintf( /* translators: %s: pack key */ __( 'Replacement pack not found: %s.', 'aio-page-builder' ), $to_pack_key );
			return new Industry_Pack_Migration_Result( false, $migrated_refs, $warnings, $errors, '' );
		}
		$to_status = isset( $to_pack[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $to_pack[ Industry_Pack_Schema::FIELD_STATUS ] )
			? $to_pack[ Industry_Pack_Schema::FIELD_STATUS ]
			: '';
		if ( $to_status !== Industry_Pack_Schema::STATUS_ACTIVE ) {
			$errors[] = sprintf( /* translators: %s: pack key */ __( 'Replacement pack is not active: %s.', 'aio-page-builder' ), $to_pack_key );
			return new Industry_Pack_Migration_Result( false, $migrated_refs, $warnings, $errors, '' );
		}

		$profile = $this->profile_repo->get_profile();
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$secondary = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: array();
		$selected_bundle = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';

		$updated_primary   = $primary;
		$updated_secondary = $secondary;
		$updated_bundle    = $selected_bundle;

		if ( $primary === $from_pack_key ) {
			$updated_primary = $to_pack_key;
			$migrated_refs[] = array(
				'object_type' => Industry_Pack_Migration_Result::OBJECT_TYPE_PRIMARY_INDUSTRY,
				'old_ref'     => $from_pack_key,
				'new_ref'     => $to_pack_key,
			);
		}

		$has_secondary_change = false;
		$new_secondary       = array();
		foreach ( $secondary as $key ) {
			if ( ! is_string( $key ) ) {
				continue;
			}
			$k = trim( $key );
			if ( $k === $from_pack_key ) {
				$new_secondary[] = $to_pack_key;
				$has_secondary_change = true;
			} else {
				$new_secondary[] = $k;
			}
		}
		if ( $has_secondary_change ) {
			$updated_secondary = array_values( array_unique( $new_secondary ) );
			$migrated_refs[] = array(
				'object_type' => Industry_Pack_Migration_Result::OBJECT_TYPE_SECONDARY_INDUSTRY,
				'old_ref'     => $from_pack_key,
				'new_ref'     => $to_pack_key,
			);
		}

		if ( $selected_bundle !== '' ) {
			$bundle_def = $this->bundle_registry->get( $selected_bundle );
			$bundle_industry = $bundle_def !== null && isset( $bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && is_string( $bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] )
				? trim( $bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $bundle_industry === $from_pack_key ) {
				$replacement_bundle = $this->get_replacement_bundle_ref( $selected_bundle );
				if ( $replacement_bundle !== null ) {
					$repl_bundle_def = $this->bundle_registry->get( $replacement_bundle );
					$repl_industry = $repl_bundle_def !== null && isset( $repl_bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && is_string( $repl_bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] )
						? trim( $repl_bundle_def[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] )
						: '';
					if ( $repl_industry === $to_pack_key ) {
						$updated_bundle = $replacement_bundle;
						$migrated_refs[] = array(
							'object_type' => Industry_Pack_Migration_Result::OBJECT_TYPE_STARTER_BUNDLE,
							'old_ref'     => $selected_bundle,
							'new_ref'     => $replacement_bundle,
						);
					} else {
						$updated_bundle = '';
						$warnings[] = __( 'Selected starter bundle was cleared; replacement bundle does not belong to the new industry.', 'aio-page-builder' );
					}
				} else {
					$updated_bundle = '';
					$warnings[] = __( 'Selected starter bundle was cleared; no valid replacement bundle defined.', 'aio-page-builder' );
				}
			}
		}

		if ( $migrated_refs === array() ) {
			$warnings[] = __( 'No profile refs matched the source pack; nothing to migrate.', 'aio-page-builder' );
			$audit_note = sprintf( /* translators: 1: from key, 2: to key */ __( 'Migration skipped: %1$s → %2$s (no matching refs).', 'aio-page-builder' ), $from_pack_key, $to_pack_key );
			return new Industry_Pack_Migration_Result( true, $migrated_refs, $warnings, $errors, $audit_note );
		}

		$this->profile_repo->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => $updated_primary,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => $updated_secondary,
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => $updated_bundle,
		) );

		$audit_note = sprintf( /* translators: 1: from key, 2: to key, 3: count */ __( 'Migrated %1$s → %2$s (%3$d ref(s) updated).', 'aio-page-builder' ), $from_pack_key, $to_pack_key, count( $migrated_refs ) );
		return new Industry_Pack_Migration_Result( true, $migrated_refs, $warnings, $errors, $audit_note );
	}

	/**
	 * Runs migration using the deprecated pack's replacement_ref when present and valid. Safe when no replacement is set.
	 *
	 * @param string $deprecated_pack_key Industry pack key (typically deprecated/superseded).
	 * @return Industry_Pack_Migration_Result
	 */
	public function run_migration_to_replacement( string $deprecated_pack_key ): Industry_Pack_Migration_Result {
		$to_key = $this->get_replacement_pack_ref( $deprecated_pack_key );
		if ( $to_key === null ) {
			return new Industry_Pack_Migration_Result(
				false,
				array(),
				array(),
				array( __( 'No valid replacement pack defined for the given key.', 'aio-page-builder' ) ),
				''
			);
		}
		return $this->run_migration( $deprecated_pack_key, $to_key );
	}
}
