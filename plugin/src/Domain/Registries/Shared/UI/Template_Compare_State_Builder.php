<?php
/**
 * Builds compare-workspace state for section and page templates (Prompt 180, spec §49.6, §49.7).
 * Produces template_compare_row, section_compare_matrix, page_compare_matrix from registry-authoritative data. Observational only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Definition_Provider;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Definition_Provider_For_Preview;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Builds state for the template compare workspace: compare list, matrix of template_compare_row, base URLs.
 */
final class Template_Compare_State_Builder {

	/** Max templates in compare list to keep workspace performant. */
	public const MAX_COMPARE_ITEMS = 10;

	/** @var Section_Definition_Provider_For_Preview */
	private Section_Definition_Provider_For_Preview $section_provider;

	/** @var Page_Template_Definition_Provider */
	private Page_Template_Definition_Provider $page_provider;

	/** @var Preview_Cache_Service|null */
	private ?Preview_Cache_Service $preview_cache;

	public function __construct(
		Section_Definition_Provider_For_Preview $section_provider,
		Page_Template_Definition_Provider $page_provider,
		?Preview_Cache_Service $preview_cache = null
	) {
		$this->section_provider = $section_provider;
		$this->page_provider    = $page_provider;
		$this->preview_cache    = $preview_cache;
	}

	/**
	 * Builds full compare state for the given type and list of keys.
	 *
	 * @param string       $type             'section' or 'page'.
	 * @param list<string> $compare_list_keys Ordered list of template keys to compare.
	 * @return array<string, mixed> State: type, compare_list_keys, section_compare_matrix|page_compare_matrix, template_compare_rows (alias), base_url_sections, base_url_pages, compare_screen_url, empty_message.
	 */
	public function build_state( string $type, array $compare_list_keys ): array {
		$type              = $type === 'page' ? 'page' : 'section';
		$compare_list_keys = array_slice( array_values( array_unique( array_map( 'sanitize_key', $compare_list_keys ) ) ), 0, self::MAX_COMPARE_ITEMS );
		$compare_list_keys = array_values( array_filter( $compare_list_keys, fn( string $k ): bool => $k !== '' ) );

		$base_url_sections  = \admin_url( 'admin.php?page=aio-page-builder-section-templates' );
		$base_url_pages     = \admin_url( 'admin.php?page=aio-page-builder-page-templates' );
		$compare_screen_url = \admin_url( 'admin.php?page=aio-page-builder-template-compare' );

		if ( count( $compare_list_keys ) === 0 ) {
			return array(
				'type'                   => $type,
				'compare_list_keys'      => array(),
				'section_compare_matrix' => array(),
				'page_compare_matrix'    => array(),
				'template_compare_rows'  => array(),
				'base_url_sections'      => $base_url_sections,
				'base_url_pages'         => $base_url_pages,
				'compare_screen_url'     => $compare_screen_url,
				'empty_message'          => $type === 'section'
					? __( 'Add section templates from the Section Templates directory or detail screen to compare them side by side.', 'aio-page-builder' )
					: __( 'Add page templates from the Page Templates directory or detail screen to compare them side by side.', 'aio-page-builder' ),
			);
		}

		$rows = array();
		if ( $type === 'section' ) {
			foreach ( $compare_list_keys as $key ) {
				$def    = $this->section_provider->get_definition_by_key( $key );
				$rows[] = $this->build_section_compare_row( $key, \is_array( $def ) ? $def : array() );
			}
			return array(
				'type'                   => $type,
				'compare_list_keys'      => $compare_list_keys,
				'section_compare_matrix' => $rows,
				'page_compare_matrix'    => array(),
				'template_compare_rows'  => $rows,
				'base_url_sections'      => $base_url_sections,
				'base_url_pages'         => $base_url_pages,
				'compare_screen_url'     => $compare_screen_url,
				'empty_message'          => '',
			);
		}

		foreach ( $compare_list_keys as $key ) {
			$def    = $this->page_provider->get_definition_by_key( $key );
			$rows[] = $this->build_page_compare_row( $key, \is_array( $def ) ? $def : array() );
		}
		return array(
			'type'                   => $type,
			'compare_list_keys'      => $compare_list_keys,
			'section_compare_matrix' => array(),
			'page_compare_matrix'    => $rows,
			'template_compare_rows'  => $rows,
			'base_url_sections'      => $base_url_sections,
			'base_url_pages'         => $base_url_pages,
			'compare_screen_url'     => $compare_screen_url,
			'empty_message'          => '',
		);
	}

