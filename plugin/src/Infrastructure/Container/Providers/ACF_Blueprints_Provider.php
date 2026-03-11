<?php
/**
 * Registers ACF blueprint domain services (spec §20.1–20.8).
 * Blueprint validator, normalizer, and retrieval service. Does not register ACF fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers section field blueprint services for validated, normalized blueprint retrieval.
 * Depends on section_template_repository (Repositories_Provider).
 */
final class ACF_Blueprints_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'section_field_blueprint_validator', function (): Section_Field_Blueprint_Validator {
			return new Section_Field_Blueprint_Validator();
		} );
		$container->register( 'section_field_blueprint_normalizer', function () use ( $container ): Section_Field_Blueprint_Normalizer {
			return new Section_Field_Blueprint_Normalizer(
				$container->get( 'section_field_blueprint_validator' )
			);
		} );
		$container->register( 'section_field_blueprint_service', function () use ( $container ): Section_Field_Blueprint_Service {
			return new Section_Field_Blueprint_Service(
				$container->get( 'section_template_repository' ),
				$container->get( 'section_field_blueprint_validator' ),
				$container->get( 'section_field_blueprint_normalizer' )
			);
		} );
	}
}
