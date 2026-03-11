<?php
/**
 * Structured extraction outcome for a crawled page (spec §24.13, §24.14, §24.15).
 * Machine-readable: page_summary, heading_outline, navigation_summary, extraction_notes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Extraction;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of navigation and content summary extraction. Used for snapshot storage and AI input building.
 */
final class Extraction_Result {

	/** Extraction note: no navigation landmarks found. */
	public const NOTE_NO_NAV = 'no_nav_found';

	/** Extraction note: no title tag. */
	public const NOTE_NO_TITLE = 'no_title';

	/** Extraction note: no H1. */
	public const NOTE_NO_H1 = 'no_h1';

	/** Extraction note: no meta description. */
	public const NOTE_NO_META_DESCRIPTION = 'no_meta_description';

	/** Extraction note: heading structure malformed or skipped. */
	public const NOTE_HEADING_SKIP = 'heading_structure_skip';

	/** Extraction note: content excerpt truncated to bound. */
	public const NOTE_EXCERPT_TRUNCATED = 'excerpt_truncated';

	/** @var array{title: string, meta_description: string, h1: string, h2_outline: list<string>, word_count: int, content_excerpt: string, internal_link_count: int} */
	public $page_summary;

	/** @var list<array{level: int, text: string}> Heading outline (h1–h6 in document order). */
	public $heading_outline;

	/** @var list<array{context: string, label: string, url: string, depth?: int}> */
	public $navigation_summary;

	/** @var list<string> Diagnostic codes/messages. */
	public $extraction_notes;

	/**
	 * @param array{title: string, meta_description: string, h1: string, h2_outline: list<string>, word_count: int, content_excerpt: string, internal_link_count: int} $page_summary
	 * @param list<array{level: int, text: string}> $heading_outline
	 * @param list<array{context: string, label: string, url: string, depth?: int}> $navigation_summary
	 * @param list<string> $extraction_notes
	 */
	public function __construct(
		array $page_summary,
		array $heading_outline,
		array $navigation_summary,
		array $extraction_notes = array()
	) {
		$this->page_summary       = $page_summary;
		$this->heading_outline   = $heading_outline;
		$this->navigation_summary = $navigation_summary;
		$this->extraction_notes   = $extraction_notes;
	}

	/**
	 * Returns a machine-readable array for logging or API.
	 *
	 * @return array{page_summary: array, heading_outline: array, navigation_summary: array, extraction_notes: list<string>}
	 */
	public function to_array(): array {
		return array(
			'page_summary'       => $this->page_summary,
			'heading_outline'    => $this->heading_outline,
			'navigation_summary' => $this->navigation_summary,
			'extraction_notes'   => $this->extraction_notes,
		);
	}

	/**
	 * Returns JSON string suitable for snapshot summary_data column storage.
	 *
	 * @return string
	 */
	public function to_summary_data_json(): string {
		$json = wp_json_encode( $this->to_array() );
		return is_string( $json ) ? $json : '{}';
	}
}
