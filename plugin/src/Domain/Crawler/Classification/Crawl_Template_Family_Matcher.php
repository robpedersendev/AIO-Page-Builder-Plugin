<?php
/**
 * Maps crawled page records to advisory template-family and hierarchy-class hints (spec §59.7, §1.9.6; Prompt 209).
 * Heuristics only; no execution authority. Low-confidence and unsupported outcomes remain explicit.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Classification;

defined( 'ABSPATH' ) || exit;

/**
 * Classifies a page record into likely page-template class, section-family patterns, and rebuild signals.
 */
final class Crawl_Template_Family_Matcher {

	/** Page hierarchy classes (spec page-template-directory-ia; stable ordering). */
	public const PAGE_CLASS_TOP_LEVEL    = 'top_level';
	public const PAGE_CLASS_HUB          = 'hub';
	public const PAGE_CLASS_NESTED_HUB   = 'nested_hub';
	public const PAGE_CLASS_CHILD_DETAIL = 'child_detail';

	/** URL path segments or title keywords that suggest top-level (about, contact, home). */
	private const TOP_LEVEL_SLUGS = array(
		'about',
		'contact',
		'home',
		'faq',
		'pricing',
		'team',
		'support',
		'request',
		'legal',
		'privacy',
		'terms',
	);

	/** URL path segments that suggest hub (listing/category level). */
	private const HUB_SLUGS = array(
		'services',
		'service',
		'products',
		'product',
		'locations',
		'location',
		'events',
		'event',
		'blog',
		'news',
		'resources',
		'offerings',
		'offering',
		'directory',
		'categories',
		'category',
	);

	/** Minimum word count for strong content signal; below suggests weak structure. */
	private const MIN_WORDS_STRONG = 200;

	/** Path depth threshold: 0–1 segments after host -> top_level candidate; 2 -> hub/nested_hub; 3+ -> child_detail. */
	private const PATH_DEPTH_TOP       = 1;
	private const PATH_DEPTH_HUB_MAX   = 2;
	private const PATH_DEPTH_CHILD_MIN = 3;

	/**
	 * Matches a page record to template-family and hierarchy hints.
	 *
	 * @param array<string, mixed> $page_record Snapshot row: url, title_snapshot, page_classification, summary_data, hierarchy_clues, navigation_participation, etc.
	 * @return Crawl_Template_Match_Result
	 */
	public function match( array $page_record ): Crawl_Template_Match_Result {
		$url            = (string) ( $page_record['url'] ?? '' );
		$title          = (string) ( $page_record['title_snapshot'] ?? '' );
		$classification = (string) ( $page_record['page_classification'] ?? '' );
		$summary_json   = (string) ( $page_record['summary_data'] ?? '' );
		$in_nav         = (int) ( $page_record['navigation_participation'] ?? 0 ) > 0;

		$summary    = $this->decode_summary_data( $summary_json );
		$path_depth = $this->path_depth( $url );
		$slug_hint  = $this->slug_hint_from_url_and_title( $url, $title );

		// * Unsupported: no URL or classification indicates exclude/unsupported.
		if ( $url === '' || $classification === Classification_Result::CLASSIFICATION_UNSUPPORTED ) {
			return $this->unsupported_result( 'missing_url_or_unsupported_classification' );
		}
		if ( $classification === Classification_Result::CLASSIFICATION_DUPLICATE ) {
			return $this->unsupported_result( 'duplicate_page' );
		}

		$word_count      = (int) ( $summary['page_summary']['word_count'] ?? 0 );
		$has_h1          = trim( (string) ( $summary['page_summary']['h1'] ?? '' ) ) !== '';
		$heading_outline = $summary['heading_outline'] ?? array();

		$suggested_class    = $this->infer_page_class( $path_depth, $slug_hint, $in_nav, $word_count, $has_h1 );
		$suggested_families = $this->infer_template_families( $slug_hint, $suggested_class );
		$confidence         = $this->confidence( $path_depth, $slug_hint, $in_nav, $word_count, $has_h1 );

		$section_summary = $this->infer_section_family_summary( $heading_outline, $word_count );
		$rebuild_summary = $this->build_rebuild_signal_summary( $classification, $word_count, $has_h1, $suggested_class, $slug_hint );

		$hint = array(
			'suggested_page_class' => $suggested_class,
			'suggested_families'   => $suggested_families,
			'confidence'           => $confidence,
			'path_depth'           => $path_depth,
			'slug_hint'            => $slug_hint,
			'in_navigation'        => $in_nav,
		);
		if ( $confidence === Crawl_Template_Match_Result::CONFIDENCE_UNSUPPORTED ) {
			$hint['unsupported_reason'] = $rebuild_summary['mismatch_reasons'][0] ?? 'unsupported';
		}

		return new Crawl_Template_Match_Result( $hint, $section_summary, $rebuild_summary );
	}

