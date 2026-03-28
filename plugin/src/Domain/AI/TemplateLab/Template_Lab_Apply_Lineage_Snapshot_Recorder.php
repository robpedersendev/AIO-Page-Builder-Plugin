<?php
/**
 * Persists a version-snapshot record after template-lab canonical apply (traceability; no prompts or secrets).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

final class Template_Lab_Apply_Lineage_Snapshot_Recorder {

	/** Scope id stored on version snapshot posts for template-lab canonical applies (query + export). */
	public const SCOPE_ID = 'template_lab_canonical_apply';

	private Version_Snapshot_Repository $snapshots;

	public function __construct( Version_Snapshot_Repository $snapshots ) {
		$this->snapshots = $snapshots;
	}

	/**
	 * Records lineage for an AI-assisted canonical write. Failures are logged and ignored (apply already succeeded).
	 */
	public function record(
		int $actor_user_id,
		string $target_kind,
		int $canonical_post_id,
		string $canonical_internal_key,
		int $ai_run_post_id,
		string $artifact_fingerprint
	): void {
		if ( $canonical_post_id <= 0 || $canonical_internal_key === '' || $ai_run_post_id <= 0 || $artifact_fingerprint === '' ) {
			return;
		}
		$snapshot_id = 'snap_tl_' . bin2hex( random_bytes( 8 ) );
		$definition    = array(
			Version_Snapshot_Schema::FIELD_SNAPSHOT_ID    => $snapshot_id,
			Version_Snapshot_Schema::FIELD_SCOPE_TYPE     => Version_Snapshot_Schema::SCOPE_BUILD_CONTEXT,
			Version_Snapshot_Schema::FIELD_SCOPE_ID       => self::SCOPE_ID,
			Version_Snapshot_Schema::FIELD_CREATED_AT     => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Version_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Version_Snapshot_Schema::FIELD_STATUS         => Version_Snapshot_Schema::STATUS_ACTIVE,
			Version_Snapshot_Schema::FIELD_OBJECT_REFS    => array(
				'target_kind'            => $target_kind,
				'canonical_post_id'      => $canonical_post_id,
				'canonical_internal_key' => $canonical_internal_key,
				'ai_run_post_id'         => $ai_run_post_id,
				'artifact_fingerprint'   => $artifact_fingerprint,
				'actor_user_id'          => $actor_user_id,
			),
			Version_Snapshot_Schema::FIELD_PROVENANCE     => array(
				'source'      => 'approved_template_lab_apply',
				'recorded_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
			'post_title'                                  => $snapshot_id,
			'payload'                                     => array(
				'kind' => 'template_lab_canonical_apply',
			),
		);
		$id = $this->snapshots->save_definition( $definition );
		if ( $id <= 0 ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_APPLY_LINEAGE_SNAPSHOT_FAIL, 'reason=persist' );
		}
	}
}
