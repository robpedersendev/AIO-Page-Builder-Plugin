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
use AIOPageBuilder\Domain\ACF\Registration\ACF_Registration_Bootstrap_Controller;
use AIOPageBuilder\Domain\ACF\Registration\Admin_Post_Edit_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Existing_Page_ACF_Registration_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Group_Key_Section_Key_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\New_Page_ACF_Registration_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Registration_Diagnostics_Service;
use AIOPageBuilder\Domain\ACF\Registration\Page_Section_Key_Cache_Service;
use AIOPageBuilder\Domain\ACF\Registration\Registration_Request_Context;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers ACF group registrar, bootstrap controller, and acf/init.
 * Depends on section_field_blueprint_service. Registration runs via controller (acf-conditional-registration-contract).
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
		$container->register( 'acf_registration_request_context', function (): Registration_Request_Context {
			return new Registration_Request_Context();
		} );
		$container->register( 'acf_group_key_section_key_resolver', function (): Group_Key_Section_Key_Resolver {
			return new Group_Key_Section_Key_Resolver();
		} );
		$container->register( 'page_section_key_cache_service', function (): Page_Section_Key_Cache_Service {
			return new Page_Section_Key_Cache_Service();
		} );
		$container->register( 'acf_registration_diagnostics_service', function (): ACF_Registration_Diagnostics_Service {
			return new ACF_Registration_Diagnostics_Service();
		} );
		$container->register( 'acf_existing_page_registration_context_resolver', function () use ( $container ): Existing_Page_ACF_Registration_Context_Resolver {
			return new Existing_Page_ACF_Registration_Context_Resolver(
				$container->get( 'page_field_group_assignment_service' ),
				$container->get( 'acf_group_key_section_key_resolver' ),
				$container->get( 'page_section_key_cache_service' ),
				$container->get( 'acf_registration_diagnostics_service' )
			);
		} );
		$container->register( 'acf_new_page_registration_context_resolver', function () use ( $container ): New_Page_ACF_Registration_Context_Resolver {
			return new New_Page_ACF_Registration_Context_Resolver(
				$container->get( 'field_group_derivation_service' ),
				$container->get( 'acf_group_key_section_key_resolver' ),
				$container->get( 'page_section_key_cache_service' ),
				$container->get( 'acf_registration_diagnostics_service' )
			);
		} );
		$container->register( 'admin_post_edit_context_resolver', function (): Admin_Post_Edit_Context_Resolver {
			return new Admin_Post_Edit_Context_Resolver();
		} );
		$container->register( 'acf_registration_bootstrap_controller', function () use ( $container ): ACF_Registration_Bootstrap_Controller {
			return new ACF_Registration_Bootstrap_Controller(
				$container->get( 'acf_group_registrar' ),
				$container->get( 'acf_registration_request_context' ),
				$container->get( 'acf_group_key_section_key_resolver' ),
				$container->get( 'acf_existing_page_registration_context_resolver' ),
				$container->get( 'acf_new_page_registration_context_resolver' ),
				$container->get( 'admin_post_edit_context_resolver' ),
				$container->get( 'acf_registration_diagnostics_service' )
			);
		} );

		// acf/init priority 5: scoped registration runs before ACF builds field-group list for edit screens (see docs/qa/acf-registration-hook-timing-report.md).
		add_action( 'acf/init', function () use ( $container ): void {
			if ( $container->has( 'page_section_key_cache_service' ) ) {
				$container->get( 'page_section_key_cache_service' )->listen_for_assignment_changes();
			}
			if ( $container->has( 'acf_registration_bootstrap_controller' ) ) {
				$container->get( 'acf_registration_bootstrap_controller' )->run_registration();
			}
		}, 5 );
	}
}