	/**
	 * Builds a single template_compare_row for a section template.
	 *
	 * @param string               $key
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed> template_compare_row
	 */
	private function build_section_compare_row( string $key, array $definition ): array {
		$name         = (string) ( $definition[ Section_Schema::FIELD_NAME ] ?? $definition['internal_key'] ?? $key );
		$purpose      = (string) ( $definition['section_purpose_family'] ?? $definition[ Section_Schema::FIELD_PURPOSE_SUMMARY ] ?? $definition[ Section_Schema::FIELD_CATEGORY ] ?? '' );
		$cta          = (string) ( $definition['cta_classification'] ?? '' );
		$compat       = $definition[ Section_Schema::FIELD_COMPATIBILITY ] ?? $definition['compatibility'] ?? array();
		$compat_notes = \is_array( $compat ) ? $compat : array();
		$helper_ref   = (string) ( $definition[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
		$detail_url   = \add_query_arg(
			array(
				'page'    => 'aio-page-builder-section-template-detail',
				'section' => $key,
			),
			\admin_url( 'admin.php' )
		);

		$preview_excerpt = __( 'Preview on detail', 'aio-page-builder' );
		if ( $this->preview_cache !== null ) {
			$context   = Synthetic_Preview_Context::for_section(
				$key,
				$purpose !== '' ? $purpose : 'other',
				'default',
				false,
				Synthetic_Preview_Context::ANIMATION_TIER_NONE
			);
			$cache_key = $this->preview_cache->get_cache_key( $context, $definition );
			$cached    = $this->preview_cache->get( $cache_key );
			if ( $cached !== null ) {
				$text            = \wp_strip_all_tags( $cached->get_html() );
				$preview_excerpt = $text !== '' ? \wp_trim_words( $text, 20 ) : $preview_excerpt;
			}
		}

		return array(
			'template_key'          => $key,
			'name'                  => $name,
			'purpose_family'        => $purpose,
			'cta_direction'         => $cta,
			'used_sections'         => array(),
			'compatibility_notes'   => $compat_notes,
			'animation_tier'        => 'default',
			'helper_ref'            => $helper_ref,
			'one_pager_ref'         => '',
			'preview_excerpt'       => $preview_excerpt,
			'differentiation_notes' => array(),
			'detail_url'            => $detail_url,
		);
	}

	/**
	 * Builds a single template_compare_row for a page template.
	 *
	 * @param string               $key
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed> template_compare_row
	 */
	private function build_page_compare_row( string $key, array $definition ): array {
		$name          = (string) ( $definition['name'] ?? $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? $key );
		$purpose       = (string) ( $definition['purpose_summary'] ?? $definition['template_category_class'] ?? '' );
		$category      = (string) ( $definition['template_category_class'] ?? '' );
		$family        = (string) ( $definition['template_family'] ?? '' );
		$ordered       = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$used_sections = array();
		if ( \is_array( $ordered ) ) {
			foreach ( $ordered as $item ) {
				if ( ! \is_array( $item ) ) {
					continue;
				}
				$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? $item['section_key'] ?? '' );
				if ( $sk !== '' ) {
					$used_sections[] = $sk;
				}
			}
		}
		$compat        = $definition[ Page_Template_Schema::FIELD_COMPATIBILITY ] ?? $definition['compatibility'] ?? array();
		$compat_notes  = \is_array( $compat ) ? $compat : array();
		$one_pager     = $definition[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? $definition['one_pager'] ?? array();
		$one_pager_ref = \is_array( $one_pager ) && isset( $one_pager['link'] ) ? (string) $one_pager['link'] : ( (string) ( $one_pager['ref'] ?? '' ) );
		$detail_url    = \add_query_arg(
			array(
				'page'     => 'aio-page-builder-page-template-detail',
				'template' => $key,
			),
			\admin_url( 'admin.php' )
		);

		$preview_excerpt = __( 'Preview on detail', 'aio-page-builder' );
		if ( $this->preview_cache !== null ) {
			$context   = Synthetic_Preview_Context::for_page(
				$key,
				$category !== '' ? $category : 'top_level',
				$family !== '' ? $family : 'home',
				'default',
				false,
				Synthetic_Preview_Context::ANIMATION_TIER_NONE
			);
			$cache_key = $this->preview_cache->get_cache_key( $context, $definition );
			$cached    = $this->preview_cache->get( $cache_key );
			if ( $cached !== null ) {
				$text            = \wp_strip_all_tags( $cached->get_html() );
				$preview_excerpt = $text !== '' ? \wp_trim_words( $text, 20 ) : $preview_excerpt;
			}
		}

		return array(
			'template_key'          => $key,
			'name'                  => $name,
			'purpose_family'        => $purpose,
			'category_class'        => $category,
			'template_family'       => $family,
			'cta_direction'         => '',
			'used_sections'         => $used_sections,
			'compatibility_notes'   => $compat_notes,
			'animation_tier'        => 'default',
			'helper_ref'            => '',
			'one_pager_ref'         => $one_pager_ref,
			'preview_excerpt'       => $preview_excerpt,
			'differentiation_notes' => array(),
			'detail_url'            => $detail_url,
		);
	}
}
