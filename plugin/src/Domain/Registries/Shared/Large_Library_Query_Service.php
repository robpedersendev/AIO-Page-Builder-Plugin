<?php
/**
 * Large-library query, filtering, and pagination for section and page template registries (spec §12, §13, §55.7, §55.8).
 * Optimization layer over existing repositories; registry remains authoritative.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Query helpers for category, family, CTA classification, variant family, compatibility tags, preview availability;
 * pagination and count summaries; search/filter combinations. Bounded for 250-section / 500-page libraries.
 */
final class Large_Library_Query_Service {

	/** Max definitions to load in memory for filtering (spec §55.8). */
	public const MAX_LIBRARY_LOAD = 1000;

	/** Default per-page size for directory views (spec §55.8). */
	public const DEFAULT_PER_PAGE = 25;

	/** Maximum per-page for directory/list requests to keep admin responsive at scale (Prompt 188). */
	public const MAX_PER_PAGE = 50;

	/** Filter key: status (section or page). */
	public const FILTER_STATUS = 'status';

	/** Filter key: category (sections). */
	public const FILTER_CATEGORY = 'category';

	/** Filter key: section_purpose_family (sections; optional taxonomy in definition). */
	public const FILTER_SECTION_PURPOSE_FAMILY = 'section_purpose_family';

	/** Filter key: cta_classification (sections; optional). */
	public const FILTER_CTA_CLASSIFICATION = 'cta_classification';

	/** Filter key: variation_family_key (sections; optional). */
	public const FILTER_VARIATION_FAMILY_KEY = 'variation_family_key';

	/** Filter key: compatibility_tags (sections; array of tags; match any). */
	public const FILTER_COMPATIBILITY_TAGS = 'compatibility_tags';

	/** Filter key: archetype (page templates). */
	public const FILTER_ARCHETYPE = 'archetype';

	/** Filter key: template_category_class (page templates; optional taxonomy). */
	public const FILTER_TEMPLATE_CATEGORY_CLASS = 'template_category_class';

	/** Filter key: template_family (page templates; optional). */
	public const FILTER_TEMPLATE_FAMILY = 'template_family';

	/** Filter key: preview_available (bool; has preview ref). */
	public const FILTER_PREVIEW_AVAILABLE = 'preview_available';

	/** Filter key: search (string; matches internal_key, name, purpose_summary). */
	public const FILTER_SEARCH = 'search';

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repository;

	public function __construct(
		Section_Template_Repository $section_repository,
		Page_Template_Repository $page_template_repository
	) {
		$this->section_repository       = $section_repository;
		$this->page_template_repository = $page_template_repository;
	}

	/**
	 * Queries section templates with filters and pagination.
	 *
	 * @param array<string, mixed> $filters  status, category, section_purpose_family, cta_classification, variation_family_key, compatibility_tags, preview_available, search.
	 * @param int                  $page    1-based page.
	 * @param int                  $per_page Items per page.
	 * @return Large_Library_Filter_Result
	 */
	public function query_sections( array $filters, int $page = 1, int $per_page = self::DEFAULT_PER_PAGE ): Large_Library_Filter_Result {
		$per_page   = min( max( 1, $per_page ), self::MAX_PER_PAGE );
		$all        = $this->section_repository->list_all_definitions_capped( self::MAX_LIBRARY_LOAD );
		$filtered   = $this->filter_sections( $all, $filters );
		$total      = count( $filtered );
		$pagination = Large_Library_Pagination::from_page_size( $page, $per_page, $total );
		$slice      = array_slice( $filtered, $pagination->get_offset(), $pagination->get_per_page() );
		$rows       = $this->section_rows_for_directory( $slice );
		$counts     = $this->section_filter_counts( $filtered );
		return new Large_Library_Filter_Result( $rows, $pagination, $counts, $total );
	}

	/**
	 * Queries page templates with filters and pagination.
	 *
	 * @param array<string, mixed> $filters  status, archetype, template_category_class, template_family, preview_available, search.
	 * @param int                  $page    1-based page.
	 * @param int                  $per_page Items per page.
	 * @return Large_Library_Filter_Result
	 */
	public function query_page_templates( array $filters, int $page = 1, int $per_page = self::DEFAULT_PER_PAGE ): Large_Library_Filter_Result {
		$per_page   = min( max( 1, $per_page ), self::MAX_PER_PAGE );
		$all        = $this->page_template_repository->list_all_definitions_capped( self::MAX_LIBRARY_LOAD );
		$filtered   = $this->filter_page_templates( $all, $filters );
		$total      = count( $filtered );
		$pagination = Large_Library_Pagination::from_page_size( $page, $per_page, $total );
		$slice      = array_slice( $filtered, $pagination->get_offset(), $pagination->get_per_page() );
		$rows       = $this->page_template_rows_for_directory( $slice );
		$counts     = $this->page_template_filter_counts( $filtered );
		return new Large_Library_Filter_Result( $rows, $pagination, $counts, $total );
	}

