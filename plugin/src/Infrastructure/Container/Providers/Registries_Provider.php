<?php
/**
 * Registers section, page template, composition, and version snapshot registry services (spec §12, §13, §10.3, §10.8, §59.4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Duplicator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Registry_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validator;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Serializer;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Domain\Registries\QA\Template_Library_Compliance_Service;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Integrity_Validator;
use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers section, page template, composition, and version snapshot registry domain services. Callers must perform capability and nonce checks before mutating.
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
		$container->register( 'registry_deprecation_service', function () use ( $container ): Registry_Deprecation_Service {
			return new Registry_Deprecation_Service(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' )
			);
		} );
		$container->register( 'large_library_query_service', function () use ( $container ): Large_Library_Query_Service {
			return new Large_Library_Query_Service(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' )
			);
		} );
		$container->register( 'section_registry_service', function () use ( $container ): Section_Registry_Service {
			$service = new Section_Registry_Service(
				$container->get( 'section_validator' ),
				$container->get( 'section_template_repository' ),
				$container->get( 'registry_deprecation_service' )
			);
			if ( $container->has( 'section_field_blueprint_service' ) ) {
				$service->set_blueprint_service( $container->get( 'section_field_blueprint_service' ) );
			}
			$service->set_large_library_query_service( $container->get( 'large_library_query_service' ) );
			return $service;
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
			$service = new Page_Template_Registry_Service(
				$container->get( 'page_template_validator' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'registry_deprecation_service' )
			);
			$service->set_large_library_query_service( $container->get( 'large_library_query_service' ) );
			return $service;
		} );
		$container->register( 'registry_integrity_validator', function () use ( $container ): Registry_Integrity_Validator {
			return new Registry_Integrity_Validator(
				$container->get( 'section_registry_service' ),
				$container->get( 'page_template_registry_service' )
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
				$container->get( 'assignment_map_service' ),
				$container->get( 'registry_integrity_validator' )
			);
		} );
		$container->register( 'composition_duplicator', function () use ( $container ): Composition_Duplicator {
			return new Composition_Duplicator( $container->get( 'composition_registry_service' ) );
		} );
		$container->register( 'version_snapshot_service', function () use ( $container ): Version_Snapshot_Service {
			return new Version_Snapshot_Service(
				$container->get( 'section_registry_service' ),
				$container->get( 'page_template_registry_service' ),
				$container->get( 'version_snapshot_repository' ),
				$container->get( 'composition_repository' )
			);
		} );
		$container->register( 'registry_export_serializer', function () use ( $container ): Registry_Export_Serializer {
			return new Registry_Export_Serializer(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' ),
				$container->get( 'composition_repository' ),
				$container->get( 'documentation_repository' ),
				$container->get( 'version_snapshot_repository' )
			);
		} );
		$container->register( 'template_library_compliance_service', function () use ( $container ): Template_Library_Compliance_Service {
			return new Template_Library_Compliance_Service(
				$container->get( 'section_template_repository' ),
				$container->get( 'page_template_repository' )
			);
		} );
	}
}
