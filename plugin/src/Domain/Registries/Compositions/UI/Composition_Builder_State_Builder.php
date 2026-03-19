<?php
/**
 * Builds composition-builder UI state for large-library assembly (Prompt 177, spec §14, §49.6).
 * Filtered section selection, ordered list display, CTA warnings, insertion guidance, preview/one-pager readiness.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Compositions\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Compositions\Validation\Large_Composition_Validator;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Filter_Result;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Builds stable UI-state payload for the composition builder: filters, section list, current ordered list,
 * CTA count/proximity warnings, insertion hint, validation status, preview and one-pager readiness.
 */
final class Composition_Builder_State_Builder {

	/** CTA-classified values (cta-sequencing-and-placement-contract §2.1). */
	private const CTA_CLASSIFIED = array( 'primary_cta', 'contact_cta', 'navigation_cta' );

	/** Screen slug for builder URLs. */
	public const SCREEN_SLUG = 'aio-page-builder-compositions';

	private Large_Library_Query_Service $query_service;
	private Section_Template_Repository $section_repository;

	/** @var Large_Composition_Validator|null When set, full validation result is included in state (Prompt 178). */
	private ?Large_Composition_Validator $large_validator = null;

	public function __construct(
		Large_Library_Query_Service $query_service,
		Section_Template_Repository $section_repository
	) {
		$this->query_service      = $query_service;
		$this->section_repository = $section_repository;
	}

	/**
	 * Sets the large-library validator so build_state includes full validation_result (blockers, warnings, CTA/compatibility/preview).
	 *
	 * @param Large_Composition_Validator|null $validator
	 * @return void
	 */
	public function set_large_validator( ?Large_Composition_Validator $validator ): void {
		$this->large_validator = $validator;
	}

