<?php
/**
 * Display-safe AI provenance hints for registry definitions (no prompts, no raw artifacts).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;

final class Registry_AI_Provenance_Helper {

	public static function composition_has_ai_trace( array $definition ): bool {
		$ref = $definition[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? null;
		if ( is_array( $ref ) && isset( $ref['ai_run_post_id'] ) && (int) $ref['ai_run_post_id'] > 0 ) {
			return true;
		}
		return false;
	}

	public static function page_template_has_ai_trace( array $definition ): bool {
		if ( isset( $definition['provenance_ai_run_post_id'] ) && (int) $definition['provenance_ai_run_post_id'] > 0 ) {
			return true;
		}
		$snap = $definition['provenance_approved_snapshot_ref'] ?? null;
		return is_array( $snap ) && $snap !== array();
	}

	public static function section_template_has_ai_trace( array $definition ): bool {
		if ( isset( $definition['provenance_ai_run_post_id'] ) && (int) $definition['provenance_ai_run_post_id'] > 0 ) {
			return true;
		}
		$snap = $definition['provenance_approved_snapshot_ref'] ?? null;
		return is_array( $snap ) && $snap !== array();
	}

	/**
	 * Short label for list tables (translated).
	 */
	public static function source_badge_label_for_composition( array $definition ): string {
		return self::composition_has_ai_trace( $definition )
			? __( 'AI-assisted', 'aio-page-builder' )
			: __( 'Manual', 'aio-page-builder' );
	}

	public static function source_badge_label_for_page_template( array $definition ): string {
		return self::page_template_has_ai_trace( $definition )
			? __( 'AI-assisted', 'aio-page-builder' )
			: __( 'Manual', 'aio-page-builder' );
	}
}
