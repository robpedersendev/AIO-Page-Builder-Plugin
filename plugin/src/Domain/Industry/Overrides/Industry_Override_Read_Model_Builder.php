<?php
/**
 * Builds a unified read model of industry overrides for listing/filtering (Prompt 436).
 * Aggregates section, page template, and Build Plan item overrides; supports filters.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a list of override rows from all scopes with optional filters.
 */
final class Industry_Override_Read_Model_Builder {

	/** @var Industry_Section_Override_Service */
	private $section_service;

	/** @var Industry_Page_Template_Override_Service */
	private $page_template_service;

	/** @var Industry_Build_Plan_Item_Override_Service */
	private $build_plan_service;

	public function __construct(
		?Industry_Section_Override_Service $section_service = null,
		?Industry_Page_Template_Override_Service $page_template_service = null,
		?Industry_Build_Plan_Item_Override_Service $build_plan_service = null
	) {
		$this->section_service       = $section_service ?? new Industry_Section_Override_Service();
		$this->page_template_service = $page_template_service ?? new Industry_Page_Template_Override_Service();
		$this->build_plan_service    = $build_plan_service ?? new Industry_Build_Plan_Item_Override_Service();
	}

	/**
	 * Filter keys accepted by build().
	 * - target_type: string (section|page_template|build_plan_item)
	 * - state: string (accepted|rejected)
	 * - reason_present: bool (true = only rows with non-empty reason)
	 * - industry_context_ref: string (exact match on override record; empty = not filtered)
	 */
	public const FILTER_TARGET_TYPE          = 'target_type';
	public const FILTER_STATE                = 'state';
	public const FILTER_REASON_PRESENT       = 'reason_present';
	public const FILTER_INDUSTRY_CONTEXT_REF = 'industry_context_ref';

	/**
	 * Builds a unified list of override rows. Each row has: row_id, target_type, target_key, plan_id (null for section/page_template), state, reason, created_at, updated_at, industry_context_ref, has_reason.
	 *
	 * @param array<string, mixed> $filters Optional filters (FILTER_* keys).
	 * @return list<array<string, mixed>>
	 */
	public function build( array $filters = array() ): array {
		$rows = array();

		$section_overrides = $this->section_service->list_overrides();
		foreach ( $section_overrides as $section_key => $override ) {
			if ( ! is_array( $override ) ) {
				continue;
			}
			$row = $this->normalize_row(
				Industry_Override_Schema::TARGET_TYPE_SECTION,
				$section_key,
				null,
				$override
			);
			if ( $row !== null && $this->passes_filters( $row, $filters ) ) {
				$rows[] = $row;
			}
		}

		$template_overrides = $this->page_template_service->list_overrides();
		foreach ( $template_overrides as $template_key => $override ) {
			if ( ! is_array( $override ) ) {
				continue;
			}
			$row = $this->normalize_row(
				Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE,
				$template_key,
				null,
				$override
			);
			if ( $row !== null && $this->passes_filters( $row, $filters ) ) {
				$rows[] = $row;
			}
		}

		$plan_items = $this->build_plan_service->list_all_overrides();
		foreach ( $plan_items as $entry ) {
			$plan_id  = $entry['plan_id'] ?? '';
			$item_id  = $entry['item_id'] ?? '';
			$override = $entry['override'] ?? array();
			if ( ! is_array( $override ) || $plan_id === '' || $item_id === '' ) {
				continue;
			}
			$row = $this->normalize_row(
				Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
				$item_id,
				$plan_id,
				$override
			);
			if ( $row !== null && $this->passes_filters( $row, $filters ) ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * @param array<string, mixed> $override Raw override record.
	 * @return array<string, mixed>|null Row with row_id, target_type, target_key, plan_id, state, reason, created_at, updated_at, industry_context_ref, has_reason.
	 */
	private function normalize_row( string $target_type, string $target_key, ?string $plan_id, array $override ): ?array {
		$state        = isset( $override[ Industry_Override_Schema::FIELD_STATE ] ) ? (string) $override[ Industry_Override_Schema::FIELD_STATE ] : '';
		$reason       = isset( $override[ Industry_Override_Schema::FIELD_REASON ] ) ? (string) $override[ Industry_Override_Schema::FIELD_REASON ] : '';
		$created      = isset( $override[ Industry_Override_Schema::FIELD_CREATED_AT ] ) ? (int) $override[ Industry_Override_Schema::FIELD_CREATED_AT ] : 0;
		$updated      = isset( $override[ Industry_Override_Schema::FIELD_UPDATED_AT ] ) ? (int) $override[ Industry_Override_Schema::FIELD_UPDATED_AT ] : 0;
		$industry_ref = isset( $override[ Industry_Override_Schema::FIELD_INDUSTRY_CONTEXT_REF ] ) ? (string) $override[ Industry_Override_Schema::FIELD_INDUSTRY_CONTEXT_REF ] : '';

		$row_id = $target_type . ':' . $target_key;
		if ( $plan_id !== null && $plan_id !== '' ) {
			$row_id .= ':' . $plan_id;
		}

		return array(
			'row_id'               => $row_id,
			'target_type'          => $target_type,
			'target_key'           => $target_key,
			'plan_id'              => $plan_id,
			'state'                => $state,
			'reason'               => $reason,
			'created_at'           => $created,
			'updated_at'           => $updated,
			'industry_context_ref' => $industry_ref,
			'has_reason'           => $reason !== '',
		);
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $filters
	 */
	private function passes_filters( array $row, array $filters ): bool {
		if ( isset( $filters[ self::FILTER_TARGET_TYPE ] ) && $filters[ self::FILTER_TARGET_TYPE ] !== '' ) {
			$want = (string) $filters[ self::FILTER_TARGET_TYPE ];
			if ( ( $row['target_type'] ?? '' ) !== $want ) {
				return false;
			}
		}
		if ( isset( $filters[ self::FILTER_STATE ] ) && $filters[ self::FILTER_STATE ] !== '' ) {
			$want = (string) $filters[ self::FILTER_STATE ];
			if ( ( $row['state'] ?? '' ) !== $want ) {
				return false;
			}
		}
		if ( isset( $filters[ self::FILTER_REASON_PRESENT ] ) && $filters[ self::FILTER_REASON_PRESENT ] === true ) {
			if ( empty( $row['has_reason'] ) ) {
				return false;
			}
		}
		if ( isset( $filters[ self::FILTER_INDUSTRY_CONTEXT_REF ] ) && $filters[ self::FILTER_INDUSTRY_CONTEXT_REF ] !== '' ) {
			$want = (string) $filters[ self::FILTER_INDUSTRY_CONTEXT_REF ];
			if ( ( $row['industry_context_ref'] ?? '' ) !== $want ) {
				return false;
			}
		}
		return true;
	}
}
