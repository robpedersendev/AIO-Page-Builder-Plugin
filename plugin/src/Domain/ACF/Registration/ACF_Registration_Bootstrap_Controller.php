<?php
/**
 * Central entrypoint for ACF field-group registration at bootstrap (acf/init).
 * Decouples generic request bootstrap from unconditional full registration so
 * context-aware registration can be applied (acf-conditional-registration-contract).
 *
 * Hook timing (Prompt 294): Run from acf/init priority 5 so groups are registered
 * before ACF builds its field-group list for the edit screen. Context (admin, pagenow,
 * post id) and assignment map are available at that point. If context or section keys
 * cannot be resolved, register zero groups; do not fall back to full registration.
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

	/** @var Admin_Post_Edit_Context_Resolver */
	private Admin_Post_Edit_Context_Resolver $admin_post_edit_context_resolver;

	/** @var ACF_Registration_Diagnostics_Service|null */
	private ?ACF_Registration_Diagnostics_Service $diagnostics;

	public function __construct(
		ACF_Group_Registrar_Interface $group_registrar,
		Registration_Request_Context $request_context,
		Group_Key_Section_Key_Resolver $group_key_resolver,
		Existing_Page_ACF_Registration_Context_Resolver $existing_page_resolver,
		New_Page_ACF_Registration_Context_Resolver $new_page_resolver,
		Admin_Post_Edit_Context_Resolver $admin_post_edit_context_resolver,
		?ACF_Registration_Diagnostics_Service $diagnostics = null
	) {
		$this->group_registrar                 = $group_registrar;
		$this->request_context                 = $request_context;
		$this->group_key_resolver              = $group_key_resolver;
		$this->existing_page_resolver          = $existing_page_resolver;
		$this->new_page_resolver               = $new_page_resolver;
		$this->admin_post_edit_context_resolver = $admin_post_edit_context_resolver;
		$this->diagnostics                     = $diagnostics;
	}

	/**
	 * Runs ACF registration for the current request. Called from acf/init.
	 * Uses Admin_Post_Edit_Context_Resolver as the canonical context; front-end skips; unsupported contexts fail safe to no full registration.
	 *
	 * @return int Number of groups registered (0 if skipped or ACF unavailable).
	 */
	public function run_registration(): int {
		// Sequencing: run_registration() is called from acf/init (priority 5). Do not call register_all() here.
		if ( $this->request_context->should_skip_registration() ) {
			if ( $this->diagnostics !== null ) {
				$mode = $this->request_context->is_scripted_context()
					? ACF_Registration_Diagnostics_Service::MODE_SCRIPTED_SKIP
					: ACF_Registration_Diagnostics_Service::MODE_FRONT_END_SKIP;
				$this->diagnostics->record_registration( $mode, 0, false, false );
			}
			return 0;
		}

		$admin_context = $this->admin_post_edit_context_resolver->resolve();

		if ( $admin_context->is_existing_page_edit() ) {
			$section_keys = $this->existing_page_resolver->get_section_keys_for_current_request();
			if ( $section_keys !== null ) {
				$count = $this->group_registrar->register_sections( $section_keys );
				if ( $this->diagnostics !== null ) {
					$this->diagnostics->record_registration(
						ACF_Registration_Diagnostics_Service::MODE_EXISTING_PAGE,
						count( $section_keys ),
						$this->diagnostics->get_request_cache_used(),
						false
					);
				}
				return $count;
			}
		}

		if ( $admin_context->is_new_page_edit() ) {
			$section_keys = $this->new_page_resolver->get_section_keys_for_current_request();
			if ( $section_keys !== null ) {
				$count = $this->group_registrar->register_sections( $section_keys );
				if ( $this->diagnostics !== null ) {
					$this->diagnostics->record_registration(
						ACF_Registration_Diagnostics_Service::MODE_NEW_PAGE,
						count( $section_keys ),
						$this->diagnostics->get_request_cache_used(),
						false
					);
				}
				return $count;
			}
		}

		// Non-page admin or unsupported: do not trigger full registration (acf-admin-context-registration-matrix).
		if ( $this->diagnostics !== null ) {
			$this->diagnostics->record_registration(
				ACF_Registration_Diagnostics_Service::MODE_NON_PAGE_ADMIN,
				0,
				false,
				false
			);
		}
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
	 * Do not call from generic request bootstrap or acf/init. See acf-registration-exception-matrix.md.
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
