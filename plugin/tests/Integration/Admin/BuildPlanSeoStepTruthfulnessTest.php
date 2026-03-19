<?php
/**
 * Integration-style tests for Step 5 SEO truthfulness.
 *
 * Verifies that the SEO step does not surface execution affordances and that
 * it consistently communicates recommendation-only behavior.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Admin;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );

require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/SEO/SEO_Media_Step_UI_Service.php';

final class BuildPlanSeoStepTruthfulnessTest extends TestCase {

	/** Builds a minimal plan definition that places the provided items at the given step index. */
	private function plan_definition_with_step_at( int $step_index, array $items = array() ): array {
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
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-seo-2',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	public function test_seo_step_never_offers_execute_or_apply_semantics(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$service  = new SEO_Media_Step_UI_Service( $resolver );

		$items = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'https://example.org/about',
					'confidence'               => 'medium',
				),
			),
		);

		$def = $this->plan_definition_with_step_at(
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			$items
		);

		$workspace = $service->build_workspace(
			$def,
			SEO_Media_Step_UI_Service::STEP_INDEX_SEO,
			array( 'can_approve' => true, 'can_execute' => true ),
			null,
			array()
		);

		// Step messages must communicate advisory posture.
		$message = strtolower( (string) ( $workspace['step_messages'][0]['message'] ?? '' ) );
		$this->assertStringContainsString( 'advisory', $message );
		$this->assertStringNotContainsString( 'execute', $message );
		$this->assertStringNotContainsString( 'apply', $message );

		// Bulk labels are review-only; no execute/apply semantics.
		foreach ( $workspace['bulk_action_states'] as $state ) {
			$label = strtolower( (string) ( $state['label'] ?? '' ) );
			$this->assertStringNotContainsString( 'apply', $label );
			$this->assertStringNotContainsString( 'execute', $label );
		}

		// Row actions must never include execute/retry for SEO items.
		$row_actions = (array) ( $workspace['step_list_rows'][0]['row_actions'] ?? array() );
		foreach ( $row_actions as $a ) {
			$action_id = (string) ( $a['action_id'] ?? '' );
			$this->assertNotContains( $action_id, array( 'execute', 'retry' ) );
		}
	}
}

