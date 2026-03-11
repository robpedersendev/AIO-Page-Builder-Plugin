<?php
/**
 * Registers crawler bucket services: snapshot, discovery, fetch, classification, extraction, comparison (spec §11.1, §24.12–24.17).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Classification\Duplicate_Detector;
use AIOPageBuilder\Domain\Crawler\Classification\Meaningful_Page_Classifier;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Discovery_Service;
use AIOPageBuilder\Domain\Crawler\Extraction\Content_Summary_Extractor;
use AIOPageBuilder\Domain\Crawler\Comparison\Recrawl_Comparison_Service;
use AIOPageBuilder\Domain\Crawler\Extraction\Navigation_Extractor;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Normalizer;
use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Request_Policy;
use AIOPageBuilder\Domain\Crawler\Fetch\HTML_Fetcher;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Crawler bucket: snapshot storage, URL discovery, HTML fetcher.
 */
final class Crawler_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'crawl_snapshot_repository', function (): Crawl_Snapshot_Repository {
			global $wpdb;
			return new Crawl_Snapshot_Repository( $wpdb );
		} );
		$container->register( 'crawl_snapshot_service', function () use ( $container ): Crawl_Snapshot_Service {
			$repository = $container->get( 'crawl_snapshot_repository' );
			return new Crawl_Snapshot_Service( $repository );
		} );
		$container->register( 'url_normalizer', function (): URL_Normalizer {
			$home = \home_url( '/', 'https' );
			$host = parse_url( $home, PHP_URL_HOST );
			return new URL_Normalizer( is_string( $host ) ? $host : '', 'https' );
		} );
		$container->register( 'url_discovery_service', function () use ( $container ): URL_Discovery_Service {
			return new URL_Discovery_Service( $container->get( 'url_normalizer' ) );
		} );
		$container->register( 'fetch_request_policy', function (): Fetch_Request_Policy {
			return new Fetch_Request_Policy();
		} );
		$container->register( 'html_fetcher', function () use ( $container ): HTML_Fetcher {
			$policy  = $container->get( 'fetch_request_policy' );
			$normalizer = $container->get( 'url_normalizer' );
			$allowed = function ( string $url ) use ( $normalizer ): bool {
				return $normalizer->normalize( $url ) === $url;
			};
			return new HTML_Fetcher( $policy, $allowed );
		} );
		$container->register( 'duplicate_detector', function (): Duplicate_Detector {
			return new Duplicate_Detector();
		} );
		$container->register( 'meaningful_page_classifier', function () use ( $container ): Meaningful_Page_Classifier {
			return new Meaningful_Page_Classifier( $container->get( 'duplicate_detector' ) );
		} );
		$container->register( 'navigation_extractor', function (): Navigation_Extractor {
			return new Navigation_Extractor();
		} );
		$container->register( 'content_summary_extractor', function (): Content_Summary_Extractor {
			return new Content_Summary_Extractor();
		} );
		$container->register( 'recrawl_comparison_service', function () use ( $container ): Recrawl_Comparison_Service {
			return new Recrawl_Comparison_Service( $container->get( 'crawl_snapshot_service' ) );
		} );
	}
}
