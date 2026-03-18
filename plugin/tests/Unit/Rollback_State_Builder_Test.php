<?php
/**
 * Unit tests for Rollback_State_Builder (spec §59.11; Prompt 197).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summary_Result;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summarizer_Service;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Type_Keys;
use AIOPageBuilder\Domain\Rollback\Diffs\Page_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Rollback\UI\Rollback_State_Builder;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Result;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Summary_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Page_Diff_Summarizer.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Navigation_Diff_Summarizer.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Token_Diff_Summarizer.php';
require_once $plugin_root . '/src/Domain/Rollback/Diff/Template_Diff_Context.php';
require_once $plugin_root . '/src/Domain/Rollback/Diff/Template_Diff_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Summarizer_Service.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Blocking_Reasons.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Service.php';
require_once $plugin_root . '/src/Domain/Rollback/UI/Rollback_State_Builder.php';

final class Rollback_State_Builder_Test extends TestCase {

	public function test_build_returns_eligibility_diff_result_and_rollback_template_context(): void {
		$repo    = new Operational_Snapshot_Repository();
		$pre_id  = 'op-snap-pre-1';
		$post_id = 'op-snap-post-1';
		$pre     = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $pre_id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '42',
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_replace_1',
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => '2025-03-12T10:00:00+00:00',
			Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS => Operational_Snapshot_Schema::ROLLBACK_STATUS_AVAILABLE,
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'state_snapshot' => array(
					'post_id'               => 42,
					'post_title'            => 'About',
					'post_name'             => 'about',
					'intended_template_key' => 'tpl_old',
				),
			),
		);
		$post    = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $post_id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '42',
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_replace_1',
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => '2025-03-12T10:01:00+00:00',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => array(
				'result_snapshot' => array(
					'post_id'          => 42,
					'post_title'       => 'About Us',
					'post_name'        => 'about-us',
					'post_status'      => 'publish',
					'template_context' => array(
						'template_key'    => 'tpl_services_hub',
						'template_family' => 'services',
						'section_count'   => 5,
					),
				),
			),
		);
		$repo->save( $pre );
		$repo->save( $post );

		$eligibility      = new Rollback_Eligibility_Service( $repo );
		$template_builder = new \AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Summary_Builder();
		$diff_summarizer  = new Diff_Summarizer_Service(
			$repo,
			new Page_Diff_Summarizer(),
			new \AIOPageBuilder\Domain\Rollback\Diffs\Navigation_Diff_Summarizer(),
			new \AIOPageBuilder\Domain\Rollback\Diffs\Token_Diff_Summarizer(),
			$template_builder
		);
		$state_builder    = new Rollback_State_Builder( $eligibility, $diff_summarizer );

		$state = $state_builder->build( $pre_id, $post_id, Diff_Type_Keys::LEVEL_SUMMARY, array( 'skip_permission_check' => true ) );

		$this->assertArrayHasKey( 'eligibility', $state );
		$this->assertArrayHasKey( 'diff_result', $state );
		$this->assertArrayHasKey( 'rollback_template_context', $state );
		$this->assertInstanceOf( Rollback_Eligibility_Result::class, $state['eligibility'] );
		$this->assertInstanceOf( Diff_Summary_Result::class, $state['diff_result'] );
		$this->assertIsArray( $state['rollback_template_context'] );
		// When diff succeeds and has template_diff_summary, rollback_template_context is populated.
		if ( $state['diff_result']->is_success() ) {
			$this->assertArrayHasKey( 'template_key', $state['rollback_template_context'] );
			$this->assertSame( 'tpl_services_hub', $state['rollback_template_context']['template_key'] );
		}
	}

	public function test_build_rollback_template_context_empty_when_diff_fails(): void {
		$repo            = new Operational_Snapshot_Repository();
		$eligibility     = new Rollback_Eligibility_Service( $repo );
		$diff_summarizer = new Diff_Summarizer_Service(
			$repo,
			new Page_Diff_Summarizer(),
			new \AIOPageBuilder\Domain\Rollback\Diffs\Navigation_Diff_Summarizer(),
			new \AIOPageBuilder\Domain\Rollback\Diffs\Token_Diff_Summarizer(),
			new \AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Summary_Builder()
		);
		$state_builder   = new Rollback_State_Builder( $eligibility, $diff_summarizer );

		$state = $state_builder->build( 'nonexistent-pre', 'nonexistent-post', Diff_Type_Keys::LEVEL_SUMMARY, array( 'skip_permission_check' => true ) );

		$this->assertArrayHasKey( 'rollback_template_context', $state );
		$this->assertSame( array(), $state['rollback_template_context'] );
		$this->assertFalse( $state['diff_result']->is_success() );
	}
}
