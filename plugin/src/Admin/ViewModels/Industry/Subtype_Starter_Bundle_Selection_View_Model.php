<?php
/**
 * View model for subtype-aware starter bundle selection (Prompt 449).
 * Explains parent vs subtype bundles, supports clearing back to parent, and provides display-ready bundle lists.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * View model for starter bundle selection when subtype context may be present.
 * Exposes parent-only vs subtype-specific bundles and difference explanation for the UI.
 */
final class Subtype_Starter_Bundle_Selection_View_Model {

	/** Display bundle: bundle_key. */
	public const DISPLAY_BUNDLE_KEY = 'bundle_key';

	/** Display bundle: label. */
	public const DISPLAY_LABEL = 'label';

	/** Display bundle: summary. */
	public const DISPLAY_SUMMARY = 'summary';

	/** Display bundle: whether this bundle is subtype-scoped. */
	public const DISPLAY_IS_SUBTYPE_BUNDLE = 'is_subtype_bundle';

	/** Display bundle: group label for optgroup (e.g. "Default industry" or "Subtype: Mobile Nail Technician"). */
	public const DISPLAY_GROUP_LABEL = 'group_label';

	/** @var string */
	public string $primary_industry_key = '';

	/** @var string */
	public string $subtype_key = '';

	/** @var string */
	public string $subtype_label = '';

	/** @var string */
	public string $selected_key = '';

	/** @var string */
	public string $field_name = '';

	/** @var bool */
	public bool $has_primary = false;

	/** @var array<int, array<string, mixed>> Parent (industry-only) bundles. */
	public array $parent_bundles = array();

	/** @var array<int, array<string, mixed>> Subtype-scoped bundles when subtype is set. */
	public array $subtype_bundles = array();

	/** @var array<int, array<string, mixed>> Unified display list (bundle_key, label, summary, is_subtype_bundle, group_label). */
	public array $display_bundles = array();

	/** @var bool True when selected bundle is subtype-scoped and parent_bundles exist (user can clear to parent). */
	public bool $can_clear_to_parent = false;

	/** @var bool True when subtype is set and subtype_bundles exist. */
	public bool $has_subtype_bundles = false;

	/**
	 * Builds the view model from profile and registries.
	 *
	 * @param array<string, mixed>                  $profile         Current industry profile.
	 * @param Industry_Starter_Bundle_Registry|null $bundle_registry Bundle registry.
	 * @param Industry_Subtype_Registry|null        $subtype_registry Subtype registry (for subtype label).
	 * @return self
	 */
	public static function from_profile(
		array $profile,
		?Industry_Starter_Bundle_Registry $bundle_registry,
		?Industry_Subtype_Registry $subtype_registry
	): self {
		$vm                       = new self();
		$primary                  = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$vm->primary_industry_key = $primary;
		$vm->has_primary          = $primary !== '';

		$vm->selected_key = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';

		$subtype_key       = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';
		$vm->subtype_key   = $subtype_key;
		$vm->subtype_label = '';
		if ( $subtype_registry !== null && $subtype_key !== '' ) {
			$subtype_def = $subtype_registry->get( $subtype_key );
			if ( $subtype_def !== null && ( $subtype_def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) === $primary ) {
				$vm->subtype_label = (string) ( $subtype_def[ Industry_Subtype_Registry::FIELD_LABEL ] ?? $subtype_key );
			}
		}

		$vm->field_name = \AIOPageBuilder\Admin\Screens\Industry\Industry_Starter_Bundle_Assistant::FIELD_NAME;

		if ( $primary === '' || $bundle_registry === null ) {
			return $vm;
		}

		$parent_list = $bundle_registry->get_for_industry( $primary, '' );
		foreach ( $parent_list as $bundle ) {
			if ( ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '' ) === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
				$vm->parent_bundles[] = $bundle;
			}
		}

		if ( $subtype_key !== '' ) {
			$subtype_list = $bundle_registry->get_for_industry( $primary, $subtype_key );
			foreach ( $subtype_list as $bundle ) {
				if ( ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '' ) === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
					$vm->subtype_bundles[] = $bundle;
				}
			}
			$vm->has_subtype_bundles = $vm->subtype_bundles !== array();
		}

		$default_group = __( 'Default industry bundle', 'aio-page-builder' );
		$subtype_group = $vm->subtype_label !== ''
			? sprintf( /* translators: %s: subtype label */ __( 'Subtype: %s', 'aio-page-builder' ), $vm->subtype_label )
			: __( 'Subtype bundle', 'aio-page-builder' );

		foreach ( $vm->parent_bundles as $bundle ) {
			$vm->display_bundles[] = array(
				self::DISPLAY_BUNDLE_KEY        => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ),
				self::DISPLAY_LABEL             => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? '' ),
				self::DISPLAY_SUMMARY           => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ?? '' ),
				self::DISPLAY_IS_SUBTYPE_BUNDLE => false,
				self::DISPLAY_GROUP_LABEL       => $default_group,
			);
		}
		foreach ( $vm->subtype_bundles as $bundle ) {
			$vm->display_bundles[] = array(
				self::DISPLAY_BUNDLE_KEY        => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ),
				self::DISPLAY_LABEL             => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? '' ),
				self::DISPLAY_SUMMARY           => (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ?? '' ),
				self::DISPLAY_IS_SUBTYPE_BUNDLE => true,
				self::DISPLAY_GROUP_LABEL       => $subtype_group,
			);
		}

		$selected_is_subtype = false;
		foreach ( $vm->subtype_bundles as $bundle ) {
			if ( ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ) === $vm->selected_key ) {
				$selected_is_subtype = true;
				break;
			}
		}
		$vm->can_clear_to_parent = $selected_is_subtype && $vm->parent_bundles !== array();

		return $vm;
	}
}
