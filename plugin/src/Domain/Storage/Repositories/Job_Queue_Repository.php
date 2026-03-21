<?php
/**
 * Data access for job queue records (spec §11.3, §42.6). Backing: table aio_job_queue.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Queue\Job_Queue_Repository_Interface;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Recovery_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Status;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Repository → storage: Table_Names::JOB_QUEUE (custom table).
 * Key column: job_ref. Supports insert, update status, list by status, and recovery reset (Prompt 080, 124).
 */
final class Job_Queue_Repository extends Abstract_Table_Repository implements Job_Queue_Repository_Interface, Queue_Recovery_Repository_Interface {

	/** @inheritdoc */
	protected function get_table_suffix(): string {
		return Table_Names::JOB_QUEUE;
	}

	/** @inheritdoc */
	protected function get_key_column(): string {
		return 'job_ref';
	}

	/**
	 * Inserts a new job. Data must include job_ref, job_type, queue_status; optional actor_ref, priority, payload_ref, related_object_refs.
	 *
	 * @param array<string, mixed> $data job_ref, job_type, queue_status, optional actor_ref, priority, payload_ref, related_object_refs.
	 * @return string job_ref of inserted job, or empty on failure.
	 */
	public function insert_job( array $data ): string {
		$id = $this->save( $data );
		if ( $id > 0 ) {
			$ref = isset( $data['job_ref'] ) && is_string( $data['job_ref'] ) ? trim( $data['job_ref'] ) : '';
			if ( $ref !== '' ) {
				return $ref;
			}
			$row = $this->get_by_id( $id );
			return isset( $row['job_ref'] ) && is_string( $row['job_ref'] ) ? $row['job_ref'] : '';
		}
		return '';
	}

	/**
	 * Updates job status and optional timestamps/failure reason.
	 *
	 * @param string      $job_ref
	 * @param string      $status
	 * @param string|null $failure_reason
	 * @param string|null $started_at   Set when transitioning to running.
	 * @param string|null $completed_at Set when transitioning to completed/failed/cancelled.
	 * @return bool
	 */
	public function update_job_status( string $job_ref, string $status, ?string $failure_reason = null, ?string $started_at = null, ?string $completed_at = null ): bool {
		$existing = $this->get_by_key( $job_ref );
		if ( $existing === null ) {
			return false;
		}
		$id = (int) ( $existing['id'] ?? 0 );
		if ( $id <= 0 ) {
			return false;
		}
		$updates = array( 'queue_status' => $status );
		if ( $failure_reason !== null ) {
			$updates['failure_reason'] = \sanitize_text_field( substr( $failure_reason, 0, 512 ) );
		}
		if ( $started_at !== null ) {
			$updates['started_at'] = $started_at;
		}
		if ( $completed_at !== null ) {
			$updates['completed_at'] = $completed_at;
		}
		return $this->update_row( $id, $updates );
	}

	/**
	 * Updates actor_ref for a row (e.g. for privacy eraser anonymization). Preserves the row for audit.
	 *
	 * @param int    $id        Row id.
	 * @param string $actor_ref New value (e.g. user:0 for anonymized).
	 * @return bool
	 */
	public function update_actor_ref( int $id, string $actor_ref ): bool {
		$actor_ref = $this->sanitize_key( $actor_ref );
		return $this->update_row( $id, array( 'actor_ref' => $actor_ref ) );
	}

	/**
	 * Resets a failed job for manual retry: sets queue_status to pending and clears lock (spec §42.4, §42.5).
	 * Caller must enforce retry eligibility and capability. Does not increment retry_count.
	 *
	 * @param string $job_ref
	 * @return bool
	 */
	public function reset_for_retry( string $job_ref ): bool {
		$existing = $this->get_by_key( $job_ref );
		if ( $existing === null ) {
			return false;
		}
		$id = (int) ( $existing['id'] ?? 0 );
		if ( $id <= 0 ) {
			return false;
		}
		return $this->update_row(
			$id,
			array(
				'queue_status' => Job_Queue_Status::PENDING,
				'lock_token'   => '',
			)
		);
	}

