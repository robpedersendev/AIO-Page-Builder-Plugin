<?php
/**
 * Maps normalized AI output sections to Build Plan items; omits or flags non-actionable records (spec §30.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;

/**
 * Converts normalized output section records into plan items. Only produces items when enough data exists.
 * Returns items + omitted list per section. No raw provider output; uses only validated normalized structure.
 */
final class Build_Plan_Item_Generator {

	public const REASON_INSUFFICIENT_DATA    = 'insufficient_data';
	public const REASON_INVALID_REFERENCE    = 'invalid_reference';
	public const REASON_TEMPLATE_UNAVAILABLE = 'template_unavailable';
	public const REASON_SKIPPED_BY_POLICY    = 'skipped_by_policy';

	/**
	 * Generates plan items for a normalized output section. Returns items and omitted entries.
	 *
	 * @param string                           $section        Build_Plan_Draft_Schema section key (e.g. existing_page_changes).
	 * @param array<int, array<string, mixed>> $records Array of record maps from normalized output.
	 * @param string                           $item_id_prefix Prefix for item_id (e.g. plan_xxx).
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	public function generate_for_section( string $section, array $records, string $item_id_prefix ): array {
		$items   = array();
		$omitted = array();

		switch ( $section ) {
			case Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES:
				return $this->map_existing_page_changes( $records, $item_id_prefix );
			case Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE:
				return $this->map_new_pages( $records, $item_id_prefix );
			case Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN:
				return $this->map_menu_changes( $records, $item_id_prefix );
			case Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS:
				return $this->map_design_tokens( $records, $item_id_prefix );
			case Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS:
				return $this->map_seo( $records, $item_id_prefix );
			default:
				return array(
					'items'   => array(),
					'omitted' => array(),
				);
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function map_existing_page_changes( array $records, string $prefix ): array {
		$items   = array();
		$omitted = array();
		foreach ( $records as $i => $rec ) {
			if ( ! is_array( $rec ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $i, self::REASON_INSUFFICIENT_DATA, 'Not an array', null );
				continue;
			}
			$missing = array();
			foreach ( Build_Plan_Draft_Schema::EPC_REQUIRED as $key ) {
				if ( ! array_key_exists( $key, $rec ) || $rec[ $key ] === '' ) {
					$missing[] = $key;
				}
			}
			if ( $missing !== array() ) {
				$omitted[] = Omitted_Recommendation_Report::entry(
					Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES,
					$i,
					self::REASON_INSUFFICIENT_DATA,
					'Missing: ' . implode( ', ', $missing ),
					$rec
				);
				continue;
			}
			$action = $rec[ Build_Plan_Draft_Schema::EPC_ACTION ] ?? '';
			if ( ! in_array( $action, Build_Plan_Draft_Schema::EPC_ENUM_ACTION, true ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $i, self::REASON_INSUFFICIENT_DATA, 'Invalid action', $rec );
				continue;
			}
			$payload = array(
				'current_page_url'   => (string) ( $rec['current_page_url'] ?? '' ),
				'current_page_title' => (string) ( $rec['current_page_title'] ?? '' ),
				'action'             => (string) $action,
				'reason'             => (string) ( $rec['reason'] ?? '' ),
				'risk_level'         => (string) ( $rec['risk_level'] ?? 'low' ),
				'confidence'         => (string) ( $rec[ Build_Plan_Draft_Schema::EPC_CONFIDENCE ] ?? 'medium' ),
			);
			if ( isset( $rec['target_page_title'] ) && is_string( $rec['target_page_title'] ) && trim( $rec['target_page_title'] ) !== '' ) {
				$payload['target_page_title'] = trim( $rec['target_page_title'] );
			}
			if ( isset( $rec['target_slug'] ) && is_string( $rec['target_slug'] ) && trim( $rec['target_slug'] ) !== '' ) {
				$payload['target_slug'] = trim( $rec['target_slug'] );
			}
			if ( isset( $rec['target_template_key'] ) && is_string( $rec['target_template_key'] ) && trim( $rec['target_template_key'] ) !== '' ) {
				$payload['target_template_key'] = trim( $rec['target_template_key'] );
				$payload['template_key']        = trim( $rec['target_template_key'] );
			}
			$this->merge_industry_metadata_into_payload( $payload, $rec );
			$item_id = $prefix . '_epc_' . $i;
			$items[] = $this->build_item(
				$item_id,
				Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
				$payload,
				Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES,
				$i,
				$rec
			);
		}
		return array(
			'items'   => $items,
			'omitted' => $omitted,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function map_new_pages( array $records, string $prefix ): array {
		$items   = array();
		$omitted = array();
		foreach ( $records as $i => $rec ) {
			if ( ! is_array( $rec ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE, $i, self::REASON_INSUFFICIENT_DATA, 'Not an array', null );
				continue;
			}
			$missing = array();
			foreach ( Build_Plan_Draft_Schema::NPC_REQUIRED as $key ) {
				if ( ! array_key_exists( $key, $rec ) || ( is_string( $rec[ $key ] ) && $rec[ $key ] === '' ) ) {
					$missing[] = $key;
				}
			}
			if ( $missing !== array() ) {
				$omitted[] = Omitted_Recommendation_Report::entry(
					Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE,
					$i,
					self::REASON_INSUFFICIENT_DATA,
					'Missing: ' . implode( ', ', $missing ),
					$rec
				);
				continue;
			}
			$payload = array(
				'proposed_page_title' => (string) ( $rec['proposed_page_title'] ?? '' ),
				'proposed_slug'       => (string) ( $rec['proposed_slug'] ?? '' ),
				'purpose'             => (string) ( $rec['purpose'] ?? '' ),
				'template_key'        => (string) ( $rec['template_key'] ?? '' ),
				'menu_eligible'       => (bool) ( $rec['menu_eligible'] ?? false ),
				'section_guidance'    => (string) ( $rec['section_guidance'] ?? '' ),
				'confidence'          => (string) ( $rec[ Build_Plan_Draft_Schema::NPC_CONFIDENCE ] ?? 'medium' ),
			);
			$this->merge_industry_metadata_into_payload( $payload, $rec );
			$item_id = $prefix . '_npc_' . $i;
			$items[] = $this->build_item(
				$item_id,
				Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
				$payload,
				Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE,
				$i,
				$rec
			);
		}
		return array(
			'items'   => $items,
			'omitted' => $omitted,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function map_menu_changes( array $records, string $prefix ): array {
		$items   = array();
		$omitted = array();
		foreach ( $records as $i => $rec ) {
			if ( ! is_array( $rec ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN, $i, self::REASON_INSUFFICIENT_DATA, 'Not an array', null );
				continue;
			}
			$missing = array();
			foreach ( Build_Plan_Draft_Schema::MCP_REQUIRED as $key ) {
				if ( ! array_key_exists( $key, $rec ) ) {
					$missing[] = $key;
				}
			}
			if ( $missing !== array() ) {
				$omitted[] = Omitted_Recommendation_Report::entry(
					Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN,
					$i,
					self::REASON_INSUFFICIENT_DATA,
					'Missing: ' . implode( ', ', $missing ),
					$rec
				);
				continue;
			}
			$item_id = $prefix . '_mcp_' . $i;
			$items[] = $this->build_item(
				$item_id,
				Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE,
				array(
					'menu_context'       => (string) ( $rec[ Build_Plan_Draft_Schema::MCP_MENU_CONTEXT ] ?? '' ),
					'action'             => (string) ( $rec[ Build_Plan_Draft_Schema::MCP_ACTION ] ?? '' ),
					'proposed_menu_name' => (string) ( $rec['proposed_menu_name'] ?? '' ),
					'items'              => is_array( $rec['items'] ?? null ) ? $rec['items'] : array(),
				),
				Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN,
				$i,
				$rec
			);
		}
		return array(
			'items'   => $items,
			'omitted' => $omitted,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function map_design_tokens( array $records, string $prefix ): array {
		$items   = array();
		$omitted = array();
		foreach ( $records as $i => $rec ) {
			if ( ! is_array( $rec ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS, $i, self::REASON_INSUFFICIENT_DATA, 'Not an array', null );
				continue;
			}
			$missing = array();
			foreach ( Build_Plan_Draft_Schema::DTR_REQUIRED as $key ) {
				if ( ! array_key_exists( $key, $rec ) || ( is_string( $rec[ $key ] ) && $rec[ $key ] === '' ) ) {
					$missing[] = $key;
				}
			}
			if ( $missing !== array() ) {
				$omitted[] = Omitted_Recommendation_Report::entry(
					Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
					$i,
					self::REASON_INSUFFICIENT_DATA,
					'Missing: ' . implode( ', ', $missing ),
					$rec
				);
				continue;
			}
			$item_id = $prefix . '_dtr_' . $i;
			$items[] = $this->build_item(
				$item_id,
				Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
				array(
					'token_group'    => (string) ( $rec[ Build_Plan_Draft_Schema::DTR_TOKEN_GROUP ] ?? '' ),
					'token_name'     => (string) ( $rec['token_name'] ?? '' ),
					'proposed_value' => $rec['proposed_value'] ?? '',
					'rationale'      => (string) ( $rec['rationale'] ?? '' ),
					'confidence'     => (string) ( $rec['confidence'] ?? 'medium' ),
				),
				Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
				$i,
				$rec
			);
		}
		return array(
			'items'   => $items,
			'omitted' => $omitted,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array{items: array<int, array<string, mixed>>, omitted: array<int, array<string, mixed>>}
	 */
	private function map_seo( array $records, string $prefix ): array {
		$items   = array();
		$omitted = array();
		foreach ( $records as $i => $rec ) {
			if ( ! is_array( $rec ) ) {
				$omitted[] = Omitted_Recommendation_Report::entry( Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS, $i, self::REASON_INSUFFICIENT_DATA, 'Not an array', null );
				continue;
			}
			$missing = array();
			foreach ( Build_Plan_Draft_Schema::SEO_REQUIRED as $key ) {
				if ( ! array_key_exists( $key, $rec ) ) {
					$missing[] = $key;
				}
			}
			if ( $missing !== array() ) {
				$omitted[] = Omitted_Recommendation_Report::entry(
					Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS,
					$i,
					self::REASON_INSUFFICIENT_DATA,
					'Missing: ' . implode( ', ', $missing ),
					$rec
				);
				continue;
			}
			$item_id = $prefix . '_seo_' . $i;
			$items[] = $this->build_item(
				$item_id,
				Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				array(
					'target_page_title_or_url' => (string) ( $rec['target_page_title_or_url'] ?? '' ),
					'confidence'               => (string) ( $rec['confidence'] ?? 'medium' ),
				),
				Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS,
				$i,
				$rec
			);
		}
		return array(
			'items'   => $items,
			'omitted' => $omitted,
		);
	}

