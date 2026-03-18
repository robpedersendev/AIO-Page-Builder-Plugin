<?php
/**
 * Registers ACF page-to-field-group assignment services (spec §20.10–20.12).
 * Derivation and assignment services. Depends on assignment map and repositories.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers field group derivation and page assignment services.
 */
final class ACF_Assignment_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'field_group_derivation_service',
			function () use ( $container ): Field_Group_Derivation_Service {
				return new Field_Group_Derivation_Service(
					$container->get( 'page_template_repository' ),
					$container->get( 'composition_repository' ),
					$container->get( 'section_template_repository' )
				);
			}
		);
		$container->register(
			'page_field_group_assignment_service',
			function () use ( $container ): Page_Field_Group_Assignment_Service {
				return new Page_Field_Group_Assignment_Service(
					$container->get( 'assignment_map_service' ),
					$container->get( 'field_group_derivation_service' )
				);
			}
		);
	}
}
