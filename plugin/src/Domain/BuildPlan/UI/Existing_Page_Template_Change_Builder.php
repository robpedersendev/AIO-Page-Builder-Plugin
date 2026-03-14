<?php
/**
 * Builds template-change and replacement-reason payloads for Build Plan Step 1 (existing-page update/replacement) (spec §32, §32.3, §32.7, Prompt 193).
 *
 * Produces existing_page_template_change_summary (proposed template family, variation, CTA posture)
 * and replacement_reason_summary (replacement vs update reasoning) so operators see why a page
 * is recommended for rebuild or replacement against the expanded library.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Recommendations\Template_Explanation_Builder_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;

/**
 * Enriches existing_page_change items with template-family and replacement-vs-update explainability.
 *
 * Example existing_page_template_change_summary payload:
 * [
 *   'template_key'             => 'pt_services_hub_01',
 *   'name'                     => 'Services hub',
 *   'template_family'          => 'services',
 *   'template_category_class' => 'hub',
 *   'cta_direction_summary'   => 'Contact, request quote',
 *   'section_count'            => 8,
 *   'deprecation_status'       => 'active',
 * ]
 *
 * Example replacement_reason_summary payload:
 * [
 *   'action_label'   => 'Full replacement (new page)',
 *   'action'         => 'replace_with_new_page',
 *   'is_replacement' => true,
 *   'is_rebuild'     => false,
 *   'reason_short'   => 'Align with new site structure and CTA flow.',
 * ]
 */
final class Existing_Page_Template_Change_Builder {

	/** Payload key for proposed template summary (family, CTA, section count). */
	public const KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY = 'existing_page_template_change_summary';

	/** Payload key for replacement vs update reasoning. */
	public const KEY_REPLACEMENT_REASON_SUMMARY = 'replacement_reason_summary';

	/** Actions that create a new page and archive the existing one (full replacement). */
	private const REPLACEMENT_ACTIONS = array( 'replace_with_new_page', 'merge_and_archive' );

	/** Action that rebuilds in place from a template. */
	private const REBUILD_ACTION = 'rebuild_from_template';

	/** @var Template_Explanation_Builder_Interface */
	private Template_Explanation_Builder_Interface $template_explanation_builder;

	public function __construct( Template_Explanation_Builder_Interface $template_explanation_builder ) {
		$this->template_explanation_builder = $template_explanation_builder;
	}

	/**
	 * Builds template-change and replacement-reason payloads for one existing_page_change item.
	 *
	 * @param array<string, mixed> $item Plan item (item_id, item_type, payload, status).
	 * @return array<string, mixed> Keys: existing_page_template_change_summary, replacement_reason_summary.
	 */
	public function build_for_item( array $item ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$template_key = (string) ( $payload['target_template'] ?? $payload['template_key'] ?? $payload['target_template_key'] ?? '' );
		$template_key = \sanitize_key( $template_key );
		$action       = (string) ( $payload['action'] ?? '' );
		$reason       = (string) ( $payload['reason'] ?? '' );

		$existing_page_template_change_summary = array();
		if ( $template_key !== '' ) {
			$explanation = $this->template_explanation_builder->build_explanation( $template_key, $payload );
			$existing_page_template_change_summary = array(
				'template_key'             => $template_key,
				'name'                     => (string) ( $explanation['name'] ?? '' ),
				'template_family'          => (string) ( $explanation['template_family'] ?? '' ),
				'template_category_class' => (string) ( $explanation['template_category_class'] ?? '' ),
				'cta_direction_summary'   => (string) ( $explanation['cta_direction_summary'] ?? '' ),
				'section_count'           => (int) ( $explanation['section_count'] ?? 0 ),
				'deprecation_status'       => (string) ( $explanation['deprecation_status'] ?? 'active' ),
			);
		}

		$replacement_reason_summary = $this->build_replacement_reason_summary( $action, $reason );

		return array(
			self::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY => $existing_page_template_change_summary,
			self::KEY_REPLACEMENT_REASON_SUMMARY           => $replacement_reason_summary,
		);
	}

	/**
	 * Builds replacement vs update reasoning from action and reason (spec §32.7).
	 *
	 * @param string $action Plan action (replace_with_new_page, rebuild_from_template, keep, merge_and_archive, defer).
	 * @param string $reason Optional reason text.
	 * @return array<string, mixed> action_label, is_replacement, is_rebuild, reason_short.
	 */
	private function build_replacement_reason_summary( string $action, string $reason ): array {
		$is_replacement = in_array( $action, self::REPLACEMENT_ACTIONS, true );
		$is_rebuild     = $action === self::REBUILD_ACTION;
		$action_label   = $this->action_to_label( $action );
		$reason_short   = $reason !== '' ? $this->truncate_reason( $reason, 120 ) : '';
		return array(
			'action_label'    => $action_label,
			'action'          => $action,
			'is_replacement'  => $is_replacement,
			'is_rebuild'      => $is_rebuild,
			'reason_short'    => $reason_short,
		);
	}

	private function action_to_label( string $action ): string {
		$labels = array(
			'replace_with_new_page' => \__( 'Full replacement (new page)', 'aio-page-builder' ),
			'merge_and_archive'     => \__( 'Merge and archive (new page)', 'aio-page-builder' ),
			'rebuild_from_template' => \__( 'In-place rebuild from template', 'aio-page-builder' ),
			'keep'                  => \__( 'No structural change', 'aio-page-builder' ),
			'defer'                 => \__( 'Deferred', 'aio-page-builder' ),
		);
		return $labels[ $action ] ?? $action;
	}

	private function truncate_reason( string $reason, int $max_len ): string {
		$reason = trim( $reason );
		if ( strlen( $reason ) <= $max_len ) {
			return $reason;
		}
		return substr( $reason, 0, $max_len - 3 ) . '...';
	}
}
