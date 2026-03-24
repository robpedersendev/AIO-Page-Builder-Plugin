<?php
/**
 * Root bootstrap plugin class.
 *
 * Single controlled entry point for activation, deactivation, and runtime.
 * Wires lifecycle hooks and delegates to domain services via registered providers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

/*
 * Load container and registrar dependencies for run(). Not loaded during activate/deactivate.
 */
$aiopagebuilder_bootstrap_dir = __DIR__;
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Service_Provider_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Service_Container.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Config/Versions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Config/Plugin_Config.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Config/Option_Names.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Settings/Settings_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Settings/Option_Store.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Schema.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Normalizer.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Store_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Store.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Migrations/Schema_Version_Tracker.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Tables/Table_Names.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Tables/Table_Schema_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Tables/DbDelta_Runner.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Db/Wpdb_Prepared_Results.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Tables/Table_Installer.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Assignments/Assignment_Types.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Assignments/Assignment_Map_Service_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Assignments/Assignment_Map_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Files/Plugin_Path_Manager.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Objects/Object_Type_Keys.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Objects/Object_Status_Families.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Objects/Post_Type_Registrar.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Section_Template_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Page_Template_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Composition_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Documentation_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Version_Snapshot_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Build_Plan_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Runs/AI_Artifact_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Execution/Queue/Job_Queue_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Execution/Queue/Queue_Recovery_Repository_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Storage/Repositories/Job_Queue_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Support/Logging/Log_Severities.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Support/Logging/Log_Categories.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Support/Logging/Error_Record.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Support/Logging/Logger_Interface.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Support/Logging/Null_Logger.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Config_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Dashboard_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Diagnostics_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Admin_Router_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Capability_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Object_Registration_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Repositories_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Storage_Services_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/FormProvider/Form_Provider_Registry.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/FormProvider/Form_Integration_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/FormProvider/Form_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/Section/Section_Schema.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/Section/ExpansionPack/Section_Expansion_Pack_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/Section/ExpansionPack/Section_Expansion_Pack_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/Composition/Composition_Validation_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ExpansionPack/Page_Template_And_Composition_Expansion_Pack_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ExpansionPack/Page_Template_And_Composition_Expansion_Pack_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelBatch/Top_Level_Marketing_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelBatch/Top_Level_Marketing_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelLegalUtilityBatch/Top_Level_Legal_Utility_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelLegalUtilityBatch/Top_Level_Legal_Utility_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelEducationalResourceAuthorityBatch/Top_Level_Educational_Resource_Authority_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelEducationalResourceAuthorityBatch/Top_Level_Educational_Resource_Authority_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelVariantExpansionBatch/Top_Level_Variant_Expansion_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/TopLevelVariantExpansionBatch/Top_Level_Variant_Expansion_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/HubBatch/Hub_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/HubBatch/Hub_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/GeographicHubBatch/Geographic_Hub_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/GeographicHubBatch/Geographic_Hub_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/NestedHubBatch/Nested_Hub_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/NestedHubBatch/Nested_Hub_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/HubNestedHubVariantExpansionBatch/Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/HubNestedHubVariantExpansionBatch/Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailBatch/Child_Detail_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailBatch/Child_Detail_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailProductBatch/Child_Detail_Product_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailProductBatch/Child_Detail_Product_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailProfileEntityBatch/Child_Detail_Profile_Entity_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailProfileEntityBatch/Child_Detail_Profile_Entity_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailVariantExpansionBatch/Child_Detail_Variant_Expansion_Page_Template_Definitions.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/PageTemplate/ChildDetailVariantExpansionBatch/Child_Detail_Variant_Expansion_Page_Template_Seeder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Contracts/Export_Mode_Keys.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Export/Export_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Export/Export_Manifest_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Export/Export_Zip_Packager.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Registries/Export/Registry_Export_Fragment_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Export/Export_Token_Set_Reader.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Export/Export_Generator.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Import/Import_Validation_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Import/Restore_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Import/Conflict_Resolution_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Import/Import_Validator.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Import/Restore_Pipeline.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Uninstall/Uninstall_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Uninstall/Uninstall_Cleanup_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/ExportRestore/Uninstall/Uninstall_Export_Prompt_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ExportRestore_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Draft_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Prefill_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_UI_State_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Onboarding_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Style_Spec_Loader.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Style_Token_Registry.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Component_Override_Registry.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Render_Surface_Style_Registry.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Frontend_Style_Enqueue_Service.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Global_Style_Settings_Schema.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Global_Style_Settings_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Global_Token_Variable_Emitter.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Global_Component_Override_Emitter.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Styling/Entity_Style_Payload_Repository.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Styling_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/Industry_Packs_Module.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Industry/Profile/Industry_Pack_Migration_Result.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Industry/Profile/Industry_Pack_Migration_Executor.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Industry/Registry/Subtypes/Builtin_Subtypes.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Industry/Profile/Industry_Subtype_Resolver.php';
// * Providers referenced by Module_Registrar::register_bootstrap() but not loaded above (runtime has no Composer autoload).
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Crawler_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Build_Plan_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Execution_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Rollback_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Reporting_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Blueprints_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Registration_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Assignment_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Compatibility_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Diagnostics_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Repair_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/ACF_Debug_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Rendering_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Registries_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Validation_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Provider_Base_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Failover_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Provider_Drivers_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Prompt_Pack_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Regression_Harness_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Experiments_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/AI_Runs_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Container/Providers/Profile_Snapshot_Provider.php';
require_once $aiopagebuilder_bootstrap_dir . '/Module_Registrar.php';
require_once $aiopagebuilder_bootstrap_dir . '/Lifecycle_Manager.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Dashboard/Dashboard_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Widgets/Industry_Status_Summary_Widget.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Admin/Dashboard/Dashboard_State_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Settings_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Diagnostics_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/AI/Onboarding_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Sessions_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Session_Detail_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Comparison_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Reporting/UI/Reporting_Health_Summary_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Reporting/UI/Logs_Monitoring_State_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Domain/Reporting/UI/Privacy_Settings_State_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Logs/Queue_Logs_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Forms/Global_Style_Token_Form_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Forms/Global_Component_Override_Form_Builder.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Settings/Global_Style_Token_Settings_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Settings/Global_Component_Override_Settings_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Settings/Privacy_Reporting_Settings_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Screens/Templates/Template_Compare_Screen.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Admin_Menu.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Admin/Admin_Assets.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Privacy/Personal_Data_Exporter.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Privacy/Personal_Data_Eraser.php';
require_once $aiopagebuilder_bootstrap_dir . '/../Infrastructure/Config/Capabilities.php';

