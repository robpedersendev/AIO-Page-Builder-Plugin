<?php
/**
 * Registers the single top-level plugin menu and submenu pages.
 * Routes each page to a dedicated screen class (see admin-screen-inventory.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\AI\Profile_Snapshot_History_Panel;
use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Bundle_Import_Preview_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Guided_Repair_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Starter_Bundle_Assistant;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Style_Preset_Screen;
use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Services\Settings_Template_Bulk_Seed_Service;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelBatch\Top_Level_Marketing_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelLegalUtilityBatch\Top_Level_Legal_Utility_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelEducationalResourceAuthorityBatch\Top_Level_Educational_Resource_Authority_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelVariantExpansionBatch\Top_Level_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubBatch\Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\GeographicHubBatch\Geographic_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\NestedHubBatch\Nested_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubNestedHubVariantExpansionBatch\Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailBatch\Child_Detail_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProductBatch\Child_Detail_Product_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProfileEntityBatch\Child_Detail_Profile_Entity_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailVariantExpansionBatch\Child_Detail_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch\Legal_Policy_Utility_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Seeder;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Export\Industry_Bundle_Upload_Validator;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator;
use AIOPageBuilder\Domain\Industry\Import\Industry_Bundle_Apply_Service;
use AIOPageBuilder\Domain\Industry\Import\Industry_Bundle_Conflict_Scanner;
use AIOPageBuilder\Domain\Onboarding\Onboarding_And_Build_Plan_Reset_Service;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Settings_Seeding_Capability_Bridge;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Registers admin menu and submenus. Screen rendering is delegated to screen classes.
 */
final class Admin_Menu {

