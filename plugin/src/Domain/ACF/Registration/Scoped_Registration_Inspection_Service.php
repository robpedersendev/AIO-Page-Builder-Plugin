<?php
/**
 * Internal inspection utility for scoped registration resolution (Prompt 308).
 * Reports which section keys and group keys would register for a given page ID or template/composition
 * without loading the full editor or running ACF registration. Reuses production resolution logic.
 * Admin/support only; no sensitive data.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;

/**
 * Inspects scoped-registration resolution for a given page, template, or composition context.
 * Does not perform ACF registration; returns structured result for support/diagnostics.
 */
final class Scoped_Registration_Inspection_Service {

	/** @var Page_Field_Group_Assignment_Service */
	private Page_Field_Group_Assignment_Service $assignment_service;

	/** @var Group_Key_Section_Key_Resolver */
	private Group_Key_Section_Key_Resolver $group_key_resolver;

	/** @var Field_Group_Derivation_Service */
	private Field_Group_Derivation_Service $derivation_service;

	/** @var Page_Section_Key_Cache_Service|null */
	private ?Page_Section_Key_Cache_Service $cache;

	public function __construct(
		Page_Field_Group_Assignment_Service $assignment_service,
		Group_Key_Section_Key_Resolver $group_key_resolver,
		Field_Group_Derivation_Service $derivation_service,
		?Page_Section_Key_Cache_Service $cache = null
	) {
		$this->assignment_service = $assignment_service;
		$this->group_key_resolver = $group_key_resolver;
		$this->derivation_service = $derivation_service;
		$this->cache              = $cache;
	}

	/**
	 * Inspects resolution for an existing page. Uses same logic as Existing_Page_ACF_Registration_Context_Resolver.
	 *
	 * @param int $page_id Page post ID.
	 * @return array{mode: string, section_keys: array<int, string>, group_keys: array<int, string>, cache_used: bool, resolved: bool}
	 */
	public function inspect_for_page( int $page_id ): array {
		if ( $page_id <= 0 ) {
			return array(
				'mode'         => 'existing_page',
				'section_keys' => array(),
				'group_keys'   => array(),
				'cache_used'   => false,
				'resolved'     => false,
			);
		}
		$cache_used = false;
		if ( $this->cache !== null ) {
			$cached = $this->cache->get_for_page( $page_id );
			if ( $cached !== null ) {
				$cache_used   = true;
				$section_keys = $cached;
				$group_keys   = $this->section_keys_to_group_keys( $section_keys );
				return array(
					'mode'         => 'existing_page',
					'section_keys' => $section_keys,
					'group_keys'   => $group_keys,
					'cache_used'   => true,
					'resolved'     => true,
				);
			}
		}
		$group_keys   = $this->assignment_service->get_visible_groups_for_page( $page_id );
		$section_keys = $this->group_key_resolver->group_keys_to_section_keys( $group_keys );
		return array(
			'mode'         => 'existing_page',
			'section_keys' => $section_keys,
			'group_keys'   => $group_keys,
			'cache_used'   => $cache_used,
			'resolved'     => true,
		);
	}

	/**
	 * Inspects resolution for a new-page template context. Uses same derivation as New_Page_ACF_Registration_Context_Resolver.
	 *
	 * @param string $template_key Page template internal_key.
	 * @return array{mode: string, section_keys: array<int, string>, group_keys: array<int, string>, cache_used: bool, resolved: bool}
	 */
	public function inspect_for_new_page_template( string $template_key ): array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return array(
				'mode'         => 'new_page_template',
				'section_keys' => array(),
				'group_keys'   => array(),
				'cache_used'   => false,
				'resolved'     => false,
			);
		}
		$cache_used = false;
		if ( $this->cache !== null ) {
			$cached = $this->cache->get_for_template( $template_key );
			if ( $cached !== null ) {
				$cache_used = true;
				$group_keys = $this->section_keys_to_group_keys( $cached );
				return array(
					'mode'         => 'new_page_template',
					'section_keys' => $cached,
					'group_keys'   => $group_keys,
					'cache_used'   => true,
					'resolved'     => true,
				);
			}
		}
		$result       = $this->derivation_service->derive_section_keys_from_template_for_registration( $template_key );
		$section_keys = $result->get_section_keys();
		$group_keys   = $this->section_keys_to_group_keys( $section_keys );
		return array(
			'mode'         => 'new_page_template',
			'section_keys' => $section_keys,
			'group_keys'   => $group_keys,
			'cache_used'   => $cache_used,
			'resolved'     => $result->is_resolved(),
		);
	}

	/**
	 * Inspects resolution for a new-page composition context.
	 *
	 * @param string $composition_id Composition internal key.
	 * @return array{mode: string, section_keys: array<int, string>, group_keys: array<int, string>, cache_used: bool, resolved: bool}
	 */
	public function inspect_for_new_page_composition( string $composition_id ): array {
		$composition_id = \sanitize_key( $composition_id );
		if ( $composition_id === '' ) {
			return array(
				'mode'         => 'new_page_composition',
				'section_keys' => array(),
				'group_keys'   => array(),
				'cache_used'   => false,
				'resolved'     => false,
			);
		}
		$cache_used = false;
		if ( $this->cache !== null ) {
			$cached = $this->cache->get_for_composition( $composition_id );
			if ( $cached !== null ) {
				$cache_used = true;
				$group_keys = $this->section_keys_to_group_keys( $cached );
				return array(
					'mode'         => 'new_page_composition',
					'section_keys' => $cached,
					'group_keys'   => $group_keys,
					'cache_used'   => true,
					'resolved'     => true,
				);
			}
		}
		$result       = $this->derivation_service->derive_section_keys_from_composition_for_registration( $composition_id );
		$section_keys = $result->get_section_keys();
		$group_keys   = $this->section_keys_to_group_keys( $section_keys );
		return array(
			'mode'         => 'new_page_composition',
			'section_keys' => $section_keys,
			'group_keys'   => $group_keys,
			'cache_used'   => $cache_used,
			'resolved'     => $result->is_resolved(),
		);
	}

	/**
	 * Maps section keys to plugin group keys (group_aio_*).
	 *
	 * @param array<int, string> $section_keys
	 * @return array<int, string>
	 */
	private function section_keys_to_group_keys( array $section_keys ): array {
		$out = array();
		foreach ( $section_keys as $sk ) {
			if ( is_string( $sk ) && $sk !== '' ) {
				$out[] = Field_Key_Generator::group_key( $sk );
			}
		}
		return array_values( array_unique( $out ) );
	}
}
