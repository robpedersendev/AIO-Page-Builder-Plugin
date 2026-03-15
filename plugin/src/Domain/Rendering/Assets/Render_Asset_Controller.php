<?php
/**
 * Asset loading controls and requirement derivation for rendering (spec §7.7, §17, §55.5, §55.8).
 * Declarative, bounded, selector-contract-aware. Does not enqueue or build assets.
 * Preview contexts enforce asset budgets for list/detail to keep admin responsive.
 * Plugin base stylesheet (aio-page-builder-base) is registered and conditionally enqueued by
 * Frontend_Style_Enqueue_Service (Prompt 245) on built pages and approved contexts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Assets;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Derives asset requirements from section/page outputs and provides loading policy helpers.
 *
 * Example asset requirement summary (from summarize_requirements):
 * [ ['handle' => 'aio-render-section-st01_hero', 'source_ref' => 'st01_hero', 'scope' => 'frontend', 'meta' => ['none' => true]], ... ]
 */
final class Render_Asset_Controller {

	/** Prefix for logical section asset handles. */
	private const SECTION_HANDLE_PREFIX = 'aio-render-section-';

	/** Preview context: list (directory row, compare cell). */
	public const PREVIEW_CONTEXT_LIST = 'list';

	/** Preview context: detail (single template full preview). */
	public const PREVIEW_CONTEXT_DETAIL = 'detail';

	/** Default max asset handles for list preview (spec §55.8). */
	private const DEFAULT_MAX_HANDLES_PREVIEW_LIST = 12;

	/** Default max asset handles for detail preview (spec §55.8). */
	private const DEFAULT_MAX_HANDLES_PREVIEW_DETAIL = 60;

	/**
	 * Collects asset requirements from ordered section render results.
	 *
	 * @param array<int, Section_Render_Result> $section_results
	 * @param string                             $scope SCOPE_FRONTEND | SCOPE_ADMIN | SCOPE_SECTION.
	 * @return list<Render_Asset_Requirements>
	 */
	public function get_requirements_from_sections( array $section_results, string $scope = Render_Asset_Requirements::SCOPE_FRONTEND ): array {
		$out = array();
		$seen = array();
		foreach ( $section_results as $result ) {
			if ( ! $result instanceof Section_Render_Result ) {
				continue;
			}
			$section_key = $result->get_section_key();
			$hints = $result->get_asset_hints();
			if ( ! is_array( $hints ) ) {
				$hints = array();
			}
			$handle = self::SECTION_HANDLE_PREFIX . $section_key;
			if ( isset( $seen[ $handle ] ) ) {
				continue;
			}
			$seen[ $handle ] = true;
			$meta = array();
			if ( ! empty( $hints['frontend_css'] ) ) {
				$meta['frontend_css'] = true;
			}
			if ( ! empty( $hints['none'] ) ) {
				$meta['none'] = true;
			}
			$anim = $result->get_animation_resolution();
			if ( $anim !== null ) {
				$meta['animation_tier']     = $anim['effective_tier'] ?? 'none';
				$meta['animation_families'] = $anim['effective_families'] ?? array();
			}
			$out[] = new Render_Asset_Requirements( $handle, $section_key, $scope, $meta );
		}
		return $out;
	}

	/**
	 * Collects asset requirements from a page assembly result (from its ordered_sections).
	 *
	 * @param Page_Block_Assembly_Result $assembly
	 * @param string                     $scope
	 * @return list<Render_Asset_Requirements>
	 */
	public function get_requirements_from_assembly( Page_Block_Assembly_Result $assembly, string $scope = Render_Asset_Requirements::SCOPE_FRONTEND ): array {
		$ordered = $assembly->get_ordered_sections();
		$out = array();
		$seen = array();
		foreach ( $ordered as $s ) {
			$section_key = $s['section_key'] ?? '';
			if ( ! is_string( $section_key ) || $section_key === '' ) {
				continue;
			}
			$handle = self::SECTION_HANDLE_PREFIX . $section_key;
			if ( isset( $seen[ $handle ] ) ) {
				continue;
			}
			$seen[ $handle ] = true;
			$hints = $s['asset_hints'] ?? array();
			$meta = array();
			if ( is_array( $hints ) && ! empty( $hints['frontend_css'] ) ) {
				$meta['frontend_css'] = true;
			}
			if ( is_array( $hints ) && ! empty( $hints['none'] ) ) {
				$meta['none'] = true;
			}
			$anim = $s['animation_resolution'] ?? null;
			if ( is_array( $anim ) ) {
				$meta['animation_tier']     = $anim['effective_tier'] ?? 'none';
				$meta['animation_families'] = $anim['effective_families'] ?? array();
			}
			$out[] = new Render_Asset_Requirements( $handle, $section_key, $scope, $meta );
		}
		return $out;
	}

	/**
	 * Returns a deterministic asset requirement summary (list of to_array() for reporting).
	 *
	 * @param list<Render_Asset_Requirements> $requirements
	 * @return array<int, array<string, mixed>>
	 */
	public function summarize_requirements( array $requirements ): array {
		$out = array();
		foreach ( $requirements as $req ) {
			if ( $req instanceof Render_Asset_Requirements ) {
				$out[] = $req->to_array();
			}
		}
		return $out;
	}

	/**
	 * Whether assets for the given scope should be considered for loading in the given context.
	 * Policy helper; does not perform enqueue. Context = 'frontend' | 'admin'.
	 *
	 * @param string $requirement_scope SCOPE_FRONTEND | SCOPE_ADMIN | SCOPE_SECTION.
	 * @param string $context          'frontend' | 'admin'.
	 * @return bool
	 */
	public function should_load_for_context( string $requirement_scope, string $context ): bool {
		if ( $requirement_scope === Render_Asset_Requirements::SCOPE_SECTION ) {
			return true;
		}
		if ( $context === 'frontend' && $requirement_scope === Render_Asset_Requirements::SCOPE_FRONTEND ) {
			return true;
		}
		if ( $context === 'admin' && $requirement_scope === Render_Asset_Requirements::SCOPE_ADMIN ) {
			return true;
		}
		if ( $context === 'admin' && $requirement_scope === Render_Asset_Requirements::SCOPE_FRONTEND ) {
			return false;
		}
		return false;
	}

	/**
	 * Applies asset budget for preview contexts (list/detail). Trims requirements to max handles (spec §55.5, §55.8).
	 *
	 * @param list<Render_Asset_Requirements> $requirements    Full list from get_requirements_from_sections or get_requirements_from_assembly.
	 * @param string                          $preview_context PREVIEW_CONTEXT_LIST | PREVIEW_CONTEXT_DETAIL.
	 * @param int                             $max_handles     Optional. 0 = use default for context.
	 * @return list<Render_Asset_Requirements>
	 */
	public function apply_preview_asset_budget( array $requirements, string $preview_context, int $max_handles = 0 ): array {
		if ( $max_handles <= 0 ) {
			$max_handles = $preview_context === self::PREVIEW_CONTEXT_DETAIL
				? self::DEFAULT_MAX_HANDLES_PREVIEW_DETAIL
				: self::DEFAULT_MAX_HANDLES_PREVIEW_LIST;
		}
		$list = array();
		foreach ( $requirements as $req ) {
			if ( $req instanceof Render_Asset_Requirements ) {
				$list[] = $req;
			}
		}
		return \array_slice( $list, 0, $max_handles );
	}
}
