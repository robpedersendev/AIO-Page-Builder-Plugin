<?php
/**
 * Builds directory state for the Section Templates admin screen (spec §49.6, section-template-directory-ia-extension).
 * Produces breadcrumbs, tree (purpose family → CTA/variant), list result, filters, and action flags.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;

/**
 * Builds stable screen-state payloads for the section template directory: purpose tree (L2),
 * CTA/variant nodes (L3), list rows, breadcrumbs, filters. Uses Large_Library_Query_Service; no registry mutation.
 */
final class Section_Template_Directory_State_Builder {

	/** Screen slug for building URLs. */
	public const SCREEN_SLUG = 'aio-page-builder-section-templates';

	/** Purpose family order (L2; section-template-directory-ia-extension §3.3). */
	private const PURPOSE_ORDER = array(
		'hero',
		'proof',
		'offer',
		'explainer',
		'legal',
		'utility',
		'listing',
		'comparison',
		'contact',
		'cta',
		'faq',
		'profile',
		'stats',
		'timeline',
		'related',
		'other',
	);

	/** CTA classification order (L3 for cta/contact; §3.3). */
	private const CTA_ORDER = array( 'primary_cta', 'contact_cta', 'navigation_cta', 'none' );

	/** CTA classification → label. */
	private const CTA_LABELS = array(
		'primary_cta'    => 'Primary CTA',
		'contact_cta'    => 'Contact CTA',
		'navigation_cta' => 'Navigation CTA',
		'none'           => 'None',
	);

	/** Purpose families that use CTA classification for L3 (cta, contact). */
	private const CTA_PURPOSE_FAMILIES = array( 'cta', 'contact' );

	/** @var Large_Library_Query_Service */
	private Large_Library_Query_Service $query_service;

	public function __construct( Large_Library_Query_Service $query_service ) {
		$this->query_service = $query_service;
	}

	/**
	 * Builds full directory state from request params.
	 *
	 * @param array<string, mixed> $request_params Sanitized query/request params.
	 * @return array<string, mixed> State payload: view, breadcrumbs, tree, l3_nodes, list_result, filters, base_url, can_manage_templates, purpose_labels, cta_labels.
	 */
	public function build_state( array $request_params ): array {
		$purpose_family       = isset( $request_params['purpose_family'] ) ? \sanitize_key( (string) $request_params['purpose_family'] ) : '';
		$cta_classification   = isset( $request_params['cta_classification'] ) ? \sanitize_key( (string) $request_params['cta_classification'] ) : '';
		$variation_family_key = isset( $request_params['variation_family_key'] ) ? \sanitize_key( (string) $request_params['variation_family_key'] ) : '';
		$show_all             = ! empty( $request_params['all'] );
		$status               = isset( $request_params['status'] ) ? \sanitize_key( (string) $request_params['status'] ) : '';
		$search               = isset( $request_params['search'] ) ? \sanitize_text_field( (string) $request_params['search'] ) : '';
		$paged                = isset( $request_params['paged'] ) ? max( 1, (int) $request_params['paged'] ) : 1;
		$per_page             = isset( $request_params['per_page'] ) ? max( 1, min( Large_Library_Query_Service::MAX_PER_PAGE, (int) $request_params['per_page'] ) ) : Large_Library_Query_Service::DEFAULT_PER_PAGE;

		$base_url = \admin_url( 'admin.php?page=' . self::SCREEN_SLUG );

		$filters = array();
		if ( $purpose_family !== '' ) {
			$filters[ Large_Library_Query_Service::FILTER_SECTION_PURPOSE_FAMILY ] = $purpose_family;
		}
		if ( $cta_classification !== '' ) {
			$filters[ Large_Library_Query_Service::FILTER_CTA_CLASSIFICATION ] = $cta_classification;
		}
		if ( $variation_family_key !== '' && ! $show_all ) {
			$filters[ Large_Library_Query_Service::FILTER_VARIATION_FAMILY_KEY ] = $variation_family_key;
		}
		if ( $show_all ) {
			$cta_classification   = '';
			$variation_family_key = '';
		}
		if ( $status !== '' ) {
			$filters[ Large_Library_Query_Service::FILTER_STATUS ] = $status;
		}
		if ( $search !== '' ) {
			$filters[ Large_Library_Query_Service::FILTER_SEARCH ] = $search;
		}

		$view = 'root';
		if ( $search !== '' ) {
			$view = 'search';
		} elseif ( $purpose_family !== '' && ( $cta_classification !== '' || $variation_family_key !== '' || $show_all ) ) {
			$view = 'list';
		} elseif ( $purpose_family !== '' ) {
			$view = 'purpose';
		}

		$breadcrumbs = $this->build_breadcrumbs( $view, $purpose_family, $cta_classification, $variation_family_key, $show_all, $search, $base_url );

		$tree = $this->build_tree( $base_url );

		$l3_nodes = array();
		if ( $purpose_family !== '' ) {
			$l3_nodes = $this->build_l3_nodes( $purpose_family, $base_url );
		}

		$list_result = array(
			'rows'           => array(),
			'pagination'     => array(),
			'total_matching' => 0,
		);
		if ( $view === 'list' || $view === 'search' ) {
			$result      = $this->query_service->query_sections( $filters, $paged, $per_page );
			$list_result = array(
				'rows'           => $result->get_rows(),
				'pagination'     => $result->get_pagination()->to_array(),
				'total_matching' => $result->get_total_matching(),
			);
		}

		$can_manage = \current_user_can( 'aio_view_build_plans' );

		return array(
			'view'                 => $view,
			'breadcrumbs'          => $breadcrumbs,
			'tree'                 => $tree,
			'l3_nodes'             => $l3_nodes,
			'list_result'          => $list_result,
			'filters'              => array(
				'purpose_family'       => $purpose_family,
				'cta_classification'   => $cta_classification,
				'variation_family_key' => $variation_family_key,
				'all'                  => $show_all,
				'status'               => $status,
				'search'               => $search,
				'paged'                => $paged,
				'per_page'             => $per_page,
			),
			'base_url'             => $base_url,
			'can_manage_templates' => $can_manage,
			'purpose_labels'       => $this->get_purpose_labels(),
			'cta_labels'           => self::CTA_LABELS,
		);
	}

