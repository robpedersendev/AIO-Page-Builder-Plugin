<?php
/**
 * Narrow page create/update helpers using instantiation payloads (spec §17.7, §19, rendering-contract §7).
 * Server-side only; callers must enforce capabilities and nonces. Produces standard WordPress pages with durable content.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Page;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;

/**
 * Converts payloads into WordPress page records. Does not perform Build Plan, approval, or execution orchestration.
 */
final class Page_Instantiator {

	/** Post type for built pages. */
	private const POST_TYPE = 'page';

	/** @var Page_Instantiation_Payload_Builder */
	private $payload_builder;

	public function __construct( Page_Instantiation_Payload_Builder $payload_builder ) {
		$this->payload_builder = $payload_builder;
	}

	/**
	 * Builds a create-ready payload from assembly result.
	 *
	 * @param Page_Block_Assembly_Result $assembly   Completed assembly.
	 * @param string                    $page_title Page title.
	 * @param array<string, mixed>      $overrides  Optional: page_slug_candidate, post_status_candidate, source_version.
	 * @return array<string, mixed>
	 */
	public function build_create_payload( Page_Block_Assembly_Result $assembly, string $page_title, array $overrides = array() ): array {
		return $this->payload_builder->build_create_payload( $assembly, $page_title, $overrides );
	}

	/**
	 * Builds an update-ready payload from assembly result and target post ID.
	 *
	 * @param Page_Block_Assembly_Result $assembly       Completed assembly.
	 * @param int                       $target_post_id Existing page ID.
	 * @param array<string, mixed>      $overrides      Optional: page_title, page_slug_candidate, post_status_candidate, source_version.
	 * @return array<string, mixed>
	 */
	public function build_update_payload( Page_Block_Assembly_Result $assembly, int $target_post_id, array $overrides = array() ): array {
		return $this->payload_builder->build_update_payload( $assembly, $target_post_id, $overrides );
	}