	/**
	 * Count summary for section library (total and optional by status).
	 *
	 * @return array{total: int, by_status?: array<string, int>}
	 */
	public function get_section_count_summary(): array {
		$all       = $this->section_repository->list_all_definitions_capped( self::MAX_LIBRARY_LOAD );
		$by_status = array();
		foreach ( $all as $def ) {
			$s = (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' );
			if ( $s !== '' ) {
				$by_status[ $s ] = ( $by_status[ $s ] ?? 0 ) + 1;
			}
		}
		return array(
			'total'     => count( $all ),
			'by_status' => $by_status,
		);
	}

	/**
	 * Count summary for page template library (total and optional by status/archetype).
	 *
	 * @return array{total: int, by_status?: array<string, int>, by_archetype?: array<string, int>}
	 */
	public function get_page_template_count_summary(): array {
		$all          = $this->page_template_repository->list_all_definitions_capped( self::MAX_LIBRARY_LOAD );
		$by_status    = array();
		$by_archetype = array();
		foreach ( $all as $def ) {
			$s = (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
			if ( $s !== '' ) {
				$by_status[ $s ] = ( $by_status[ $s ] ?? 0 ) + 1;
			}
			$a = (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' );
			if ( $a !== '' ) {
				$by_archetype[ $a ] = ( $by_archetype[ $a ] ?? 0 ) + 1;
			}
		}
		return array(
			'total'        => count( $all ),
			'by_status'    => $by_status,
			'by_archetype' => $by_archetype,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $defs
	 * @param array<string, mixed>             $filters
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_sections( array $defs, array $filters ): array {
		$status         = isset( $filters[ self::FILTER_STATUS ] ) ? (string) $filters[ self::FILTER_STATUS ] : '';
		$category       = isset( $filters[ self::FILTER_CATEGORY ] ) ? (string) $filters[ self::FILTER_CATEGORY ] : '';
		$purpose_family = isset( $filters[ self::FILTER_SECTION_PURPOSE_FAMILY ] ) ? (string) $filters[ self::FILTER_SECTION_PURPOSE_FAMILY ] : '';
		$cta            = isset( $filters[ self::FILTER_CTA_CLASSIFICATION ] ) ? (string) $filters[ self::FILTER_CTA_CLASSIFICATION ] : '';
		$variation      = isset( $filters[ self::FILTER_VARIATION_FAMILY_KEY ] ) ? (string) $filters[ self::FILTER_VARIATION_FAMILY_KEY ] : '';
		$tags           = isset( $filters[ self::FILTER_COMPATIBILITY_TAGS ] ) && is_array( $filters[ self::FILTER_COMPATIBILITY_TAGS ] ) ? $filters[ self::FILTER_COMPATIBILITY_TAGS ] : array();
		$preview        = isset( $filters[ self::FILTER_PREVIEW_AVAILABLE ] ) ? (bool) $filters[ self::FILTER_PREVIEW_AVAILABLE ] : null;
		$search         = isset( $filters[ self::FILTER_SEARCH ] ) ? trim( (string) $filters[ self::FILTER_SEARCH ] ) : '';
		$search_lower   = $search !== '' ? mb_strtolower( $search, 'UTF-8' ) : '';

		$out = array();
		foreach ( $defs as $def ) {
			if ( $status !== '' && ( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ) ) !== $status ) {
				continue;
			}
			if ( $category !== '' && ( (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ) ) !== $category ) {
				continue;
			}
			if ( $purpose_family !== '' && ( (string) ( $def['section_purpose_family'] ?? '' ) ) !== $purpose_family ) {
				continue;
			}
			if ( $cta !== '' && ( (string) ( $def['cta_classification'] ?? '' ) ) !== $cta ) {
				continue;
			}
			if ( $variation !== '' && ( (string) ( $def['variation_family_key'] ?? '' ) ) !== $variation ) {
				continue;
			}
			if ( $preview === true ) {
				$ref = $def['preview_image_ref'] ?? $def['preview_description'] ?? '';
				if ( $ref === '' || $ref === null ) {
					continue;
				}
			}
			if ( ! empty( $tags ) ) {
				$comp      = $def[ Section_Schema::FIELD_COMPATIBILITY ] ?? array();
				$comp_tags = is_array( $comp ) ? ( $comp['tags'] ?? $comp['compatibility_tags'] ?? array() ) : array();
				if ( ! is_array( $comp_tags ) ) {
					$comp_tags = array();
				}
				$match = false;
				foreach ( $tags as $t ) {
					if ( in_array( (string) $t, $comp_tags, true ) ) {
						$match = true;
						break;
					}
				}
				if ( ! $match ) {
					continue;
				}
			}
			if ( $search_lower !== '' ) {
				$key  = mb_strtolower( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), 'UTF-8' );
				$name = mb_strtolower( (string) ( $def[ Section_Schema::FIELD_NAME ] ?? '' ), 'UTF-8' );
				$sum  = mb_strtolower( (string) ( $def[ Section_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ), 'UTF-8' );
				if ( strpos( $key, $search_lower ) === false && strpos( $name, $search_lower ) === false && strpos( $sum, $search_lower ) === false ) {
					continue;
				}
			}
			$out[] = $def;
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $defs
	 * @param array<string, mixed>             $filters
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_page_templates( array $defs, array $filters ): array {
		$status       = isset( $filters[ self::FILTER_STATUS ] ) ? (string) $filters[ self::FILTER_STATUS ] : '';
		$archetype    = isset( $filters[ self::FILTER_ARCHETYPE ] ) ? (string) $filters[ self::FILTER_ARCHETYPE ] : '';
		$tpl_cat      = isset( $filters[ self::FILTER_TEMPLATE_CATEGORY_CLASS ] ) ? (string) $filters[ self::FILTER_TEMPLATE_CATEGORY_CLASS ] : '';
		$tpl_fam      = isset( $filters[ self::FILTER_TEMPLATE_FAMILY ] ) ? (string) $filters[ self::FILTER_TEMPLATE_FAMILY ] : '';
		$preview      = isset( $filters[ self::FILTER_PREVIEW_AVAILABLE ] ) ? (bool) $filters[ self::FILTER_PREVIEW_AVAILABLE ] : null;
		$search       = isset( $filters[ self::FILTER_SEARCH ] ) ? trim( (string) $filters[ self::FILTER_SEARCH ] ) : '';
		$search_lower = $search !== '' ? mb_strtolower( $search, 'UTF-8' ) : '';

		$out = array();
		foreach ( $defs as $def ) {
			if ( $status !== '' && ( (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' ) ) !== $status ) {
				continue;
			}
			if ( $archetype !== '' && ( (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' ) ) !== $archetype ) {
				continue;
			}
			if ( $tpl_cat !== '' && ( (string) ( $def['template_category_class'] ?? '' ) ) !== $tpl_cat ) {
				continue;
			}
			if ( $tpl_fam !== '' && ( (string) ( $def['template_family'] ?? '' ) ) !== $tpl_fam ) {
				continue;
			}
			if ( $preview === true ) {
				$pm  = $def['preview_metadata'] ?? array();
				$ref = is_array( $pm ) ? ( $pm['preview_image_ref'] ?? $pm['preview_ref'] ?? '' ) : '';
				if ( $ref === '' || $ref === null ) {
					continue;
				}
			}
			if ( $search_lower !== '' ) {
				$key  = mb_strtolower( (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' ), 'UTF-8' );
				$name = mb_strtolower( (string) ( $def[ Page_Template_Schema::FIELD_NAME ] ?? '' ), 'UTF-8' );
				$sum  = mb_strtolower( (string) ( $def[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ), 'UTF-8' );
				if ( strpos( $key, $search_lower ) === false && strpos( $name, $search_lower ) === false && strpos( $sum, $search_lower ) === false ) {
					continue;
				}
			}
			$out[] = $def;
		}
		return $out;
	}

	/**
	 * Builds row summaries for directory IA (section).
	 *
	 * @param array<int, array<string, mixed>> $defs
	 * @return array<int, array<string, mixed>>
	 */
	private function section_rows_for_directory( array $defs ): array {
		$rows = array();
		foreach ( $defs as $def ) {
			$variants      = $def[ Section_Schema::FIELD_VARIANTS ] ?? array();
			$variant_count = is_array( $variants ) ? count( $variants ) : 0;
			$version_arr   = $def[ Section_Schema::FIELD_VERSION ] ?? array();
			$version       = is_array( $version_arr ) ? (string) ( $version_arr['version'] ?? '1' ) : '1';
			$rows[]        = array(
				'internal_key'           => (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ),
				'name'                   => (string) ( $def[ Section_Schema::FIELD_NAME ] ?? '' ),
				'status'                 => (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ),
				'category'               => (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ),
				'purpose_summary'        => (string) ( $def[ Section_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ),
				'section_purpose_family' => (string) ( $def['section_purpose_family'] ?? '' ),
				'cta_classification'     => (string) ( $def['cta_classification'] ?? '' ),
				'variation_family_key'   => (string) ( $def['variation_family_key'] ?? '' ),
				'placement_tendency'     => (string) ( $def['placement_tendency'] ?? '' ),
				'helper_ref'             => (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' ),
				'field_blueprint_ref'    => (string) ( $def[ Section_Schema::FIELD_FIELD_BLUEPRINT_REF ] ?? '' ),
				'preview_available'      => ( ( $def['preview_image_ref'] ?? $def['preview_description'] ?? '' ) !== '' ),
				'version'                => $version,
				'variant_count'          => $variant_count,
			);
		}
		return $rows;
	}

	/**
	 * Builds row summaries for directory IA (page template).
	 *
	 * @param array<int, array<string, mixed>> $defs
	 * @return array<int, array<string, mixed>>
	 */
	private function page_template_rows_for_directory( array $defs ): array {
		$rows = array();
		foreach ( $defs as $def ) {
			$pm            = $def['preview_metadata'] ?? array();
			$preview_ref   = is_array( $pm ) ? ( $pm['preview_image_ref'] ?? $pm['preview_ref'] ?? '' ) : '';
			$ordered       = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_count = is_array( $ordered ) ? count( $ordered ) : 0;
			$version_arr   = $def[ Page_Template_Schema::FIELD_VERSION ] ?? array();
			$version       = is_array( $version_arr ) ? (string) ( $version_arr['version'] ?? '1' ) : '1';
			$one_pager     = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$one_pager_url = \is_array( $one_pager ) && isset( $one_pager['link'] ) ? (string) $one_pager['link'] : '';
			$rows[]        = array(
				'internal_key'            => (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' ),
				'name'                    => (string) ( $def[ Page_Template_Schema::FIELD_NAME ] ?? '' ),
				'status'                  => (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' ),
				'archetype'               => (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' ),
				'purpose_summary'         => (string) ( $def[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ),
				'template_category_class' => (string) ( $def['template_category_class'] ?? '' ),
				'template_family'         => (string) ( $def['template_family'] ?? '' ),
				'preview_available'       => ( $preview_ref !== '' ),
				'section_count'           => $section_count,
				'version'                 => $version,
				'one_pager_link'          => $one_pager_url,
				'ai_source_badge'         => Registry_AI_Provenance_Helper::source_badge_label_for_page_template( $def ),
			);
		}
		return $rows;
	}

	/**
	 * @param array<int, array<string, mixed>> $filtered
	 * @return array<string, array<string, int>>
	 */
	private function section_filter_counts( array $filtered ): array {
		$by_status           = array();
		$by_category         = array();
		$by_purpose_family   = array();
		$by_cta              = array();
		$by_variation_family = array();
		foreach ( $filtered as $def ) {
			$s = (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' );
			if ( $s !== '' ) {
				$by_status[ $s ] = ( $by_status[ $s ] ?? 0 ) + 1;
			}
			$c = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			if ( $c !== '' ) {
				$by_category[ $c ] = ( $by_category[ $c ] ?? 0 ) + 1;
			}
			$pf = (string) ( $def['section_purpose_family'] ?? '' );
			if ( $pf !== '' ) {
				$by_purpose_family[ $pf ] = ( $by_purpose_family[ $pf ] ?? 0 ) + 1;
			}
			$cta = (string) ( $def['cta_classification'] ?? '' );
			if ( $cta !== '' ) {
				$by_cta[ $cta ] = ( $by_cta[ $cta ] ?? 0 ) + 1;
			}
			$vf = (string) ( $def['variation_family_key'] ?? '' );
			if ( $vf !== '' ) {
				$by_variation_family[ $vf ] = ( $by_variation_family[ $vf ] ?? 0 ) + 1;
			}
		}
		return array(
			'status'                 => $by_status,
			'category'               => $by_category,
			'section_purpose_family' => $by_purpose_family,
			'cta_classification'     => $by_cta,
			'variation_family_key'   => $by_variation_family,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $filtered
	 * @return array<string, array<string, int>>
	 */
	private function page_template_filter_counts( array $filtered ): array {
		$by_status    = array();
		$by_archetype = array();
		$by_tpl_cat   = array();
		$by_tpl_fam   = array();
		foreach ( $filtered as $def ) {
			$s = (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
			if ( $s !== '' ) {
				$by_status[ $s ] = ( $by_status[ $s ] ?? 0 ) + 1;
			}
			$a = (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' );
			if ( $a !== '' ) {
				$by_archetype[ $a ] = ( $by_archetype[ $a ] ?? 0 ) + 1;
			}
			$tc = (string) ( $def['template_category_class'] ?? '' );
			if ( $tc !== '' ) {
				$by_tpl_cat[ $tc ] = ( $by_tpl_cat[ $tc ] ?? 0 ) + 1;
			}
			$tf = (string) ( $def['template_family'] ?? '' );
			if ( $tf !== '' ) {
				$by_tpl_fam[ $tf ] = ( $by_tpl_fam[ $tf ] ?? 0 ) + 1;
			}
		}
		return array(
			'status'                  => $by_status,
			'archetype'               => $by_archetype,
			'template_category_class' => $by_tpl_cat,
			'template_family'         => $by_tpl_fam,
		);
	}
}
