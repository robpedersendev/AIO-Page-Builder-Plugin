<?php
/**
 * Extracts bounded content summary and heading outline from HTML (spec §24.13, §24.14).
 * Title, meta description, H1, H2 outline, word count, content excerpt (500-word cap), extraction notes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Extraction;

defined( 'ABSPATH' ) || exit;

/**
 * Produces page_summary and heading_outline from page HTML. Bounded; no full-text archival.
 */
final class Content_Summary_Extractor {

	/** Max H2s in outline (spec §24.13). */
	private const MAX_H2_OUTLINE = 10;

	/** Max words in content excerpt (spec §24.13: concise page summary limited to 500 words). */
	private const MAX_EXCERPT_WORDS = 500;

	/** Max characters for content_excerpt fallback. */
	private const MAX_EXCERPT_CHARS = 3000;

	/**
	 * Extracts page summary and heading outline from HTML.
	 *
	 * @param string      $html       Raw HTML.
	 * @param string|null $base_host  Optional host for internal-link count (e.g. example.com).
	 * @return array{page_summary: array, heading_outline: array, extraction_notes: array<int, string>}
	 */
	public function extract( string $html, ?string $base_host = null ): array {
		$notes = array();
		$title = $this->extract_title( $html );
		if ( $title === '' ) {
			$notes[] = Extraction_Result::NOTE_NO_TITLE;
		}
		$meta_desc = $this->extract_meta_description( $html );
		if ( $meta_desc === '' ) {
			$notes[] = Extraction_Result::NOTE_NO_META_DESCRIPTION;
		}
		$h1 = $this->extract_first_h1( $html );
		if ( $h1 === '' ) {
			$notes[] = Extraction_Result::NOTE_NO_H1;
		}
		$body_plain      = $this->strip_to_body_text( $html );
		$word_count      = str_word_count( $body_plain );
		$excerpt         = $this->bounded_excerpt( $body_plain, $notes );
		$h2_outline      = $this->extract_h2_outline( $html );
		$heading_outline = $this->extract_heading_outline( $html );
		if ( count( $heading_outline ) === 0 && ( $h1 !== '' || count( $h2_outline ) > 0 ) ) {
			$notes[] = Extraction_Result::NOTE_HEADING_SKIP;
		}
		$internal_link_count = $this->count_internal_links( $html, $base_host );
		$page_summary        = array(
			'title'               => $title,
			'meta_description'    => $meta_desc,
			'h1'                  => $h1,
			'h2_outline'          => $h2_outline,
			'word_count'          => $word_count,
			'content_excerpt'     => $excerpt,
			'internal_link_count' => $internal_link_count,
		);
		return array(
			'page_summary'     => $page_summary,
			'heading_outline'  => $heading_outline,
			'extraction_notes' => $notes,
		);
	}

	private function extract_title( string $html ): string {
		if ( ! preg_match( '#<title[^>]*>([^<]+)</title>#is', $html, $m ) ) {
			return '';
		}
		return trim( wp_strip_all_tags( $m[1] ) );
	}

	private function extract_meta_description( string $html ): string {
		if ( ! preg_match( '#<meta\s+name=["\']description["\'][^>]*content=["\']([^"\']*)["\']#is', $html, $m ) ) {
			if ( ! preg_match( '#<meta\s+content=["\']([^"\']*)["\'][^>]*name=["\']description["\']#is', $html, $m ) ) {
				return '';
			}
		}
		return trim( $this->decode_entities( $m[1] ?? '' ) );
	}

	private function extract_first_h1( string $html ): string {
		if ( ! preg_match( '#<h1[^>]*>([^<]+)</h1>#is', $html, $m ) ) {
			return '';
		}
		return trim( wp_strip_all_tags( $m[1] ) );
	}

	/** @return array<int, string> First N H2 texts. */
	private function extract_h2_outline( string $html ): array {
		$out = array();
		if ( preg_match_all( '#<h2[^>]*>([^<]+)</h2>#is', $html, $matches ) ) {
			foreach ( array_slice( $matches[1], 0, self::MAX_H2_OUTLINE ) as $text ) {
				$out[] = trim( wp_strip_all_tags( $text ) );
			}
		}
		return $out;
	}

	/** @return array<int, array{level: int, text: string}> */
	private function extract_heading_outline( string $html ): array {
		$out = array();
		if ( preg_match_all( '#<h([1-6])[^>]*>([^<]+)</h\1>#is', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$out[] = array(
					'level' => (int) $m[1],
					'text'  => trim( wp_strip_all_tags( $m[2] ) ),
				);
			}
		}
		return $out;
	}

	private function strip_to_body_text( string $html ): string {
		$html = preg_replace( '#<script[^>]*>.*?</script>#is', ' ', $html );
		$html = preg_replace( '#<style[^>]*>.*?</style>#is', ' ', $html );
		$html = wp_strip_all_tags( $html );
		$html = preg_replace( '/\s+/', ' ', $html );
		return trim( $html );
	}

	private function bounded_excerpt( string $body_plain, array &$notes ): string {
		$words_raw = preg_split( '/\s+/', $body_plain, -1, PREG_SPLIT_NO_EMPTY );
		$words     = $words_raw !== false ? $words_raw : array();
		if ( count( $words ) > self::MAX_EXCERPT_WORDS ) {
			$notes[] = Extraction_Result::NOTE_EXCERPT_TRUNCATED;
			$words   = array_slice( $words, 0, self::MAX_EXCERPT_WORDS );
		}
		$excerpt = implode( ' ', $words );
		if ( strlen( $excerpt ) > self::MAX_EXCERPT_CHARS ) {
			$excerpt = substr( $excerpt, 0, self::MAX_EXCERPT_CHARS );
		}
		return $excerpt;
	}

	private function count_internal_links( string $html, ?string $base_host ): int {
		if ( ! preg_match_all( '#<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\']#is', $html, $matches ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $matches[1] as $href ) {
			$href = trim( $this->decode_entities( $href ) );
			if ( $href === '' || strpos( $href, '#' ) === 0 || strpos( strtolower( $href ), 'mailto:' ) === 0 ) {
				continue;
			}
			if ( strpos( $href, '/' ) === 0 ) {
				++$count;
				continue;
			}
			if ( $base_host !== null && $base_host !== '' ) {
				$host = \wp_parse_url( $href, PHP_URL_HOST );
				if ( is_string( $host ) && strtolower( $host ) === strtolower( $base_host ) ) {
					++$count;
				}
			}
		}
		return $count;
	}

	private function decode_entities( string $s ): string {
		return html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
