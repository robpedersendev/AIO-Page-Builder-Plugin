<?php
/**
 * Resolves section keys for existing-page admin edit context (acf-conditional-registration-contract §4.2).
 * Uses assignment map visible groups and group-key → section-key mapping; no full blueprint load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;

/**
 * When the current request is an existing-page edit (admin post.php, page post type),
 * returns the section keys for that page's visible groups so only those sections are registered.
 * Non-final so tests can mock it.
 */
class Existing_Page_ACF_Registration_Context_Resolver {

	/** @var Page_Field_Group_Assignment_Service */
	private Page_Field_Group_Assignment_Service $assignment_service;

	/** @var Group_Key_Section_Key_Resolver */
	private Group_Key_Section_Key_Resolver $group_key_resolver;

	/** @var Page_Section_Key_Cache_Service|null */
	private ?Page_Section_Key_Cache_Service $section_key_cache;

	/** @var ACF_Registration_Diagnostics_Service|null */
	private ?ACF_Registration_Diagnostics_Service $diagnostics;

	public function __construct(
		Page_Field_Group_Assignment_Service $assignment_service,
		Group_Key_Section_Key_Resolver $group_key_resolver,
		?Page_Section_Key_Cache_Service $section_key_cache = null,
		?ACF_Registration_Diagnostics_Service $diagnostics = null
	) {
		$this->assignment_service = $assignment_service;
		$this->group_key_resolver = $group_key_resolver;
		$this->section_key_cache  = $section_key_cache;
		$this->diagnostics        = $diagnostics;
	}

	/**
	 * Returns true if the current request appears to be editing an existing page (admin post.php, page post type).
	 *
	 * @return bool
	 */
	public function is_existing_page_edit_context(): bool {
		global $pagenow;
		if ( ! is_admin() || $pagenow !== 'post.php' ) {
			return false;
		}
		$post_id = isset( $_GET['post'] ) ? (int) \wp_unslash( $_GET['post'] ) : 0;
		if ( $post_id <= 0 ) {
			return false;
		}
		$post_type = get_post_type( $post_id );
		return $post_type === 'page';
	}

	/**
	 * Returns the post ID being edited in existing-page context, or 0.
	 *
	 * @return int
	 */
	public function get_edited_page_id(): int {
		if ( ! $this->is_existing_page_edit_context() ) {
			return 0;
		}
		return isset( $_GET['post'] ) ? (int) \wp_unslash( $_GET['post'] ) : 0;
	}

	/**
	 * Resolves section keys for the current existing-page edit. Returns null when not in that context.
	 *
	 * @return array<int, string>|null Section keys to register, or null if not existing-page edit context.
	 */
	public function get_section_keys_for_current_request(): ?array {
		if ( ! $this->is_existing_page_edit_context() ) {
			return null;
		}
		$page_id = $this->get_edited_page_id();
		if ( $page_id <= 0 ) {
			return array();
		}
		if ( $this->section_key_cache !== null ) {
			$cached = $this->section_key_cache->get_for_page( $page_id );
			if ( $cached !== null ) {
				if ( $this->diagnostics !== null ) {
					$this->diagnostics->set_request_cache_used( true );
				}
				return $cached;
			}
		}
		if ( $this->diagnostics !== null ) {
			$this->diagnostics->set_request_cache_used( false );
		}
		$group_keys   = $this->assignment_service->get_visible_groups_for_page( $page_id );
		$section_keys = $this->group_key_resolver->group_keys_to_section_keys( $group_keys );
		if ( $this->section_key_cache !== null ) {
			$this->section_key_cache->set_for_page( $page_id, $section_keys );
		}
		return $section_keys;
	}
}
