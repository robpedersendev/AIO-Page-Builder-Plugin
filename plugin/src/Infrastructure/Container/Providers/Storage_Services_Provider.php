<?php
/**
 * Registers storage-layer services: assignment map and plugin path manager (spec §11.7, §9.8).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;

final class Storage_Services_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'assignment_map_service', function (): Assignment_Map_Service {
			global $wpdb;
			return new Assignment_Map_Service( $wpdb );
		} );
		$container->register( 'plugin_path_manager', function (): Plugin_Path_Manager {
			return new Plugin_Path_Manager();
		} );
	}
}