	private const PARENT_SLUG = 'aio-page-builder';

	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Registers admin_post_* callbacks. Call from admin_init priority 0 (see Plugin::register_admin_post_handlers).
	 * wp-admin/admin-post.php never runs admin_menu, so hooks registered only there are invisible to form POSTs.
	 *
	 * @return void
	 */
	public function register_admin_post_actions(): void {
		\add_action( 'admin_post_aio_seed_form_templates', array( $this, 'handle_seed_form_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_section_expansion_pack', array( $this, 'handle_seed_section_expansion_pack' ), 10 );
		\add_action( 'admin_post_aio_seed_hero_intro_library_batch', array( $this, 'handle_seed_hero_intro_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_trust_proof_library_batch', array( $this, 'handle_seed_trust_proof_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_feature_benefit_value_batch', array( $this, 'handle_seed_feature_benefit_value_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_process_timeline_faq_batch', array( $this, 'handle_seed_process_timeline_faq_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_media_listing_profile_batch', array( $this, 'handle_seed_media_listing_profile_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_legal_policy_utility_batch', array( $this, 'handle_seed_legal_policy_utility_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_cta_super_library_batch', array( $this, 'handle_seed_cta_super_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_page_composition_expansion_pack', array( $this, 'handle_seed_page_composition_expansion_pack' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_marketing_templates', array( $this, 'handle_seed_top_level_marketing_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_legal_utility_templates', array( $this, 'handle_seed_top_level_legal_utility_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_educational_resource_authority_templates', array( $this, 'handle_seed_top_level_educational_resource_authority_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_variant_expansion_templates', array( $this, 'handle_seed_top_level_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_hub_page_templates', array( $this, 'handle_seed_hub_page_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_geographic_hub_templates', array( $this, 'handle_seed_geographic_hub_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_nested_hub_templates', array( $this, 'handle_seed_nested_hub_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_hub_nested_hub_variant_expansion_templates', array( $this, 'handle_seed_hub_nested_hub_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_templates', array( $this, 'handle_seed_child_detail_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_product_templates', array( $this, 'handle_seed_child_detail_product_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_profile_entity_templates', array( $this, 'handle_seed_child_detail_profile_entity_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_variant_expansion_templates', array( $this, 'handle_seed_child_detail_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_all_section_templates', array( $this, 'handle_seed_all_section_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_all_page_templates', array( $this, 'handle_seed_all_page_templates' ), 10 );
		\add_action( 'admin_post_aio_save_industry_profile', array( $this, 'handle_save_industry_profile' ), 10 );
		\add_action( 'admin_post_aio_toggle_industry_pack', array( $this, 'handle_toggle_industry_pack' ), 10 );
		\add_action( 'admin_post_aio_apply_industry_style_preset', array( $this, 'handle_apply_industry_style_preset' ), 10 );
		\add_action( 'admin_post_aio_save_industry_section_override', array( $this, 'handle_save_industry_section_override' ), 10 );
		\add_action( 'admin_post_aio_save_industry_page_template_override', array( $this, 'handle_save_industry_page_template_override' ), 10 );
		\add_action( 'admin_post_aio_save_industry_build_plan_override', array( $this, 'handle_save_industry_build_plan_override' ), 10 );
		\add_action( 'admin_post_aio_remove_industry_override', array( $this, 'handle_remove_industry_override' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_migrate', array( $this, 'handle_guided_repair_migrate' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_apply_ref', array( $this, 'handle_guided_repair_apply_ref' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_activate', array( $this, 'handle_guided_repair_activate' ), 10 );
		\add_action( 'admin_post_aio_create_plan_from_bundle', array( $this, 'handle_create_plan_from_bundle' ), 10 );
		\add_action( 'admin_post_aio_create_build_plan_from_ai_run', array( $this, 'handle_create_build_plan_from_ai_run' ), 10 );
		\add_action( 'admin_post_aio_industry_bundle_preview', array( $this, 'handle_industry_bundle_preview' ), 10 );
		\add_action( 'admin_post_aio_industry_bundle_apply', array( $this, 'handle_industry_bundle_apply' ), 10 );
		\add_action( 'admin_post_aio_reset_onboarding_build_plans', array( $this, 'handle_reset_onboarding_build_plans' ), 10 );

		$profile_snapshots_for_post = new Profile_Snapshot_History_Panel( $this->container );
		$profile_snapshots_for_post->register_hooks();
	}

	/**
	 * Registers the top-level menu and Dashboard, Settings, Diagnostics, Crawler submenus.
	 * Call from admin_menu action. Capability-aware; no mutation actions.
	 * admin_post_* hooks belong in register_admin_post_actions(), invoked from Admin_Post_Handler_Registrar on admin_init.
	 *
	 * @return void
	 */
	public function register(): void {
		$dashboard         = new Dashboard_Screen( $this->container );
		$profile_snapshots = new Profile_Snapshot_History_Panel( $this->container );
		$hub_renderer      = new \AIOPageBuilder\Admin\Admin_Menu_Hub_Renderer( $this->container, $profile_snapshots );

		add_menu_page(
			__( 'AIO Page Builder', 'aio-page-builder' ),
			__( 'AIO Page Builder', 'aio-page-builder' ),
			$dashboard->get_menu_capability(),
			self::PARENT_SLUG,
			array( $dashboard, 'render' ),
			'dashicons-admin-generic',
			59
		);

		$hub_renderer->register_submenus( self::PARENT_SLUG );
	}

	/**
	 * Handles admin-post save of Industry Profile (industry-admin-screen-contract).
	 * Verifies nonce and capability; validates then merges profile via Industry_Profile_Repository; redirects back to Industry Profile screen.
	 *
	 * @return void
	 */
	public function handle_save_industry_profile(): void {
		$redirect_url = \admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG );
		if ( ! isset( $_POST['aio_industry_profile_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_profile_nonce'] ) ), 'aio_save_industry_profile' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$repo = null;
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof Industry_Profile_Repository ) {
				$repo = $store;
			}
		}
		if ( $repo === null ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$primary = isset( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) ) )
			: '';
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized in loop below.
		$secondary_raw = isset( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? \wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			: ( isset( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS . '[]' ] ) ? \wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS . '[]' ] ) : array() );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$secondary = array();
		if ( is_array( $secondary_raw ) ) {
			foreach ( $secondary_raw as $v ) {
				if ( is_string( $v ) ) {
					$k = trim( \sanitize_text_field( \wp_unslash( $v ) ) );
					if ( $k !== '' ) {
						$secondary[] = $k;
					}
				}
			}
			$secondary = array_values( array_unique( $secondary ) );
		}
		$selected_bundle_raw = isset( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] ) && \is_string( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] ) ) )
			: '';
		$selected_bundle     = '';
		if ( $selected_bundle_raw !== '' && \strlen( $selected_bundle_raw ) <= 64 ) {
			$bundle_registry = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY )
				? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY )
				: null;
			if ( $bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ) {
				$bundle_def = $bundle_registry->get( $selected_bundle_raw );
				if ( $bundle_def !== null && isset( $bundle_def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && (string) $bundle_def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] === $primary ) {
					$selected_bundle = $selected_bundle_raw;
				}
			}
		}

		$subtype_raw      = isset( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) ) )
			: '';
		$current          = $repo->get_profile();
		$previous_primary = isset( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$subtype_registry = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
			? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
			: null;
		$subtype          = '';
		if ( $subtype_raw !== '' && $primary !== '' && strlen( $subtype_raw ) <= 64 && $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ) {
			$def = $subtype_registry->get( $subtype_raw );
			if ( $def !== null && isset( $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && trim( (string) $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) === $primary ) {
				$subtype = $subtype_raw;
			}
		}
		if ( $previous_primary !== '' && $previous_primary !== $primary ) {
			$subtype = '';
		}
		$partial = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => $primary,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => $secondary,
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => $selected_bundle,
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY   => $subtype,
		);
		$merged  = array_merge( $current, $partial );
		$merged[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] = $secondary;
		$pack_registry = null;
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ) {
			$r = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
			if ( $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ) {
				$pack_registry = $r;
			}
		}
		$qp_registry = $this->container->has( 'industry_question_pack_registry' ) ? $this->container->get( 'industry_question_pack_registry' ) : null;
		$validator   = new Industry_Profile_Validator();
		if ( ! $validator->validate( $merged, $pack_registry, $qp_registry instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp_registry : null, $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$repo->merge_profile( $partial );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( $redirect_url . '&aio_industry_result=saved' );
		exit;
	}

	/**
	 * Handles admin-post toggle of industry pack (industry-pack-activation-contract). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_toggle_industry_pack(): void {
		$redirect_url = \admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG );
		if ( ! isset( $_POST['aio_toggle_industry_pack_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_toggle_industry_pack_nonce'] ) ), 'aio_toggle_industry_pack' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$industry_key = isset( $_POST['aio_industry_pack_key'] ) && \is_string( $_POST['aio_industry_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_pack_key'] ) ) )
			: '';
		$disable      = isset( $_POST['aio_industry_pack_disable'] ) && (string) $_POST['aio_industry_pack_disable'] === '1';
		if ( $industry_key === '' ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		if ( ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$controller = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
		if ( ! $controller instanceof Industry_Pack_Toggle_Controller ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$controller->set_pack_disabled( $industry_key, $disable );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggled' );
		exit;
	}

	/**
	 * Handles admin-post apply of Industry Style Preset (industry-style-preset-application-contract).
	 * Verifies nonce and capability; applies preset via Industry_Style_Preset_Application_Service; redirects back to Industry Style Preset screen.
	 *
	 * @return void
	 */
	public function handle_apply_industry_style_preset(): void {
		$redirect_url = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'style' );
		if ( ! isset( $_POST['aio_industry_style_preset_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_style_preset_nonce'] ) ), 'aio_apply_industry_style_preset' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$preset_key = isset( $_POST['preset_key'] ) && is_string( $_POST['preset_key'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['preset_key'] ) ) )
			: '';
		if ( $preset_key === '' ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		if ( ! $this->container->has( 'industry_style_preset_application_service' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$service = $this->container->get( 'industry_style_preset_application_service' );
		if ( ! $service instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$applied = $service->apply_preset( $preset_key );
		\wp_safe_redirect( $redirect_url . ( $applied ? '&aio_style_preset_msg=applied' : '&aio_style_preset_msg=error' ) );
		exit;
	}

	/**
	 * Handles admin-post save of industry section override (Prompt 367). Delegates to Save_Industry_Section_Override_Action.
	 *
	 * @return void
	 */
	public function handle_save_industry_section_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Section_Override_Action::handle();
	}

	/**
	 * Handles admin-post save of industry page template override (Prompt 368).
	 *
	 * @return void
	 */
	public function handle_save_industry_page_template_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Page_Template_Override_Action::handle();
	}

	/**
	 * Handles admin-post save of industry Build Plan item override (Prompt 369).
	 *
	 * @return void
	 */
	public function handle_save_industry_build_plan_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Build_Plan_Override_Action::handle();
	}

