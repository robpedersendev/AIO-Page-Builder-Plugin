<?php
/**
 * Registers crawler bucket services: snapshot repository and snapshot service (spec §11.1, §24.15).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Crawler bucket: snapshot storage only. No discovery or fetcher.
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
	}
}
