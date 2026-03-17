<?php
/**
 * Unit tests for Industry_Author_Task_Queue_Service (Prompt 525). Task queue from report inputs.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Author_Task_Queue_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Override_Conflict_Detector.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Author_Task_Queue_Service.php';

final class Industry_Author_Task_Queue_Service_Test extends TestCase {

	public function test_generate_queue_empty_inputs_returns_structure(): void {
		$service = new Industry_Author_Task_Queue_Service();
		$result  = $service->generate_queue( array(), array(), array() );
		$this->assertArrayHasKey( 'tasks', $result );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertIsArray( $result['tasks'] );
		$this->assertCount( 0, $result['tasks'] );
		$this->assertSame( 0, $result['summary']['blocker_count'] );
		$this->assertSame( 0, $result['summary']['cleanup_count'] );
		$this->assertSame( 0, $result['summary']['expansion_count'] );
	}

	public function test_generate_queue_from_completeness_blocker_produces_blocker_task(): void {
		$completeness = array(
			'pack_results' => array(
				array(
					'pack_key'      => 'realtor',
					'subtype_key'   => '',
					'band'          => 'below_minimal',
					'blocker_flags'  => array( 'missing_bundle' ),
				),
			),
			'summary' => array(),
		);
		$service = new Industry_Author_Task_Queue_Service();
		$result  = $service->generate_queue( $completeness, array(), array() );
		$this->assertNotEmpty( $result['tasks'] );
		$blockers = array_filter( $result['tasks'], static function ( $t ) {
			return $t['category'] === Industry_Author_Task_Queue_Service::CATEGORY_BLOCKER;
		} );
		$this->assertCount( 1, $blockers );
		$this->assertSame( 'completeness:realtor:blocker', $blockers[0]['task_key'] );
		$this->assertArrayHasKey( 'source_evidence_refs', $blockers[0] );
		$this->assertSame( 'completeness:pack:realtor', $blockers[0]['source_evidence_refs'][0] );
		$this->assertSame( 1, $result['summary']['blocker_count'] );
	}

	public function test_generate_queue_from_gap_prioritization_produces_tasks_with_evidence(): void {
		$gap_report = array(
			'ranked' => array(
				array(
					'scope'                 => 'realtor',
					'missing_artifact_class' => 'starter_bundle',
					'tier'                  => 'urgent',
					'rationale'             => 'No starter bundle for realtor.',
				),
			),
		);
		$service = new Industry_Author_Task_Queue_Service();
		$result  = $service->generate_queue( array(), $gap_report, array() );
		$this->assertNotEmpty( $result['tasks'] );
		$blocker = null;
		foreach ( $result['tasks'] as $t ) {
			if ( $t['task_key'] === 'gap:realtor:starter_bundle' ) {
				$blocker = $t;
				break;
			}
		}
		$this->assertNotNull( $blocker );
		$this->assertSame( Industry_Author_Task_Queue_Service::CATEGORY_BLOCKER, $blocker['category'] );
		$this->assertSame( 'gap_prioritization:realtor:starter_bundle', $blocker['source_evidence_refs'][0] );
	}

	public function test_generate_queue_from_override_conflicts_produces_cleanup_or_blocker(): void {
		$conflicts = array(
			array(
				'override_ref'             => 'row_1',
				'conflict_type'             => Industry_Override_Conflict_Detector::CONFLICT_TYPE_MISSING_TARGET,
				'severity'                 => Industry_Override_Conflict_Detector::SEVERITY_WARNING,
				'suggested_review_action'   => 'Fix or remove override for missing target.',
			),
		);
		$service = new Industry_Author_Task_Queue_Service();
		$result  = $service->generate_queue( array(), array(), $conflicts );
		$this->assertNotEmpty( $result['tasks'] );
		$task = $result['tasks'][0];
		$this->assertSame( 'conflict:row_1', $task['task_key'] );
		$this->assertContains( $task['category'], array( Industry_Author_Task_Queue_Service::CATEGORY_BLOCKER, Industry_Author_Task_Queue_Service::CATEGORY_CLEANUP ) );
		$this->assertSame( 'override_conflict:row_1', $task['source_evidence_refs'][0] );
	}

	public function test_generate_queue_adds_validation_task_when_tasks_exist(): void {
		$gap_report = array( 'ranked' => array( array( 'scope' => 'x', 'missing_artifact_class' => 'bundle', 'tier' => 'optional', 'rationale' => 'Add bundle.' ) ) );
		$service = new Industry_Author_Task_Queue_Service();
		$result  = $service->generate_queue( array(), $gap_report, array() );
		$validation = array_filter( $result['tasks'], static function ( $t ) {
			return $t['category'] === Industry_Author_Task_Queue_Service::CATEGORY_VALIDATION;
		} );
		$this->assertCount( 1, $validation );
		$this->assertSame( 1, $result['summary']['validation_count'] );
	}
}
