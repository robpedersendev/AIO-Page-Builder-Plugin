<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Run_Orchestrator;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Run_States;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Validation_Port;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Fake_Template_Lab_Validation_Port implements Template_Lab_Validation_Port {

	/** @var list<Validation_Report> */
	public array $reports = array();

	private int $index = 0;

	public function validate( mixed $raw, string $schema_ref, bool $is_repair_attempt ): Validation_Report {
		unset( $raw, $schema_ref, $is_repair_attempt );
		$list = $this->reports;
		if ( $list === array() ) {
			throw new \RuntimeException( 'no reports' );
		}
		$i = $this->index;
		if ( $i >= count( $list ) ) {
			$i = count( $list ) - 1;
		}
		++$this->index;
		return $list[ $i ];
	}
}

final class Template_Lab_Run_Orchestrator_Test extends TestCase {

	private AI_Run_Repository $repo;

	private Fake_Template_Lab_Validation_Port $port;

	private Template_Lab_Run_Orchestrator $orch;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                = new AI_Run_Repository();
		$this->port                = new Fake_Template_Lab_Validation_Port();
		$artifact                  = new AI_Run_Artifact_Service( $this->repo );
		$run_svc                   = new AI_Run_Service( $this->repo, $artifact );
		$this->orch                = new Template_Lab_Run_Orchestrator( $run_svc, $this->repo, $this->port );
		$GLOBALS['_aio_post_meta'] = array();
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_insert_post_return'], $GLOBALS['_aio_get_post_by_id'] );
		parent::tearDown();
	}

	private function stub_run_post( int $pid ): void {
		if ( ! isset( $GLOBALS['_aio_get_post_by_id'] ) || ! is_array( $GLOBALS['_aio_get_post_by_id'] ) ) {
			$GLOBALS['_aio_get_post_by_id'] = array();
		}
		$GLOBALS['_aio_get_post_by_id'][ $pid ] = new \WP_Post(
			array(
				'ID'          => $pid,
				'post_type'   => Object_Type_Keys::AI_RUN,
				'post_title'  => 'run',
				'post_status' => 'publish',
				'post_name'   => 'run',
			)
		);
	}

	private function make_pass_report(): Validation_Report {
		return new Validation_Report(
			Validation_Report::RAW_CAPTURE_OK,
			Validation_Report::PARSE_OK,
			true,
			'aio/test',
			array(),
			array(),
			array( 'k' => 'v' ),
			Validation_Report::STATE_PASSED,
			null,
			false,
			false
		);
	}

	private function make_fail_report(): Validation_Report {
		return new Validation_Report(
			Validation_Report::RAW_CAPTURE_OK,
			Validation_Report::PARSE_OK,
			false,
			'aio/test',
			array(),
			array(),
			null,
			Validation_Report::STATE_FAILED,
			'item',
			false,
			false
		);
	}

	public function test_successful_validation_saves_artifacts_and_completes(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8100;
		$this->port->reports                   = array( $this->make_pass_report() );
		$pid                                   = $this->orch->create_template_lab_run(
			'run-1',
			array(
				'actor'       => '1',
				'provider_id' => 'p',
				'model_used'  => 'm',
			),
			'aio/test',
			'sess-1',
			300,
			2
		);
		$this->assertSame( 8100, $pid );
		$this->stub_run_post( $pid );
		$this->orch->mark_requesting_provider( $pid );
		$end = $this->orch->process_provider_response( $pid, '{}' );
		$this->assertSame( Template_Lab_Run_States::COMPLETED, $end );
		$meta = $this->repo->get_run_metadata( $pid );
		$this->assertSame( Template_Lab_Run_States::COMPLETED, (string) ( $meta['template_lab']['state'] ?? '' ) );
	}

	public function test_repair_then_success(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8101;
		$this->port->reports                   = array( $this->make_fail_report(), $this->make_pass_report() );
		$pid                                   = $this->orch->create_template_lab_run(
			'run-2',
			array( 'actor' => '1' ),
			'aio/test',
			null,
			300,
			2
		);
		$this->stub_run_post( $pid );
		$this->orch->mark_requesting_provider( $pid );
		$this->assertSame( Template_Lab_Run_States::REPAIRING, $this->orch->process_provider_response( $pid, 'bad' ) );
		$this->assertSame( Template_Lab_Run_States::COMPLETED, $this->orch->process_provider_response( $pid, 'ok' ) );
	}

	public function test_repeated_failure_terminates(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8102;
		$this->port->reports                   = array( $this->make_fail_report(), $this->make_fail_report(), $this->make_fail_report() );
		$pid                                   = $this->orch->create_template_lab_run( 'run-3', array( 'actor' => '1' ), 'aio/test', null, 300, 2 );
		$this->stub_run_post( $pid );
		$this->orch->mark_requesting_provider( $pid );
		$this->orch->process_provider_response( $pid, 'a' );
		$this->orch->process_provider_response( $pid, 'b' );
		$end = $this->orch->process_provider_response( $pid, 'c' );
		$this->assertSame( Template_Lab_Run_States::FAILED, $end );
	}

	public function test_idempotent_second_call_short_circuits(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8103;
		$this->port->reports                   = array( $this->make_pass_report() );
		$pid                                   = $this->orch->create_template_lab_run( 'run-4', array( 'actor' => '1' ), 'aio/test', null, 300, 2 );
		$this->stub_run_post( $pid );
		$this->orch->mark_requesting_provider( $pid );
		$this->assertSame( Template_Lab_Run_States::COMPLETED, $this->orch->process_provider_response( $pid, 'x' ) );
		$this->assertSame( Template_Lab_Run_States::COMPLETED, $this->orch->process_provider_response( $pid, 'x' ) );
	}

	public function test_timeout_terminal(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8104;
		$this->port->reports                   = array( $this->make_pass_report() );
		$pid                                   = $this->orch->create_template_lab_run( 'run-5', array( 'actor' => '1' ), 'aio/test', null, 10, 2 );
		$meta                                  = $this->repo->get_run_metadata( $pid );
		$tl                                    = $meta['template_lab'] ?? array();
		$tl['started_at_unix']                 = 1000;
		$meta['template_lab']                  = $tl;
		$this->repo->save_run_metadata( $pid, $meta );
		$this->stub_run_post( $pid );
		$end = $this->orch->process_provider_response( $pid, '{}', static fn (): int => 2000 );
		$this->assertSame( Template_Lab_Run_States::TIMED_OUT, $end );
	}
}
