<?php
/**
 * Central entrypoint for ACF field-group registration at bootstrap (acf/init).
 * Decouples generic request bootstrap from unconditional full registration so
 * context-aware registration can be applied in later prompts (acf-conditional-registration-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Single central ACF registration bootstrap. Generic acf/init must call this
 * instead of calling register_all() directly. Extension point for context-aware
 * registration (front-end skip, admin page-scoped) in later prompts.
 */
final class ACF_Registration_Bootstrap_Controller {

	/** @var ACF_Group_Registrar_Interface */
	private ACF_Group_Registrar_Interface $group_registrar;

	/** @var Registration_Request_Context */
	private Registration_Request_Context $request_context;

	/** @var Group_Key_Section_Key_Resolver */
	private Group_Key_Section_Key_Resolver $group_key_resolver;

	/** @var Existing_Page_ACF_Registration_Context_Resolver */
	private Existing_Page_ACF_Registration_Context_Resolver $existing_page_resolver;

	/** @var New_Page_ACF_Registration_Context_Resolver */
	private New_Page_ACF_Registration_Context_Resolver $new_page_resolver;

	public function __construct(
		ACF_Group_Registrar_Interface $group_registrar,
		Registration_Request_Context $request_context,
		Group_Key_Section_Key_Resolver $group_key_resolver,
		Existing_Page_ACF_Registration_Context_Resolver $existing_page_resolver,
		New_Page_ACF_Registration_Context_Resolver $new_page_resolver
	) {
		$this->group_registrar     = $group_registrar;
		$this->request_context     = $request_context;
		$this->group_key_resolver  = $group_key_resolver;
		$this->existing_page_resolver = $existing_page_resolver;
		$this->new_page_resolver   = $new_page_resolver;
	}

	/**
	 * Runs ACF registration for the current request. Called from acf/init.
	 * Front-end: skip. Existing-page: register only that page's sections. New-page: register template/composition sections or none. Non-page admin: register 0 (acf-admin-context-registration-matrix).
	 *
	 * @return int Number of groups registered (0 if skipped or ACF unavailable).
	 */
	public function run_registration(): int {
		if ( $this->request_context->should_skip_registration() ) {
			return 0;
		}
		$section_keys = $this->existing_page_resolver->get_section_keys_for_current_request();
		if ( $section_keys !== null ) {
			return $this->group_registrar->register_sections( $section_keys );
		}
		$section_keys = $this->new_page_resolver->get_section_keys_for_current_request();
		if ( $section_keys !== null ) {
			return $this->group_registrar->register_sections( $section_keys );
		}
		// Non-page admin: do not trigger full registration (see acf-admin-context-registration-matrix).
		return 0;
	}

	/**
	 * Exposes the group-key → section-key resolver for admin registration context resolvers.
	 *
	 * @return Group_Key_Section_Key_Resolver
	 */
	public function get_group_key_section_key_resolver(): Group_Key_Section_Key_Resolver {
		return $this->group_key_resolver;
	}

	/**
	 * Registers all section-owned groups. For explicit tooling only (e.g. debug export, regeneration).
	 * Do not call from generic request bootstrap.
	 *
	 * @return int Number of groups registered.
	 */
	public function run_full_registration(): int {
		return $this->group_registrar->register_all();
	}

	/**
	 * Exposes the registrar for contexts that need section-scoped registration (e.g. admin page edit).
	 *
	 * @return ACF_Group_Registrar_Interface
	 */
	public function get_registrar(): ACF_Group_Registrar_Interface {
		return $this->group_registrar;
	}
}
