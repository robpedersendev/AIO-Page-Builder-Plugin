<?php
/**
 * Extracts bounded navigation link summaries from HTML (spec §24.12, §24.14).
 * Detects links in nav, header, footer landmarks. No full DOM; regex-based extraction.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Extraction;

defined( 'ABSPATH' ) || exit;

/**
 * Produces navigation_summary (context, label, url, depth) from page HTML. Bounded and deterministic.
 */
final class Navigation_Extractor {

	/** Max number of nav links to extract per page (spec: bounded). */
	private const MAX_NAV_LINKS = 100;

	/**
	 * Extracts navigation links from HTML. Looks for <nav>, <header>, <footer> and role="navigation".
	 *
	 * @param string $html Raw HTML.
	 * @return array<int, array{context: string, label: string, url: string, depth?: int}>
	 */
	public function extract( string $html ): array {
		$out      = array();
		$seen     = array();
		$patterns = array(
			'nav'    => '#<nav[^>]*>(.*?)</nav>#is',
			'header' => '#<header[^>]*>(.*?)</header>#is',
			'footer' => '#<footer[^>]*>(.*?)</footer>#is',
		);
		foreach ( $patterns as $context => $pattern ) {
			if ( preg_match_all( $pattern, $html, $blocks, PREG_SET_ORDER ) ) {
				foreach ( $blocks as $block ) {
					$fragment = $block[1] ?? '';
					$this->extract_links_from_fragment( $fragment, $context, $out, $seen );
				}
			}
		}
		// * Role-based: div/section with role="navigation" (capture to matching closing tag so inner <a> is included)
		$role_patterns = array( '#<div[^>]*\s+role=["\']navigation["\'][^>]*>(.*?)</div>#is', '#<section[^>]*\s+role=["\']navigation["\'][^>]*>(.*?)</section>#is' );
		foreach ( $role_patterns as $role_pattern ) {
			if ( preg_match_all( $role_pattern, $html, $role_blocks, PREG_SET_ORDER ) ) {
				foreach ( $role_blocks as $rb ) {
					$this->extract_links_from_fragment( $rb[1] ?? '', 'nav', $out, $seen );
				}
			}
		}
		return array_slice( $out, 0, self::MAX_NAV_LINKS );
	}

	/**
	 * Pulls <a href="...">label</a> from a fragment and appends to $out (deduplicated by href+label).
	 *
	 * @param string                                                                      $fragment HTML fragment.
	 * @param string                                                                      $context Context key (nav, header, footer).
	 * @param array<int, array{context: string, label: string, url: string, depth?: int}> $out Mutable output list.
	 * @param array<string, true>                                                         $seen Mutable set of "url\tlabel" to avoid duplicates.
	 */
	private function extract_links_from_fragment( string $fragment, string $context, array &$out, array &$seen ): void {
		if ( preg_match_all( '#<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]*(?:<[^>]+>[^<]*)*?)</a>#is', $fragment, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$url   = trim( $this->decode_entities( $m[1] ?? '' ) );
				$label = trim( wp_strip_all_tags( $m[2] ?? '' ) );
				$label = preg_replace( '/\s+/', ' ', $label ) ?? $label;
				if ( $url === '' ) {
					continue;
				}
				$key = $url . "\t" . $label;
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = true;
				$out[]        = array(
					'context' => $context,
					'label'   => $label,
					'url'     => $url,
				);
			}
		}
	}

	private function decode_entities( string $s ): string {
		return html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
