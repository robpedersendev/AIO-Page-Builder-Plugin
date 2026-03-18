<?php
/**
 * Builds deterministic page instantiation payloads from page assembly results (spec §17.7, §19, rendering-contract §7).
 * Create-ready and update-ready shapes; no persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Page;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;

/**
 * Builds stable payload from Page_Block_Assembly_Result for use by Page_Instantiator.
 * Payload keys: source_type, source_key, source_version, page_title, page_slug_candidate, post_content,
 * post_status_candidate, provenance_meta, assignment_updates, survivability_notes; update-ready adds target_post_id.
 *
 * Example create-ready payload:
 * [
 *   'source_type' => 'page_template', 'source_key' => 'tpl_landing', 'source_version' => '',
 *   'page_title' => 'Landing Page', 'page_slug_candidate' => 'landing', 'post_content' => '<!-- wp:html -->...',
 *   'post_status_candidate' => 'draft', 'provenance_meta' => [ '_aio_build_source_type' => 'page_template', ... ],
 *   'assignment_updates' => [ 'source_type' => 'page_template', 'source_key' => 'tpl_landing', 'section_keys' => [ 'st01_hero' ] ],
 *   'survivability_notes' => [ 'durable_native_blocks' ],
 * ]
 *
 * Example update-ready payload (adds target_post_id):
 * [ ...same keys..., 'target_post_id' => 1001 ]
 */
final class Page_Instantiation_Payload_Builder {

	/** Meta key for build source type (plugin-scoped). */
	public const META_SOURCE_TYPE = '_aio_build_source_type';

	/** Meta key for build source key (plugin-scoped). */
	public const META_SOURCE_KEY = '_aio_build_source_key';

	/** Meta key for build source version (plugin-scoped). */
	public const META_SOURCE_VERSION = '_aio_build_source_version';

	/** Default post status when creating. */
	private const DEFAULT_STATUS = 'draft';

	/**
	 * Builds a create-ready payload from assembly result and overrides.
	 *
	 * @param Page_Block_Assembly_Result $assembly  Completed assembly result (block_content used for post_content).
	 * @param string                     $page_title Required page title.
	 * @param array<string, mixed>       $overrides Optional: page_slug_candidate, post_status_candidate, source_version.
	 * @return array<string, mixed> Payload for Page_Instantiator::create_page().
	 */
	public function build_create_payload( Page_Block_Assembly_Result $assembly, string $page_title, array $overrides = array() ): array {
		$slug    = isset( $overrides['page_slug_candidate'] ) && is_string( $overrides['page_slug_candidate'] )
			? $this->sanitize_slug_candidate( $overrides['page_slug_candidate'] )
			: '';
		$status  = isset( $overrides['post_status_candidate'] ) && is_string( $overrides['post_status_candidate'] )
			? $this->sanitize_status( $overrides['post_status_candidate'] )
			: self::DEFAULT_STATUS;
		$version = isset( $overrides['source_version'] ) && is_string( $overrides['source_version'] )
			? sanitize_text_field( $overrides['source_version'] )
			: '';

		return $this->build_payload( $assembly, $page_title, $slug, $status, $version, null );
	}

	/**
	 * Builds an update-ready payload from assembly result and target post ID.
	 *
	 * @param Page_Block_Assembly_Result $assembly       Completed assembly result.
	 * @param int                        $target_post_id Existing page ID to update.
	 * @param array<string, mixed>       $overrides      Optional: page_title, page_slug_candidate, post_status_candidate, source_version. Empty values mean keep existing.
	 * @return array<string, mixed> Payload for Page_Instantiator::update_page().
	 */
	public function build_update_payload( Page_Block_Assembly_Result $assembly, int $target_post_id, array $overrides = array() ): array {
		$title   = isset( $overrides['page_title'] ) && is_string( $overrides['page_title'] ) ? sanitize_text_field( $overrides['page_title'] ) : '';
		$slug    = isset( $overrides['page_slug_candidate'] ) && is_string( $overrides['page_slug_candidate'] ) ? $this->sanitize_slug_candidate( $overrides['page_slug_candidate'] ) : '';
		$status  = isset( $overrides['post_status_candidate'] ) && is_string( $overrides['post_status_candidate'] ) ? $this->sanitize_status( $overrides['post_status_candidate'] ) : '';
		$version = isset( $overrides['source_version'] ) && is_string( $overrides['source_version'] ) ? sanitize_text_field( $overrides['source_version'] ) : '';

		$payload                   = $this->build_payload( $assembly, $title, $slug, $status, $version, $target_post_id );
		$payload['target_post_id'] = $target_post_id;
		return $payload;
	}

	/**
	 * @param string|null $version
	 * @param int|null    $target_post_id
	 * @return array<string, mixed>
	 */
	private function build_payload( Page_Block_Assembly_Result $assembly, string $page_title, string $page_slug_candidate, string $post_status_candidate, string $version, ?int $target_post_id ): array {
		$source_type  = $assembly->get_source_type();
		$source_key   = $assembly->get_source_key();
		$section_keys = array();
		foreach ( $assembly->get_ordered_sections() as $section ) {
			$key = $section['section_key'] ?? null;
			if ( is_string( $key ) && $key !== '' ) {
				$section_keys[] = $key;
			}
		}

		$provenance_meta = array(
			self::META_SOURCE_TYPE    => $source_type,
			self::META_SOURCE_KEY     => $source_key,
			self::META_SOURCE_VERSION => $version,
		);

		$assignment_updates = array(
			'source_type'  => $source_type,
			'source_key'   => $source_key,
			'section_keys' => $section_keys,
		);

		$out = array(
			'source_type'           => $source_type,
			'source_key'            => $source_key,
			'source_version'        => $version,
			'page_title'            => $page_title,
			'page_slug_candidate'   => $page_slug_candidate,
			'post_content'          => $assembly->get_block_content(),
			'post_status_candidate' => $post_status_candidate,
			'provenance_meta'       => $provenance_meta,
			'assignment_updates'    => $assignment_updates,
			'survivability_notes'   => $assembly->get_survivability_notes(),
		);
		if ( $target_post_id !== null ) {
			$out['target_post_id'] = $target_post_id;
		}
		return $out;
	}

	private function sanitize_slug_candidate( string $slug ): string {
		$slug = \sanitize_title( $slug );
		return substr( $slug, 0, 200 );
	}

	private function sanitize_status( string $status ): string {
		$status  = sanitize_text_field( $status );
		$allowed = array( 'draft', 'publish', 'private', 'pending' );
		return in_array( $status, $allowed, true ) ? $status : '';
	}
}
