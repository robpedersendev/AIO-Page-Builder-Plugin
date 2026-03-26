<?php
/**
 * Executes a queued crawl run: bounded BFS discovery, fetch, classification, snapshot persistence (spec §24).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Execution;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Discovery\Discovery_Result;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Discovery_Service;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Normalizer;
use AIOPageBuilder\Domain\Crawler\Extraction\Content_Summary_Extractor;
use AIOPageBuilder\Domain\Crawler\Extraction\Extraction_Result;
use AIOPageBuilder\Domain\Crawler\Extraction\Navigation_Extractor;
use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Request_Policy;
use AIOPageBuilder\Domain\Crawler\Fetch\HTML_Fetcher;
use AIOPageBuilder\Domain\Crawler\Classification\Meaningful_Page_Classifier;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Service;
use AIOPageBuilder\Domain\Crawler\Queue\Crawl_Enqueue_Service;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;

/**
 * Runs one crawl session to completion, partial stop, or failure; releases per-site enqueue lock in a finally block.
 */
final class Crawl_Run_Processor {

	/** WordPress single-event hook for processing one run after enqueue. */
	public const CRON_HOOK = 'aio_pb_process_crawl_run';

	private const MAX_HREFS_PER_PAGE = 200;

	private Crawl_Snapshot_Service $snapshot_service;

	private Crawl_Profile_Service $profile_service;

	private URL_Discovery_Service $discovery;

	private HTML_Fetcher $fetcher;

	private Meaningful_Page_Classifier $classifier;

	private Navigation_Extractor $nav_extractor;

	private Content_Summary_Extractor $content_extractor;

	private URL_Normalizer $normalizer;

	private Fetch_Request_Policy $fetch_policy;

	private Crawl_Enqueue_Service $enqueue_service;

	public function __construct(
		Crawl_Snapshot_Service $snapshot_service,
		Crawl_Profile_Service $profile_service,
		URL_Discovery_Service $discovery,
		HTML_Fetcher $fetcher,
		Meaningful_Page_Classifier $classifier,
		Navigation_Extractor $nav_extractor,
		Content_Summary_Extractor $content_extractor,
		URL_Normalizer $normalizer,
		Fetch_Request_Policy $fetch_policy,
		Crawl_Enqueue_Service $enqueue_service
	) {
		$this->snapshot_service  = $snapshot_service;
		$this->profile_service   = $profile_service;
		$this->discovery         = $discovery;
		$this->fetcher           = $fetcher;
		$this->classifier        = $classifier;
		$this->nav_extractor     = $nav_extractor;
		$this->content_extractor = $content_extractor;
		$this->normalizer        = $normalizer;
		$this->fetch_policy      = $fetch_policy;
		$this->enqueue_service   = $enqueue_service;
	}

