<?php
/**
 * Meaningful-page classifier (spec §24.5, §24.10–24.12; crawler contract §5).
 * Classifies fetched HTML into meaningful, low_value, duplicate, or unsupported with explicit reason codes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Classification;

defined( 'ABSPATH' ) || exit;

/**
 * Classifies a single fetched page from HTML and context. Uses Duplicate_Detector when known pages provided.
 */
final class Meaningful_Page_Classifier {

	/** Minimum word count for content-weight criterion (contract §5). */
	private const MIN_WORDS_CONTENT_WEIGHT = 150;

	/** URL path segments or title keywords that suggest a likely role (contract §5). */
	private const LIKELY_ROLE_SEGMENTS = array(
		'about',
		'contact',
		'services',
		'service',
		'products',
		'product',
		'locations',
		'location',
		'faq',
		'pricing',
		'events',
		'event',
		'team',
		'blog',
		'news',
		'support',
		'request',
	);

	/** @var Duplicate_Detector */
	private $duplicate_detector;

	public function __construct( Duplicate_Detector $duplicate_detector ) {
		$this->duplicate_detector = $duplicate_detector;
	}

	/**
	 * Classifies a page from its HTML and optional context.
	 *
	 * @param string                                                                                                                              $normalized_url   Page URL.
	 * @param string                                                                                                                              $html             Response body (HTML).
	 * @param array{canonical_url?: string|null, final_url?: string|null, in_navigation?: bool, link_count?: int}                                 $context Optional context.
	 * @param array<int, array{normalized_url: string, canonical_url?: string|null, title?: string|null, h1?: string|null, content_hash?: string|null}> $known_pages Already-accepted pages for duplicate check.
	 * @return Classification_Result
	 */
	public function classify(
		string $normalized_url,
		string $html,
		array $context = array(),
		array $known_pages = array()
	): Classification_Result {
		$extracted        = $this->extract_minimal( $html );
		$title            = $extracted['title'];
		$h1               = $extracted['h1'];
		$word_count       = $extracted['word_count'];
		$body_excerpt     = $extracted['body_excerpt'];
		$content_hash     = Duplicate_Detector::content_hash( $title, $h1, $body_excerpt );
		$in_nav           = $context['in_navigation'] ?? false;
		$link_count       = (int) ( $context['link_count'] ?? 0 );
		$canonical_url    = $context['canonical_url'] ?? $normalized_url;
		$final_url        = $context['final_url'] ?? $normalized_url;
		$meaningful_flags = array(
			'has_h1'     => $h1 !== '',
			'word_count' => $word_count,
			'in_nav'     => $in_nav,
			'link_count' => $link_count,
		);
		if ( $html === '' ) {
			return new Classification_Result(
				Classification_Result::CLASSIFICATION_UNSUPPORTED,
				array( Classification_Result::REASON_FETCH_FAILED ),
				null,
				$meaningful_flags,
				Classification_Result::RETENTION_EXCLUDE,
				null
			);
		}
		$candidate = array(
			'normalized_url' => $normalized_url,
			'canonical_url'  => $canonical_url,
			'final_url'      => $final_url,
			'title'          => $title,
			'h1'             => $h1,
			'content_hash'   => $content_hash,
		);
		$dup       = $this->duplicate_detector->find_duplicate( $candidate, $known_pages );
		if ( $dup !== null ) {
			return new Classification_Result(
				Classification_Result::CLASSIFICATION_DUPLICATE,
				array( $dup['reason'] ),
				$dup['duplicate_of'],
				$meaningful_flags,
				Classification_Result::RETENTION_EXCLUDE,
				$content_hash
			);
		}
		$reasons            = array();
		$has_content_weight = $h1 !== '' && $word_count >= self::MIN_WORDS_CONTENT_WEIGHT;
		if ( $has_content_weight ) {
			$reasons[] = Classification_Result::REASON_CONTENT_WEIGHT;
		}
		if ( $in_nav ) {
			$reasons[] = Classification_Result::REASON_IN_NAVIGATION;
		}
		$likely_role = $this->likely_role( $normalized_url, $title );
		if ( $likely_role ) {
			$reasons[] = Classification_Result::REASON_LIKELY_ROLE;
		}
		if ( $link_count >= 3 ) {
			$reasons[] = Classification_Result::REASON_LINK_WEIGHT;
		}
		if ( count( $reasons ) > 0 ) {
			return new Classification_Result(
				Classification_Result::CLASSIFICATION_MEANINGFUL,
				$reasons,
				null,
				$meaningful_flags,
				Classification_Result::RETENTION_RETAIN,
				$content_hash
			);
		}
		return new Classification_Result(
			Classification_Result::CLASSIFICATION_LOW_VALUE,
			array( Classification_Result::REASON_THIN_CONTENT ),
			null,
			$meaningful_flags,
			Classification_Result::RETENTION_EXCLUDE,
			$content_hash
		);
	}

	/**
	 * Extracts title, first H1, word count, and body excerpt from HTML (minimal parse; no full DOM).
	 *
	 * @param string $html Raw HTML.
	 * @return array{title: string, h1: string, word_count: int, body_excerpt: string}
	 */
	private function extract_minimal( string $html ): array {
		$title = '';
		$h1    = '';
		if ( preg_match( '#<title[^>]*>([^<]+)</title>#is', $html, $m ) ) {
			$title = trim( wp_strip_all_tags( $m[1] ) );
		}
		if ( preg_match( '#<h1[^>]*>([^<]+)</h1>#is', $html, $m ) ) {
			$h1 = trim( wp_strip_all_tags( $m[1] ) );
		}
		$body         = preg_replace( '#<script[^>]*>.*?</script>#is', ' ', $html );
		$body         = preg_replace( '#<style[^>]*>.*?</style>#is', ' ', $body );
		$body         = wp_strip_all_tags( $body );
		$body         = preg_replace( '/\s+/', ' ', $body );
		$word_count   = str_word_count( $body );
		$body_excerpt = substr( $body, 0, 2000 );
		return array(
			'title'        => $title,
			'h1'           => $h1,
			'word_count'   => $word_count,
			'body_excerpt' => $body_excerpt,
		);
	}

	private function likely_role( string $url, string $title ): bool {
		$path        = (string) parse_url( $url, PHP_URL_PATH );
		$path        = strtolower( trim( $path, '/' ) );
		$segments    = array_filter( explode( '/', $path ) );
		$title_lower = strtolower( $title );
		foreach ( self::LIKELY_ROLE_SEGMENTS as $seg ) {
			if ( in_array( $seg, $segments, true ) ) {
				return true;
			}
			if ( strpos( $title_lower, $seg ) !== false ) {
				return true;
			}
		}
		return false;
	}
}
