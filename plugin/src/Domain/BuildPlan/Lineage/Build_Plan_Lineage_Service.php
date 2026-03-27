<?php
/**
 * Groups build plan posts by lineage and computes next version sequence (onboarding fork).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Lineage;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Lists plan versions and resolves the next monotonic version index within a lineage.
 */
final class Build_Plan_Lineage_Service implements Lineage_Previous_Version_Resolver_Interface {

	private Build_Plan_Repository $repository;

	public function __construct( Build_Plan_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Returns lineage groups for onboarding UI: each row has lineage_id, display_title, version_count.
	 *
	 * @return array<int, array{lineage_id: string, display_title: string, version_count: int}>
	 */
	public function list_lineages_for_onboarding_selector(): array {
		$query      = new \WP_Query(
			array(
				'post_type'              => Object_Type_Keys::BUILD_PLAN,
				'post_status'            => 'any',
				'posts_per_page'         => 200,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'     => Build_Plan_Repository::META_PLAN_LINEAGE_ID,
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$by_lineage = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$lid = (string) \get_post_meta( $post->ID, Build_Plan_Repository::META_PLAN_LINEAGE_ID, true );
			$lid = trim( $lid );
			if ( $lid === '' ) {
				continue;
			}
			if ( ! isset( $by_lineage[ $lid ] ) ) {
				$by_lineage[ $lid ] = array();
			}
			$by_lineage[ $lid ][] = (int) $post->ID;
		}
		$out = array();
		foreach ( $by_lineage as $lineage_id => $post_ids ) {
			$title = $this->resolve_lineage_display_title( $post_ids );
			$out[] = array(
				'lineage_id'    => $lineage_id,
				'display_title' => $title,
				'version_count' => count( $post_ids ),
			);
		}
		usort(
			$out,
			static function ( array $a, array $b ): int {
				return strcmp( $a['display_title'], $b['display_title'] );
			}
		);
		return $out;
	}

	/**
	 * Returns all versions for a lineage ordered by version sequence then date.
	 *
	 * @return array<int, array{post_id: int, plan_id: string, version_label: string, version_seq: int, post_date: string, version_purpose: string, plan_status: string, ai_run_ref: string}>
	 */
	public function list_versions_in_lineage( string $lineage_id ): array {
		$lineage_id = trim( $lineage_id );
		if ( $lineage_id === '' ) {
			return array();
		}
		$query = new \WP_Query(
			array(
				'post_type'              => Object_Type_Keys::BUILD_PLAN,
				'post_status'            => 'any',
				'posts_per_page'         => 200,
				'orderby'                => 'date',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'   => Build_Plan_Repository::META_PLAN_LINEAGE_ID,
						'value' => $lineage_id,
					),
				),
			)
		);
		$rows  = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$def = $this->repository->get_plan_definition( $post->ID );
			$pid = (string) ( $def[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' );
			$lbl = (string) ( $def[ Build_Plan_Schema::KEY_PLAN_VERSION_LABEL ] ?? '' );
			$seq = isset( $def[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] ) ? (int) $def[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] : (int) \get_post_meta( $post->ID, Build_Plan_Repository::META_PLAN_VERSION_SEQ, true );
			if ( $seq < 1 ) {
				$seq = 1;
			}
			$purpose = (string) ( $def[ Build_Plan_Schema::KEY_VERSION_PURPOSE_DESCRIPTION ] ?? '' );
			$pstatus = (string) ( $def[ Build_Plan_Schema::KEY_STATUS ] ?? '' );
			$rows[]  = array(
				'post_id'         => (int) $post->ID,
				'plan_id'         => $pid,
				'version_label'   => $lbl !== '' ? $lbl : ( (string) $seq . '.0' ),
				'version_seq'     => $seq,
				'post_date'       => (string) $post->post_date,
				'version_purpose' => $purpose,
				'plan_status'     => $pstatus,
				'ai_run_ref'      => (string) ( $def[ Build_Plan_Schema::KEY_AI_RUN_REF ] ?? '' ),
			);
		}
		usort(
			$rows,
			static function ( array $a, array $b ): int {
				if ( $a['version_seq'] !== $b['version_seq'] ) {
					return $a['version_seq'] <=> $b['version_seq'];
				}
				return strcmp( $a['post_date'], $b['post_date'] );
			}
		);
		return $rows;
	}

	/**
	 * Next version index for a new plan post in this lineage (1-based).
	 */
	public function get_next_version_seq( string $lineage_id ): int {
		$lineage_id = trim( $lineage_id );
		if ( $lineage_id === '' ) {
			return 1;
		}
		$max   = 0;
		$query = new \WP_Query(
			array(
				'post_type'              => Object_Type_Keys::BUILD_PLAN,
				'post_status'            => 'any',
				'posts_per_page'         => 500,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'   => Build_Plan_Repository::META_PLAN_LINEAGE_ID,
						'value' => $lineage_id,
					),
				),
			)
		);
		foreach ( $query->posts as $post_id ) {
			$seq = (int) \get_post_meta( (int) $post_id, Build_Plan_Repository::META_PLAN_VERSION_SEQ, true );
			if ( $seq > $max ) {
				$max = $seq;
			}
		}
		return $max + 1;
	}

	/**
	 * Post ID of the plan with version_seq = current_version_seq - 1, or null if missing.
	 */
	public function get_previous_version_post_id( string $lineage_id, int $current_version_seq ): ?int {
		$lineage_id          = trim( $lineage_id );
		$current_version_seq = max( 0, $current_version_seq );
		if ( $lineage_id === '' || $current_version_seq <= 1 ) {
			return null;
		}
		$want = $current_version_seq - 1;
		$rows = $this->list_versions_in_lineage( $lineage_id );
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( (int) ( $row['version_seq'] ?? 0 ) === $want ) {
				$pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
				return $pid > 0 ? $pid : null;
			}
		}
		return null;
	}

	/**
	 * @param array<int, int> $post_ids
	 */
	private function resolve_lineage_display_title( array $post_ids ): string {
		$best = __( 'Site build plan', 'aio-page-builder' );
		foreach ( $post_ids as $post_id ) {
			$def = $this->repository->get_plan_definition( $post_id );
			$t   = isset( $def[ Build_Plan_Schema::KEY_PLAN_TITLE ] ) ? trim( (string) $def[ Build_Plan_Schema::KEY_PLAN_TITLE ] ) : '';
			if ( $t !== '' ) {
				$best = $t;
				break;
			}
			$post = \get_post( $post_id );
			if ( $post instanceof \WP_Post && $post->post_title !== '' ) {
				$best = $post->post_title;
				break;
			}
		}
		return $best;
	}

	/**
	 * Admin list: each lineage row includes ordered versions (for grouped Build Plans screen).
	 *
	 * @return array<int, array{lineage_id: string, display_title: string, version_count: int, versions: array<int, array<string, mixed>>}>
	 */
	public function list_lineages_with_versions_for_admin(): array {
		$lineages = $this->list_lineages_for_onboarding_selector();
		$out      = array();
		foreach ( $lineages as $row ) {
			if ( ! isset( $row['lineage_id'] ) || ! is_string( $row['lineage_id'] ) ) {
				continue;
			}
			$lid   = $row['lineage_id'];
			$vers  = $this->list_versions_in_lineage( $lid );
			$out[] = array(
				'lineage_id'    => $lid,
				'display_title' => (string) ( $row['display_title'] ?? $lid ),
				'version_count' => count( $vers ),
				'versions'      => $vers,
			);
		}
		return $out;
	}
}