	/**
	 * Creates a new WordPress page from a create-ready payload. Fails safely on invalid payload.
	 *
	 * @param array<string, mixed> $payload From build_create_payload().
	 * @return Page_Instantiation_Result
	 */
	public function create_page( array $payload ): Page_Instantiation_Result {
		$errors = $this->validate_create_payload( $payload );
		if ( ! empty( $errors ) ) {
			return new Page_Instantiation_Result( false, 0, $payload, $errors );
		}

		$title  = isset( $payload['page_title'] ) && is_string( $payload['page_title'] ) ? sanitize_text_field( $payload['page_title'] ) : '';
		$slug   = isset( $payload['page_slug_candidate'] ) && is_string( $payload['page_slug_candidate'] ) ? $payload['page_slug_candidate'] : '';
		$status = isset( $payload['post_status_candidate'] ) && is_string( $payload['post_status_candidate'] ) && $payload['post_status_candidate'] !== ''
			? $payload['post_status_candidate']
			: 'draft';
		$content = isset( $payload['post_content'] ) && is_string( $payload['post_content'] ) ? $payload['post_content'] : '';

		$post_data = array(
			'post_type'    => self::POST_TYPE,
			'post_title'   => $title !== '' ? $title : 'Untitled',
			'post_name'    => $slug !== '' ? $slug : '',
			'post_status'  => $status,
			'post_content' => $content,
		);

		$id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $id ) || $id === 0 ) {
			return new Page_Instantiation_Result( false, 0, $payload, array( 'wp_insert_post failed' ) );
		}

		$this->save_provenance_meta( (int) $id, $payload );
		return new Page_Instantiation_Result( true, (int) $id, $payload, array() );
	}

	/**
	 * Updates an existing WordPress page from an update-ready payload. Fails safely on invalid payload or target.
	 *
	 * @param array<string, mixed> $payload From build_update_payload(); must contain target_post_id.
	 * @return Page_Instantiation_Result
	 */
	public function update_page( array $payload ): Page_Instantiation_Result {
		$errors = $this->validate_update_payload( $payload );
		if ( ! empty( $errors ) ) {
			return new Page_Instantiation_Result( false, 0, $payload, $errors );
		}

		$target_id = (int) $payload['target_post_id'];
		$post      = get_post( $target_id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== self::POST_TYPE ) {
			return new Page_Instantiation_Result( false, 0, $payload, array( 'Target post is not a page or does not exist' ) );
		}

		$post_data = array( 'ID' => $target_id );
		if ( isset( $payload['post_content'] ) && is_string( $payload['post_content'] ) ) {
			$post_data['post_content'] = $payload['post_content'];
		}
		$title = isset( $payload['page_title'] ) && is_string( $payload['page_title'] ) ? sanitize_text_field( $payload['page_title'] ) : '';
		if ( $title !== '' ) {
			$post_data['post_title'] = $title;
		}
		$slug = isset( $payload['page_slug_candidate'] ) && is_string( $payload['page_slug_candidate'] ) ? $payload['page_slug_candidate'] : '';
		if ( $slug !== '' ) {
			$post_data['post_name'] = $slug;
		}
		$status = isset( $payload['post_status_candidate'] ) && is_string( $payload['post_status_candidate'] ) && $payload['post_status_candidate'] !== '' ? $payload['post_status_candidate'] : '';
		if ( $status !== '' ) {
			$post_data['post_status'] = $status;
		}

		$updated = wp_update_post( $post_data, true );
		if ( is_wp_error( $updated ) || $updated === 0 ) {
			return new Page_Instantiation_Result( false, 0, $payload, array( 'wp_update_post failed' ) );
		}

		$this->save_provenance_meta( (int) $updated, $payload );
		return new Page_Instantiation_Result( true, (int) $updated, $payload, array() );
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return list<string>
	 */
	private function validate_create_payload( array $payload ): array {
		$errors = array();
		if ( empty( $payload['source_type'] ) || ! is_string( $payload['source_type'] ) ) {
			$errors[] = 'Missing or invalid source_type';
		}
		if ( empty( $payload['source_key'] ) || ! is_string( $payload['source_key'] ) ) {
			$errors[] = 'Missing or invalid source_key';
		}
		if ( ! isset( $payload['post_content'] ) || ! is_string( $payload['post_content'] ) ) {
			$errors[] = 'Missing post_content';
		}
		if ( empty( $payload['page_title'] ) || ! is_string( $payload['page_title'] ) ) {
			$errors[] = 'Missing or empty page_title';
		}
		if ( isset( $payload['target_post_id'] ) && (int) $payload['target_post_id'] > 0 ) {
			$errors[] = 'Create payload must not contain a positive target_post_id';
		}
		return $errors;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return list<string>
	 */
	private function validate_update_payload( array $payload ): array {
		$errors = array();
		if ( ! isset( $payload['target_post_id'] ) || (int) $payload['target_post_id'] <= 0 ) {
			$errors[] = 'Missing or invalid target_post_id';
		}
		if ( empty( $payload['source_type'] ) || ! is_string( $payload['source_type'] ) ) {
			$errors[] = 'Missing or invalid source_type';
		}
		if ( empty( $payload['source_key'] ) || ! is_string( $payload['source_key'] ) ) {
			$errors[] = 'Missing or invalid source_key';
		}
		if ( ! isset( $payload['post_content'] ) || ! is_string( $payload['post_content'] ) ) {
			$errors[] = 'Missing post_content';
		}
		return $errors;
	}

	/**
	 * @param int                  $post_id
	 * @param array<string, mixed> $payload
	 */
	private function save_provenance_meta( int $post_id, array $payload ): void {
		$meta = $payload['provenance_meta'] ?? array();
		if ( ! is_array( $meta ) ) {
			return;
		}
		foreach ( array( Page_Instantiation_Payload_Builder::META_SOURCE_TYPE, Page_Instantiation_Payload_Builder::META_SOURCE_KEY, Page_Instantiation_Payload_Builder::META_SOURCE_VERSION ) as $key ) {
			if ( isset( $meta[ $key ] ) && is_string( $meta[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $meta[ $key ] ) );
			}
		}
	}
}