	/**
	 * Builds breadcrumb segments (label + url); last segment has empty url (current).
	 *
	 * @param string $view
	 * @param string $purpose_family
	 * @param string $cta_classification
	 * @param string $variation_family_key
	 * @param bool   $show_all
	 * @param string $search
	 * @param string $base_url
	 * @return array<int, array{label: string, url: string}>
	 */
	private function build_breadcrumbs( string $view, string $purpose_family, string $cta_classification, string $variation_family_key, bool $show_all, string $search, string $base_url ): array {
		$segments = array(
			array(
				'label' => __( 'Section Templates', 'aio-page-builder' ),
				'url'   => $base_url,
			),
		);

		if ( $search !== '' ) {
			$segments[] = array(
				'label' => sprintf( __( 'Search: %s', 'aio-page-builder' ), \esc_html( $search ) ),
				'url'   => '',
			);
			return $segments;
		}

		if ( $purpose_family === '' ) {
			return $segments;
		}

		$purpose_label = $this->purpose_to_label( $purpose_family );
		$purpose_url   = $base_url . '&purpose_family=' . \rawurlencode( $purpose_family );
		$segments[]    = array(
			'label' => $purpose_label,
			'url'   => $purpose_url,
		);

		if ( $cta_classification === '' && $variation_family_key === '' && ! $show_all ) {
			return $segments;
		}

		$l3_label = $show_all ? __( 'All', 'aio-page-builder' ) : ( $cta_classification !== ''
			? ( self::CTA_LABELS[ $cta_classification ] ?? $cta_classification )
			: $this->variation_to_label( $variation_family_key ) );
		$l3_url   = $purpose_url;
		if ( $show_all ) {
			$l3_url .= '&all=1';
		} elseif ( $cta_classification !== '' ) {
			$l3_url .= '&cta_classification=' . \rawurlencode( $cta_classification );
		} else {
			$l3_url .= '&variation_family_key=' . \rawurlencode( $variation_family_key );
		}
		$segments[] = array(
			'label' => $l3_label,
			'url'   => $view === 'list' ? $l3_url : '',
		);
		return $segments;
	}

