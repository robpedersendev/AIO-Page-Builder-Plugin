<?php
/**
 * Seeds or resets the E2E Build Plan (Step 2 pending row) for Playwright. Run via WP-CLI:
 * `wp eval-file wp-content/plugins/plugin/tools/e2e-seed-build-plan.php`
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Creates or updates the canonical E2E plan used by Playwright Step 2 tests.
 */
function aio_pb_run_e2e_seed_build_plan(): void {
	$internal_key = 'e2e-step2-deny';

	$items = array(
		array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_0',
			Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
			Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
				'proposed_page_title' => 'E2E Contact',
				'proposed_slug'       => 'e2e-contact',
				'purpose'             => 'E2E seed',
				'template_key'        => 'contact',
				'hierarchy_position'  => 'root',
				'page_type'           => 'landing',
				'confidence'          => 'medium',
			),
		),
	);

	$steps = array(
		array(
			'step_type' => 'overview',
			'items'     => array(),
		),
		array(
			'step_type' => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
			'items'     => array(),
		),
		array(
			Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
			Build_Plan_Item_Schema::KEY_ITEMS     => $items,
		),
	);

	$definition = array(
		Build_Plan_Schema::KEY_PLAN_ID    => $internal_key,
		Build_Plan_Schema::KEY_PLAN_TITLE => 'E2E Step 2 Deny',
		Build_Plan_Schema::KEY_STEPS      => $steps,
	);

	$repo = new Build_Plan_Repository();

	// phpcs:disable WordPress.DB.SlowDBQuery -- One-off CLI seed; not a hot path.
	$query = new \WP_Query(
		array(
			'post_type'              => 'aio_build_plan',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'meta_key'               => '_aio_internal_key',
			'meta_value'             => $internal_key,
			'fields'                 => 'ids',
		)
	);
	// phpcs:enable WordPress.DB.SlowDBQuery

	$post_ids      = $query->posts;
	$existing_id   = isset( $post_ids[0] ) ? (int) $post_ids[0] : 0;
	$saved_post_id = $repo->save(
		array(
			'id'              => $existing_id,
			'plan_definition' => $definition,
			'post_title'      => 'E2E Step 2 Deny',
			'status'          => 'pending_review',
		)
	);

	if ( $saved_post_id <= 0 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI failure path.
		fwrite( STDERR, "e2e-seed-build-plan: save failed.\n" );
		exit( 1 );
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI success line.
	echo 'OK seeded aio_build_plan id=' . (string) $saved_post_id . ' internal_key=' . $internal_key . "\n";
}

aio_pb_run_e2e_seed_build_plan();
