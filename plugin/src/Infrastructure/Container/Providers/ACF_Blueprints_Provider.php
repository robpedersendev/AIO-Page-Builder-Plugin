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

use AIOPageBuilder\Domain\ACF\Blueprints\Blueprint_Family_Registry;
use AIOPageBuilder\Domain\ACF\Blueprints\Blueprint_Family_Resolver;
use AIOPageBuilder\Domain\ACF\Blueprints\Preview_Family_Mapping;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers section field blueprint services and scale-aware family registry/resolver/preview mapping (large-scale-acf-lpagery-binding-contract §2.2, §5.2).
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
		$container->register( 'blueprint_family_registry', function (): Blueprint_Family_Registry {
			return new Blueprint_Family_Registry();
		} );
		$container->register( 'blueprint_family_resolver', function () use ( $container ): Blueprint_Family_Resolver {
			return new Blueprint_Family_Resolver( $container->get( 'blueprint_family_registry' ) );
		} );
		$container->register( 'preview_family_mapping', function (): Preview_Family_Mapping {
			return new Preview_Family_Mapping();
		} );
		$container->register( 'section_field_blueprint_service', function () use ( $container ): Section_Field_Blueprint_Service {
			$service = new Section_Field_Blueprint_Service(
				$container->get( 'section_template_repository' ),
				$container->get( 'section_field_blueprint_validator' ),
				$container->get( 'section_field_blueprint_normalizer' ),
				$container->get( 'blueprint_family_resolver' )
			);
			if ( $container->has( 'form_provider_registry' ) ) {
				$reg = $container->get( 'form_provider_registry' );
				if ( $reg instanceof Form_Provider_Registry ) {
					$service->set_form_provider_registry( $reg );
				}
			}
			return $service;
		} );
	}
}
