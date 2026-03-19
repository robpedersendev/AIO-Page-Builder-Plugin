<?php
/**
 * Duplicate detection for crawled pages (spec §24.11; crawler contract §8).
 * Compares candidate to already-accepted pages by canonical URL, title+H1+content hash, redirect target.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Classification;

defined( 'ABSPATH' ) || exit;

/**
 * Determines if a candidate page duplicates an already-accepted page. Deterministic; no external calls.
 */
final class Duplicate_Detector {

	/**
	 * Known page record shape: normalized_url, canonical_url (optional), title, h1, content_hash.
	 *
	 * @var array{normalized_url: string, canonical_url?: string|null, title?: string|null, h1?: string|null, content_hash?: string|null}
	 */
	private const KNOWN_KEYS = array( 'normalized_url', 'canonical_url', 'title', 'h1', 'content_hash' );

	/**
	 * Checks whether the candidate duplicates any known page. Returns duplicate URL and reason or null.
	 *
	 * @param array{normalized_url: string, canonical_url?: string|null, title?: string|null, h1?: string|null, content_hash?: string|null, final_url?: string|null} $candidate Candidate page data.
	 * @param array<int, array{normalized_url: string, canonical_url?: string|null, title?: string|null, h1?: string|null, content_hash?: string|null}>                    $known_pages Already-accepted pages (same run).
	 * @return array{duplicate_of: string, reason: string}|null Null if no duplicate found.
	 */
	public function find_duplicate( array $candidate, array $known_pages ): ?array {
		$c_url   = $this->norm( $candidate['normalized_url'] ?? '' );
		$c_canon = $this->norm( $candidate['canonical_url'] ?? $c_url );
		$c_title = $this->norm( $candidate['title'] ?? '' );
		$c_h1    = $this->norm( $candidate['h1'] ?? '' );
		$c_hash  = $this->norm( $candidate['content_hash'] ?? '' );
		$c_final = $this->norm( $candidate['final_url'] ?? $c_url );
		if ( $c_url === '' ) {
			return null;
		}
		foreach ( $known_pages as $known ) {
			$k_url = $this->norm( $known['normalized_url'] ?? '' );
			if ( $k_url === '' ) {
				continue;
			}
			if ( $k_url === $c_url ) {
				continue;
			}
			$k_canon = $this->norm( $known['canonical_url'] ?? $k_url );
			$k_title = $this->norm( $known['title'] ?? '' );
			$k_h1    = $this->norm( $known['h1'] ?? '' );
			$k_hash  = $this->norm( $known['content_hash'] ?? '' );
			if ( $c_canon !== '' && $k_canon !== '' && $c_canon === $k_canon ) {
				return array(
					'duplicate_of' => $k_url,
					'reason'       => Classification_Result::REASON_DUPLICATE_CANONICAL,
				);
			}
			if ( $c_final !== '' && $c_final === $k_url ) {
				return array(
					'duplicate_of' => $k_url,
					'reason'       => Classification_Result::REASON_DUPLICATE_REDIRECT,
				);
			}
			if ( $c_hash !== '' && $k_hash !== '' && $c_hash === $k_hash ) {
				if ( $c_title === $k_title && $c_h1 === $k_h1 ) {
					return array(
						'duplicate_of' => $k_url,
						'reason'       => Classification_Result::REASON_DUPLICATE_CONTENT_HASH,
					);
				}
				return array(
					'duplicate_of' => $k_url,
					'reason'       => Classification_Result::REASON_DUPLICATE_CONTENT_HASH,
				);
			}
		}
		return null;
	}

	/**
	 * Builds a stable content hash for duplicate comparison (title + H1 + body excerpt).
	 *
	 * @param string $title Page title.
	 * @param string $h1    First H1 text.
	 * @param string $body_excerpt First N chars of normalized body text.
	 * @param int    $excerpt_len  Length of body excerpt to include.
	 * @return string Hash string (e.g. sha256 hex).
	 */
	public static function content_hash( string $title, string $h1, string $body_excerpt, int $excerpt_len = 2000 ): string {
		$t        = trim( $title );
		$h        = trim( $h1 );
		$b        = trim( substr( $body_excerpt, 0, $excerpt_len ) );
		$combined = $t . "\n" . $h . "\n" . $b;
		return hash( 'sha256', $combined );
	}

	private function norm( ?string $s ): string {
		return $s === null ? '' : trim( $s );
	}
}
