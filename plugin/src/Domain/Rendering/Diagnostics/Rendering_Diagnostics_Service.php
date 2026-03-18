<?php
/**
 * Builds machine-readable rendering diagnostics summaries (spec §17, §45.4, §59.5).
 * Describes rendering state without mutating content. Internal/admin use only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Produces render_summary, assembly_summary, and instantiation_readiness payloads for diagnostics and future UI.
 */
final class Rendering_Diagnostics_Service {

	/**
	 * Builds a render summary from one or more section render results.
	 *
	 * @param array<int, Section_Render_Result> $section_results Section results in order.
	 * @return array<string, mixed> render_summary shape: section_count, sections (list of section_key, variant, position, valid, error_count), valid_count, has_errors.
	 */
	public function build_render_summary( array $section_results ): array {
		$sections    = array();
		$valid_count = 0;
		foreach ( $section_results as $result ) {
			if ( ! $result instanceof Section_Render_Result ) {
				continue;
			}
			$errors = $result->get_errors();
			$valid  = empty( $errors );
			if ( $valid ) {
				++$valid_count;
			}
			$sections[] = array(
				'section_key' => $result->get_section_key(),
				'variant'     => $result->get_variant(),
				'position'    => $result->get_position(),
				'valid'       => $valid,
				'error_count' => count( $errors ),
			);
		}
		return array(
			'section_count' => count( $sections ),
			'sections'      => $sections,
			'valid_count'   => $valid_count,
			'has_errors'    => $valid_count < count( $sections ),
		);
	}

	/**
	 * Builds an assembly summary from a page assembly result.
	 *
	 * @param Page_Block_Assembly_Result $assembly
	 * @return array<string, mixed> assembly_summary shape: source_type, source_key, section_count, block_content_length, dynamic_dependency_count, survivability_notes, valid, error_count.
	 */
	public function build_assembly_summary( Page_Block_Assembly_Result $assembly ): array {
		$ordered = $assembly->get_ordered_sections();
		$errors  = $assembly->get_errors();
		return array(
			'source_type'              => $assembly->get_source_type(),
			'source_key'               => $assembly->get_source_key(),
			'section_count'            => count( $ordered ),
			'block_content_length'     => strlen( $assembly->get_block_content() ),
			'dynamic_dependency_count' => count( $assembly->get_dynamic_dependencies() ),
			'survivability_notes'      => $assembly->get_survivability_notes(),
			'valid'                    => $assembly->is_valid(),
			'error_count'              => count( $errors ),
		);
	}

	/**
	 * Builds an instantiation readiness summary from an instantiation result or a payload snapshot.
	 *
	 * @param Page_Instantiation_Result|null $result  Optional result (success, post_id, errors).
	 * @param array<string, mixed>|null      $payload Optional payload snapshot (source_type, source_key, page_title, etc.).
	 * @return array<string, mixed> instantiation_readiness shape: ready (bool), success (bool when result provided), post_id (int when result provided), error_count, source_type, source_key (when payload provided).
	 */
	public function build_instantiation_readiness( ?Page_Instantiation_Result $result = null, ?array $payload = null ): array {
		$out = array(
			'ready'       => true,
			'error_count' => 0,
		);
		if ( $result !== null ) {
			$out['success']     = $result->is_success();
			$out['post_id']     = $result->get_post_id();
			$out['ready']       = $result->is_success();
			$out['errors']      = $result->get_errors();
			$out['error_count'] = count( $result->get_errors() );
		}
		if ( $payload !== null ) {
			$out['source_type'] = $payload['source_type'] ?? '';
			$out['source_key']  = $payload['source_key'] ?? '';
			$out['page_title']  = isset( $payload['page_title'] ) && is_string( $payload['page_title'] ) ? $payload['page_title'] : '';
		}
		return $out;
	}
}
