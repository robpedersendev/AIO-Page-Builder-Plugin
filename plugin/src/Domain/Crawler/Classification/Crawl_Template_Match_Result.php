<?php
/**
 * Result DTO for crawl-to-template-family matching (spec §59.7, §1.9.6; Prompt 209).
 *
 * Stable payloads: crawl_template_family_hint, section_family_match_summary, page_rebuild_signal_summary.
 * Advisory only; confidence is explicit so low-confidence matches are not overclaimed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Classification;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of matching a crawled page to template families and hierarchy classes.
 */
final class Crawl_Template_Match_Result {

	/** Confidence: high – strong signals (path, nav, content). */
	public const CONFIDENCE_HIGH = 'high';

	/** Confidence: medium – some signals. */
	public const CONFIDENCE_MEDIUM = 'medium';

	/** Confidence: low – weak or conflicting signals; advisory only. */
	public const CONFIDENCE_LOW = 'low';

	/** Confidence: unsupported – page shape or classification not suitable for matching. */
	public const CONFIDENCE_UNSUPPORTED = 'unsupported';

	/** @var array<string, mixed> crawl_template_family_hint (suggested_page_class, suggested_families, confidence, etc.). */
	private $crawl_template_family_hint;

	/** @var array<string, mixed> section_family_match_summary. */
	private $section_family_match_summary;

	/** @var array<string, mixed> page_rebuild_signal_summary. */
	private $page_rebuild_signal_summary;

	public function __construct(
		array $crawl_template_family_hint,
		array $section_family_match_summary,
		array $page_rebuild_signal_summary
	) {
		$this->crawl_template_family_hint   = $crawl_template_family_hint;
		$this->section_family_match_summary = $section_family_match_summary;
		$this->page_rebuild_signal_summary  = $page_rebuild_signal_summary;
	}

	/** @return array<string, mixed> */
	public function get_crawl_template_family_hint(): array {
		return $this->crawl_template_family_hint;
	}

	/** @return array<string, mixed> */
	public function get_section_family_match_summary(): array {
		return $this->section_family_match_summary;
	}

	/** @return array<string, mixed> */
	public function get_page_rebuild_signal_summary(): array {
		return $this->page_rebuild_signal_summary;
	}

	/** Whether the match is low-confidence or unsupported (advisory; do not overclaim). */
	public function is_low_confidence(): bool {
		$conf = (string) ( $this->crawl_template_family_hint['confidence'] ?? '' );
		return $conf === self::CONFIDENCE_LOW || $conf === self::CONFIDENCE_UNSUPPORTED;
	}

	/**
	 * Stable payload for persistence in hierarchy_clues or planning input.
	 *
	 * @return array<string, mixed>
	 */
	public function to_payload(): array {
		return array(
			'crawl_template_family_hint'   => $this->crawl_template_family_hint,
			'section_family_match_summary' => $this->section_family_match_summary,
			'page_rebuild_signal_summary'  => $this->page_rebuild_signal_summary,
		);
	}

	/**
	 * JSON string for storing in hierarchy_clues column.
	 *
	 * @return string
	 */
	public function to_json(): string {
		$payload = $this->to_payload();
		$json = function_exists( 'wp_json_encode' ) ? \wp_json_encode( $payload ) : json_encode( $payload );
		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Parses hierarchy_clues JSON back into payload (for reading); returns null if invalid.
	 *
	 * @param string|null $hierarchy_clues_json
	 * @return array<string, mixed>|null
	 */
	public static function from_hierarchy_clues_json( ?string $hierarchy_clues_json ): ?array {
		if ( $hierarchy_clues_json === null || trim( $hierarchy_clues_json ) === '' ) {
			return null;
		}
		$decoded = json_decode( $hierarchy_clues_json, true );
		if ( ! is_array( $decoded ) || ! isset( $decoded['crawl_template_family_hint'] ) ) {
			return null;
		}
		return $decoded;
	}
}