	/**
	 * Builds full builder state from request params and optional current composition.
	 *
	 * @param array<string, mixed>      $request_params Sanitized GET/POST (view, composition_id, filters, paged, per_page).
	 * @param array<string, mixed>|null $current_composition Composition definition if editing; null for new.
	 * @return array<string, mixed> State: filter_state, section_result, ordered_sections_display, cta_warnings, insertion_hint, validation_status, validation_codes, preview_readiness, one_pager_ready, base_url, can_manage, category_labels, cta_labels.
	 */
	public function build_state( array $request_params, ?array $current_composition = null ): array {
		$filter_state   = $this->build_filter_state( $request_params );
		$filters        = $filter_state->to_query_filters();
		$paged          = $filter_state->get_paged();
		$per_page       = $filter_state->get_per_page();
		$section_result = $this->query_service->query_sections( $filters, $paged, $per_page );

		$ordered_sections_display = array();
		$cta_warnings             = array();
		$insertion_hint           = '';
		$preview_readiness        = true;
		$ordered_list             = array();
		$section_by_key           = array();

		if ( $current_composition !== null ) {
			$ordered_list = $current_composition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
			if ( is_array( $ordered_list ) ) {
				usort(
					$ordered_list,
					function ( $a, $b ) {
						$pa = isset( $a[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $a[ Composition_Schema::SECTION_ITEM_POSITION ] : 0;
						$pb = isset( $b[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $b[ Composition_Schema::SECTION_ITEM_POSITION ] : 0;
						return $pa <=> $pb;
					}
				);
				foreach ( $ordered_list as $item ) {
					$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
					if ( $key === '' ) {
						continue;
					}
					$def = $this->section_repository->get_definition_by_key( $key );
					if ( $def !== null ) {
						$section_by_key[ $key ]     = $def;
						$cta                        = (string) ( $def['cta_classification'] ?? '' );
						$is_cta                     = in_array( $cta, self::CTA_CLASSIFIED, true );
						$ordered_sections_display[] = array(
							'section_key'        => $key,
							'name'               => (string) ( $def[ \AIOPageBuilder\Domain\Registries\Section\Section_Schema::FIELD_NAME ] ?? $key ),
							'position'           => (int) ( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ?? 0 ),
							'cta_classification' => $cta,
							'is_cta'             => $is_cta,
						);
						$has_preview                = ( ! empty( $def['preview_defaults'] ) && is_array( $def['preview_defaults'] ) )
							|| ( (string) ( $def['preview_image_ref'] ?? '' ) ) !== ''
							|| ( (string) ( $def['preview_description'] ?? '' ) ) !== '';
						if ( ! $has_preview ) {
							$preview_readiness = false;
						}
					}
				}
				$cta_warnings   = $this->compute_cta_warnings( $ordered_list, $section_by_key );
				$insertion_hint = $this->compute_insertion_hint( $ordered_list, $section_by_key );
			}
		} else {
			$insertion_hint = __( 'Add a section to start. Prefer a hero or opener first; end with a CTA section.', 'aio-page-builder' );
		}

		$validation_status = '';
		$validation_codes  = array();
		$validation_result = null;
		if ( $current_composition !== null ) {
			$validation_status = (string) ( $current_composition[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? '' );
			$validation_codes  = $current_composition[ Composition_Schema::FIELD_VALIDATION_CODES ] ?? array();
			if ( ! is_array( $validation_codes ) ) {
				$validation_codes = array();
			}
			if ( $this->large_validator !== null ) {
				$result            = $this->large_validator->validate( $current_composition );
				$validation_result = $result->to_array();
			}
		}

		$one_pager_ready = $current_composition !== null
			&& ( (string) ( $current_composition[ Composition_Schema::FIELD_HELPER_ONE_PAGER_REF ] ?? '' ) ) !== '';

		$base_url = \admin_url( 'admin.php?page=' . self::SCREEN_SLUG );

		return array(
			'filter_state'             => $filter_state->to_array(),
			'section_result'           => array(
				'rows'           => $section_result->get_rows(),
				'pagination'     => $section_result->get_pagination()->to_array(),
				'total_matching' => $section_result->get_total_matching(),
			),
			'ordered_sections_display' => $ordered_sections_display,
			'cta_warnings'             => $cta_warnings,
			'insertion_hint'           => $insertion_hint,
			'validation_status'        => $validation_status,
			'validation_codes'         => $validation_codes,
			'validation_result'        => $validation_result,
			'preview_readiness'        => $preview_readiness,
			'one_pager_ready'          => $one_pager_ready,
			'base_url'                 => $base_url,
			'current_composition'      => $current_composition,
			'category_labels'          => $this->get_category_labels(),
			'cta_labels'               => array(
				'primary_cta'    => __( 'Primary CTA', 'aio-page-builder' ),
				'contact_cta'    => __( 'Contact CTA', 'aio-page-builder' ),
				'navigation_cta' => __( 'Navigation CTA', 'aio-page-builder' ),
				'none'           => __( 'None', 'aio-page-builder' ),
			),
		);
	}

	private function build_filter_state( array $request_params ): Composition_Filter_State {
		$purpose_family       = isset( $request_params['purpose_family'] ) ? \sanitize_key( (string) $request_params['purpose_family'] ) : '';
		$category             = isset( $request_params['category'] ) ? \sanitize_key( (string) $request_params['category'] ) : '';
		$cta_classification   = isset( $request_params['cta_classification'] ) ? \sanitize_key( (string) $request_params['cta_classification'] ) : '';
		$variation_family_key = isset( $request_params['variation_family_key'] ) ? \sanitize_key( (string) $request_params['variation_family_key'] ) : '';
		$search               = isset( $request_params['search'] ) ? \sanitize_text_field( (string) $request_params['search'] ) : '';
		$status               = isset( $request_params['status'] ) ? \sanitize_key( (string) $request_params['status'] ) : '';
		$paged                = isset( $request_params['paged'] ) ? max( 1, (int) $request_params['paged'] ) : 1;
		$per_page             = isset( $request_params['per_page'] ) ? max( 1, min( Large_Library_Query_Service::MAX_PER_PAGE, (int) ( $request_params['per_page'] ?? 25 ) ) ) : Large_Library_Query_Service::DEFAULT_PER_PAGE;
		return new Composition_Filter_State( $purpose_family, $category, $cta_classification, $variation_family_key, $search, $status, $paged, $per_page );
	}

	/**
	 * @param array<int, array<string, mixed>>          $ordered
	 * @param array<string, array<string, mixed>> $section_by_key
	 * @return array<int, array{code: string, message: string}>
	 */
	private function compute_cta_warnings( array $ordered, array $section_by_key ): array {
		$warnings  = array();
		$cta_flags = array();
		foreach ( $ordered as $item ) {
			$key         = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			$def         = $section_by_key[ $key ] ?? null;
			$cta_flags[] = $def !== null && in_array( (string) ( $def['cta_classification'] ?? '' ), self::CTA_CLASSIFIED, true );
		}
		if ( count( $cta_flags ) > 0 ) {
			$last = end( $cta_flags );
			if ( ! $last ) {
				$warnings[] = array(
					'code'    => 'bottom_cta_missing',
					'message' => __( 'Last section should be CTA-classified for best practice.', 'aio-page-builder' ),
				);
			}
		}
		for ( $i = 0; $i < count( $cta_flags ) - 1; $i++ ) {
			if ( $cta_flags[ $i ] && $cta_flags[ $i + 1 ] ) {
				$warnings[] = array(
					'code'    => 'adjacent_cta',
					'message' => __( 'Two CTA sections are adjacent; add a non-CTA section between them.', 'aio-page-builder' ),
				);
				break;
			}
		}
		return $warnings;
	}

	/**
	 * @param array<int, array<string, mixed>>          $ordered
	 * @param array<string, array<string, mixed>> $section_by_key
	 */
	private function compute_insertion_hint( array $ordered, array $section_by_key ): string {
		if ( count( $ordered ) === 0 ) {
			return __( 'Add a section to start. Prefer a hero or opener first; end with a CTA section.', 'aio-page-builder' );
		}
		$cta_flags = array();
		foreach ( $ordered as $item ) {
			$key         = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			$def         = $section_by_key[ $key ] ?? null;
			$cta_flags[] = $def !== null && in_array( (string) ( $def['cta_classification'] ?? '' ), self::CTA_CLASSIFIED, true );
		}
		$last = end( $cta_flags );
		if ( $last ) {
			return __( 'Next section should be non-CTA to avoid adjacent CTAs. Add content (proof, feature, FAQ, etc.) then another CTA later.', 'aio-page-builder' );
		}
		return __( 'You can add a CTA section to end the composition, or add more content sections first.', 'aio-page-builder' );
	}

	/** @return array<string, string> */
	private function get_category_labels(): array {
		return array(
			'hero_intro'       => __( 'Hero / Intro', 'aio-page-builder' ),
			'trust_proof'      => __( 'Trust / Proof', 'aio-page-builder' ),
			'cta_conversion'   => __( 'CTA', 'aio-page-builder' ),
			'faq'              => __( 'FAQ', 'aio-page-builder' ),
			'legal_disclaimer' => __( 'Legal', 'aio-page-builder' ),
		);
	}
}
