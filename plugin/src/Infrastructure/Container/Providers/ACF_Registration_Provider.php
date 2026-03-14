<?php
/**
 * Registers ACF group registration services and acf/init hook (spec §7.3, §20.8, §59.5).
 * Wires group builder, field builder, and registrar. Registers on acf/init when ACF is available.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers ACF group registrar and wires acf/init. Depends on section_field_blueprint_service.
 */
final class ACF_Registration_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'acf_field_builder', function (): ACF_Field_Builder {
			return new ACF_Field_Builder();
		} );
		$container->register( 'acf_group_builder', function () use ( $container ): ACF_Group_Builder {
			return new ACF_Group_Builder( $container->get( 'acf_field_builder' ) );
		} );
		$container->register( 'acf_group_registrar', function () use ( $container ): ACF_Group_Registrar {
			return new ACF_Group_Registrar(
				$container->get( 'section_field_blueprint_service' ),
				$container->get( 'acf_group_builder' ),
				$container->get( 'section_template_repository' )
			);
		} );

		add_action( 'acf/init', function () use ( $container ): void {
			if ( $container->has( 'acf_group_registrar' ) ) {
				$container->get( 'acf_group_registrar' )->register_all();
			}
		}, 5 );
	}
}