	/**
	 * Processes a crawl run (typically from WP-Cron). Idempotent against missing session.
	 *
	 * @param string $crawl_run_id Crawl run id.
	 * @return void
	 */
	public function process( string $crawl_run_id ): void {
		$crawl_run_id = \sanitize_text_field( $crawl_run_id );
		if ( $crawl_run_id === '' ) {
			return;
		}
		$session = $this->snapshot_service->get_session( $crawl_run_id );
		if ( $session === null ) {
			return;
		}
		$site_host = (string) ( $session[ Crawl_Snapshot_Payload_Builder::SESSION_SITE_HOST ] ?? '' );
		$site_host = strtolower( trim( $site_host ) );
		try {
			if ( $site_host === '' ) {
				$this->finalize_session(
					$crawl_run_id,
					0,
					0,
					0,
					0,
					Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED
				);
				return;
			}
			$site_url = isset( $session['site_url'] ) && is_string( $session['site_url'] ) ? trim( $session['site_url'] ) : '';
			if ( $site_url === '' ) {
				$site_url = (string) \site_url( '/' );
			}
			$profile_key = (string) ( $session[ Crawl_Snapshot_Payload_Builder::SESSION_CRAWL_PROFILE_KEY ] ?? '' );
			if ( $profile_key === '' ) {
				$settings    = $session[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] ?? array();
				$profile_key = is_array( $settings ) ? (string) ( $settings['crawl_profile_key'] ?? '' ) : '';
			}
			$profile_key = $this->profile_service->resolve_profile_key( $profile_key );
			$max_pages   = $this->profile_service->get_max_pages_for_profile( $profile_key );
			$max_depth   = $this->profile_service->get_max_depth_for_profile( $profile_key );

			$seed_results = $this->discovery->discover_from_seeds( array( $site_url ) );
			/** @var array<int, array{0: string, 1: int}> $queue */
			$queue = array();
			$seen  = array();
			foreach ( $seed_results as $r ) {
				if ( $r->acceptance_status !== Discovery_Result::STATUS_ACCEPTED ) {
					continue;
				}
				$key = $r->dedup_key;
				if ( $key === '' || isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = true;
				$queue[]      = array( $r->normalized_url, 0 );
			}
			if ( $queue === array() ) {
				$this->finalize_session(
					$crawl_run_id,
					0,
					0,
					0,
					0,
					Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED
				);
				return;
			}

			$fetch_attempts = 0;
			$accepted       = 0;
			$excluded       = 0;
			$failed         = 0;
			/** @var array<int, array{normalized_url: string, canonical_url?: string|null, title?: string|null, h1?: string|null, content_hash?: string|null}> $known_pages */
			$known_pages = array();
			$queue_left  = false;
			$first_fetch = true;

			while ( $queue !== array() && $fetch_attempts < $max_pages ) {
				$item  = array_shift( $queue );
				$url   = $item[0];
				$depth = $item[1];
				if ( $depth > $max_depth ) {
					++$excluded;
					continue;
				}
				if ( ! $first_fetch ) {
					$delay_ms = $this->fetch_policy->get_delay_after_request_ms();
					if ( $delay_ms > 0 ) {
						usleep( $delay_ms * 1000 );
					}
				}
				$first_fetch = false;
				++$fetch_attempts;
				$fetch      = $this->fetcher->fetch( $url );
				$crawled_at = \gmdate( 'c' );
				if ( ! $fetch->is_success() || $fetch->html === null || $fetch->html === '' ) {
					++$failed;
					$err = $fetch->error_code ?? $fetch->fetch_status;
					$this->snapshot_service->store_page_record(
						$crawl_run_id,
						$url,
						array(
							Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS => Crawl_Snapshot_Payload_Builder::STATUS_ERROR,
							Crawl_Snapshot_Payload_Builder::PAGE_ERROR_STATE  => is_string( $err ) ? \substr( $err, 0, 200 ) : 'fetch_failed',
							Crawl_Snapshot_Payload_Builder::PAGE_CRAWLED_AT   => $crawled_at,
						)
					);
					continue;
				}
				$html           = $fetch->html;
				$nav            = $this->nav_extractor->extract( $html );
				$link_count     = $this->count_hrefs_in_html( $html );
				$in_nav         = $this->is_url_represented_in_nav( $url, $nav );
				$class          = $this->classifier->classify(
					$url,
					$html,
					array(
						'in_navigation' => $in_nav,
						'link_count'    => $link_count,
						'final_url'     => $fetch->final_url ?? $url,
					),
					$known_pages
				);
				$content_bundle = $this->content_extractor->extract( $html, $site_host );
				$ex_notes       = $content_bundle['extraction_notes'];
				if ( $nav === array() ) {
					$ex_notes[] = Extraction_Result::NOTE_NO_NAV;
				}
				$extraction = new Extraction_Result(
					$content_bundle['page_summary'],
					$content_bundle['heading_outline'],
					$nav,
					$ex_notes
				);
				$title_snap = $content_bundle['page_summary']['title'] ?? '';
				$this->snapshot_service->record_classification(
					$crawl_run_id,
					$url,
					$class,
					$title_snap !== '' ? $title_snap : null
				);
				$this->snapshot_service->record_extraction( $crawl_run_id, $url, $extraction );
				$nav_flag = count( $nav ) > 0 ? 1 : 0;
				$this->snapshot_service->store_page_record(
					$crawl_run_id,
					$url,
					array(
						Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS => Crawl_Snapshot_Payload_Builder::STATUS_COMPLETED,
						Crawl_Snapshot_Payload_Builder::PAGE_CRAWLED_AT   => $crawled_at,
						Crawl_Snapshot_Payload_Builder::PAGE_NAVIGATION  => $nav_flag,
					)
				);
				$this->snapshot_service->enrich_page_with_template_hint( $crawl_run_id, $url );

				if ( $class->is_retain() ) {
					++$accepted;
					$known_pages[] = array(
						'normalized_url' => $url,
						'title'          => $title_snap !== '' ? $title_snap : null,
						'h1'             => $content_bundle['page_summary']['h1'] ?? null,
						'content_hash'   => $class->content_hash,
					);
				} else {
					++$excluded;
				}

				if ( $depth < $max_depth && $fetch_attempts < $max_pages ) {
					$hrefs = $this->extract_hrefs_from_html( $html );
					$raw   = array();
					foreach ( $hrefs as $h ) {
						$abs = $this->absolutize_url( $url, $h );
						if ( $abs !== '' ) {
							$raw[] = $abs;
						}
					}
					$link_disc = $this->discovery->discover_from_links( $raw, Discovery_Result::SOURCE_LINK );
					foreach ( $link_disc as $dr ) {
						if ( $dr->acceptance_status !== Discovery_Result::STATUS_ACCEPTED ) {
							continue;
						}
						if ( $dr->dedup_key === '' || isset( $seen[ $dr->dedup_key ] ) ) {
							continue;
						}
						if ( count( $queue ) + $fetch_attempts >= $max_pages * 2 ) {
							$queue_left = true;
							break;
						}
						$seen[ $dr->dedup_key ] = true;
						$queue[]                = array( $dr->normalized_url, $depth + 1 );
					}
				}
			}

			if ( $queue !== array() ) {
				$queue_left = true;
			}
			$final = Crawl_Snapshot_Payload_Builder::SESSION_STATUS_COMPLETED;
			if ( $failed > 0 && $accepted === 0 && $excluded === 0 ) {
				$final = Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED;
			} elseif ( $queue_left || ( $queue !== array() && $fetch_attempts >= $max_pages ) ) {
				$final = Crawl_Snapshot_Payload_Builder::SESSION_STATUS_PARTIAL;
			}
			$this->finalize_session( $crawl_run_id, $fetch_attempts, $accepted, $excluded, $failed, $final );
		} finally {
			if ( $site_host !== '' ) {
				$this->enqueue_service->release_lock_for_host( $site_host );
			}
		}
	}

	/**
	 * @param string $crawl_run_id Run id.
	 * @param int    $discovered   Fetch attempts.
	 * @param int    $accepted     Retained meaningful pages.
	 * @param int    $excluded     Excluded or skipped-by-depth.
	 * @param int    $failed       Failed fetches.
	 * @param string $final_status SESSION_STATUS_*.
	 * @return void
	 */
	private function finalize_session(
		string $crawl_run_id,
		int $discovered,
		int $accepted,
		int $excluded,
		int $failed,
		string $final_status
	): void {
		$this->snapshot_service->update_session(
			$crawl_run_id,
			array(
				Crawl_Snapshot_Payload_Builder::SESSION_TOTAL_DISCOVERED => max( 0, $discovered ),
				Crawl_Snapshot_Payload_Builder::SESSION_ACCEPTED_COUNT   => max( 0, $accepted ),
				Crawl_Snapshot_Payload_Builder::SESSION_EXCLUDED_COUNT   => max( 0, $excluded ),
				Crawl_Snapshot_Payload_Builder::SESSION_FAILED_COUNT     => max( 0, $failed ),
				Crawl_Snapshot_Payload_Builder::SESSION_FINAL_STATUS     => $final_status,
				Crawl_Snapshot_Payload_Builder::SESSION_ENDED_AT         => \gmdate( 'c' ),
			)
		);
	}

	/**
	 * @param string $page_url Current page URL.
	 * @param string $href     Raw href.
	 * @return string Absolute URL or empty.
	 */
	private function absolutize_url( string $page_url, string $href ): string {
		$href = trim( html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( $href === '' || str_starts_with( strtolower( $href ), 'javascript:' ) || str_starts_with( $href, '#' ) ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $href ) ) {
			return $href;
		}
		$base = \wp_parse_url( $page_url );
		if ( ! is_array( $base ) || empty( $base['host'] ) ) {
			return '';
		}
		$scheme = isset( $base['scheme'] ) && is_string( $base['scheme'] ) ? $base['scheme'] : 'https';
		$host   = (string) $base['host'];
		if ( str_starts_with( $href, '//' ) ) {
			return $scheme . ':' . $href;
		}
		if ( str_starts_with( $href, '/' ) ) {
			return $scheme . '://' . $host . $href;
		}
		$path = isset( $base['path'] ) ? (string) $base['path'] : '/';
		$dir  = \str_replace( '\\', '/', \dirname( $path ) );
		if ( $dir === '/' || $dir === '.' ) {
			$prefix = $scheme . '://' . $host . '/';
		} else {
			$prefix = $scheme . '://' . $host . ( str_ends_with( $dir, '/' ) ? $dir : $dir . '/' );
		}
		return $prefix . $href;
	}

	/**
	 * @param string                                                         $url Normalized page URL.
	 * @param array<int, array{context: string, label: string, url: string}> $nav Nav extraction.
	 * @return bool
	 */
	private function is_url_represented_in_nav( string $url, array $nav ): bool {
		$target = $this->normalizer->normalize( $url );
		if ( $target === '' ) {
			return false;
		}
		$tkey = $this->normalizer->dedup_key( $target );
		foreach ( $nav as $item ) {
			$raw = isset( $item['url'] ) ? (string) $item['url'] : '';
			if ( $raw === '' ) {
				continue;
			}
			$n = $this->normalizer->normalize( $this->absolutize_url( $url, $raw ) );
			if ( $n === '' ) {
				continue;
			}
			if ( $this->normalizer->dedup_key( $n ) === $tkey ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $html HTML body.
	 * @return int Approximate count of anchor hrefs.
	 */
	private function count_hrefs_in_html( string $html ): int {
		$n = preg_match_all( '#<a\s[^>]*\bhref\s*=#i', $html );
		return $n === false ? 0 : $n;
	}

	/**
	 * @param string $html HTML.
	 * @return array<int, string>
	 */
	private function extract_hrefs_from_html( string $html ): array {
		if ( ! preg_match_all( '#<a\s[^>]*\bhref\s*=\s*["\']([^"\']+)#i', $html, $m ) ) {
			return array();
		}
		$out = array();
		foreach ( $m[1] as $h ) {
			$out[] = (string) $h;
			if ( count( $out ) >= self::MAX_HREFS_PER_PAGE ) {
				break;
			}
		}
		return $out;
	}
}
