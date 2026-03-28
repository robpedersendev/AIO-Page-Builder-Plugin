<?php
/**
 * AI run lifecycle: create run, persist metadata and artifacts by category (spec §29, §59.8). No provider calls.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Runs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Run_Dispatch_Port;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Creates and persists AI runs with run metadata and categorized artifacts.
 * Does not perform provider calls or Build Plan generation.
 */
final class AI_Run_Service {

	/** @var AI_Run_Repository */
	private $run_repository;

	/** @var AI_Run_Artifact_Service */
	private $artifact_service;

	private ?Template_Lab_Run_Dispatch_Port $template_lab_dispatch;

	public function __construct(
		AI_Run_Repository $run_repository,
		AI_Run_Artifact_Service $artifact_service,
		?Template_Lab_Run_Dispatch_Port $template_lab_dispatch = null
	) {
		$this->run_repository        = $run_repository;
		$this->artifact_service      = $artifact_service;
		$this->template_lab_dispatch = $template_lab_dispatch;
	}

	/**
	 * * Default `sync`; inject {@see Template_Lab_Run_Dispatch_Port} when a queue-backed path is wired.
	 */
	public function get_template_lab_dispatch_mode(): string {
		return $this->template_lab_dispatch !== null ? $this->template_lab_dispatch->mode() : 'sync';
	}

	/**
	 * Creates a new run record and optionally persists initial metadata and artifacts.
	 *
	 * @param string               $run_id     Stable run ID (e.g. UUID).
	 * @param array<string, mixed> $metadata   Run metadata: actor, created_at, completed_at?, provider_id, model_used, prompt_pack_ref, retry_count, build_plan_ref?, failover_policy?, failover_attempt?, fallback_provider_reference?, effective_provider_used?, is_experiment?, experiment_id?, experiment_variant_id?, experiment_variant_label?, etc.
	 * @param string               $status     pending_generation | completed | failed_validation | failed.
	 * @param array<string, mixed> $artifacts  Optional map of Artifact_Category_Keys => payload (only categories present are stored).
	 * @return int Run post ID, or 0 on failure.
	 */
	public function create_run( string $run_id, array $metadata, string $status, array $artifacts = array() ): int {
		$post_id = $this->run_repository->save(
			array(
				'internal_key' => $run_id,
				'post_title'   => $run_id,
				'status'       => $status,
			)
		);
		if ( $post_id === 0 ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::RUN_SERVICE_CREATE_FAILED, 'run_id=' . $run_id . ' status=' . $status );
			return 0;
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::RUN_SERVICE_CREATE, 'run_id=' . $run_id . ' post_id=' . (string) $post_id . ' status=' . $status );
		$this->run_repository->save_run_metadata( $post_id, $metadata );
		foreach ( $artifacts as $category => $payload ) {
			if ( Artifact_Category_Keys::is_valid( $category ) ) {
				$this->artifact_service->store( $post_id, $category, $payload );
			}
		}
		return $post_id;
	}

	/**
	 * Updates an existing run: status and optionally metadata and artifacts.
	 *
	 * @param int                  $post_id   Run post ID.
	 * @param string               $status   New status.
	 * @param array<string, mixed> $metadata  Optional run metadata to merge/set.
	 * @param array<string, mixed> $artifacts Optional map of category => payload to store.
	 * @return bool Success.
	 */
	public function update_run( int $post_id, string $status, array $metadata = array(), array $artifacts = array() ): bool {
		$record = $this->run_repository->get_by_id( $post_id );
		if ( $record === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::RUN_SERVICE_UPDATE_MISSING, 'post_id=' . (string) $post_id );
			return false;
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::RUN_SERVICE_UPDATE, 'post_id=' . (string) $post_id . ' status=' . $status );
		$this->run_repository->save(
			array(
				'id'           => $post_id,
				'internal_key' => $record['internal_key'] ?? '',
				'post_title'   => $record['post_title'] ?? '',
				'status'       => $status,
			)
		);
		if ( ! empty( $metadata ) ) {
			$existing = $this->run_repository->get_run_metadata( $post_id );
			$this->run_repository->save_run_metadata( $post_id, array_merge( $existing, $metadata ) );
		}
		foreach ( $artifacts as $category => $payload ) {
			if ( Artifact_Category_Keys::is_valid( $category ) ) {
				$this->artifact_service->store( $post_id, $category, $payload );
			}
		}
		return true;
	}

	/**
	 * Persists artifacts for an existing run (add or overwrite by category).
	 *
	 * @param int                  $post_id   Run post ID.
	 * @param array<string, mixed> $artifacts Map of Artifact_Category_Keys => payload.
	 * @return bool Success (true if at least one stored).
	 */
	public function persist_artifacts( int $post_id, array $artifacts ): bool {
		Named_Debug_Log::event( Named_Debug_Log_Event::RUN_SERVICE_PERSIST_ARTIFACTS, 'post_id=' . (string) $post_id . ' categories=' . (string) count( $artifacts ) );
		$ok = false;
		foreach ( $artifacts as $category => $payload ) {
			if ( Artifact_Category_Keys::is_valid( $category ) ) {
				$ok = $this->artifact_service->store( $post_id, $category, $payload ) || $ok;
			}
		}
		return $ok;
	}

	/**
	 * Returns run record by run_id (internal key).
	 *
	 * @param string $run_id Run ID.
	 * @return array<string, mixed>|null
	 */
	public function get_run_by_id( string $run_id ): ?array {
		return $this->run_repository->get_by_key( $run_id );
	}

	/**
	 * Returns run record by post ID.
	 *
	 * @param int $post_id Run post ID.
	 * @return array<string, mixed>|null
	 */
	public function get_run_by_post_id( int $post_id ): ?array {
		return $this->run_repository->get_by_id( $post_id );
	}
}
