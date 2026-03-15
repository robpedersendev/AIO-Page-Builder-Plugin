<?php
/**
 * Enriches page template directory state with industry filter and badges (industry-admin-screen-contract, industry-page-template-recommendation-contract).
 * Applies industry_view (recommended_only, recommended_plus_weak_fit, full_library); invalid/missing profile → full_library, neutral.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\PageTemplates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Item_View;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Read_Model_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Adds industry_view filter and industry_badges_by_key to page template directory state. Safe fallback when profile missing.
 */
final class Industry_Page_Template_Filter_Controller {

	/** @var Industry_Page_Template_Directory_Read_Model_Builder */
	private $read_model_builder;

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	public function __construct(
		Industry_Page_Template_Directory_Read_Model_Builder $read_model_builder,
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Pack_Registry $pack_registry = null
	) {
		$this->read_model_builder = $read_model_builder;
		$this->profile_repo       = $profile_repo;
		$this->pack_registry      = $pack_registry;
	}

	/**
	 * Enriches directory state with industry_view and industry_badges_by_key. Optionally filters list_result.rows by view.
	 *
	 * @param array<string, mixed> $state          State from Page_Template_Directory_State_Builder (with list_result.rows when view is list).
	 * @param array<string, mixed> $request_params Must include 'industry_view' (recommended_only | recommended_plus_weak_fit | full_library).
	 * @return array<string, mixed> State with industry_view, industry_badges_by_key; list_result.rows filtered when not full_library.
	 */
	public function enrich_state( array $state, array $request_params ): array {
		$industry_view = isset( $request_params['industry_view'] ) ? \sanitize_key( (string) $request_params['industry_view'] ) : '';
		$allowed = array(
			Industry_Page_Template_Directory_Read_Model_Builder::VIEW_RECOMMENDED_ONLY,
			Industry_Page_Template_Directory_Read_Model_Builder::VIEW_RECOMMENDED_PLUS_WEAK,
			Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY,
		);
		if ( ! \in_array( $industry_view, $allowed, true ) ) {
			$industry_view = Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY;
		}

		$state['industry_view']          = $industry_view;
		$state['industry_badges_by_key'] = array();

		$profile = array();
		$primary_pack = null;
		if ( $this->profile_repo !== null ) {
			$profile = $this->profile_repo->get_profile();
		}
		$primary_key = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		if ( $primary_key !== '' && $this->pack_registry !== null ) {
			$primary_pack = $this->pack_registry->get( $primary_key );
		}

		$list_result = $state['list_result'] ?? array( 'rows' => array(), 'pagination' => array(), 'total_matching' => 0 );
		$rows = $list_result['rows'] ?? array();
		if ( count( $rows ) === 0 ) {
			return $state;
		}

		$templates = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$key = isset( $row['internal_key'] ) && is_string( $row['internal_key'] ) ? trim( $row['internal_key'] ) : '';
			if ( $key === '' ) {
				$key = isset( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) ? trim( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) : '';
			}
			if ( $key !== '' ) {
				$templates[] = $row;
			}
		}

		$item_views = $this->read_model_builder->build( $profile, $primary_pack, $templates, $industry_view );
		$badges_by_key = array();
		$ordered_keys = array();
		foreach ( $item_views as $item ) {
			if ( ! $item instanceof Industry_Page_Template_Directory_Item_View ) {
				continue;
			}
			$k = $item->get_page_template_key();
			$badges_by_key[ $k ] = $item;
			$ordered_keys[] = $k;
		}

		$state['industry_badges_by_key'] = $badges_by_key;

		if ( $industry_view !== Industry_Page_Template_Directory_Read_Model_Builder::VIEW_FULL_LIBRARY && count( $ordered_keys ) > 0 ) {
			$rows_by_key = array();
			foreach ( $rows as $row ) {
				$key = isset( $row['internal_key'] ) && is_string( $row['internal_key'] ) ? trim( $row['internal_key'] ) : ( isset( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) ? trim( $row[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) : '' );
				if ( $key !== '' ) {
					$rows_by_key[ $key ] = $row;
				}
			}
			$filtered_rows = array();
			foreach ( $ordered_keys as $key ) {
				if ( isset( $rows_by_key[ $key ] ) ) {
					$filtered_rows[] = $rows_by_key[ $key ];
				}
			}
			$state['list_result']['rows'] = $filtered_rows;
			$state['list_result']['total_matching'] = count( $filtered_rows );
			if ( isset( $state['list_result']['pagination'] ) && is_array( $state['list_result']['pagination'] ) ) {
				$state['list_result']['pagination']['total'] = count( $filtered_rows );
				$state['list_result']['pagination']['total_pages'] = max( 1, (int) \ceil( count( $filtered_rows ) / ( $state['list_result']['pagination']['per_page'] ?? 25 ) ) );
			}
		}

		return $state;
	}
}
