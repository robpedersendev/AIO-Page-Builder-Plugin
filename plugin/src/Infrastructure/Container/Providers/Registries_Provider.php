<?php
/**
 * Registers section, page template, and composition registry services (spec §12, §13, §10.3, §59.4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Duplicator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Registry_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers section, page template, and composition registry domain services. Callers must perform capability and nonce checks before mutating.
 */
final class Registries_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'section_definition_normalizer', function (): Section_Definition_Normalizer {
			return new Section_Definition_Normalizer();
		} );
		$container->register( 'section_validator', function () use ( $container ): Section_Validator {
			return new Section_Validator(
				$container->get( 'section_definition_normalizer' ),
				$container->get( 'section_template_repository' )
			);
		} );
		$container->register( 'section_registry_service', function () use ( $container ): Section_Registry_Service {
			return new Section_Registry_Service(
				$container->get( 'section_validator' ),
				$container->get( 'section_template_repository' )
			);
		} );
		$container->register( 'page_template_normalizer', function (): Page_Template_Normalizer {
			return new Page_Template_Normalizer();
		} );
		$container->register( 'page_template_validator', function () use ( $container ): Page_Template_Validator {
			return new Page_Template_Validator(
				$container->get( 'page_template_normalizer' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'section_registry_service' )
			);
		} );
		$container->register( 'page_template_registry_service', function () use ( $container ): Page_Template_Registry_Service {
			return new Page_Template_Registry_Service(
				$container->get( 'page_template_validator' ),
				$container->get( 'page_template_repository' )
			);
		} );
		$container->register( 'composition_validator', function () use ( $container ): Composition_Validator {
			return new Composition_Validator(
				$container->get( 'section_registry_service' ),
				$container->get( 'page_template_registry_service' )
			);
		} );
		$container->register( 'composition_registry_service', function () use ( $container ): Composition_Registry_Service {
			return new Composition_Registry_Service(
				$container->get( 'composition_validator' ),
				$container->get( 'composition_repository' ),
				$container->get( 'assignment_map_service' )
			);
		} );
		$container->register( 'composition_duplicator', function () use ( $container ): Composition_Duplicator {
			return new Composition_Duplicator( $container->get( 'composition_registry_service' ) );
		} );
	}
}