	/**
	 * Infers page hierarchy class from path depth, slug, nav, and content signals.
	 *
	 * @param int    $path_depth
	 * @param string $slug_hint  top_level|hub|child|unknown
	 * @param bool   $in_nav
	 * @param int    $word_count
	 * @param bool   $has_h1
	 * @return string One of PAGE_CLASS_*.
	 */
	private function infer_page_class( int $path_depth, string $slug_hint, bool $in_nav, int $word_count, bool $has_h1 ): string {
		if ( $path_depth <= self::PATH_DEPTH_TOP ) {
			if ( $slug_hint === 'hub' ) {
				return self::PAGE_CLASS_HUB;
			}
			if ( $slug_hint === 'child' ) {
				return self::PAGE_CLASS_CHILD_DETAIL;
			}
			return self::PAGE_CLASS_TOP_LEVEL;
		}
		if ( $path_depth <= self::PATH_DEPTH_HUB_MAX ) {
			if ( $slug_hint === 'top_level' ) {
				return self::PAGE_CLASS_TOP_LEVEL;
			}
			if ( $slug_hint === 'child' ) {
				return self::PAGE_CLASS_CHILD_DETAIL;
			}
			return $in_nav ? self::PAGE_CLASS_HUB : self::PAGE_CLASS_NESTED_HUB;
		}
		// 3+ segments -> child/detail unless slug strongly suggests hub (e.g. /products).
		if ( $slug_hint === 'hub' && $in_nav ) {
			return self::PAGE_CLASS_NESTED_HUB;
		}
		return self::PAGE_CLASS_CHILD_DETAIL;
	}

	/**
	 * Suggests template_family slugs for planning (advisory).
	 *
	 * @param string $slug_hint
	 * @param string $suggested_class
	 * @return array<int, string>
	 */
	private function infer_template_families( string $slug_hint, string $suggested_class ): array {
		$families = array();
		if ( $slug_hint === 'top_level' || $suggested_class === self::PAGE_CLASS_TOP_LEVEL ) {
			$families = array( 'home', 'about', 'contact', 'services', 'offerings' );
		}
		if ( $slug_hint === 'hub' || $suggested_class === self::PAGE_CLASS_HUB || $suggested_class === self::PAGE_CLASS_NESTED_HUB ) {
			$families = array_merge( $families, array( 'services', 'products', 'offerings', 'directories', 'locations' ) );
		}
		if ( $suggested_class === self::PAGE_CLASS_CHILD_DETAIL ) {
			$families = array_merge( $families, array( 'product_detail', 'service_detail', 'profile_entity', 'location_detail' ) );
		}
		$families = array_values( array_unique( $families ) );
		return $families;
	}

	/**
	 * Infers likely section-purpose-family patterns from heading outline (advisory).
	 *
	 * @param array<int, array{level: int, text: string}> $heading_outline
	 * @param int                                         $word_count
	 * @return array<string, mixed> section_family_match_summary
	 */
	private function infer_section_family_summary( array $heading_outline, int $word_count ): array {
		$matched  = array();
		$h1_count = 0;
		$h2_count = 0;
		$h3_count = 0;
		foreach ( $heading_outline as $h ) {
			$level = (int) ( $h['level'] ?? 0 );
			if ( $level === 1 ) {
				++$h1_count;
			} elseif ( $level === 2 ) {
				++$h2_count;
			} elseif ( $level === 3 ) {
				++$h3_count;
			}
		}
		if ( $h1_count >= 1 && $h2_count <= 2 && $h3_count <= 4 ) {
			$matched[] = 'hero_like';
		}
		if ( $h2_count >= 4 || $h3_count >= 6 ) {
			$matched[] = 'listing_like';
		}
		if ( $h2_count >= 3 && $word_count < 800 ) {
			$matched[] = 'faq_like';
		}
		if ( $word_count >= self::MIN_WORDS_STRONG && count( $heading_outline ) >= 4 ) {
			$matched[] = 'explainer_like';
		}
		return array(
			'matched_section_families' => array_values( array_unique( $matched ) ),
			'confidence'               => count( $matched ) > 0 ? Crawl_Template_Match_Result::CONFIDENCE_MEDIUM : Crawl_Template_Match_Result::CONFIDENCE_LOW,
		);
	}

	/**
	 * Builds page_rebuild_signal_summary: likely_rebuild, mismatch_reasons, weak_structure_signals.
	 *
	 * @param string $classification
	 * @param int    $word_count
	 * @param bool   $has_h1
	 * @param string $suggested_class
	 * @param string $slug_hint
	 * @return array<string, mixed>
	 */
	private function build_rebuild_signal_summary( string $classification, int $word_count, bool $has_h1, string $suggested_class, string $slug_hint ): array {
		$mismatch_reasons = array();
		$weak_signals     = array();
		if ( ! $has_h1 ) {
			$weak_signals[] = 'no_h1';
		}
		if ( $word_count < 100 ) {
			$weak_signals[] = 'low_word_count';
		}
		if ( $classification === Classification_Result::CLASSIFICATION_LOW_VALUE && $slug_hint !== 'unknown' ) {
			$mismatch_reasons[] = 'low_value_but_structured_path';
		}
		$likely_rebuild = count( $weak_signals ) > 0 || count( $mismatch_reasons ) > 0;
		return array(
			'likely_rebuild'         => $likely_rebuild,
			'mismatch_reasons'       => $mismatch_reasons,
			'weak_structure_signals' => $weak_signals,
		);
	}

