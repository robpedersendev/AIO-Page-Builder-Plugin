<?php
/**
 * Builds directory state for the Page Templates admin screen (spec §49.7, page-template-directory-ia-extension).
 * Produces breadcrumbs, tree (category/family) nodes, list result, filters, and action flags.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Filter_Result;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Pagination;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;

/**
 * Builds stable screen-state payloads for the page template directory: tree nodes, list rows,
 * breadcrumbs, filters, pagination. Uses Large_Library_Query_Service; no registry mutation.
 */
final class Page_Template_Directory_State_Builder {

	/** Screen slug for building URLs. */
	public const SCREEN_SLUG = 'aio-page-builder-page-templates';

	/** Category class order (L2). */
	private const CATEGORY_ORDER = array( 'top_level', 'hub', 'nested_hub', 'child_detail' );

	/** Category class → label (L2, page-template-directory-ia-extension §3.2). */
	private const CATEGORY_LABELS = array(
		'top_level'    => 'Top Level Page Templates',
		'hub'          => 'Hub Page Templates',
		'nested_hub'   => 'Nested Hub Page Templates',
		'child_detail' => 'Child/Detail Page Templates',
	);

	/** @var Large_Library_Query_Service */
	private Large_Library_Query_Service $query_service;

	public function __construct( Large_Library_Query_Service $query_service ) {
		$this->query_service = $query_service;
	}

	/**
	 * Builds full directory state from request params (category_class, family, status, search, paged, per_page).
	 *
	 * @param array<string, mixed> $request_params Sanitized query/request params.
	 * @return array<string, mixed> State payload: view, breadcrumbs, tree, families, list_result, filters, base_url, can_manage_templates.
	 */
	public function build_state( array $request_params ): array {
		$category_class = isset( $request_params['category_class'] ) ? \sanitize_key( (string) $request_params['category_class'] ) : '';
		$family         = isset( $request_params['family'] ) ? \sanitize_key( (string) $request_params['family'] ) : '';
		$status         = isset( $request_params['status'] ) ? \sanitize_key( (string) $request_params['status'] ) : '';
		$search         = isset( $request_params['search'] ) ? \sanitize_text_field( (string) $request_params['search'] ) : '';
		$paged          = isset( $request_params['paged'] ) ? max( 1, (int) $request_params['paged'] ) : 1;
		$per_page       = isset( $request_params['per_page'] ) ? max( 1, min( 100, (int) $request_params['per_page'] ) ) : Large_Library_Query_Service::DEFAULT_PER_PAGE;

		$base_url = \admin_url( 'admin.php?page=' . self::SCREEN_SLUG );

		$filters = array();
		if ( $category_class !== '' && \in_array( $category_class, self::CATEGORY_ORDER, true ) ) {
			$filters[ Large_Library_Query_Service::FILTER_TEMPLATE_CATEGORY_CLASS ] = $category_class;
		}
		if ( $family !== '' ) {
			$filters[ Large_Library_Query_Service::FILTER_TEMPLATE_FAMILY ] = $family;
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
		} elseif ( $category_class !== '' && $family !== '' ) {
			$view = 'list';
		} elseif ( $category_class !== '' ) {
			$view = 'category';
		}

		$breadcrumbs = $this->build_breadcrumbs( $view, $category_class, $family, $search, $base_url );

		$tree = $this->build_tree( $base_url );

		$families = array();
		if ( $category_class !== '' ) {
			$families = $this->build_families_for_category( $category_class, $base_url );
		}

		$list_result = array( 'rows' => array(), 'pagination' => array(), 'total_matching' => 0 );
		if ( $view === 'list' || $view === 'search' ) {
			$result = $this->query_service->query_page_templates( $filters, $paged, $per_page );
			$list_result = array(
				'rows'           => $result->get_rows(),
				'pagination'     => $result->get_pagination()->to_array(),
				'total_matching' => $result->get_total_matching(),
			);
		}

		$can_manage = \current_user_can( 'aio_view_build_plans' );

		return array(
			'view'                => $view,
			'breadcrumbs'         => $breadcrumbs,
			'tree'                => $tree,
			'families'            => $families,
			'list_result'         => $list_result,
			'filters'             => array(
				'category_class' => $category_class,
				'family'         => $family,
				'status'         => $status,
				'search'         => $search,
				'paged'          => $paged,
				'per_page'       => $per_page,
			),
			'base_url'            => $base_url,
			'can_manage_templates' => $can_manage,
			'category_labels'     => self::CATEGORY_LABELS,
		);
	}

