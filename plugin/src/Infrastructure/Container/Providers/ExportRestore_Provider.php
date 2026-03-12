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
		$container->register( 'restore_pipeline', function () use ( $container ): \AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline {
			global $wpdb;
			return new \AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline(
				$container->get( 'settings' ),
				$container->get( 'profile_store' ),
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' ),
				$container->get( 'build_plan_repository' ),
				$wpdb,
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null
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
		$container->register( 'export_generator', function () use ( $container ): Export_Generator {
			return new Export_Generator(
				$container->get( 'plugin_path_manager' ),
				$container->get( 'settings' ),
				$container->get( 'profile_store' ),
				$container->get( 'registry_export_serializer' ),
				$container->get( 'build_plan_repository' ),
				$container->get( 'export_token_set_reader' ),
				$container->get( 'export_manifest_builder' ),
				$container->get( 'export_zip_packager' ),
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null
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
		$container->register( 'import_export_state_builder', function () use ( $container ): Import_Export_State_Builder {
			return new Import_Export_State_Builder( $container->get( 'plugin_path_manager' ) );
		} );
	}
}
