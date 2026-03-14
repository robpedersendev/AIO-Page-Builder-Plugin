<?php
/**
 * Registers ACF local JSON mirror and debug export services (spec §20, §52, §59.13; Prompt 224).
 * Internal/debug only; registry remains source of truth.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Debug\ACF_Field_Group_Debug_Exporter;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers acf_local_json_mirror_service and acf_field_group_debug_exporter for debug export and environment comparison.
 */
final class ACF_Debug_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$debug_dir = __DIR__ . '/../../../Domain/ACF/Debug';
		require_once $debug_dir . '/ACF_Local_JSON_Mirror_Service.php';
		require_once $debug_dir . '/ACF_Field_Group_Debug_Exporter.php';

		$container->register( 'acf_local_json_mirror_service', function () use ( $container ): ACF_Local_JSON_Mirror_Service {
			return new ACF_Local_JSON_Mirror_Service(
				$container->get( 'section_field_blueprint_service' ),
				$container->get( 'acf_group_builder' )
			);
		} );
		$container->register( 'acf_field_group_debug_exporter', function () use ( $container ): ACF_Field_Group_Debug_Exporter {
			return new ACF_Field_Group_Debug_Exporter(
				$container->get( 'section_field_blueprint_service' ),
				$container->get( 'acf_local_json_mirror_service' )
			);
		} );
	}
}
