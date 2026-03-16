<?php
/**
 * Builds detail panel sections for Step 2 new-page items (spec §33.3–33.10).
 *
 * Renders proposed metadata, parent/child hierarchy, dependency validation,
 * post-build status placeholder, and retry/recovery messaging.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\BuildPlan\Industry_Build_Plan_Explanation_View_Model;
use AIOPageBuilder\Domain\BuildPlan\Recommendations\Build_Plan_Template_Explanation_Builder;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;

/**
 * Produces detail_panel sections for one new_page item.
 * Payload: proposed_page_title, proposed_slug, purpose, template_key, menu_eligible, section_guidance, confidence;
 * optional: page_type, parent_ref, hierarchy_position, intended_parent, intended_children, dependency_blocking_reasons, post_build_status, retry_message.
 * When Build_Plan_Template_Explanation_Builder is provided, adds a "Template rationale" section (Prompt 190).
 */
final class New_Page_Creation_Detail_Builder {

	/** @var Build_Plan_Template_Explanation_Builder|null */
	private ?Build_Plan_Template_Explanation_Builder $template_explanation_builder;

	public function __construct( ?Build_Plan_Template_Explanation_Builder $template_explanation_builder = null ) {
		$this->template_explanation_builder = $template_explanation_builder;
	}

	/**
	 * Builds sections for the detail panel. Escapes output; no raw AI artifacts.
	 *
	 * @param array<string, mixed> $item    Plan item (item_id, item_type, payload, status, ...).
	 * @param string|null          $plan_id Optional plan ID for industry override section (Prompt 369).
	 * @param array<string, mixed> $context Optional. Keys: primary_industry_key, compliance_warning_resolver (Prompt 407).
	 * @return array<int, array<string, mixed>> Sections with heading, key, content or content_lines.
	 */
	public function build_sections( array $item, ?string $plan_id = null, array $context = array() ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$status  = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
		$sections = array();

		$sections[] = $this->section_metadata( $payload );
		$template_rationale = $this->section_template_rationale( $payload );
		if ( $template_rationale !== null ) {
			$sections[] = $template_rationale;
		}
		$industry_section = $this->section_industry_explanation( $payload, $context );
		if ( $industry_section !== null ) {
			$sections[] = $industry_section;
		}
		if ( $plan_id !== null && $plan_id !== '' ) {
			$override_section = $this->section_industry_override( $item, $plan_id );
			if ( $override_section !== null ) {
				$sections[] = $override_section;
			}
		}
		$sections[] = $this->section_parent_child_hierarchy( $payload );
		$sections[] = $this->section_dependency_validation( $payload, $item );
		$sections[] = $this->section_post_build_status( $status, $payload );
		$sections[] = $this->section_retry_recovery( $payload, $status );

		return array_filter( $sections, static function ( $s ) {
			return $s !== null && is_array( $s );
		} );
	}

	/**
	 * Template rationale section from Build_Plan_Template_Explanation_Builder when template_key is set (Prompt 190).
	 *
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|null Section or null when no template_key or no builder.
	 */
	private function section_template_rationale( array $payload ): ?array {
		$template_key = (string) ( $payload['template_key'] ?? '' );
		if ( $template_key === '' || $this->template_explanation_builder === null ) {
			return null;
		}
		$explanation = $this->template_explanation_builder->build_explanation( $template_key, $payload );
		$lines = isset( $explanation['explanation_lines'] ) && is_array( $explanation['explanation_lines'] )
			? $explanation['explanation_lines']
			: array();
		$escaped = array_map( function ( $line ) {
			return \esc_html( (string) $line );
		}, $lines );
		if ( $escaped === array() ) {
			$escaped[] = \esc_html( $explanation['template_key'] ?? $template_key );
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING       => \__( 'Template rationale', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY           => 'template_rationale',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $escaped,
		);
	}

	/**
	 * Industry context section from item payload (Prompt 365). Renders rationale, fit, warnings, and advisory compliance cautions (Prompt 407).
	 *
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $context Optional. primary_industry_key (string), compliance_warning_resolver (Industry_Compliance_Warning_Resolver).
	 * @return array<string, mixed>|null Section or null when no industry data.
	 */
	private function section_industry_explanation( array $payload, array $context = array() ): ?array {
		$compliance_warnings = array();
		$primary_industry   = isset( $context['primary_industry_key'] ) && is_string( $context['primary_industry_key'] ) ? trim( $context['primary_industry_key'] ) : '';
		$resolver           = isset( $context['compliance_warning_resolver'] ) && $context['compliance_warning_resolver'] instanceof Industry_Compliance_Warning_Resolver
			? $context['compliance_warning_resolver']
			: null;
		if ( $primary_industry !== '' && $resolver !== null ) {
			$compliance_warnings = $resolver->get_for_display( $primary_industry );
		}
		$view_model = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload, $compliance_warnings );
		if ( empty( $view_model['has_industry_data'] ) ) {
			return null;
		}
		\ob_start();
		$view_model = $view_model;
		require \dirname( __DIR__, 4 ) . '/Admin/Views/build-plan/industry-plan-explanations.php';
		$content = (string) \ob_get_clean();
		if ( $content === '' ) {
			return null;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Industry context', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'industry_explanation',
			Detail_Panel_Component::SECTION_KEY_CONTENT => $content,
		);
	}

