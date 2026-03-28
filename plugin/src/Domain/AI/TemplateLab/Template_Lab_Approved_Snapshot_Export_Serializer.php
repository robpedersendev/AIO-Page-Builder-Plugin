<?php
/**
 * Deterministic, privacy-safe export shape for approved template-lab snapshots (no prompts, no raw provider bodies, no tokens).
 *
 * Chat transcripts and RAW_* artifact categories are intentionally omitted; portability targets structured normalized output
 * plus linkage metadata for audit and future import.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;

final class Template_Lab_Approved_Snapshot_Export_Serializer {

	public const EXPORT_VERSION = 1;

	/**
	 * Safe run-metadata keys copied into export (values still passed through redaction).
	 *
	 * @var list<string>
	 */
	private const METADATA_ALLOWLIST = array(
		'provider_id',
		'model_used',
		'created_at',
		'completed_at',
		'routing_task',
		'prompt_pack_ref',
		'actor',
	);

	/**
	 * @param array<string, mixed> $run_metadata Full run metadata from storage (trimmed + redacted subset is exported only).
	 * @return array<string, mixed>
	 */
	public static function serialize(
		int $run_post_id,
		AI_Run_Artifact_Read_Port $artifacts,
		AI_Run_Template_Lab_Apply_State_Port $apply_state,
		array $run_metadata
	): array {
		$trace = $artifacts->get( $run_post_id, Artifact_Category_Keys::TEMPLATE_LAB_TRACE );
		$trace = is_array( $trace ) ? AI_Run_Artifact_Service::redact_sensitive_values( $trace ) : array();

		$norm = $artifacts->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		$norm = is_array( $norm ) ? AI_Run_Artifact_Service::redact_sensitive_values( $norm ) : array();

		$val = $artifacts->get( $run_post_id, Artifact_Category_Keys::VALIDATION_REPORT );
		$val_safe = self::summarize_validation_report( $val );

		$meta_slice = array();
		foreach ( self::METADATA_ALLOWLIST as $k ) {
			if ( array_key_exists( $k, $run_metadata ) ) {
				$meta_slice[ $k ] = $run_metadata[ $k ];
			}
		}
		$meta_slice = AI_Run_Artifact_Service::redact_sensitive_values( $meta_slice );

		$tl = isset( $run_metadata['template_lab'] ) && is_array( $run_metadata['template_lab'] )
			? AI_Run_Artifact_Service::redact_sensitive_values( $run_metadata['template_lab'] )
			: array();

		$apply = $apply_state->get_template_lab_canonical_apply_record( $run_post_id );
		$applied = is_array( $apply ) && (string) ( $apply['canonical_internal_key'] ?? '' ) !== '';

		$out = array(
			'export_version'        => self::EXPORT_VERSION,
			'exported_at_utc'       => gmdate( 'c' ),
			'run_post_id'           => $run_post_id,
			'safe_run_metadata'     => $meta_slice,
			'template_lab_state'    => $tl,
			'template_lab_trace'    => $trace,
			'normalized_output'     => $norm,
			'validation_summary'    => $val_safe,
			'canonical_apply'       => $applied
				? array(
					'applied'                => true,
					'canonical_internal_key' => (string) ( $apply['canonical_internal_key'] ?? '' ),
					'canonical_post_id'      => (int) ( $apply['canonical_post_id'] ?? 0 ),
					'target_kind'            => (string) ( $apply['target_kind'] ?? '' ),
					'artifact_fingerprint'   => (string) ( $apply['artifact_fingerprint'] ?? '' ),
				)
				: array( 'applied' => false ),
		);
		return self::ksort_recursive( $out );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function summarize_validation_report( mixed $val ): array {
		if ( ! is_array( $val ) ) {
			return array( 'present' => false );
		}
		$keys = array_keys( $val );
		sort( $keys );
		return array(
			'present'    => true,
			'field_keys' => $keys,
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function ksort_recursive( array $data ): array {
		ksort( $data );
		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$data[ $k ] = self::ksort_recursive( $v );
			}
		}
		return $data;
	}
}
