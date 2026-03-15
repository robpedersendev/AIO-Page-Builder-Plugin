<?php
/**
 * Typed result for admin post-edit context resolution (Prompt 293, acf-conditional-registration-contract).
 * Consumed by bootstrap controller to decide which registration path to use.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of resolving the current admin request for ACF registration.
 */
final class Admin_Post_Edit_Context_Result {

	/** No registration (e.g. front-end); not used when resolver runs in admin. */
	public const NO_REGISTRATION_REQUIRED = 'no_registration_required';

	/** Admin: editing an existing page (post.php, post type page). */
	public const EXISTING_PAGE_EDIT = 'existing_page_edit';

	/** Admin: creating a new page (post-new.php, post type page). */
	public const NEW_PAGE_EDIT = 'new_page_edit';

	/** Admin: non-page screen (dashboard, settings, template directory, etc.). */
	public const NON_PAGE_ADMIN = 'non_page_admin';

	/** Admin: post-edit screen but unsupported (e.g. post.php for non-page post type). Fail safe to no full registration. */
	public const UNSUPPORTED_ADMIN = 'unsupported_admin';

	/** @var string One of the constants. */
	private string $context_type;

	/** @var int When EXISTING_PAGE_EDIT, the page ID being edited; otherwise 0. */
	private int $page_id;

	public function __construct( string $context_type, int $page_id = 0 ) {
		$this->context_type = $context_type;
		$this->page_id      = $page_id;
	}

	public function get_context_type(): string {
		return $this->context_type;
	}

	/**
	 * When context is EXISTING_PAGE_EDIT, returns the page ID; otherwise 0.
	 *
	 * @return int
	 */
	public function get_page_id(): int {
		return $this->page_id;
	}

	public function is_existing_page_edit(): bool {
		return $this->context_type === self::EXISTING_PAGE_EDIT;
	}

	public function is_new_page_edit(): bool {
		return $this->context_type === self::NEW_PAGE_EDIT;
	}

	public function is_non_page_admin(): bool {
		return $this->context_type === self::NON_PAGE_ADMIN;
	}

	public function is_unsupported_admin(): bool {
		return $this->context_type === self::UNSUPPORTED_ADMIN;
	}

	/** Whether scoped registration may run (existing- or new-page edit). */
	public function is_scoped_registration_context(): bool {
		return $this->is_existing_page_edit() || $this->is_new_page_edit();
	}
}
