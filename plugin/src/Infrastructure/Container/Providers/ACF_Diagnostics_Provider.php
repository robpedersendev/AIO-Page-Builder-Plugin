<?php
/**
 * Registers ACF diagnostics services and fixture builder (spec §20, §45, §56).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Diagnostics_Service;
use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Diagnostics_State_Builder;
use AIOPageBuilder\Domain\ACF\Diagnostics\ACF_Fixture_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers ACF diagnostics service, state builder for diagnostics screen, and fixture builder.
 * Depends on ACF blueprint, registration, assignment, compatibility, and regeneration providers.
 */
final class ACF_Diagnostics_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'acf_diagnostics_service', function () use ( $container ): ACF_Diagnostics_Service {
			return new ACF_Diagnostics_Service(
				$container->get( 'section_field_blueprint_service' ),
				$container->get( 'acf_group_registrar' ),
				$container->get( 'page_field_group_assignment_service' ),
				$container->get( 'assignment_map_service' ),
				$container->get( 'field_cleanup_advisor' ),
				$container->get( 'logger' )
			);
		} );

		$container->register( 'acf_diagnostics_state_builder', function () use ( $container ): ACF_Diagnostics_State_Builder {
			$lpagery = $container->has( 'library_lpagery_compatibility_service' )
				? $container->get( 'library_lpagery_compatibility_service' )
				: null;
			return new ACF_Diagnostics_State_Builder(
				$container->get( 'acf_diagnostics_service' ),
				$container->get( 'acf_regeneration_service' ),
				$lpagery
			);
		} );

		$container->register( 'acf_fixture_builder', function (): ACF_Fixture_Builder {
			return new ACF_Fixture_Builder();
		} );
	}
}
