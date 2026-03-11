<?php
/**
 * Registers crawler bucket services: snapshot repository, snapshot service, URL discovery (spec §11.1, §24.15, §24.8–24.9).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Discovery\URL_Discovery_Service;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Normalizer;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Crawler bucket: snapshot storage and URL discovery. No fetcher.
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
		// * URL normalizer bound to current site host (for same-site crawl). For other hosts, instantiate URL_Normalizer directly.
		$container->register( 'url_normalizer', function (): URL_Normalizer {
			$home = \home_url( '/', 'https' );
			$host = parse_url( $home, PHP_URL_HOST );
			return new URL_Normalizer( is_string( $host ) ? $host : '', 'https' );
		} );
		$container->register( 'url_discovery_service', function () use ( $container ): URL_Discovery_Service {
			return new URL_Discovery_Service( $container->get( 'url_normalizer' ) );
		} );
	}
}
