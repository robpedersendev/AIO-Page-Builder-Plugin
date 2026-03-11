<?php
/**
 * Unit tests for URL_Discovery_Service: same-host, rejections, dedup, accepted/rejected categorization (spec §24.8–24.9, contract §3, §6).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Discovery\Discovery_Result;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Discovery_Service;
use AIOPageBuilder\Domain\Crawler\Discovery\URL_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Discovery/Discovery_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Discovery/URL_Normalizer.php';
require_once $plugin_root . '/src/Domain/Crawler/Discovery/URL_Discovery_Service.php';

final class URL_Discovery_Service_Test extends TestCase {

	private const HOST = 'example.com';

	/**
	 * Accepted vs rejected normalization matrix (contract §3.3, §6, §14).
	 *
	 * @return array<string, array{url: string, expected_status: string, expected_code?: string|null}>
	 */
	private static function normalization_matrix(): array {
		return array(
			array( 'https://example.com/about', Discovery_Result::STATUS_ACCEPTED, null ),
			array( 'https://example.com/blog/my-post', Discovery_Result::STATUS_ACCEPTED, null ),
			array( 'https://example.com/contact', Discovery_Result::STATUS_ACCEPTED, null ),
			array( 'https://example.com/wp-admin/', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_PROHIBITED_ADMIN ),
			array( 'https://example.com/wp-login.php', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_PROHIBITED_LOGIN ),
			array( 'https://example.com/cart', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_CART ),
			array( 'https://example.com/checkout', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_CHECKOUT ),
			array( 'https://example.com/my-account', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_ACCOUNT ),
			array( 'https://example.com/?s=query', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_SEARCH ),
			array( 'https://example.com/feed/', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_FEED ),
			array( 'https://example.com/thank-you', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_THANKYOU ),
			array( 'https://example.com/?preview=true', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_PREVIEW ),
			array( 'https://example.com/page/2', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_IGNORED_PAGINATION ),
			array( 'https://other.com/page', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_EXTERNAL_HOST ),
			array( 'https://example.com/wp-json/', Discovery_Result::STATUS_REJECTED, URL_Discovery_Service::REJECT_PROHIBITED_REST_AJAX ),
			array( 'https://example.com/?utm_source=email', Discovery_Result::STATUS_ACCEPTED, null ),
		);
	}

	public function test_normalization_matrix(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		foreach ( self::normalization_matrix() as $row ) {
			$url             = $row[0];
			$expected_status = $row[1];
			$expected_code   = $row[2] ?? null;
			$results         = $service->discover_from_seeds( array( $url ) );
			$this->assertCount( 1, $results, 'One result for: ' . $url );
			$r = $results[0];
			$this->assertSame( $expected_status, $r->acceptance_status, 'Status for: ' . $url );
			if ( $expected_code !== null ) {
				$this->assertSame( $expected_code, $r->rejection_code, 'Rejection code for: ' . $url );
			}
		}
	}

	public function test_duplicate_suppression(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		$results    = $service->discover_from_seeds( array(
			'https://example.com/about',
			'https://example.com/about#section',
			'https://example.com/about?utm_source=x',
		) );
		$this->assertCount( 3, $results );
		$accepted = array_filter( $results, fn( Discovery_Result $r ) => $r->is_accepted() );
		$duplicates = array_filter( $results, fn( Discovery_Result $r ) => $r->is_duplicate() );
		$this->assertCount( 1, $accepted );
		$this->assertCount( 2, $duplicates );
	}

	public function test_discover_from_links_source(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		$results    = $service->discover_from_links( array( 'https://example.com/contact' ), Discovery_Result::SOURCE_LINK );
		$this->assertCount( 1, $results );
		$this->assertSame( Discovery_Result::SOURCE_LINK, $results[0]->discovery_source );
	}

	public function test_rejected_result_has_rejection_code(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		$results    = $service->discover_from_seeds( array( 'https://example.com/cart' ) );
		$this->assertTrue( $results[0]->is_rejected() );
		$this->assertSame( URL_Discovery_Service::REJECT_IGNORED_CART, $results[0]->rejection_code );
	}

	public function test_accepted_result_has_dedup_key(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		$results    = $service->discover_from_seeds( array( 'https://example.com/' ) );
		$this->assertTrue( $results[0]->is_accepted() );
		$this->assertSame( 'https://example.com/', $results[0]->dedup_key );
	}

	public function test_non_html_extension_rejected(): void {
		$normalizer = new URL_Normalizer( self::HOST );
		$service    = new URL_Discovery_Service( $normalizer );
		$results    = $service->discover_from_seeds( array( 'https://example.com/file.pdf' ) );
		$this->assertCount( 1, $results );
		$this->assertSame( URL_Discovery_Service::REJECT_PROHIBITED_FILE_MEDIA, $results[0]->rejection_code );
	}
}
