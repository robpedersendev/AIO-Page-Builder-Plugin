<?php
/**
 * Registers ACF field cleanup and compatibility services (spec §20.15, §58.4, §58.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Compatibility\Field_Assignment_Compatibility_Service;
use AIOPageBuilder\Domain\ACF\Compatibility\Field_Cleanup_Advisor;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers field assignment compatibility and cleanup advisory services.
 */
final class ACF_Compatibility_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'field_assignment_compatibility_service',
			function () use ( $container ): Field_Assignment_Compatibility_Service {
				return new Field_Assignment_Compatibility_Service(
					$container->get( 'page_field_group_assignment_service' ),
					$container->get( 'section_template_repository' )
				);
			}
		);
		$container->register(
			'field_cleanup_advisor',
			function () use ( $container ): Field_Cleanup_Advisor {
				return new Field_Cleanup_Advisor(
					$container->get( 'page_field_group_assignment_service' ),
					$container->get( 'field_group_derivation_service' ),
					$container->get( 'assignment_map_service' ),
					$container->get( 'section_template_repository' )
				);
			}
		);
	}
}
