<?php
/**
 * Registers ACF regeneration/repair service (spec §20, §20.15; Prompt 222).
 * Repair workflow: dry-run plan, mismatch detection, selective repair, page-assignment rebuild.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Repair\ACF_Regeneration_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers acf_regeneration_service for controlled field group and page-assignment repair.
 */
final class ACF_Repair_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'acf_regeneration_service',
			function () use ( $container ): ACF_Regeneration_Service {
				return new ACF_Regeneration_Service(
					$container->get( 'section_field_blueprint_service' ),
					$container->get( 'acf_group_registrar' ),
					$container->get( 'page_field_group_assignment_service' ),
					$container->get( 'assignment_map_service' ),
					$container->get( 'section_template_repository' ),
					$container->get( 'page_template_repository' )
				);
			}
		);
	}
}