	/**
	 * Handles admin-post request to remove a single industry override (Prompt 436).
	 *
	 * @return void
	 */
	public function handle_remove_industry_override(): void {
		\AIOPageBuilder\Admin\Actions\Remove_Industry_Override_Action::handle();
	}

	/**
	 * Handles guided repair: migrate deprecated pack to replacement (Prompt 527). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_migrate(): void {
		$redirect = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'repair' );
		if ( ! isset( $_POST['aio_guided_repair_migrate_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_migrate_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_MIGRATE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$deprecated_key = isset( $_POST['deprecated_pack_key'] ) && \is_string( $_POST['deprecated_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['deprecated_pack_key'] ) ) )
			: '';
		if ( $deprecated_key === '' || ! $this->container->has( 'industry_pack_migration_executor' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$executor = $this->container->get( 'industry_pack_migration_executor' );
		if ( ! $executor instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Executor ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$result = $executor->run_migration_to_replacement( $deprecated_key );
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', $result->is_success() ? 'migrated' : 'error', $redirect ) );
		exit;
	}

	/**
	 * Handles guided repair: apply suggested profile ref (e.g. selected_starter_bundle_key). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_apply_ref(): void {
		$redirect = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'repair' );
		if ( ! isset( $_POST['aio_guided_repair_apply_ref_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_apply_ref_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_APPLY_REF ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$field   = isset( $_POST['profile_field'] ) && \is_string( $_POST['profile_field'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['profile_field'] ) ) )
			: '';
		$value   = isset( $_POST['profile_value'] ) && \is_string( $_POST['profile_value'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['profile_value'] ) ) )
			: '';
		$allowed = array( \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY );
		if ( $field === '' || ! \in_array( $field, $allowed, true ) || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$profile_repo = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		if ( ! $profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$profile_repo->merge_profile( array( $field => $value ) );
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'applied', $redirect ) );
		exit;
	}

	/**
	 * Handles guided repair: enable (activate) industry pack. Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_activate(): void {
		$redirect = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'repair' );
		if ( ! isset( $_POST['aio_guided_repair_activate_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_activate_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_ACTIVATE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$industry_key = isset( $_POST['industry_pack_key'] ) && \is_string( $_POST['industry_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['industry_pack_key'] ) ) )
			: '';
		if ( $industry_key === '' || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$controller = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
		if ( ! $controller instanceof Industry_Pack_Toggle_Controller ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$controller->set_pack_disabled( $industry_key, false );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'activated', $redirect ) );
		exit;
	}

	/**
	 * Handles admin-post create Build Plan from starter bundle (Prompt 409).
	 *
	 * @return void
	 */
	public function handle_create_plan_from_bundle(): void {
		\AIOPageBuilder\Admin\Actions\Create_Plan_From_Starter_Bundle_Action::handle( $this->container );
	}