	/**
	 * Builds breadcrumb segments (label + url); last segment has empty url (current).
	 *
	 * @param string $view
	 * @param string $category_class
	 * @param string $family
	 * @param string $search
	 * @param string $base_url
	 * @return list<array{label: string, url: string}>
	 */
	private function build_breadcrumbs( string $view, string $category_class, string $family, string $search, string $base_url ): array {
		$segments = array( array( 'label' => __( 'Page Templates', 'aio-page-builder' ), 'url' => $base_url ) );

		if ( $search !== '' ) {
			$segments[] = array( 'label' => sprintf( __( 'Search: %s', 'aio-page-builder' ), \esc_html( $search ) ), 'url' => '' );
			return $segments;
		}

		if ( $category_class === '' ) {
			return $segments;
		}

		$cat_label = self::CATEGORY_LABELS[ $category_class ] ?? $category_class;
		$cat_url = $base_url . '&category_class=' . \rawurlencode( $category_class );
		$segments[] = array( 'label' => $cat_label, 'url' => $cat_url );

		if ( $family === '' ) {
			return $segments;
		}

		$family_label = $this->family_to_label( $family );
		$family_url = $cat_url . '&family=' . \rawurlencode( $family );
		$segments[] = array( 'label' => $family_label, 'url' => $view === 'list' ? $family_url : '' );
		return $segments;
	}

	/**
	 * Builds tree: list of categories with count and url.
	 *
	 * @param string $base_url
	 * @return list<array{slug: string, label: string, count: int, url: string}>
	 */
	private function build_tree( string $base_url ): array {
		$empty_filters = array();
		$result = $this->query_service->query_page_templates( $empty_filters, 1, 1 );
		$counts = $result->get_filter_counts();
		$by_cat = $counts[ 'template_category_class' ] ?? array();

		$tree = array();
		foreach ( self::CATEGORY_ORDER as $slug ) {
			$count = (int) ( $by_cat[ $slug ] ?? 0 );
			$tree[] = array(
				'slug'  => $slug,
				'label' => self::CATEGORY_LABELS[ $slug ] ?? $slug,
				'count' => $count,
				'url'   => $base_url . '&category_class=' . \rawurlencode( $slug ),
			);
		}
		return $tree;
	}

	/**
	 * Builds families for a given category (L3 nodes) with count and url.
	 *
	 * @param string $category_class
	 * @param string $base_url
	 * @return list<array{slug: string, label: string, count: int, url: string}>
	 */
	private function build_families_for_category( string $category_class, string $base_url ): array {
		$filters = array( Large_Library_Query_Service::FILTER_TEMPLATE_CATEGORY_CLASS => $category_class );
		$result = $this->query_service->query_page_templates( $filters, 1, 1 );
		$counts = $result->get_filter_counts();
		$by_fam = $counts[ 'template_family' ] ?? array();
		\ksort( $by_fam );

		$families = array();
		foreach ( $by_fam as $slug => $count ) {
			$families[] = array(
				'slug'  => $slug,
				'label' => $this->family_to_label( $slug ),
				'count' => (int) $count,
				'url'   => $base_url . '&category_class=' . \rawurlencode( $category_class ) . '&family=' . \rawurlencode( $slug ),
			);
		}
		return $families;
	}

	private function family_to_label( string $slug ): string {
		$human = \str_replace( array( '_', '-' ), ' ', $slug );
		return \ucfirst( $human ) . ' ' . __( 'Page Templates', 'aio-page-builder' );
	}
}