	/**
	 * Updates a single row by id (partial update).
	 *
	 * @param int                  $id
	 * @param array<string, mixed> $data Column => value.
	 * @return bool
	 */
	private function update_row( int $id, array $data ): bool {
		if ( empty( $data ) ) {
			return true;
		}
		$table  = $this->get_table_name();
		$set    = array();
		$values = array();
		$parts  = array();
		$values = array( $table );
		foreach ( $data as $col => $val ) {
			$col = \sanitize_key( $col );
			if ( $col === '' || ! in_array( $col, array( 'queue_status', 'failure_reason', 'started_at', 'completed_at', 'retry_count', 'lock_token', 'actor_ref' ), true ) ) {
				continue;
			}
			$this->assert_sql_identifier( $col );
			$parts[]  = '%i = %s';
			$values[] = $col;
			$values[] = $val;
		}
		if ( empty( $parts ) ) {
			return true;
		}
		$values[] = $id;
		$this->assert_sql_identifier( $table );
		$sql      = 'UPDATE %i SET ' . implode( ', ', $parts ) . ' WHERE id = %d';
		$prepared = $this->wpdb->prepare( $sql, ...$values );
		return $prepared !== false && $this->wpdb->query( $prepared ) !== false;
	}

	/** @inheritdoc */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$table  = $this->get_table_name();
		$limit  = $limit > 0 ? $limit : 50;
		$offset = $offset >= 0 ? $offset : 0;
		$this->assert_sql_identifier( $table );
		$sql      = 'SELECT * FROM %i WHERE queue_status = %s ORDER BY priority DESC, created_at ASC LIMIT %d OFFSET %d';
		$prepared = $this->wpdb->prepare( $sql, $table, $status, $limit, $offset );
		$rows     = $this->wpdb->get_results( $prepared );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = $this->row_to_record( $row );
		}
		return $out;
	}

	/**
	 * Lists jobs by actor reference (e.g. user:123). Used by privacy exporter/eraser.
	 *
	 * @param string $actor_ref Sanitized actor ref (e.g. user:5).
	 * @param int    $limit     Max rows (default 50).
	 * @param int    $offset    Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_by_actor_ref( string $actor_ref, int $limit = 50, int $offset = 0 ): array {
		$actor_ref = $this->sanitize_key( $actor_ref );
		if ( $actor_ref === '' ) {
			return array();
		}
		$table  = $this->get_table_name();
		$limit  = $limit > 0 ? $limit : 50;
		$offset = $offset >= 0 ? $offset : 0;
		$this->assert_sql_identifier( $table );
		$sql      = 'SELECT * FROM %i WHERE actor_ref = %s ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$prepared = $this->wpdb->prepare( $sql, $table, $actor_ref, $limit, $offset );
		$rows     = $this->wpdb->get_results( $prepared );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = $this->row_to_record( $row );
		}
		return $out;
	}

	/** @inheritdoc */
	public function save( array $data ): int {
		$job_ref  = isset( $data['job_ref'] ) && is_string( $data['job_ref'] ) ? $this->sanitize_key( $data['job_ref'] ) : '';
		$job_type = isset( $data['job_type'] ) && is_string( $data['job_type'] ) ? \sanitize_text_field( substr( $data['job_type'], 0, 64 ) ) : '';
		$status   = isset( $data['queue_status'] ) && is_string( $data['queue_status'] ) ? \sanitize_text_field( substr( $data['queue_status'], 0, 32 ) ) : Job_Queue_Status::PENDING;
		if ( $job_ref === '' || $job_type === '' ) {
			return 0;
		}
		$table = $this->get_table_name();
		if ( $this->exists( $job_ref ) ) {
			return 0;
		}
		$actor_ref   = isset( $data['actor_ref'] ) && is_string( $data['actor_ref'] ) ? $this->sanitize_key( $data['actor_ref'] ) : '';
		$priority    = isset( $data['priority'] ) && is_numeric( $data['priority'] ) ? (int) $data['priority'] : 0;
		$payload_ref = isset( $data['payload_ref'] ) && is_string( $data['payload_ref'] ) ? \sanitize_text_field( substr( $data['payload_ref'], 0, 512 ) ) : '';
		$related     = isset( $data['related_object_refs'] ) && is_string( $data['related_object_refs'] ) ? $data['related_object_refs'] : '';
		$now         = current_time( 'mysql' );

		$this->assert_sql_identifier( $table );
		$sql      = 'INSERT INTO %i ( job_ref, job_type, queue_status, priority, payload_ref, actor_ref, created_at, retry_count, related_object_refs ) VALUES ( %s, %s, %s, %d, %s, %s, %s, 0, %s )';
		$prepared = $this->wpdb->prepare(
			$sql,
			$table,
			$job_ref,
			$job_type,
			$status,
			$priority,
			$payload_ref,
			$actor_ref,
			$now,
			$related
		);
		if ( $prepared === false || $this->wpdb->query( $prepared ) === false ) {
			return 0;
		}
		$id = (int) $this->wpdb->insert_id;
		return $id > 0 ? $id : 0;
	}
}
