<?php
/**
 * Bounded state machine for template-lab AI runs: validate, repair, idempotent draft artifact save.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Planning\AI_Prompt_Pack_Keys;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Stores machine state in run metadata under template_lab. Does not call HTTP providers.
 */
final class Template_Lab_Run_Orchestrator {

	private const META_BLOCK = 'template_lab';

	private AI_Run_Service $run_service;

	private AI_Run_Repository $run_repo;

	private Template_Lab_Validation_Port $validation;

	public function __construct(
		AI_Run_Service $run_service,
		AI_Run_Repository $run_repo,
		Template_Lab_Validation_Port $validation
	) {
		$this->run_service = $run_service;
		$this->run_repo    = $run_repo;
		$this->validation  = $validation;
	}

	/**
	 * @param array<string, mixed> $run_metadata Base run metadata (actor, provider_id, etc.).
	 */
	public function create_template_lab_run(
		string $run_id,
		array $run_metadata,
		string $schema_ref,
		?string $chat_session_key = null,
		int $timeout_seconds = 300,
		int $max_repairs = 2,
		string $routing_task = ''
	): int {
		if ( $routing_task === '' ) {
			$routing_task = AI_Routing_Task::TEMPLATE_LAB_COMPOSITION_DRAFT;
		}
		$run_metadata['routing_task']     = $routing_task;
		$run_metadata['prompt_pack_ref']  = AI_Prompt_Pack_Keys::for_routing_task( $routing_task );
		$run_metadata[ self::META_BLOCK ] = array(
			'state'            => Template_Lab_Run_States::QUEUED,
			'schema_ref'       => $schema_ref,
			'chat_session_key' => $chat_session_key,
			'repair_count'     => 0,
			'max_repairs'      => max( 0, $max_repairs ),
			'started_at_unix'  => time(),
			'timeout_seconds'  => max( 1, $timeout_seconds ),
			'last_fingerprint' => '',
		);
		$post_id                          = $this->run_service->create_run( $run_id, $run_metadata, 'pending_generation' );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_RUN_ENTER_STATE,
			'post_id=' . (string) $post_id . ' state=' . Template_Lab_Run_States::QUEUED
		);
		return $post_id;
	}

	public function mark_requesting_provider( int $post_id ): void {
		$this->set_lab_state( $post_id, Template_Lab_Run_States::REQUESTING_PROVIDER );
		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_PROVIDER_REQUEST_START, 'post_id=' . (string) $post_id );
	}

	/**
	 * Processes raw provider payload: validation, bounded repair, artifact save, terminal states.
	 *
	 * @param callable|null $now_unix Returns current unix timestamp (tests).
	 */
	public function process_provider_response( int $post_id, mixed $raw, ?callable $now_unix = null ): string {
		$now  = $now_unix !== null ? (int) call_user_func( $now_unix ) : time();
		$meta = $this->run_repo->get_run_metadata( $post_id );
		$tl   = $meta[ self::META_BLOCK ] ?? null;
		if ( ! is_array( $tl ) ) {
			return Template_Lab_Run_States::FAILED;
		}
		$tl['process_round']      = (int) ( $tl['process_round'] ?? 0 ) + 1;
		$meta[ self::META_BLOCK ] = $tl;
		$this->run_repo->save_run_metadata( $post_id, $meta );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_PROCESS_PROVIDER_ROUND,
			'post_id=' . (string) $post_id . ' round=' . (string) $tl['process_round']
		);
		$start   = (int) ( $tl['started_at_unix'] ?? 0 );
		$timeout = (int) ( $tl['timeout_seconds'] ?? 300 );
		if ( $start > 0 && ( $now - $start ) > $timeout ) {
			$this->terminal( $post_id, $tl, Template_Lab_Run_States::TIMED_OUT, 'failed' );
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_TERMINAL, 'post_id=' . (string) $post_id . ' state=timed_out' );
			return Template_Lab_Run_States::TIMED_OUT;
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_PROVIDER_REQUEST_END, 'post_id=' . (string) $post_id );

		if ( (string) ( $tl['state'] ?? '' ) === Template_Lab_Run_States::COMPLETED ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_IDEMPOTENT_SKIP, 'post_id=' . (string) $post_id . ' reason=already_completed' );
			return Template_Lab_Run_States::COMPLETED;
		}

		$prior_state = (string) ( $tl['state'] ?? '' );
		$is_repair   = $prior_state === Template_Lab_Run_States::REPAIRING;
		$schema_ref  = (string) ( $tl['schema_ref'] ?? '' );
		$this->set_lab_state( $post_id, Template_Lab_Run_States::VALIDATING );

		$report = $this->validation->validate( $raw, $schema_ref, $is_repair );

		if ( $report->get_final_validation_state() === Validation_Report::STATE_PASSED && $report->get_normalized_output() !== null ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::TEMPLATE_LAB_VALIDATION_PASS,
				'post_id=' . (string) $post_id . ' schema_ref=' . $schema_ref
			);
			return $this->complete_with_normalized( $post_id, $meta, $tl, $report, $schema_ref );
		}

		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_VALIDATION_FAIL,
			'post_id=' . (string) $post_id . ' state=' . $report->get_final_validation_state()
		);

		$repair_count = (int) ( $tl['repair_count'] ?? 0 );
		$max_repairs  = (int) ( $tl['max_repairs'] ?? 2 );
		if ( $repair_count < $max_repairs ) {
			$tl['repair_count']       = $repair_count + 1;
			$tl['state']              = Template_Lab_Run_States::REPAIRING;
			$meta[ self::META_BLOCK ] = $tl;
			$this->run_repo->save_run_metadata( $post_id, $meta );
			Named_Debug_Log::event(
				Named_Debug_Log_Event::TEMPLATE_LAB_REPAIR_START,
				'post_id=' . (string) $post_id . ' attempt=' . (string) $tl['repair_count']
			);
			return Template_Lab_Run_States::REPAIRING;
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_REPAIR_END, 'post_id=' . (string) $post_id . ' outcome=exhausted' );
		$this->terminal( $post_id, $tl, Template_Lab_Run_States::FAILED, 'failed_validation' );
		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_TERMINAL, 'post_id=' . (string) $post_id . ' state=failed' );
		return Template_Lab_Run_States::FAILED;
	}

	/**
	 * @param array<string, mixed> $meta
	 * @param array<string, mixed> $tl
	 */
	private function complete_with_normalized( int $post_id, array $meta, array $tl, Validation_Report $report, string $schema_ref ): string {
		$norm = $report->get_normalized_output();
		if ( ! is_array( $norm ) ) {
			return Template_Lab_Run_States::FAILED;
		}
		$json = \wp_json_encode( $norm );
		$fp   = $json !== false ? md5( $schema_ref . $json ) : md5( $schema_ref );

		$current_state = (string) ( $tl['state'] ?? '' );
		$last_fp       = (string) ( $tl['last_fingerprint'] ?? '' );
		if ( $last_fp !== '' && $fp === $last_fp && in_array( $current_state, array( Template_Lab_Run_States::DRAFT_SAVED, Template_Lab_Run_States::COMPLETED ), true ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_IDEMPOTENT_SKIP, 'post_id=' . (string) $post_id );
			return Template_Lab_Run_States::COMPLETED;
		}

		$trace = $this->build_trace( $meta, $tl, $report, $fp );
		$this->run_service->persist_artifacts(
			$post_id,
			array(
				Artifact_Category_Keys::NORMALIZED_OUTPUT  => $norm,
				Artifact_Category_Keys::VALIDATION_REPORT  => array(
					'final_validation_state' => $report->get_final_validation_state(),
					'schema_ref'             => $schema_ref,
				),
				Artifact_Category_Keys::TEMPLATE_LAB_TRACE => $trace,
			)
		);
		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_ARTIFACT_SAVE, 'post_id=' . (string) $post_id . ' fp=' . $fp );

		$tl['last_fingerprint']   = $fp;
		$tl['state']              = Template_Lab_Run_States::DRAFT_SAVED;
		$meta[ self::META_BLOCK ] = $tl;
		$this->run_repo->save_run_metadata( $post_id, $meta );

		$this->run_service->update_run( $post_id, 'completed', array(), array() );

		$meta = $this->run_repo->get_run_metadata( $post_id );
		$tl   = $meta[ self::META_BLOCK ] ?? array();
		if ( is_array( $tl ) ) {
			$tl['state']              = Template_Lab_Run_States::COMPLETED;
			$meta[ self::META_BLOCK ] = $tl;
			$this->run_repo->save_run_metadata( $post_id, $meta );
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_TERMINAL, 'post_id=' . (string) $post_id . ' state=completed' );
		return Template_Lab_Run_States::COMPLETED;
	}

	/**
	 * @param array<string, mixed> $tl
	 */
	private function terminal( int $post_id, array $tl, string $lab_state, string $run_status ): void {
		$tl['state']              = $lab_state;
		$meta                     = $this->run_repo->get_run_metadata( $post_id );
		$meta[ self::META_BLOCK ] = $tl;
		$this->run_repo->save_run_metadata( $post_id, $meta );
		$this->run_service->update_run( $post_id, $run_status, array(), array() );
	}

	private function set_lab_state( int $post_id, string $state ): void {
		$meta = $this->run_repo->get_run_metadata( $post_id );
		$tl   = $meta[ self::META_BLOCK ] ?? array();
		if ( ! is_array( $tl ) ) {
			$tl = array();
		}
		$tl['state']              = $state;
		$meta[ self::META_BLOCK ] = $tl;
		$this->run_repo->save_run_metadata( $post_id, $meta );
		Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_RUN_ENTER_STATE, 'post_id=' . (string) $post_id . ' state=' . $state );
	}

	/**
	 * @param array<string, mixed> $run_meta
	 * @param array<string, mixed> $tl
	 * @return array<string, mixed>
	 */
	private function build_trace( array $run_meta, array $tl, Validation_Report $report, string $fingerprint ): array {
		return array(
			'chat_session_key'     => (string) ( $tl['chat_session_key'] ?? '' ),
			'schema_ref'           => (string) ( $tl['schema_ref'] ?? '' ),
			'provider_id'          => (string) ( $run_meta['provider_id'] ?? '' ),
			'model_used'           => (string) ( $run_meta['model_used'] ?? '' ),
			'validation_outcome'   => $report->get_final_validation_state(),
			'repair_count'         => (int) ( $tl['repair_count'] ?? 0 ),
			'artifact_fingerprint' => $fingerprint,
		);
	}
}
