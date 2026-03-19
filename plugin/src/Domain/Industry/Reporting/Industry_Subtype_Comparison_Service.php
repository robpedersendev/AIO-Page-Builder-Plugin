<?php
/**
 * Builds read-only comparison data: parent-industry vs subtype bundles and recommendation highlights (Prompt 442).
 * Used by Industry_Subtype_Comparison_Screen. No mutation; safe fallback when subtype invalid or missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;

/**
 * Returns parent vs subtype bundle lists and recommendation top keys for comparison UI.
 */
final class Industry_Subtype_Comparison_Service {

	private const PREVIEW_TEMPLATE_CAP = 50;
	private const PREVIEW_TOP_N        = 10;

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	/** @var Industry_Page_Template_Recommendation_Resolver|null */
	private $page_resolver;

	/** @var Industry_Section_Recommendation_Resolver|null */
	private $section_resolver;

	/** @var Page_Template_Repository_Interface|null */
	private $page_repo;

	/** @var callable(): array<int, array<string, mixed>>|null */
	private $section_list_provider;

	public function __construct(
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null,
		?Industry_Page_Template_Recommendation_Resolver $page_resolver = null,
		?Industry_Section_Recommendation_Resolver $section_resolver = null,
		?Page_Template_Repository_Interface $page_repo = null,
		?callable $section_list_provider = null
	) {
		$this->profile_repo            = $profile_repo;
		$this->pack_registry           = $pack_registry;
		$this->starter_bundle_registry = $starter_bundle_registry;
		$this->subtype_registry        = $subtype_registry;
		$this->page_resolver           = $page_resolver;
		$this->section_resolver        = $section_resolver;
		$this->page_repo               = $page_repo;
		$this->section_list_provider   = $section_list_provider;
	}

	/**
	 * Returns comparison data for parent industry and optional subtype. Read-only; safe when subtype missing/invalid.
	 *
	 * @param string $industry_key Primary industry key.
	 * @param string $subtype_key  Optional subtype key; empty = parent-only (subtype columns empty).
	 * @return array{
	 *   primary_industry_key: string,
	 *   subtype_key: string,
	 *   subtype_label: string,
	 *   parent_bundles: array<int, array{bundle_key: string, label: string}>,
	 *   subtype_bundles: array<int, array{bundle_key: string, label: string}>,
	 *   parent_top_template_keys: array<int, string>,
	 *   parent_top_section_keys: array<int, string>,
	 *   subtype_top_template_keys: array<int, string>,
	 *   subtype_top_section_keys: array<int, string>,
	 *   pack_found: bool,
	 *   has_subtype: bool
	 * }
	 */
	public function get_comparison( string $industry_key, string $subtype_key = '' ): array {
		$industry_key  = \trim( $industry_key );
		$subtype_key   = \trim( $subtype_key );
		$empty_bundles = array();
		$empty_keys    = array();
		$subtype_label = '';
		$has_subtype   = false;
		if ( $subtype_key !== '' && $this->subtype_registry !== null ) {
			$def = $this->subtype_registry->get( $subtype_key );
			if ( $def !== null ) {
				$parent = isset( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && \is_string( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					? \trim( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] )
					: '';
				$status = isset( $def[ Industry_Subtype_Registry::FIELD_STATUS ] ) && \is_string( $def[ Industry_Subtype_Registry::FIELD_STATUS ] )
					? $def[ Industry_Subtype_Registry::FIELD_STATUS ]
					: '';
				if ( $parent === $industry_key && $status === Industry_Subtype_Registry::STATUS_ACTIVE ) {
					$has_subtype   = true;
					$subtype_label = isset( $def[ Industry_Subtype_Registry::FIELD_LABEL ] ) && \is_string( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
						? \trim( $def[ Industry_Subtype_Registry::FIELD_LABEL ] )
						: \ucfirst( \str_replace( array( '_', '-' ), ' ', $subtype_key ) );
				}
			}
		}

		$parent_bundles  = $this->bundle_list( $industry_key, '' );
		$subtype_bundles = $has_subtype ? $this->bundle_list( $industry_key, $subtype_key ) : $empty_bundles;

		$profile_parent  = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => $industry_key,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => '',
		);
		$profile_subtype = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => $industry_key,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => $subtype_key,
		);
		$primary_pack    = $this->pack_registry !== null ? $this->pack_registry->get( $industry_key ) : null;

		$parent_templates  = $empty_keys;
		$parent_sections   = $empty_keys;
		$subtype_templates = $empty_keys;
		$subtype_sections  = $empty_keys;

		if ( $this->page_resolver !== null && $this->page_repo !== null && $primary_pack !== null ) {
			$templates        = $this->page_repo->list_all_definitions( self::PREVIEW_TEMPLATE_CAP, 0 );
			$res_parent       = $this->page_resolver->resolve( $profile_parent, $primary_pack, $templates, array() );
			$parent_templates = \array_slice( \array_values( $res_parent->get_ranked_keys() ), 0, self::PREVIEW_TOP_N );
			if ( $has_subtype ) {
				$res_subtype       = $this->page_resolver->resolve( $profile_subtype, $primary_pack, $templates, array() );
				$subtype_templates = \array_slice( \array_values( $res_subtype->get_ranked_keys() ), 0, self::PREVIEW_TOP_N );
			}
		}
		if ( $this->section_resolver !== null && $this->section_list_provider !== null && $primary_pack !== null ) {
			$sections        = ( $this->section_list_provider )();
			$res_parent      = $this->section_resolver->resolve( $profile_parent, $primary_pack, $sections, array() );
			$parent_sections = \array_slice( \array_values( $res_parent->get_ranked_keys() ), 0, self::PREVIEW_TOP_N );
			if ( $has_subtype ) {
				$res_subtype      = $this->section_resolver->resolve( $profile_subtype, $primary_pack, $sections, array() );
				$subtype_sections = \array_slice( \array_values( $res_subtype->get_ranked_keys() ), 0, self::PREVIEW_TOP_N );
			}
		}

		return array(
			'primary_industry_key'      => $industry_key,
			'subtype_key'               => $subtype_key,
			'subtype_label'             => $subtype_label,
			'parent_bundles'            => $parent_bundles,
			'subtype_bundles'           => $subtype_bundles,
			'parent_top_template_keys'  => $parent_templates,
			'parent_top_section_keys'   => $parent_sections,
			'subtype_top_template_keys' => $subtype_templates,
			'subtype_top_section_keys'  => $subtype_sections,
			'pack_found'                => $primary_pack !== null,
			'has_subtype'               => $has_subtype,
		);
	}

	/**
	 * @return array<int, array{bundle_key: string, label: string}>
	 */
	private function bundle_list( string $industry_key, string $subtype_key ): array {
		if ( $this->starter_bundle_registry === null ) {
			return array();
		}
		$bundles = $this->starter_bundle_registry->get_for_industry( $industry_key, $subtype_key );
		$out     = array();
		foreach ( $bundles as $bundle ) {
			$key   = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
				? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
				: '';
			$label = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ) && \is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] )
				? \trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] )
				: $key;
			if ( $key !== '' ) {
				$out[] = array(
					'bundle_key' => $key,
					'label'      => $label,
				);
			}
		}
		return $out;
	}
}