/**
 * Plugin bootstrap. Activation, deactivation, and run() are the only public entrypoints.
 */
final class Plugin {

	/** @var \AIOPageBuilder\Infrastructure\Container\Service_Container|null Container for bootstrap wiring; set in run(). */
	private ?\AIOPageBuilder\Infrastructure\Container\Service_Container $container = null;

	/**
	 * Called on plugins_loaded. Instantiates the plugin and runs it.
	 * Future: will register autoloader, environment check, and service wiring here.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		$plugin = new self();
		$plugin->run();
	}

	/**
	 * Activation hook. Delegates to Lifecycle_Manager; aborts on blocking failure.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$manager = new Lifecycle_Manager();
		$result  = $manager->activate();
		if ( $result->is_blocking() ) {
			if ( function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins( Constants::plugin_basename() );
			}
			\wp_die(
				\esc_html( $result->message !== '' ? $result->message : 'Plugin activation failed.' ),
				'Activation Failed',
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Deactivation hook. Delegates to Lifecycle_Manager; non-destructive.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$manager = new Lifecycle_Manager();
		$manager->deactivate();
	}

	/**
	 * Runtime entry point. Runs after WordPress has loaded.
	 * Wires bootstrap through Module_Registrar; no ad hoc service instantiation here.
	 *
	 * @return void
	 */
	public function run(): void {
		Constants::plugin_file();
		$container = new \AIOPageBuilder\Infrastructure\Container\Service_Container();
		$registrar = new Module_Registrar( $container );
		$registrar->register_bootstrap();
		$this->container = $registrar->container();
		if ( is_admin() ) {
			\AIOPageBuilder\Infrastructure\Config\Hub_Menu_Capabilities::register();
			\AIOPageBuilder\Admin\Admin_Assets::register();
			\AIOPageBuilder\Admin\Admin_Template_Compare_Ajax::register();
			// * Onboarding POST must redirect before any admin HTML; otherwise wp_safe_redirect fails (headers already sent) and the user sees a blank page.
			add_action( 'admin_init', array( $this, 'maybe_handle_onboarding_post_redirect' ), 0 );
			// * Priority 0: admin-post.php dispatches admin_post_* after admin_init; those hooks are never registered on admin_menu.
			add_action( 'admin_init', array( $this, 'register_admin_post_handlers' ), 0 );
			add_action( 'admin_init', array( $this, 'maybe_repair_administrator_capabilities' ), 2 );
			add_action( 'admin_init', array( $this, 'maybe_do_first_run_redirect' ), 5 );
		}
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_personal_data_exporter' ), 10, 1 );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_personal_data_eraser' ), 10, 1 );
		$live_preview = new \AIOPageBuilder\Frontend\Template_Live_Preview_Controller( $this->container );
		$live_preview->register();
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 10 );
		}
	}

	/**
	 * Ensures roles with manage_options have all plugin caps when any are missing (e.g. activation halted early).
	 *
	 * @return void
	 */
	public function maybe_repair_administrator_capabilities(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( \is_callable( array( \AIOPageBuilder\Bootstrap\Capability_Registrar::class, 'maybe_repair_administrator_caps' ) ) ) {
			\AIOPageBuilder\Bootstrap\Capability_Registrar::maybe_repair_administrator_caps();
			return;
		}
		// * Stale plugin copies may omit repair; register() is always present and re-applies role caps.
		\AIOPageBuilder\Bootstrap\Capability_Registrar::register();
		$uid = (int) \get_current_user_id();
		if ( $uid > 0 && \function_exists( 'clean_user_cache' ) ) {
			\clean_user_cache( $uid );
		}
	}

	/**
	 * One-time first run redirect after activation.
	 *
	 * @return void
	 */
	public function maybe_do_first_run_redirect(): void {
		if ( ! \current_user_can( \AIOPageBuilder\Infrastructure\Config\Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}
		$flag = \get_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_DO_FIRST_RUN_REDIRECT, '' );
		$flag = is_string( $flag ) ? $flag : '';
		if ( $flag !== '1' ) {
			return;
		}
		\delete_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_DO_FIRST_RUN_REDIRECT );
		\wp_safe_redirect(
			\admin_url(
				'admin.php?page=' . \AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen::SLUG
			)
		);
		exit;
	}

	/**
	 * Registers the plugin personal data exporter (Tools → Export Personal Data). SPR-004.
	 *
	 * @param array<string, array{exporter_friendly_name: string, callback: callable}> $exporters
	 * @return array<string, array{exporter_friendly_name: string, callback: callable}>
	 */
	public static function register_personal_data_exporter( array $exporters ): array {
		$exporters['aio-page-builder'] = array(
			'exporter_friendly_name' => __( 'AIO Page Builder', 'aio-page-builder' ),
			'callback'               => function ( string $email_address, int $page = 1 ): array {
				return \AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Exporter::export( $email_address, $page );
			},
		);
		return $exporters;
	}

	/**
	 * Registers the plugin personal data eraser (Tools → Erase Personal Data). SPR-004.
	 *
	 * @param array<string, array{eraser_friendly_name: string, callback: callable}> $erasers
	 * @return array<string, array{eraser_friendly_name: string, callback: callable}>
	 */
	public static function register_personal_data_eraser( array $erasers ): array {
		$erasers['aio-page-builder'] = array(
			'eraser_friendly_name' => __( 'AIO Page Builder', 'aio-page-builder' ),
			'callback'             => function ( string $email_address, int $page = 1 ): array {
				return \AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Eraser::erase( $email_address, $page );
			},
		);
		return $erasers;
	}

	/**
	 * Registers admin_post_* handlers. Must run on admin_init (not admin_menu) so wp-admin/admin-post.php can see them.
	 *
	 * @return void
	 */
	public function register_admin_post_handlers(): void {
		\AIOPageBuilder\Admin\Admin_Post_Handler_Registrar::register_all( $this->container );
	}

	/**
	 * Handles onboarding wizard POST and redirects before admin header output (see Onboarding_Screen::get_post_redirect_url).
	 *
	 * @return void
	 */
	public function maybe_handle_onboarding_post_redirect(): void {
		$page = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( $page !== \AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in Onboarding_Screen::handle_post() via wp_verify_nonce().
		if ( ! isset( $_POST[ \AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen::NONCE_ACTION ] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! \AIOPageBuilder\Infrastructure\Config\Capabilities::current_user_can_for_route( \AIOPageBuilder\Infrastructure\Config\Capabilities::RUN_ONBOARDING ) ) {
			return;
		}
		$screen   = new \AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen( $this->container );
		$redirect = $screen->get_post_redirect_url();
		if ( $redirect !== null ) {
			\wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Registers the plugin admin menu and routes to screen classes.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$menu = new \AIOPageBuilder\Admin\Admin_Menu( $this->container );
		$menu->register();
	}

	/*
	 * Manual verification checklist (Prompt 002):
	 * - [ ] Plugin appears in WP admin Plugins list.
	 * - [ ] Activate: no fatal error; activation completes.
	 * - [ ] Deactivate: no fatal error; deactivation completes.
	 * - [ ] Direct access to uninstall.php: exits without defining WP_UNINSTALL_PLUGIN (safe).
	 * - [ ] No options, capabilities, or content deleted by root/bootstrap/uninstall stubs.
	 *
	 * Manual verification checklist (Prompt 003):
	 * - [ ] Constants load before plugin run (root calls Constants::init() before bootstrap).
	 * - [ ] No duplicate version/path definitions in root file.
	 * - [ ] Min WP 6.6, min PHP 8.1 (Constants::min_wp_version(), min_php_version()).
	 * - [ ] Versions::all() returns plugin, global_schema, table_schema, registry_schema, export_schema.
	 *
	 * Manual verification checklist (Prompt 005):
	 * - [ ] Activation calls Lifecycle_Manager::activate(); deactivation calls Lifecycle_Manager::deactivate().
	 * - [ ] Blocking failure from a phase aborts activation (plugin deactivated, message shown) without fatal.
	 * - [ ] Uninstall file guarded by WP_UNINSTALL_PLUGIN; uninstall runs Lifecycle_Manager::uninstall(); no deletion.
	 *
	 * Manual verification checklist (Prompt 056 – Crawler admin):
	 * - [ ] AIO Page Builder menu shows submenus: Dashboard, Settings, Diagnostics, Crawl Sessions, Crawl Comparison.
	 * - [ ] Crawl Sessions: list view shows run ID, site host, status, counts, Started, View pages link (or "No crawl sessions yet").
	 * - [ ] Crawl Sessions: clicking View pages opens session detail with Run ID, Site host, Status, Page snapshots table (URL, Title, Classification, Nav, Status) or "No page records".
	 * - [ ] Crawl Comparison: form with Prior run and New run dropdowns, Compare button; after submit shows summary table and page changes table when two runs selected.
	 * - [ ] No raw HTML or unbounded content in list/detail; errors are user-friendly (e.g. Invalid run ID).
	 */
}