	/**
	 * Industry override section when plan_id set and item has industry warnings (Prompt 369). Form or overridden state.
	 *
	 * @param array<string, mixed> $item
	 * @param string               $plan_id
	 * @return array<string, mixed>|null
	 */
	private function section_industry_override( array $item, string $plan_id ): ?array {
		$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
		if ( $item_id === '' ) {
			return null;
		}
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$warnings = isset( $payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ] ) && is_array( $payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ] )
			? $payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ]
			: array();
		$has_warnings = count( $warnings ) > 0;
		$override_service = new Industry_Build_Plan_Item_Override_Service();
		$override = $override_service->get_override( $plan_id, $item_id );
		if ( $override !== null ) {
			$reason = (string) ( $override[ \AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema::FIELD_REASON ] ?? '' );
			$content = '<p class="aio-plan-item-overridden"><strong>' . \esc_html__( 'Overridden', 'aio-page-builder' ) . '</strong>';
			if ( $reason !== '' ) {
				$content .= ' <span class="aio-override-reason">' . \esc_html( $reason ) . '</span>';
			}
			$content .= '</p>';
			return array(
				Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Industry override', 'aio-page-builder' ),
				Detail_Panel_Component::SECTION_KEY_KEY   => 'industry_override',
				Detail_Panel_Component::SECTION_KEY_CONTENT => $content,
			);
		}
		if ( ! $has_warnings ) {
			return null;
		}
		$action_url = \admin_url( 'admin-post.php' );
		$nonce_action = \AIOPageBuilder\Admin\Actions\Save_Industry_Build_Plan_Override_Action::NONCE_ACTION;
		$nonce_name = \AIOPageBuilder\Admin\Actions\Save_Industry_Build_Plan_Override_Action::NONCE_NAME;
		$referer = \esc_attr( \wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$content = '<form method="post" action="' . \esc_url( $action_url ) . '" class="aio-industry-override-form">';
		$content .= '<input type="hidden" name="action" value="aio_save_industry_build_plan_override" />';
		$content .= \wp_nonce_field( $nonce_action, $nonce_name, true, false );
		$content .= '<input type="hidden" name="plan_id" value="' . \esc_attr( $plan_id ) . '" />';
		$content .= '<input type="hidden" name="item_id" value="' . \esc_attr( $item_id ) . '" />';
		$content .= '<input type="hidden" name="state" value="accepted" />';
		$content .= '<input type="hidden" name="_wp_http_referer" value="' . $referer . '" />';
		$content .= '<p><label for="aio_override_reason_' . \esc_attr( $item_id ) . '">' . \esc_html__( 'Review note (optional)', 'aio-page-builder' ) . '</label><br />';
		$content .= '<textarea id="aio_override_reason_' . \esc_attr( $item_id ) . '" name="reason" rows="2" class="large-text" maxlength="500"></textarea></p>';
		$content .= '<p><button type="submit" class="button">' . \esc_html__( 'Accept anyway', 'aio-page-builder' ) . '</button></p>';
		$content .= '</form>';
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Industry override', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'industry_override',
			Detail_Panel_Component::SECTION_KEY_CONTENT => $content,
		);
	}

	private function section_metadata( array $payload ): array {
		$title   = (string) ( $payload['proposed_page_title'] ?? '' );
		$slug    = (string) ( $payload['proposed_slug'] ?? '' );
		$purpose = (string) ( $payload['purpose'] ?? '' );
		$template = (string) ( $payload['template_key'] ?? '' );
		$page_type = (string) ( $payload['page_type'] ?? '' );
		$confidence = (string) ( $payload['confidence'] ?? '' );
		$lines = array();
		if ( $title !== '' ) {
			$lines[] = \__( 'Proposed title:', 'aio-page-builder' ) . ' ' . \esc_html( $title );
		}
		if ( $slug !== '' ) {
			$lines[] = \__( 'Proposed slug:', 'aio-page-builder' ) . ' ' . \esc_html( $slug );
		}
		if ( $purpose !== '' ) {
			$lines[] = \__( 'Purpose:', 'aio-page-builder' ) . ' ' . \esc_html( $purpose );
		}
		if ( $template !== '' ) {
			$lines[] = \__( 'Target template / composition:', 'aio-page-builder' ) . ' ' . \esc_html( $template );
		}
		if ( $page_type !== '' ) {
			$lines[] = \__( 'Page type:', 'aio-page-builder' ) . ' ' . \esc_html( $page_type );
		}
		if ( $confidence !== '' ) {
			$lines[] = \__( 'Confidence:', 'aio-page-builder' ) . ' ' . \esc_html( $confidence );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Page metadata', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'metadata',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_parent_child_hierarchy( array $payload ): array {
		$parent   = (string) ( $payload['intended_parent'] ?? $payload['parent_ref'] ?? '' );
		$children = $payload['intended_children'] ?? $payload['child_refs'] ?? array();
		$position = (string) ( $payload['hierarchy_position'] ?? '' );
		$role     = (string) ( $payload['hierarchy_role'] ?? $payload['page_type'] ?? '' );
		$lines = array();
		if ( $parent !== '' ) {
			$lines[] = \__( 'Intended parent:', 'aio-page-builder' ) . ' ' . \esc_html( $parent );
		}
		if ( is_array( $children ) && ! empty( $children ) ) {
			$lines[] = \__( 'Intended child pages:', 'aio-page-builder' ) . ' ' . \esc_html( implode( ', ', array_map( 'strval', $children ) ) );
		}
		if ( $position !== '' ) {
			$lines[] = \__( 'Hierarchy position:', 'aio-page-builder' ) . ' ' . \esc_html( $position );
		}
		if ( $role !== '' ) {
			$lines[] = \__( 'Hub / branch / leaf:', 'aio-page-builder' ) . ' ' . \esc_html( $role );
		}
		if ( $lines === array() ) {
			$lines[] = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Parent / child hierarchy', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'parent_child',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_dependency_validation( array $payload, array $item ): array {
		$reasons = $payload['dependency_blocking_reasons'] ?? $payload['blocking_reasons'] ?? array();
		if ( ! is_array( $reasons ) ) {
			$reasons = array();
		}
		$lines = array();
		foreach ( $reasons as $r ) {
			$lines[] = is_string( $r ) ? $r : (string) \wp_json_encode( $r );
		}
		if ( $lines === array() ) {
			$lines[] = \__( 'No blocking dependencies reported.', 'aio-page-builder' );
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Dependency validation', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'dependency_validation',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_post_build_status( string $status, array $payload ): array {
		$post_status = (string) ( $payload['post_build_status'] ?? '' );
		$lines = array( \__( 'Current Build Plan state:', 'aio-page-builder' ) . ' ' . $status );
		if ( $post_status !== '' ) {
			$lines[] = \__( 'Post-build placeholder:', 'aio-page-builder' ) . ' ' . $post_status;
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Post-build status', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'post_build_status',
			Detail_Panel_Component::SECTION_KEY_CONTENT_LINES => $lines,
		);
	}

	private function section_retry_recovery( array $payload, string $status ): array {
		$message = (string) ( $payload['retry_message'] ?? $payload['recovery_message'] ?? '' );
		if ( $message === '' && $status === 'failed' ) {
			$message = \__( 'This item can be retried after resolving dependency or execution issues.', 'aio-page-builder' );
		}
		if ( $message === '' ) {
			$message = '—';
		}
		return array(
			Detail_Panel_Component::SECTION_KEY_HEADING => \__( 'Retry and recovery', 'aio-page-builder' ),
			Detail_Panel_Component::SECTION_KEY_KEY     => 'retry_recovery',
			Detail_Panel_Component::SECTION_KEY_CONTENT => '<p>' . \esc_html( $message ) . '</p>',
		);
	}
}