	/**
	 * Creates a Build Plan from a completed AI run (normalized output).
	 *
	 * @return void
	 */
	public function handle_create_build_plan_from_ai_run(): void {
		\AIOPageBuilder\Admin\Actions\Create_Build_Plan_From_AI_Run_Action::handle( $this->container );
	}

	/**
	 * Handles industry bundle preview: validate upload, analyze conflicts, store preview in transient, redirect.
	 *
	 * @return void
	 */
	public function handle_industry_bundle_preview(): void {
		$redirect = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'import' );
		if ( ! isset( $_POST['aio_industry_bundle_preview_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_bundle_preview_nonce'] ) ), 'aio_pb_preview_industry_bundle' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Invalid request.', $redirect ) );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::IMPORT_DATA ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Permission denied.', $redirect ) );
			exit;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by Industry_Bundle_Upload_Validator; not used in output or SQL.
		$file          = isset( $_FILES['aio_industry_bundle_file'] ) && is_array( $_FILES['aio_industry_bundle_file'] ) ? $_FILES['aio_industry_bundle_file'] : array();
		$upload_result = Industry_Bundle_Upload_Validator::validate_upload( $file );
		if ( ! $upload_result['ok'] ) {
			if ( $upload_result['log_reason'] !== '' ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::ADMIN_MENU_INDUSTRY_BUNDLE_UPLOAD_REJECT,
					(string) ( $upload_result['log_reason'] ?? '' )
				);
			}
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', \rawurlencode( $upload_result['user_message'] ), $redirect ) );
			exit;
		}
		$parse_result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle(
			$upload_result['tmp_path'],
			Industry_Bundle_Upload_Validator::MAX_BYTES
		);
		if ( $parse_result['bundle'] === null ) {
			if ( $parse_result['log_reason'] !== '' ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::ADMIN_MENU_INDUSTRY_BUNDLE_PARSE_REJECT,
					(string) ( $parse_result['log_reason'] ?? '' )
				);
			}
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', \rawurlencode( $parse_result['user_message'] ), $redirect ) );
			exit;
		}
		$bundle        = $parse_result['bundle'];
		$screen        = new Industry_Bundle_Import_Preview_Screen( $this->container );
		$settings      = $this->container->has( 'settings' ) ? $this->container->get( 'settings' ) : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
		$apply_service = new Industry_Bundle_Apply_Service(
			$settings instanceof \AIOPageBuilder\Infrastructure\Settings\Settings_Service ? $settings : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service(),
			new Industry_Bundle_Conflict_Scanner()
		);
		$local_hashes  = $apply_service->get_effective_local_hashes();
		$conflicts     = ( new Industry_Bundle_Conflict_Scanner() )->scan( $bundle, $local_hashes );
		$included      = isset( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ) && \is_array( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] )
			? $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ]
			: array();
		$summary       = array();
		foreach ( $included as $cat ) {
			if ( \is_string( $cat ) && isset( $bundle[ $cat ] ) && \is_array( $bundle[ $cat ] ) ) {
				$summary[ $cat ] = \count( $bundle[ $cat ] );
			}
		}
		$transient_key = \sprintf( 'aio_industry_bundle_preview_%d', \get_current_user_id() );
		\set_transient(
			$transient_key,
			array(
				'bundle'    => $bundle,
				'conflicts' => $conflicts,
				'included'  => $included,
				'summary'   => $summary,
			),
			900
		);
		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handles industry bundle apply: requires preview transient, scope selection, and explicit conflict decisions.
	 *
	 * @return void
	 */
	public function handle_industry_bundle_apply(): void {
		$redirect = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'import' );
		if ( ! isset( $_POST['aio_industry_bundle_apply_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_bundle_apply_nonce'] ) ), 'aio_pb_apply_industry_bundle' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Invalid request.', $redirect ) );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Permission denied.', $redirect ) );
			exit;
		}

		$transient_key = \sprintf( 'aio_industry_bundle_preview_%d', \get_current_user_id() );
		$preview       = \get_transient( $transient_key );
		$bundle        = is_array( $preview ) && isset( $preview['bundle'] ) && is_array( $preview['bundle'] ) ? $preview['bundle'] : null;
		if ( $bundle === null ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'No preview bundle to apply.', $redirect ) );
			exit;
		}

		$scope = isset( $_POST['aio_industry_bundle_scope'] ) && is_string( $_POST['aio_industry_bundle_scope'] )
			? sanitize_key( wp_unslash( $_POST['aio_industry_bundle_scope'] ) )
			: Industry_Bundle_Apply_Service::SCOPE_FULL_SITE_PACKAGE;
		$slug  = isset( $_POST['aio_industry_bundle_slug'] ) && is_string( $_POST['aio_industry_bundle_slug'] )
			? sanitize_key( wp_unslash( $_POST['aio_industry_bundle_slug'] ) )
			: '';

		$decisions_raw = array();
		if ( isset( $_POST['conflict_decisions'] ) && is_array( $_POST['conflict_decisions'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Keys/values sanitized in the foreach below.
			$decisions_raw = wp_unslash( $_POST['conflict_decisions'] );
		}
		$decisions = array();
		foreach ( $decisions_raw as $k => $v ) {
			if ( ! is_string( $k ) || ! is_string( $v ) ) {
				continue;
			}
			$key = sanitize_text_field( wp_unslash( $k ) );
			$val = sanitize_key( wp_unslash( $v ) );
			if ( $val === 'replace' || $val === 'skip' ) {
				$decisions[ $key ] = $val;
			}
		}

		$settings = $this->container->has( 'settings' ) ? $this->container->get( 'settings' ) : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
		$apply    = new Industry_Bundle_Apply_Service(
			$settings instanceof \AIOPageBuilder\Infrastructure\Settings\Settings_Service ? $settings : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service(),
			new Industry_Bundle_Conflict_Scanner()
		);
		$result   = $apply->apply( $bundle, $slug, $scope, $decisions, (int) \get_current_user_id() );
		if ( ! $result['ok'] ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', rawurlencode( $result['error'] ), $redirect ) );
			exit;
		}

		\delete_transient( $transient_key );
		\wp_safe_redirect( \add_query_arg( 'aio_bundle_apply_result', 'applied', $redirect ) );
		exit;
	}

	/**
	 * Settings hub General & seeding: form templates require settings or both section and page registry caps.
	 *
	 * @return bool
	 */
	private function current_user_can_settings_hub_form_templates_seed(): bool {
		return Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS )
			|| ( Capabilities::current_user_can_for_route( Capabilities::MANAGE_SECTION_TEMPLATES )
				&& Capabilities::current_user_can_for_route( Capabilities::MANAGE_PAGE_TEMPLATES ) );
	}

	/**
	 * Settings hub: section library seeds — settings or section registry cap.
	 *
	 * @return bool
	 */
	private function current_user_can_settings_hub_section_batch_seed(): bool {
		return Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS )
			|| Capabilities::current_user_can_for_route( Capabilities::MANAGE_SECTION_TEMPLATES );
	}

	/**
	 * Settings hub: page template seeds — settings or page registry cap.
	 *
	 * @return bool
	 */
	private function current_user_can_settings_hub_page_batch_seed(): bool {
		return Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS )
			|| Capabilities::current_user_can_for_route( Capabilities::MANAGE_PAGE_TEMPLATES );
	}

	/**
	 * Settings hub: page + composition seeds — settings or both registry caps.
	 *
	 * @return bool
	 */
	private function current_user_can_settings_hub_page_and_composition_batch_seed(): bool {
		return Capabilities::current_user_can_for_route( Capabilities::MANAGE_SETTINGS )
			|| ( Capabilities::current_user_can_for_route( Capabilities::MANAGE_PAGE_TEMPLATES )
				&& Capabilities::current_user_can_for_route( Capabilities::MANAGE_COMPOSITIONS ) );
	}

	/**
	 * Redirects to Settings → General & seeding → Section & page templates with query args.
	 *
	 * @param array<string, string> $query_args
	 * @return void
	 */
	private function redirect_settings_section_page_seeding( array $query_args ): void {
		\wp_safe_redirect(
			Admin_Screen_Hub::subtab_url(
				Settings_Screen::SLUG,
				'general',
				Settings_Screen::SETTINGS_SUBTAB_SECTION_PAGE_TEMPLATES,
				$query_args
			)
		);
		exit;
	}

	/**
	 * Handles admin-post request to seed form section and request page template (form-provider-integration-contract).
	 * Verifies nonce and capability; redirects back to Settings with result.
	 *
	 * @return void
	 */
	public function handle_seed_form_templates(): void {
		$redirect_error = Admin_Screen_Hub::tab_url(
			Settings_Screen::SLUG,
			'general',
			array( 'aio_seed_result' => 'error' )
		);
		if ( ! isset( $_POST['aio_seed_form_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_form_templates_nonce'] ) ), 'aio_seed_form_templates' ) ) {
			\wp_safe_redirect( $redirect_error );
			exit;
		}
		// * Matches Settings hub "General & seeding" tab (MANAGE_SETTINGS), not only granular template caps.
		if ( ! $this->current_user_can_settings_hub_form_templates_seed() ) {
			\wp_safe_redirect( $redirect_error );
			exit;
		}
		try {
			if ( ! $this->container->has( 'section_registry_service' ) || ! $this->container->has( 'page_template_repository' ) ) {
				Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_FORM_TEMPLATES_NOT_REGISTERED, '' );
				\wp_safe_redirect( $redirect_error );
				exit;
			}
			$section_registry = $this->container->get( 'section_registry_service' );
			$page_repo        = $this->container->get( 'page_template_repository' );
			if ( ! $section_registry instanceof \AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service || ! $page_repo instanceof Page_Template_Repository ) {
				\wp_safe_redirect( $redirect_error );
				exit;
			}
			$result      = Settings_Seeding_Capability_Bridge::run(
				static function () use ( $section_registry, $page_repo ) {
					return $section_registry->ensure_bundled_form_templates( $page_repo );
				},
				Capabilities::MANAGE_SECTION_TEMPLATES,
				Capabilities::MANAGE_PAGE_TEMPLATES
			);
			$result_flag = $result['success'] ? 'success' : 'error';
			\wp_safe_redirect(
				Admin_Screen_Hub::tab_url(
					Settings_Screen::SLUG,
					'general',
					array( 'aio_seed_result' => $result_flag )
				)
			);
			exit;
		} catch ( \Throwable $e ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_FORM_TEMPLATES_EXCEPTION, $e->getMessage() );
			\wp_safe_redirect( $redirect_error );
			exit;
		}
	}

	/**
	 * Handles admin-post request to seed the section expansion pack (Prompt 122).
	 *
	 * @return void
	 */
	public function handle_seed_section_expansion_pack(): void {
		if ( ! isset( $_POST['aio_seed_expansion_pack_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_expansion_pack_nonce'] ) ), 'aio_seed_section_expansion_pack' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_expansion_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_expansion_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_expansion_seed_result' => 'error' ) );
		}
		$result = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Section_Expansion_Pack_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		if ( ! $result['success'] && ! empty( $result['errors'] ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_SECTION_EXPANSION_ERRORS, implode( '; ', $result['errors'] ) );
		}
		$query         = $result['success'] ? 'aio_expansion_seed_result=success' : 'aio_expansion_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the hero/intro library batch (SEC-01, Prompt 147).
	 *
	 * @return void
	 */
	public function handle_seed_hero_intro_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_hero_intro_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hero_intro_batch_nonce'] ) ), 'aio_seed_hero_intro_library_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hero_intro_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hero_intro_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hero_intro_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Hero_Intro_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_hero_intro_batch_seed_result=success' : 'aio_hero_intro_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the trust/proof library batch (SEC-02, Prompt 148).
	 *
	 * @return void
	 */
	public function handle_seed_trust_proof_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_trust_proof_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_trust_proof_batch_nonce'] ) ), 'aio_seed_trust_proof_library_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_trust_proof_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_trust_proof_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_trust_proof_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Trust_Proof_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_trust_proof_batch_seed_result=success' : 'aio_trust_proof_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the feature/benefit/value library batch (SEC-03, Prompt 149).
	 *
	 * @return void
	 */
	public function handle_seed_feature_benefit_value_batch(): void {
		if ( ! isset( $_POST['aio_seed_fb_value_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_fb_value_batch_nonce'] ) ), 'aio_seed_feature_benefit_value_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_fb_value_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_fb_value_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_fb_value_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Feature_Benefit_Value_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_fb_value_batch_seed_result=success' : 'aio_fb_value_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the process/timeline/FAQ library batch (SEC-05, Prompt 150).
	 *
	 * @return void
	 */
	public function handle_seed_process_timeline_faq_batch(): void {
		if ( ! isset( $_POST['aio_seed_ptf_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_ptf_batch_nonce'] ) ), 'aio_seed_process_timeline_faq_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_ptf_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_ptf_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_ptf_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Process_Timeline_FAQ_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_ptf_batch_seed_result=success' : 'aio_ptf_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the media/listing/profile/detail library batch (SEC-06, Prompt 151).
	 *
	 * @return void
	 */
	public function handle_seed_media_listing_profile_batch(): void {
		if ( ! isset( $_POST['aio_seed_mlp_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_mlp_batch_nonce'] ) ), 'aio_seed_media_listing_profile_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_mlp_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_mlp_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_mlp_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Media_Listing_Profile_Detail_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_mlp_batch_seed_result=success' : 'aio_mlp_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the legal/policy/utility library batch (SEC-07, Prompt 152).
	 *
	 * @return void
	 */
	public function handle_seed_legal_policy_utility_batch(): void {
		if ( ! isset( $_POST['aio_seed_lpu_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_lpu_batch_nonce'] ) ), 'aio_seed_legal_policy_utility_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_lpu_batch_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_lpu_batch_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_lpu_batch_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Legal_Policy_Utility_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_lpu_batch_seed_result=success' : 'aio_lpu_batch_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the CTA super-library batch (SEC-08, Prompt 153).
	 *
	 * @return void
	 */
	public function handle_seed_cta_super_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_cta_super_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_cta_super_nonce'] ) ), 'aio_seed_cta_super_library_batch' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_cta_super_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_cta_super_seed_result' => 'error' ) );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_cta_super_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return CTA_Super_Library_Batch_Seeder::run( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_cta_super_seed_result=success' : 'aio_cta_super_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the page template and composition expansion pack (Prompt 123).
	 *
	 * @return void
	 */
	public function handle_seed_page_composition_expansion_pack(): void {
		if ( ! isset( $_POST['aio_seed_pt_comp_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_pt_comp_expansion_nonce'] ) ), 'aio_seed_page_composition_expansion_pack' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_pt_comp_expansion_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_and_composition_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_pt_comp_expansion_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		$comp_repo = $this->container->get( 'composition_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository || ! $comp_repo instanceof Composition_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_pt_comp_expansion_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo, $comp_repo ) {
				return Page_Template_And_Composition_Expansion_Pack_Seeder::run( $page_repo, $comp_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES,
			Capabilities::MANAGE_COMPOSITIONS
		);
		$query         = $result['success'] ? 'aio_pt_comp_expansion_seed_result=success' : 'aio_pt_comp_expansion_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the top-level marketing page template batch (Prompt 155).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_marketing_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_marketing_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_marketing_nonce'] ) ), 'aio_seed_top_level_marketing_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_marketing_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_marketing_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_marketing_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Top_Level_Marketing_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_top_level_marketing_seed_result=success' : 'aio_top_level_marketing_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the top-level legal/utility page template batch (Prompt 156).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_legal_utility_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_legal_utility_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_legal_utility_nonce'] ) ), 'aio_seed_top_level_legal_utility_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_legal_utility_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_legal_utility_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_legal_utility_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Top_Level_Legal_Utility_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_top_level_legal_utility_seed_result=success' : 'aio_top_level_legal_utility_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the top-level educational/resource/authority page template batch (Prompt 163).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_educational_resource_authority_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_edu_resource_authority_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_edu_resource_authority_nonce'] ) ), 'aio_seed_top_level_educational_resource_authority_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_edu_resource_authority_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_edu_resource_authority_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_edu_resource_authority_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Top_Level_Educational_Resource_Authority_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_top_level_edu_resource_authority_seed_result=success' : 'aio_top_level_edu_resource_authority_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the top-level variant expansion super-batch (Prompt 164).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_variant_expansion_nonce'] ) ), 'aio_seed_top_level_variant_expansion_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_variant_expansion_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_variant_expansion_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_top_level_variant_expansion_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Top_Level_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_top_level_variant_expansion_seed_result=success' : 'aio_top_level_variant_expansion_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the hub page template batch (Prompt 157).
	 *
	 * @return void
	 */
	public function handle_seed_hub_page_templates(): void {
		if ( ! isset( $_POST['aio_seed_hub_page_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hub_page_templates_nonce'] ) ), 'aio_seed_hub_page_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_page_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_page_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_page_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Hub_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_hub_page_seed_result=success' : 'aio_hub_page_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles admin-post request to seed the geographic hub page template batch (Prompt 158).
	 *
	 * @return void
	 */
	public function handle_seed_geographic_hub_templates(): void {
		if ( ! isset( $_POST['aio_seed_geographic_hub_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_geographic_hub_nonce'] ) ), 'aio_seed_geographic_hub_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_geographic_hub_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_geographic_hub_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_geographic_hub_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Geographic_Hub_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_geographic_hub_seed_result=success' : 'aio_geographic_hub_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for nested hub page template batch (PT-06). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_nested_hub_templates(): void {
		if ( ! isset( $_POST['aio_seed_nested_hub_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_nested_hub_nonce'] ) ), 'aio_seed_nested_hub_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_nested_hub_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_nested_hub_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_nested_hub_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Nested_Hub_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_nested_hub_seed_result=success' : 'aio_nested_hub_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for hub and nested hub variant expansion super-batch (PT-12). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_hub_nested_hub_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_hub_nested_hub_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hub_nested_hub_variant_expansion_nonce'] ) ), 'aio_seed_hub_nested_hub_variant_expansion_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_nested_hub_variant_expansion_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_nested_hub_variant_expansion_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_hub_nested_hub_variant_expansion_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_hub_nested_hub_variant_expansion_seed_result=success' : 'aio_hub_nested_hub_variant_expansion_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for child/detail page template batch (PT-07). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_nonce'] ) ), 'aio_seed_child_detail_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Child_Detail_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_child_detail_seed_result=success' : 'aio_child_detail_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for product/catalog child/detail page template batch (PT-08). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_product_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_product_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_product_nonce'] ) ), 'aio_seed_child_detail_product_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_product_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_product_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_product_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Child_Detail_Product_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_child_detail_product_seed_result=success' : 'aio_child_detail_product_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for directory/profile/entity/resource child/detail page template batch (PT-09). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_profile_entity_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_profile_entity_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_profile_entity_nonce'] ) ), 'aio_seed_child_detail_profile_entity_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_profile_entity_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_profile_entity_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_profile_entity_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Child_Detail_Profile_Entity_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_child_detail_profile_entity_seed_result=success' : 'aio_child_detail_profile_entity_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Handles seed request for child/detail variant expansion super-batch (PT-13). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_variant_expansion_nonce'] ) ), 'aio_seed_child_detail_variant_expansion_templates' ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_variant_expansion_seed_result' => 'error' ) );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_variant_expansion_seed_result' => 'error' ) );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_child_detail_variant_expansion_seed_result' => 'error' ) );
		}
		$result        = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo ) {
				return Child_Detail_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES
		);
		$query         = $result['success'] ? 'aio_child_detail_variant_expansion_seed_result=success' : 'aio_child_detail_variant_expansion_seed_result=error';
		$redirect_args = array();
		parse_str( $query, $redirect_args );
		$this->redirect_settings_section_page_seeding( $redirect_args );
	}

	/**
	 * Seeds all section template batches in order (Settings → Section & page templates).
	 *
	 * @return void
	 */
	public function handle_seed_all_section_templates(): void {
		$err = array( 'aio_seed_all_section_result' => 'error' );
		if ( ! isset( $_POST['aio_seed_all_section_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_all_section_templates_nonce'] ) ), 'aio_seed_all_section_templates' ) ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		if ( ! $this->current_user_can_settings_hub_section_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		$bulk = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $section_repo ) {
				return Settings_Template_Bulk_Seed_Service::seed_all_sections( $section_repo );
			},
			Capabilities::MANAGE_SECTION_TEMPLATES
		);
		if ( ! empty( $bulk['success'] ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_seed_all_section_result' => 'success' ) );
		}
		if ( ! empty( $bulk['errors'] ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_ALL_SECTION_TEMPLATES_ERRORS, implode( '; ', $bulk['errors'] ) );
		}
		if ( $bulk['failed_step'] !== '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_ALL_SECTION_TEMPLATES_STEP, 'failed_step=' . (string) $bulk['failed_step'] );
		}
		$this->redirect_settings_section_page_seeding( $err );
	}

	/**
	 * Seeds all page template batches in order (Settings → Section & page templates).
	 *
	 * @return void
	 */
	public function handle_seed_all_page_templates(): void {
		$err = array( 'aio_seed_all_page_result' => 'error' );
		if ( ! isset( $_POST['aio_seed_all_page_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_all_page_templates_nonce'] ) ), 'aio_seed_all_page_templates' ) ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		if ( ! $this->current_user_can_settings_hub_page_batch_seed() || ! $this->current_user_can_settings_hub_page_and_composition_batch_seed() ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		$comp_repo = $this->container->get( 'composition_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository || ! $comp_repo instanceof Composition_Repository ) {
			$this->redirect_settings_section_page_seeding( $err );
		}
		$bulk = Settings_Seeding_Capability_Bridge::run(
			static function () use ( $page_repo, $comp_repo ) {
				return Settings_Template_Bulk_Seed_Service::seed_all_pages( $page_repo, $comp_repo );
			},
			Capabilities::MANAGE_PAGE_TEMPLATES,
			Capabilities::MANAGE_COMPOSITIONS
		);
		if ( ! empty( $bulk['success'] ) ) {
			$this->redirect_settings_section_page_seeding( array( 'aio_seed_all_page_result' => 'success' ) );
		}
		if ( ! empty( $bulk['errors'] ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_ALL_PAGE_TEMPLATES_ERRORS, implode( '; ', $bulk['errors'] ) );
		}
		if ( $bulk['failed_step'] !== '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_MENU_SEED_ALL_PAGE_TEMPLATES_STEP, 'failed_step=' . (string) $bulk['failed_step'] );
		}
		$this->redirect_settings_section_page_seeding( $err );
	}

	/**
	 * Clears onboarding wizard state and deletes all Build Plan posts. Privacy hub; capability {@see Capabilities::MANAGE_REPORTING_AND_PRIVACY}.
	 *
	 * @return void
	 */
	public function handle_reset_onboarding_build_plans(): void {
		$redirect_base = Admin_Screen_Hub::tab_url( Settings_Screen::SLUG, 'privacy' );
		$fail          = static function () use ( $redirect_base ): void {
			\wp_safe_redirect( \add_query_arg( array( 'aio_reset_obp' => 'error' ), $redirect_base ) );
			exit;
		};
		if ( ! isset( $_POST['_wpnonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ), 'aio_reset_onboarding_build_plans' ) ) {
			$fail();
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_REPORTING_AND_PRIVACY ) ) {
			$fail();
		}
		if ( ! $this->container->has( 'onboarding_build_plan_reset_service' ) ) {
			$fail();
		}
		$svc = $this->container->get( 'onboarding_build_plan_reset_service' );
		if ( ! $svc instanceof Onboarding_And_Build_Plan_Reset_Service ) {
			$fail();
		}
		$svc->reset();
		\wp_safe_redirect( \add_query_arg( array( 'aio_reset_obp' => 'success' ), $redirect_base ) );
		exit;
	}
}
