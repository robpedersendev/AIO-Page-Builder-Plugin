<?php
/**
 * Registers export generation services (spec §52, §59.13). No import/restore or uninstall UI.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Manifest_Builder;
use AIOPageBuilder\Domain\ExportRestore\Export\Support_Package_Generator;
use AIOPageBuilder\Domain\ExportRestore\Export\Template_Library_Support_Summary_Builder;
use AIOPageBuilder\Domain\Lifecycle\Template_Library_Lifecycle_Summary_Builder;
use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Domain\ExportRestore\Validation\Template_Library_Export_Validator;
use AIOPageBuilder\Domain\ExportRestore\Validation\Template_Library_Restore_Validator;
use AIOPageBuilder\Domain\ExportRestore\UI\Import_Export_State_Builder;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Export_Prompt_Service;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Token_Set_Reader;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Zip_Packager;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers export generator, import validator, restore pipeline, and related services.
 */
final class ExportRestore_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'import_validator', function () use ( $container ): \AIOPageBuilder\Domain\ExportRestore\Import\Import_Validator {
			return new \AIOPageBuilder\Domain\ExportRestore\Import\Import_Validator(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' ),
				$container->get( 'build_plan_repository' ),
				$container->get( 'export_token_set_reader' )
			);
		} );
		$container->register( 'conflict_resolution_service', function (): \AIOPageBuilder\Domain\ExportRestore\Import\Conflict_Resolution_Service {
			return new \AIOPageBuilder\Domain\ExportRestore\Import\Conflict_Resolution_Service();
		} );
		$container->register( 'template_library_export_validator', function () use ( $container ): Template_Library_Export_Validator {
			$section_appendix = $container->has( 'section_inventory_appendix_generator' ) ? $container->get( 'section_inventory_appendix_generator' ) : null;
			$page_appendix    = $container->has( 'page_template_inventory_appendix_generator' ) ? $container->get( 'page_template_inventory_appendix_generator' ) : null;
			return new Template_Library_Export_Validator( $section_appendix, $page_appendix );
		} );
		$container->register( 'template_library_restore_validator', function () use ( $container ): Template_Library_Restore_Validator {
			$section_appendix = $container->has( 'section_inventory_appendix_generator' ) ? $container->get( 'section_inventory_appendix_generator' ) : null;
			$page_appendix    = $container->has( 'page_template_inventory_appendix_generator' ) ? $container->get( 'page_template_inventory_appendix_generator' ) : null;
			return new Template_Library_Restore_Validator(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' ),
				$section_appendix,
				$page_appendix
			);
		} );
		$container->register( 'restore_pipeline', function () use ( $container ): \AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline {
			global $wpdb;
			$restore_validator = $container->has( 'template_library_restore_validator' ) ? $container->get( 'template_library_restore_validator' ) : null;
			$style_cache = $container->has( 'style_cache_service' ) ? $container->get( 'style_cache_service' ) : null;
			$styles_norm = $container->has( 'styles_json_normalizer' ) ? $container->get( 'styles_json_normalizer' ) : null;
			$styles_san  = $container->has( 'styles_json_sanitizer' ) ? $container->get( 'styles_json_sanitizer' ) : null;
			$industry_cache = null;
			if ( $container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
				$industry_cache = $container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			}
			return new \AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline(
				$container->get( 'settings' ),
				$container->get( 'profile_store' ),
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' ),
				$container->get( 'build_plan_repository' ),
				$wpdb,
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null,
				$restore_validator,
				$style_cache instanceof \AIOPageBuilder\Domain\Styling\Style_Cache_Service ? $style_cache : null,
				$styles_norm,
				$styles_san,
				$industry_cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $industry_cache : null
			);
		} );
		$container->register( 'export_manifest_builder', function (): Export_Manifest_Builder {
			return new Export_Manifest_Builder();
		} );
		$container->register( 'export_zip_packager', function () use ( $container ): Export_Zip_Packager {
			return new Export_Zip_Packager( $container->get( 'plugin_path_manager' ) );
		} );
		$container->register( 'export_token_set_reader', function (): Export_Token_Set_Reader {
			global $wpdb;
			return new Export_Token_Set_Reader( $wpdb );
		} );
		$export_dir = __DIR__ . '/../../../Domain/ExportRestore/Export';
		require_once $export_dir . '/Template_Library_Support_Summary_Builder.php';

		$container->register( 'template_library_support_summary_builder', function () use ( $container ): Template_Library_Support_Summary_Builder {
			return new Template_Library_Support_Summary_Builder(
				$container->has( 'template_library_compliance_service' ) ? $container->get( 'template_library_compliance_service' ) : null,
				$container->has( 'section_inventory_appendix_generator' ) ? $container->get( 'section_inventory_appendix_generator' ) : null,
				$container->has( 'page_template_inventory_appendix_generator' ) ? $container->get( 'page_template_inventory_appendix_generator' ) : null,
				$container->has( 'template_deprecation_service' ) ? $container->get( 'template_deprecation_service' ) : null,
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				new Reporting_Redaction_Service(),
				$container->has( 'form_provider_availability_service' ) ? $container->get( 'form_provider_availability_service' ) : null,
				$container->has( 'form_provider_health_summary_service' ) ? $container->get( 'form_provider_health_summary_service' ) : null
			);
		} );

		$container->register( 'support_package_generator', function () use ( $container ): Support_Package_Generator {
			return new Support_Package_Generator(
				$container->get( 'plugin_path_manager' ),
				$container->get( 'settings' ),
				$container->get( 'profile_store' ),
				$container->get( 'registry_export_serializer' ),
				$container->get( 'build_plan_repository' ),
				$container->get( 'export_token_set_reader' ),
				$container->get( 'export_manifest_builder' ),
				$container->get( 'export_zip_packager' ),
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null,
				$container->has( 'template_library_support_summary_builder' ) ? $container->get( 'template_library_support_summary_builder' ) : null
			);
		} );
		$container->register( 'export_generator', function () use ( $container ): Export_Generator {
			$template_library_validator = $container->has( 'template_library_export_validator' ) ? $container->get( 'template_library_export_validator' ) : null;
			$acf_mirror_service = $container->has( 'acf_local_json_mirror_service' ) ? $container->get( 'acf_local_json_mirror_service' ) : null;
			return new Export_Generator(
				$container->get( 'plugin_path_manager' ),
				$container->get( 'settings' ),
				$container->get( 'profile_store' ),
				$container->get( 'registry_export_serializer' ),
				$container->get( 'build_plan_repository' ),
				$container->get( 'export_token_set_reader' ),
				$container->get( 'export_manifest_builder' ),
				$container->get( 'export_zip_packager' ),
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null,
				$container->get( 'support_package_generator' ),
				$template_library_validator,
				$acf_mirror_service
			);
		} );
		$container->register( 'uninstall_cleanup_service', function (): Uninstall_Cleanup_Service {
			return new Uninstall_Cleanup_Service();
		} );
		$container->register( 'uninstall_export_prompt_service', function () use ( $container ): Uninstall_Export_Prompt_Service {
			return new Uninstall_Export_Prompt_Service(
				$container->get( 'export_generator' ),
				$container->get( 'uninstall_cleanup_service' )
			);
		} );
		require_once __DIR__ . '/../../../Domain/Lifecycle/Template_Library_Lifecycle_Summary_Builder.php';
		$container->register( 'template_library_lifecycle_summary_builder', function () use ( $container ): Template_Library_Lifecycle_Summary_Builder {
			return new Template_Library_Lifecycle_Summary_Builder(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' )
			);
		} );
		$container->register( 'import_export_state_builder', function () use ( $container ): Import_Export_State_Builder {
			return new Import_Export_State_Builder(
				$container->get( 'plugin_path_manager' ),
				$container->has( 'template_library_lifecycle_summary_builder' ) ? $container->get( 'template_library_lifecycle_summary_builder' ) : null
			);
		} );
	}
}
