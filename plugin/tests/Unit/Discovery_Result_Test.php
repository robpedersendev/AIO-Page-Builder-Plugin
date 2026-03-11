<?php
/**
 * Unit tests for Discovery_Result value object (spec §24.8–24.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Discovery\Discovery_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Discovery/Discovery_Result.php';

final class Discovery_Result_Test extends TestCase {

	public function test_to_array_contains_all_fields(): void {
		$r = new Discovery_Result( 'https://example.com/', Discovery_Result::SOURCE_SEED, Discovery_Result::STATUS_ACCEPTED, null, 'https://example.com/' );
		$arr = $r->to_array();
		$this->assertSame( 'https://example.com/', $arr['normalized_url'] );
		$this->assertSame( Discovery_Result::SOURCE_SEED, $arr['discovery_source'] );
		$this->assertSame( Discovery_Result::STATUS_ACCEPTED, $arr['acceptance_status'] );
		$this->assertNull( $arr['rejection_code'] );
		$this->assertSame( 'https://example.com/', $arr['dedup_key'] );
	}

	public function test_is_accepted_is_rejected_is_duplicate(): void {
		$accepted = new Discovery_Result( 'u', 'link', Discovery_Result::STATUS_ACCEPTED, null, 'k' );
		$this->assertTrue( $accepted->is_accepted() );
		$this->assertFalse( $accepted->is_rejected() );
		$this->assertFalse( $accepted->is_duplicate() );

		$rejected = new Discovery_Result( 'u', 'link', Discovery_Result::STATUS_REJECTED, 'external_host', 'k' );
		$this->assertFalse( $rejected->is_accepted() );
		$this->assertTrue( $rejected->is_rejected() );
		$this->assertFalse( $rejected->is_duplicate() );

		$dup = new Discovery_Result( 'u', 'link', Discovery_Result::STATUS_DUPLICATE, null, 'k' );
		$this->assertFalse( $dup->is_accepted() );
		$this->assertFalse( $dup->is_rejected() );
		$this->assertTrue( $dup->is_duplicate() );
	}
}
