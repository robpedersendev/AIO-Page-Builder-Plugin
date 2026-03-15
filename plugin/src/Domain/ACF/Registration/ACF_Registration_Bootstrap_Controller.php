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

	public function __construct(
		ACF_Group_Registrar_Interface $group_registrar,
		Registration_Request_Context $request_context
	) {
		$this->group_registrar = $group_registrar;
		$this->request_context = $request_context;
	}

	/**
	 * Runs ACF registration for the current request. Called from acf/init.
	 * Skips registration on front-end; runs full registration in admin (later: page-scoped).
	 *
	 * @return int Number of groups registered (0 if skipped or ACF unavailable).
	 */
	public function run_registration(): int {
		if ( $this->request_context->should_skip_registration() ) {
			return 0;
		}
		return $this->group_registrar->register_all();
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
