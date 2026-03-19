<?php
/**
 * Unit tests for Build Plan Step 5 (SEO/media) truthfulness (advisory-only).
 *
 * Ensures the step does not surface misleading "apply/execute" semantics and
 * that row/detail payload content reflects the recommendation-only contract.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\BuildPlan;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../../wordpress/' );

$plugin_root = dirname( __DIR__, 4 );

require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/SEO/SEO_Media_Step_UI_Service.php';

final class SeoMediaStepUiServiceTest extends TestCase {

	/** Builds a minimal plan definition that places the provided items at the given step index. */
	private function plan_definition_with_step_at( int $step_index, string $step_type, array $items = array() ): array {
		$step_types = array(
			Build_Plan_Schema::STEP_TYPE_OVERVIEW,
			Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
			Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
			Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW,
			Build_Plan_Schema::STEP_TYPE_NAVIGATION,
			Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS,
			Build_Plan_Schema::STEP_TYPE_SEO,
			Build_Plan_Schema::STEP_TYPE_CONFIRMATION,
			Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK,
		);

		$steps = array();
		foreach ( $step_types as $i => $type ) {
			$steps[] = array(
				Build_Plan_Item_Schema::KEY_STEP_TYPE => $type,
				Build_Plan_Item_Schema::KEY_ITEMS     => $i === $step_index ? $items : array(),
			);
		}

		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-seo-1',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	public function test_seo_step_is_advisory_only_in_messages_and_bulk_labels(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$service  = new SEO_Media_Step_UI_Service( $resolver );
		$items    = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'About Us',
					'confidence'               => 'medium',
				),
			),
		);

		$def = $this->plan_definition_with_step_at(
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			Build_Plan_Schema::STEP_TYPE_SEO,
			$items
		);

		$workspace = $service->build_workspace(
			$def,
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			array( 'can_approve' => true, 'can_execute' => true, 'can_view_artifacts' => false ),
			null,
			array()
		);

		$this->assertNotEmpty( $workspace['step_messages'] );
		$this->assertStringContainsString( 'advisory', strtolower( (string) $workspace['step_messages'][0]['message'] ) );
		$this->assertStringNotContainsString( 'execute', strtolower( (string) $workspace['step_messages'][0]['message'] ) );

		$bulk_all_label = (string) ( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL ]['label'] ?? '' );
		$this->assertStringNotContainsString( 'Apply', $bulk_all_label );
		$this->assertStringContainsString( 'Approve', $bulk_all_label );

		$bulk_sel_label = (string) ( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED ]['label'] ?? '' );
		$this->assertStringNotContainsString( 'Apply', $bulk_sel_label );
		$this->assertStringContainsString( 'Approve', $bulk_sel_label );
	}

	public function test_seo_row_summary_has_truthful_placeholder_values_for_missing_fields(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$service  = new SEO_Media_Step_UI_Service( $resolver );
		$items    = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'About Us',
					'confidence'               => 'high',
				),
			),
		);

		$def = $this->plan_definition_with_step_at(
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			Build_Plan_Schema::STEP_TYPE_SEO,
			$items
		);

		$workspace = $service->build_workspace(
			$def,
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			array( 'can_approve' => true ),
			null,
			array()
		);

		$row     = $workspace['step_list_rows'][0];
		$summary = (array) ( $row[ Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS ] ?? array() );

		$this->assertSame( '—', (string) ( $summary['current'] ?? '' ) );
		$this->assertSame( '—', (string) ( $summary['proposed'] ?? '' ) );
		$this->assertStringContainsString( 'advisory', strtolower( (string) ( $summary['action_type'] ?? '' ) ) );
		$this->assertSame( 'high', (string) ( $summary['confidence'] ?? '' ) );
	}

	public function test_seo_detail_panel_is_recommendation_only_and_mentions_no_direct_write_execution(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$service  = new SEO_Media_Step_UI_Service( $resolver );
		$items    = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::APPROVED,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'About Us',
					'confidence'               => 'high',
				),
			),
		);

		$def = $this->plan_definition_with_step_at(
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			Build_Plan_Schema::STEP_TYPE_SEO,
			$items
		);

		$workspace = $service->build_workspace(
			$def,
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			array( 'can_approve' => true, 'can_execute' => true ),
			'plan_seo_0',
			array()
		);

		$rec_section = null;
		foreach ( $workspace['detail_panel']['sections'] as $sec ) {
			if ( (string) ( $sec['key'] ?? '' ) === 'recommendations' ) {
				$rec_section = $sec;
				break;
			}
		}

		$this->assertNotNull( $rec_section );
		$lines = (array) ( $rec_section['content_lines'] ?? array() );
		$this->assertNotEmpty( $lines );
		$this->assertStringContainsString( 'advisory', strtolower( (string) ( $lines[0] ?? '' ) ) );
		$this->assertStringContainsString( 'no direct write', strtolower( \implode( ' ', $lines ) ) );

		// Row actions for SEO must never offer execute/retry affordances.
		$this->assertNotEmpty( $workspace['detail_panel']['row_actions'] );
		foreach ( $workspace['detail_panel']['row_actions'] as $action ) {
			$action_id = (string) ( $action['action_id'] ?? '' );
			$this->assertNotContains( $action_id, array( 'execute', 'retry' ) );
		}
	}
}

