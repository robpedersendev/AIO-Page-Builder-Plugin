<?php
/**
 * Server-side template-lab session UI helpers: readiness text, pre-apply summary, lightweight compare hints.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Router_Interface;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

final class Template_Lab_Session_Admin_Helper {

	/**
	 * @return list<string>
	 */
	public static function generation_readiness_lines( AI_Provider_Router_Interface $router ): array {
		$route = $router->resolve_route( AI_Routing_Task::TEMPLATE_LAB_CHAT, array() );
		$lines = array();
		if ( ! $route->is_valid() ) {
			$lines[] = __( 'No valid provider route for template-lab chat tasks. Configure AI routing or provider defaults.', 'aio-page-builder' );
			return $lines;
		}
		$lines[] = __( 'A provider route is configured for template-lab chat.', 'aio-page-builder' );
		$fb      = $route->get_fallback_provider_id();
		if ( is_string( $fb ) && $fb !== '' && $fb !== $route->get_primary_provider_id() ) {
			$lines[] = __( 'Fallback route metadata is present if the primary route is unavailable.', 'aio-page-builder' );
		}
		return $lines;
	}

	/**
	 * @return list<string>
	 */
	public static function approved_snapshot_summary_lines( string $target_kind, array $normalized ): array {
		$lines = array();
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			$lines[] = (string) ( $normalized[ Composition_Schema::FIELD_NAME ] ?? '' );
			$list    = $normalized[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
			$lines[] = is_array( $list )
				/* translators: %d: section count */
				? sprintf( __( 'Ordered sections: %d', 'aio-page-builder' ), count( $list ) )
				: '';
		} elseif ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			$lines[] = (string) ( $normalized[ Page_Template_Schema::FIELD_NAME ] ?? '' );
			$lines[] = (string) ( $normalized[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' );
			$os      = $normalized[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$lines[] = is_array( $os )
				? sprintf( __( 'Ordered sections: %d', 'aio-page-builder' ), count( $os ) )
				: '';
		} elseif ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_SECTION ) {
			$lines[] = (string) ( $normalized[ Section_Schema::FIELD_NAME ] ?? '' );
			$lines[] = (string) ( $normalized[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			$lines[] = (string) ( $normalized[ Section_Schema::FIELD_RENDER_MODE ] ?? '' );
		}
		return array_values( array_filter( $lines, static fn( $x ) => $x !== '' ) );
	}

	/**
	 * @return list<string>
	 */
	public static function compare_preview_lines(
		string $target_kind,
		array $proposed,
		?array $existing
	): array {
		if ( $existing === null || $existing === array() ) {
			return array( __( 'No existing canonical record with this key; apply will create a new entry.', 'aio-page-builder' ) );
		}
		$lines = array( __( 'Existing canonical record found; review key differences below (summary only).', 'aio-page-builder' ) );
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			$on = (string) ( $existing[ Composition_Schema::FIELD_NAME ] ?? '' );
			$nn = (string) ( $proposed[ Composition_Schema::FIELD_NAME ] ?? '' );
			if ( $on !== $nn ) {
				$lines[] = __( 'Name differs between existing and proposed draft.', 'aio-page-builder' );
			}
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			$on = (string) ( $existing[ Page_Template_Schema::FIELD_NAME ] ?? '' );
			$nn = (string) ( $proposed[ Page_Template_Schema::FIELD_NAME ] ?? '' );
			if ( $on !== $nn ) {
				$lines[] = __( 'Page template name differs.', 'aio-page-builder' );
			}
			$oc = is_array( $existing[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null ) ? count( $existing[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ) : 0;
			$nc = is_array( $proposed[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null ) ? count( $proposed[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ) : 0;
			if ( $oc !== $nc ) {
				$lines[] = sprintf(
					/* translators: 1: existing count, 2: proposed count */
					__( 'Ordered section count changed (%1$d → %2$d).', 'aio-page-builder' ),
					$oc,
					$nc
				);
			}
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_SECTION ) {
			$om = (string) ( $existing[ Section_Schema::FIELD_RENDER_MODE ] ?? '' );
			$nm = (string) ( $proposed[ Section_Schema::FIELD_RENDER_MODE ] ?? '' );
			if ( $om !== $nm ) {
				$lines[] = __( 'Render mode differs.', 'aio-page-builder' );
			}
		}
		return $lines;
	}

	public static function load_existing_definition_for_compare(
		string $target_kind,
		array $normalized,
		Composition_Repository $compositions,
		Page_Template_Repository $pages,
		Section_Template_Repository $sections
	): ?array {
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			$k = (string) ( $normalized[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
			return $k !== '' ? $compositions->get_definition_by_key( $k ) : null;
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			$k = (string) ( $normalized[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			return $k !== '' ? $pages->get_definition_by_key( $k ) : null;
		}
		$k = (string) ( $normalized[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		return $k !== '' ? $sections->get_definition_by_key( $k ) : null;
	}

	public static function transcript_row_label( string $role ): string {
		return match ( $role ) {
			'user' => __( 'You', 'aio-page-builder' ),
			'assistant' => __( 'Assistant', 'aio-page-builder' ),
			'system' => __( 'System', 'aio-page-builder' ),
			default => $role,
		};
	}
}
