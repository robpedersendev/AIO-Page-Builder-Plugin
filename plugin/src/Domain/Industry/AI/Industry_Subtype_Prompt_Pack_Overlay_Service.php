<?php
/**
 * Builds subtype prompt-pack overlay fragment from input artifact (industry-subtype-ai-overlay-contract.md; Prompt 430).
 * Returns subtype guidance for prompt-pack assembly; safe when subtype context missing or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;

/**
 * Produces subtype overlay fragment for prompt-pack assembly. No throw; returns minimal overlay when subtype absent or invalid.
 */
final class Industry_Subtype_Prompt_Pack_Overlay_Service {

	public const OVERLAY_SCHEMA_VERSION = '1';

	/**
	 * Builds subtype overlay fragment from input artifact. Safe: returns minimal overlay when industry_context has no valid subtype.
	 *
	 * @param array<string, mixed> $input_artifact Built input artifact (may contain industry_context with subtype fields).
	 * @return array<string, mixed> Overlay fragment with schema_version; optional subtype_guidance_text, subtype_cta_priorities.
	 */
	public function get_overlay_for_artifact( array $input_artifact ): array {
		$base = array( 'schema_version' => self::OVERLAY_SCHEMA_VERSION );
		$industry_context = isset( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] ) && is_array( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] )
			? $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ]
			: null;
		if ( $industry_context === null ) {
			return $base;
		}
		$subtype_key = isset( $industry_context['industry_subtype_key'] ) && is_string( $industry_context['industry_subtype_key'] )
			? trim( $industry_context['industry_subtype_key'] )
			: '';
		if ( $subtype_key === '' ) {
			return $base;
		}
		$snapshot = isset( $industry_context['resolved_subtype_snapshot'] ) && is_array( $industry_context['resolved_subtype_snapshot'] )
			? $industry_context['resolved_subtype_snapshot']
			: array();
		$summary = isset( $snapshot['summary'] ) && is_string( $snapshot['summary'] ) ? trim( $snapshot['summary'] ) : '';
		$label   = isset( $snapshot['label'] ) && is_string( $snapshot['label'] ) ? trim( $snapshot['label'] ) : '';
		if ( $summary !== '' ) {
			$base['subtype_guidance_text'] = $label !== '' ? $label . ': ' . $summary : $summary;
		}
		$cta_ref = isset( $industry_context['subtype_cta_posture_ref'] ) && is_string( $industry_context['subtype_cta_posture_ref'] )
			? trim( $industry_context['subtype_cta_posture_ref'] )
			: '';
		if ( $cta_ref !== '' ) {
			$base['subtype_cta_priorities'] = array( $cta_ref );
		}
		return $base;
	}
}
