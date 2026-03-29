<?php
/**
 * Safe, non-authoritative template-lab provenance lines for build-plan admin (informational only).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;

final class Build_Plan_Template_Lab_Provenance_Admin {

	/**
	 * @param array<string, mixed> $definition Plan root definition.
	 * @return list<string> Empty when no safe linkage.
	 */
	public static function lines( array $definition ): array {
		$ctx = $definition[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ] ?? null;
		if ( ! is_array( $ctx ) || $ctx === array() ) {
			return array();
		}
		$lines   = array();
		$lines[] = __( 'Template-lab context (informational; this build plan remains the canonical workflow).', 'aio-page-builder' );
		$tk      = isset( $ctx['target_kind'] ) ? (string) $ctx['target_kind'] : '';
		if ( $tk !== '' ) {
			/* translators: %s: target kind slug */
			$lines[] = sprintf( __( 'Linked draft target kind: %s', 'aio-page-builder' ), $tk );
		}
		$key = isset( $ctx['canonical_internal_key'] ) ? (string) $ctx['canonical_internal_key'] : '';
		if ( $key !== '' ) {
			/* translators: %s: registry internal key */
			$lines[] = sprintf( __( 'Related canonical registry key (if saved): %s', 'aio-page-builder' ), $key );
		}
		$rid = isset( $ctx['run_post_id'] ) ? (int) $ctx['run_post_id'] : 0;
		if ( $rid > 0 ) {
			/* translators: %d: AI run post ID */
			$lines[] = sprintf( __( 'Related AI run record id: %d', 'aio-page-builder' ), $rid );
		}
		return $lines;
	}
}
