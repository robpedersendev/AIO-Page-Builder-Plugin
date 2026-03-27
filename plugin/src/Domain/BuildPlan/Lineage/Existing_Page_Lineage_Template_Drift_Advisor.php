<?php
/**
 * Compares existing-page template targets to the immediately prior plan version in the same lineage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Lineage;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Bulk_Action_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;

/**
 * Surfaces when the same URL/title was recommended a different template in the previous plan version.
 */
final class Existing_Page_Lineage_Template_Drift_Advisor {

	private Lineage_Previous_Version_Resolver_Interface $previous_version_resolver;

	private Build_Plan_Repository_Interface $plan_repository;

	public function __construct( Lineage_Previous_Version_Resolver_Interface $previous_version_resolver, Build_Plan_Repository_Interface $plan_repository ) {
		$this->previous_version_resolver = $previous_version_resolver;
		$this->plan_repository           = $plan_repository;
	}

	/**
	 * Plain-text note for the step table (escaped on output). Empty when no drift or no prior version.
	 *
	 * @param array<string, mixed> $plan_definition Current plan root.
	 * @param array<string, mixed> $item            Existing page change item.
	 */
	public function note_for_item( array $plan_definition, array $item ): string {
		$prior_map = $this->prior_version_template_map( $plan_definition );
		if ( $prior_map === null ) {
			return '';
		}
		$payload      = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$key          = $this->page_identity_key( $payload );
		$cur_template = $this->template_key_from_payload( $payload );
		if ( $key === '' ) {
			return '';
		}
		if ( ! isset( $prior_map[ $key ] ) ) {
			return '';
		}
		$prior_template = $prior_map[ $key ];
		$prior_norm     = $this->normalize_template_key( $prior_template );
		$cur_norm       = $this->normalize_template_key( $cur_template );
		if ( $prior_norm === $cur_norm ) {
			return '';
		}
		if ( $prior_norm !== '' && $cur_norm !== '' ) {
			return sprintf(
				/* translators: 1: template key from prior plan version, 2: template key in this plan version */
				__( 'Prior plan version recommended “%1$s”; this version recommends “%2$s”.', 'aio-page-builder' ),
				$prior_template,
				$cur_template !== '' ? $cur_template : __( '(none)', 'aio-page-builder' )
			);
		}
		if ( $prior_norm === '' && $cur_norm !== '' ) {
			return sprintf(
				/* translators: %s: template key */
				__( 'Prior plan version did not set a template target for this page; this version recommends “%s”.', 'aio-page-builder' ),
				$cur_template
			);
		}
		if ( $prior_norm !== '' && $cur_norm === '' ) {
			return sprintf(
				/* translators: %s: template key from prior plan */
				__( 'Prior plan version recommended “%s”; this version does not specify a template target.', 'aio-page-builder' ),
				$prior_template
			);
		}
		return '';
	}

	/**
	 * @return array<string, string>|null Map of page identity key => prior template key (may be empty).
	 */
	private function prior_version_template_map( array $plan_definition ): ?array {
		$lineage_id = isset( $plan_definition[ Build_Plan_Schema::KEY_PLAN_LINEAGE_ID ] ) ? trim( (string) $plan_definition[ Build_Plan_Schema::KEY_PLAN_LINEAGE_ID ] ) : '';
		$seq        = isset( $plan_definition[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] ) ? (int) $plan_definition[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] : 0;
		if ( $lineage_id === '' || $seq <= 1 ) {
			return null;
		}
		$prior_post_id = $this->previous_version_resolver->get_previous_version_post_id( $lineage_id, $seq );
		if ( $prior_post_id === null || $prior_post_id <= 0 ) {
			return null;
		}
		$prior_def = $this->plan_repository->get_plan_definition( $prior_post_id );
		if ( $prior_def === array() ) {
			return null;
		}
		$items = $this->existing_page_items_from_definition( $prior_def );
		$map   = array();
		foreach ( $items as $prior_item ) {
			if ( ! is_array( $prior_item ) ) {
				continue;
			}
			$pp = isset( $prior_item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $prior_item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $prior_item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$ik = $this->page_identity_key( $pp );
			if ( $ik === '' ) {
				continue;
			}
			$map[ $ik ] = $this->template_key_from_payload( $pp );
		}
		return $map;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function existing_page_items_from_definition( array $definition ): array {
		$steps_raw = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step      = $steps_raw[ Existing_Page_Update_Bulk_Action_Service::STEP_INDEX_EXISTING_PAGE_CHANGES ] ?? null;
		if ( ! is_array( $step ) ) {
			return array();
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES ) {
			return array();
		}
		$items_raw = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$out       = array();
		foreach ( $items_raw as $it ) {
			if ( is_array( $it ) ) {
				$out[] = $it;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $payload Item payload.
	 */
	private function page_identity_key( array $payload ): string {
		$url = isset( $payload['current_page_url'] ) ? trim( (string) $payload['current_page_url'] ) : '';
		if ( $url !== '' ) {
			$path = \wp_parse_url( $url, PHP_URL_PATH );
			if ( ! is_string( $path ) || $path === '' ) {
				$path = $url;
			}
			$path = strtolower( rtrim( $path, '/' ) );
			if ( $path === '' ) {
				$path = '/';
			}
			return 'url:' . $path;
		}
		$title = isset( $payload['current_page_title'] ) ? trim( (string) $payload['current_page_title'] ) : '';
		if ( $title !== '' ) {
			return 'title:' . strtolower( $title );
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $payload Item payload.
	 */
	private function template_key_from_payload( array $payload ): string {
		$t = (string) ( $payload['target_template'] ?? $payload['template_key'] ?? $payload['target_template_key'] ?? '' );
		return trim( $t );
	}

	private function normalize_template_key( string $key ): string {
		return strtolower( trim( $key ) );
	}
}
