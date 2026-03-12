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
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Token_Set_Reader;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Zip_Packager;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers export generator, manifest builder, ZIP packager, and token set reader.
 */
final class ExportRestore_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
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
	}
}