	private function path_depth( string $url ): int {
		$parsed = parse_url( $url );
		$path   = (string) ( is_array( $parsed ) && isset( $parsed['path'] ) ? $parsed['path'] : '/' );
		$path   = trim( $path, '/' );
		if ( $path === '' ) {
			return 0;
		}
		return count( array_filter( explode( '/', $path ) ) );
	}

	/**
	 * Returns top_level|hub|child|unknown from URL path and title.
	 *
	 * @param string $url
	 * @param string $title
	 * @return string
	 */
	private function slug_hint_from_url_and_title( string $url, string $title ): string {
		$parsed   = parse_url( $url );
		$path     = (string) ( is_array( $parsed ) && isset( $parsed['path'] ) ? $parsed['path'] : '/' );
		$path     = strtolower( trim( $path, '/' ) );
		$segments = array_filter( explode( '/', $path ) );
		$last     = (string) ( end( $segments ) ?? '' );
		$combined = $path . ' ' . strtolower( $title );

		foreach ( self::TOP_LEVEL_SLUGS as $slug ) {
			if ( $path === $slug || $last === $slug || strpos( $combined, $slug ) !== false ) {
				return 'top_level';
			}
		}
		foreach ( self::HUB_SLUGS as $slug ) {
			if ( strpos( $path, $slug ) !== false || strpos( $combined, $slug ) !== false ) {
				// * If this is the last segment (e.g. /services), hub; if there are more (e.g. /services/cleaning), child.
				if ( $last === $slug || count( $segments ) <= 2 ) {
					return 'hub';
				}
				return 'child';
			}
		}
		// Numeric or slug-like last segment with deeper path suggests child/detail.
		if ( count( $segments ) >= 2 && ( is_numeric( $last ) || preg_match( '/^[a-z0-9\-]+$/i', $last ) ) ) {
			return 'child';
		}
		return 'unknown';
	}

	/**
	 * Computes confidence: high when path + slug + nav + content align; low when weak or conflicting.
	 *
	 * @param int    $path_depth
	 * @param string $slug_hint
	 * @param bool   $in_nav
	 * @param int    $word_count
	 * @param bool   $has_h1
	 * @return string
	 */
	private function confidence( int $path_depth, string $slug_hint, bool $in_nav, int $word_count, bool $has_h1 ): string {
		$signals = 0;
		if ( $slug_hint !== 'unknown' ) {
			++$signals;
		}
		if ( $path_depth <= 2 ) {
			++$signals;
		}
		if ( $in_nav ) {
			++$signals;
		}
		if ( $word_count >= self::MIN_WORDS_STRONG && $has_h1 ) {
			++$signals;
		}
		if ( $signals >= 3 ) {
			return Crawl_Template_Match_Result::CONFIDENCE_HIGH;
		}
		if ( $signals >= 2 ) {
			return Crawl_Template_Match_Result::CONFIDENCE_MEDIUM;
		}
		return Crawl_Template_Match_Result::CONFIDENCE_LOW;
	}

	private function unsupported_result( string $reason ): Crawl_Template_Match_Result {
		$hint = array(
			'suggested_page_class' => '',
			'suggested_families'   => array(),
			'confidence'           => Crawl_Template_Match_Result::CONFIDENCE_UNSUPPORTED,
			'unsupported_reason'   => $reason,
		);
		return new Crawl_Template_Match_Result(
			$hint,
			array(
				'matched_section_families' => array(),
				'confidence'               => Crawl_Template_Match_Result::CONFIDENCE_UNSUPPORTED,
			),
			array(
				'likely_rebuild'         => false,
				'mismatch_reasons'       => array(),
				'weak_structure_signals' => array(),
			)
		);
	}

	/**
	 * @param string $summary_json
	 * @return array{page_summary: array, heading_outline: array}
	 */
	private function decode_summary_data( string $summary_json ): array {
		if ( trim( $summary_json ) === '' ) {
			return array(
				'page_summary'    => array(),
				'heading_outline' => array(),
			);
		}
		$decoded = json_decode( $summary_json, true );
		if ( ! is_array( $decoded ) ) {
			return array(
				'page_summary'    => array(),
				'heading_outline' => array(),
			);
		}
		return array(
			'page_summary'    => is_array( $decoded['page_summary'] ?? null ) ? $decoded['page_summary'] : array(),
			'heading_outline' => is_array( $decoded['heading_outline'] ?? null ) ? $decoded['heading_outline'] : array(),
		);
	}
}
