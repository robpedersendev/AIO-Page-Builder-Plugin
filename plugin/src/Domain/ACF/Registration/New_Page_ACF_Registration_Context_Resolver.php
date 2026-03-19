<?php
/**
 * Resolves section keys for new-page admin edit when a template or composition is already chosen (acf-conditional-registration-contract §4.3).
 * When no template/composition is chosen, returns empty list so no groups are registered.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;

/**
 * New-page edit: register only sections for the chosen template/composition when available;
 * otherwise register no groups (no full registration).
 */
class New_Page_ACF_Registration_Context_Resolver {

	/** Filter name for chosen template key on new-page (admin only). Default '' = no groups. */
	public const FILTER_NEW_PAGE_TEMPLATE_KEY = 'aio_acf_registration_new_page_template_key';

	/** Filter name for chosen composition id on new-page (admin only). Default '' = no groups. */
	public const FILTER_NEW_PAGE_COMPOSITION_ID = 'aio_acf_registration_new_page_composition_id';

	/** @var Field_Group_Derivation_Service */
	private Field_Group_Derivation_Service $derivation_service;

	/** @var Group_Key_Section_Key_Resolver */
	private Group_Key_Section_Key_Resolver $group_key_resolver;

	/** @var Page_Section_Key_Cache_Service|null */
	private ?Page_Section_Key_Cache_Service $section_key_cache;

	/** @var ACF_Registration_Diagnostics_Service|null */
	private ?ACF_Registration_Diagnostics_Service $diagnostics;

	public function __construct(
		Field_Group_Derivation_Service $derivation_service,
		Group_Key_Section_Key_Resolver $group_key_resolver,
		?Page_Section_Key_Cache_Service $section_key_cache = null,
		?ACF_Registration_Diagnostics_Service $diagnostics = null
	) {
		$this->derivation_service = $derivation_service;
		$this->group_key_resolver = $group_key_resolver;
		$this->section_key_cache  = $section_key_cache;
		$this->diagnostics        = $diagnostics;
	}

	/**
	 * Returns true when the current request is new-page edit (admin post-new.php, page post type).
	 *
	 * @return bool
	 */
	public function is_new_page_edit_context(): bool {
		global $pagenow;
		if ( ! is_admin() || $pagenow !== 'post-new.php' ) {
			return false;
		}
		$post_type = isset( $_GET['post_type'] ) ? \sanitize_key( \wp_unslash( $_GET['post_type'] ) ) : '';
		if ( $post_type === '' ) {
			$post_type = get_post_type_object( 'page' ) ? 'page' : '';
		}
		return $post_type === 'page';
	}

	/**
	 * Resolves section keys for new-page edit when a template or composition is chosen. Returns null when not new-page context.
	 *
	 * @return array<int, string>|null Section keys to register; empty list when new-page but no template chosen; null when not new-page.
	 */
	public function get_section_keys_for_current_request(): ?array {
		if ( ! $this->is_new_page_edit_context() ) {
			return null;
		}
		$template_key = (string) \apply_filters( self::FILTER_NEW_PAGE_TEMPLATE_KEY, '' );
		if ( $template_key !== '' ) {
			if ( $this->section_key_cache !== null ) {
				$cached = $this->section_key_cache->get_for_template( $template_key );
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
			$result       = $this->derivation_service->derive_section_keys_from_template_for_registration( $template_key );
			$section_keys = $result->get_section_keys();
			if ( $this->section_key_cache !== null && $result->is_resolved() ) {
				$this->section_key_cache->set_for_template( $template_key, $section_keys );
			}
			return $section_keys;
		}
		$composition_id = (string) \apply_filters( self::FILTER_NEW_PAGE_COMPOSITION_ID, '' );
		if ( $composition_id !== '' ) {
			if ( $this->section_key_cache !== null ) {
				$cached = $this->section_key_cache->get_for_composition( $composition_id );
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
			$result       = $this->derivation_service->derive_section_keys_from_composition_for_registration( $composition_id );
			$section_keys = $result->get_section_keys();
			if ( $this->section_key_cache !== null && $result->is_resolved() ) {
				$this->section_key_cache->set_for_composition( $composition_id, $section_keys );
			}
			return $section_keys;
		}
		return array();
	}
}
