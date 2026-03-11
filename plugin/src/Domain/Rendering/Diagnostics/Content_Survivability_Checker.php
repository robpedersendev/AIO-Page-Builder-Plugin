<?php
/**
 * Inspects page/assembly content for prohibited runtime lock-in and reports survivability (spec §9.12, §17.3, §17.4).
 * Internal diagnostic utility; no mutation; server-side and deterministic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;

/**
 * Detects prohibited heavy dependence on plugin runtime (shortcodes, unreplaced tokens) and flags justified dynamic output.
 * Does not treat "page still loads" as sufficient; requires meaningful durable content.
 */
final class Content_Survivability_Checker {

	/** Plugin shortcode prefix that indicates runtime dependency. */
	private const PLUGIN_SHORTCODE_PATTERN = '/\[aio[_\-][^\]]*\]/';

	/** Unreplaced token placeholder (e.g. LPagery) that would require plugin at render time. */
	private const UNREPLACED_TOKEN_PATTERN = '/\{\{[^}]+\}\}/';

	/**
	 * Checks post_content and optional context for survivability.
	 *
	 * @param string              $post_content Raw post_content (block markup or HTML).
	 * @param array<string, mixed> $context      Optional: survivability_notes (list), source_type, source_key for reporting.
	 * @return Content_Survivability_Result
	 */
	public function check( string $post_content, array $context = array() ): Content_Survivability_Result {
		$prohibited = array();

		if ( preg_match( self::PLUGIN_SHORTCODE_PATTERN, $post_content ) ) {
			$prohibited[] = 'plugin_shortcode_detected';
		}
		if ( preg_match( self::UNREPLACED_TOKEN_PATTERN, $post_content ) ) {
			$prohibited[] = 'unreplaced_token_placeholder';
		}

		$notes = isset( $context['survivability_notes'] ) && is_array( $context['survivability_notes'] )
			? $context['survivability_notes']
			: array();
		$dynamic_flags = array();
		if ( in_array( 'generateblocks_compatible', $notes, true ) ) {
			$dynamic_flags[] = 'generateblocks_compatible_optional';
		}

		$editability = array();
		if ( strpos( $post_content, '<!-- wp:' ) !== false ) {
			$editability[] = 'block_markup_editable_in_block_editor';
		}
		if ( trim( $post_content ) !== '' && empty( $prohibited ) ) {
			$editability[] = 'content_meaningful_without_plugin_runtime';
		}

		$is_survivable = empty( $prohibited );
		$deactivation_ready = $is_survivable;

		return new Content_Survivability_Result(
			$is_survivable,
			$prohibited,
			$dynamic_flags,
			$editability,
			$deactivation_ready
		);
	}

	/**
	 * Checks a Page_Block_Assembly_Result's block_content and survivability_notes (convenience).
	 *
	 * @param Page_Block_Assembly_Result $assembly_result
	 * @return Content_Survivability_Result
	 */
	public function check_assembly_result( $assembly_result ): Content_Survivability_Result {
		$content = $assembly_result->get_block_content();
		$context = array(
			'survivability_notes' => $assembly_result->get_survivability_notes(),
			'source_type'         => $assembly_result->get_source_type(),
			'source_key'          => $assembly_result->get_source_key(),
		);
		return $this->check( $content, $context );
	}
}