	/**
	 * @param string               $item_id         Item identifier.
	 * @param string               $item_type      Item type key.
	 * @param array<string, mixed> $payload        Item payload.
	 * @param string               $source_section Source section key.
	 * @param int                  $source_index   Source index.
	 * @param array<string, mixed> $record_snapshot For source traceability (redacted by caller if needed).
	 * @return array<string, mixed>
	 */
	private function build_item( string $item_id, string $item_type, array $payload, string $source_section, int $source_index, array $record_snapshot = array() ): array {
		$item = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID        => $item_id,
			Build_Plan_Item_Schema::KEY_ITEM_TYPE      => $item_type,
			Build_Plan_Item_Schema::KEY_PAYLOAD        => $payload,
			Build_Plan_Item_Schema::KEY_SOURCE_SECTION => $source_section,
			Build_Plan_Item_Schema::KEY_SOURCE_INDEX   => $source_index,
			Build_Plan_Item_Schema::KEY_STATUS         => Build_Plan_Item_Statuses::PENDING,
		);
		if ( isset( $payload['confidence'] ) ) {
			$item[ Build_Plan_Item_Schema::KEY_CONFIDENCE ] = $payload['confidence'];
		}
		if ( isset( $payload['risk_level'] ) ) {
			$item[ Build_Plan_Item_Schema::KEY_RISK_LEVEL ] = $payload['risk_level'];
		}
		return $item;
	}

	/**
	 * Merges industry scoring metadata from enriched record into payload (industry-build-plan-scoring-contract.md).
	 *
	 * @param array<string, mixed> $payload Mutable payload to merge into.
	 * @param array<string, mixed> $record Enriched record (may contain industry_source_refs, recommendation_reasons, industry_fit_score, industry_warning_flags).
	 * @return void
	 */
	private function merge_industry_metadata_into_payload( array &$payload, array $record ): void {
		if ( isset( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS ] ) && is_array( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS ] ) ) {
			$payload['industry_source_refs'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS ];
		}
		if ( isset( $record[ Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS ] ) && is_array( $record[ Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS ] ) ) {
			$payload['recommendation_reasons'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS ];
		}
		if ( array_key_exists( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE, $record ) ) {
			$payload['industry_fit_score'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE ];
		}
		if ( isset( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ] ) && is_array( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ] ) ) {
			$payload['industry_warning_flags'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ];
		}
		if ( isset( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ] ) && is_array( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ] ) ) {
			$payload['industry_conflict_results'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ];
		}
		if ( array_key_exists( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY, $record ) && is_string( $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY ] ) ) {
			$payload['industry_explanation_summary'] = $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY ];
		}
		if ( array_key_exists( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_HAS_WARNING, $record ) ) {
			$payload['industry_has_warning'] = (bool) $record[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_HAS_WARNING ];
		}
	}
}
