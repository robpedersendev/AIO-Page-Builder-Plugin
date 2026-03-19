<?php
/**
 * Canonical resolver for admin post-edit context (Prompt 293, acf-conditional-registration-contract).
 * Determines whether the current request is existing-page edit, new-page edit, non-page admin, or unsupported.
 * Used by ACF_Registration_Bootstrap_Controller to choose the downstream section-key resolver.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves current admin request into a typed context. Conditions documented in acf-page-visibility-contract.
 * Unsupported contexts fail safe toward no full registration.
 */
class Admin_Post_Edit_Context_Resolver {

	/**
	 * Resolves the current request into an admin post-edit context.
	 * Call only when is_admin(); for non-admin use Registration_Request_Context::should_skip_registration().
	 *
	 * Conditions:
	 * - post.php + valid post ID + post type 'page' → EXISTING_PAGE_EDIT (with page_id).
	 * - post.php + invalid/missing post or non-page type → UNSUPPORTED_ADMIN (fail safe).
	 * - post-new.php + post_type=page → NEW_PAGE_EDIT.
	 * - post-new.php + other post type → NON_PAGE_ADMIN.
	 * - Any other admin screen → NON_PAGE_ADMIN.
	 *
	 * @return Admin_Post_Edit_Context_Result
	 */
	public function resolve(): Admin_Post_Edit_Context_Result {
		global $pagenow;

		if ( ! is_admin() ) {
			return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NO_REGISTRATION_REQUIRED, 0 );
		}

		// Prompt 305: third-party plugins can alter or unset $pagenow; fail safe to non-page admin.
		if ( ! isset( $pagenow ) || ! \is_string( $pagenow ) || $pagenow === '' ) {
			return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 );
		}

		if ( $pagenow === 'post.php' ) {
			// Secondary admin request types: no scoped registration (acf-secondary-admin-request-matrix).
			if ( $this->is_secondary_edit_request() ) {
				return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::UNSUPPORTED_ADMIN, 0 );
			}
			$post_id = 0;
			if ( isset( $_GET['post'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to int after unslash; used as post ID.
				$post_id = (int) \wp_unslash( $_GET['post'] );
			}
			if ( $post_id <= 0 ) {
				return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::UNSUPPORTED_ADMIN, 0 );
			}
			$post_type = get_post_type( $post_id );
			// Prompt 305: get_post_type can be false if post missing or third-party filter; fail safe.
			if ( $post_type !== 'page' ) {
				return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::UNSUPPORTED_ADMIN, 0 );
			}
			return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::EXISTING_PAGE_EDIT, $post_id );
		}

		if ( $pagenow === 'post-new.php' ) {
			$post_type = isset( $_GET['post_type'] ) ? \sanitize_key( \wp_unslash( $_GET['post_type'] ) ) : '';
			if ( $post_type === '' ) {
				$post_type = get_post_type_object( 'page' ) ? 'page' : '';
			}
			if ( $post_type === 'page' ) {
				return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NEW_PAGE_EDIT, 0 );
			}
			return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 );
		}

		return new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 );
	}

	/**
	 * Whether the current request is a secondary edit type (autosave, heartbeat, quick-edit, revision) that must not get scoped registration (Prompt 299).
	 *
	 * @return bool
	 */
	private function is_secondary_edit_request(): bool {
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			$action = isset( $_REQUEST['action'] ) ? \sanitize_key( \wp_unslash( $_REQUEST['action'] ) ) : '';
			$no_reg = array( 'autosave', 'heartbeat', 'inline-save', 'wp-block-editor-autosave' );
			if ( $action !== '' && in_array( $action, $no_reg, true ) ) {
				return true;
			}
		}
		$post_id = 0;
		if ( isset( $_GET['post'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to int after unslash; used as post ID.
			$post_id = (int) \wp_unslash( $_GET['post'] );
		}
		if ( $post_id > 0 && get_post_type( $post_id ) === 'revision' ) {
			return true;
		}
		return false;
	}
}