	/**
	 * Builds tree: list of purpose families (L2) with count and url.
	 *
	 * @param string $base_url
	 * @return array<int, array{slug: string, label: string, count: int, url: string}>
	 */
	private function build_tree( string $base_url ): array {
		$empty_filters = array();
		$result        = $this->query_service->query_sections( $empty_filters, 1, 1 );
		$counts        = $result->get_filter_counts();
		$by_purpose    = $counts['section_purpose_family'] ?? array();

		$tree   = array();
		$labels = $this->get_purpose_labels();
		foreach ( self::PURPOSE_ORDER as $slug ) {
			$count  = (int) ( $by_purpose[ $slug ] ?? 0 );
			$tree[] = array(
				'slug'  => $slug,
				'label' => $labels[ $slug ] ?? \ucfirst( $slug ),
				'count' => $count,
				'url'   => $base_url . '&purpose_family=' . \rawurlencode( $slug ),
			);
		}
		// * Include any purpose families present in data but not in PURPOSE_ORDER (e.g. custom).
		foreach ( $by_purpose as $slug => $count ) {
			if ( ! \in_array( $slug, self::PURPOSE_ORDER, true ) ) {
				$tree[] = array(
					'slug'  => $slug,
					'label' => $labels[ $slug ] ?? \ucfirst( \str_replace( array( '_', '-' ), ' ', $slug ) ),
					'count' => (int) $count,
					'url'   => $base_url . '&purpose_family=' . \rawurlencode( $slug ),
				);
			}
		}
		return $tree;
	}

	/**
	 * Builds L3 nodes for selected purpose: CTA classification (for cta/contact) or variant family + All.
	 *
	 * @param string $purpose_family
	 * @param string $base_url
	 * @return array<int, array{slug: string, label: string, count: int, url: string, type: string}>
	 */
	private function build_l3_nodes( string $purpose_family, string $base_url ): array {
		$filters = array( Large_Library_Query_Service::FILTER_SECTION_PURPOSE_FAMILY => $purpose_family );
		$result  = $this->query_service->query_sections( $filters, 1, 1 );
		$counts  = $result->get_filter_counts();
		$base    = $base_url . '&purpose_family=' . \rawurlencode( $purpose_family );

		$nodes = array();

		if ( \in_array( $purpose_family, self::CTA_PURPOSE_FAMILIES, true ) ) {
			$by_cta = $counts['cta_classification'] ?? array();
			foreach ( self::CTA_ORDER as $slug ) {
				$count   = (int) ( $by_cta[ $slug ] ?? 0 );
				$nodes[] = array(
					'slug'  => $slug,
					'label' => self::CTA_LABELS[ $slug ] ?? $slug,
					'count' => $count,
					'url'   => $base . '&cta_classification=' . \rawurlencode( $slug ),
					'type'  => 'cta',
				);
			}
		}

		$by_variant = $counts['variation_family_key'] ?? array();
		\ksort( $by_variant );
		$total_in_purpose = array_sum( array_map( 'intval', $by_variant ) );
		foreach ( $by_variant as $slug => $count ) {
			$nodes[] = array(
				'slug'  => $slug,
				'label' => $this->variation_to_label( $slug ),
				'count' => (int) $count,
				'url'   => $base . '&variation_family_key=' . \rawurlencode( $slug ),
				'type'  => 'variant',
			);
		}

		// * "All" shows full purpose list (no cta/variant filter); only when we have variant nodes or CTA purpose to avoid duplicate "All".
		$all_count = $result->get_total_matching();
		if ( $all_count > 0 && ! \in_array( $purpose_family, self::CTA_PURPOSE_FAMILIES, true ) ) {
			$nodes[] = array(
				'slug'  => '',
				'label' => __( 'All', 'aio-page-builder' ),
				'count' => $all_count,
				'url'   => $base . '&all=1',
				'type'  => 'all',
			);
		}
		if ( \in_array( $purpose_family, self::CTA_PURPOSE_FAMILIES, true ) && $all_count > 0 ) {
			$nodes[] = array(
				'slug'  => 'all',
				'label' => __( 'All', 'aio-page-builder' ),
				'count' => $all_count,
				'url'   => $base . '&all=1',
				'type'  => 'all',
			);
		}

		return $nodes;
	}

	private function purpose_to_label( string $slug ): string {
		$labels = $this->get_purpose_labels();
		return $labels[ $slug ] ?? \ucfirst( \str_replace( array( '_', '-' ), ' ', $slug ) );
	}

	/** @return array<string, string> */
	private function get_purpose_labels(): array {
		$out = array();
		foreach ( self::PURPOSE_ORDER as $slug ) {
			$out[ $slug ] = \ucfirst( \str_replace( array( '_', '-' ), ' ', $slug ) );
		}
		return $out;
	}

	private function variation_to_label( string $slug ): string {
		if ( $slug === '' ) {
			return __( 'All', 'aio-page-builder' );
		}
		return \ucfirst( \str_replace( array( '_', '-' ), ' ', $slug ) );
	}
}
