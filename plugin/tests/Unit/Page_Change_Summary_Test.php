<?php
/**
 * Unit tests for Page_Change_Summary (spec §24.17).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Comparison\Page_Change_Summary;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Page_Change_Summary.php';

final class Page_Change_Summary_Test extends TestCase {

	public function test_to_array_returns_stable_shape(): void {
		$p   = new Page_Change_Summary(
			'https://example.com/page',
			Page_Change_Summary::CATEGORY_CHANGED,
			array( Page_Change_Summary::REASON_TITLE_CHANGED ),
			array( 'title_snapshot' => 'Old' ),
			array( 'title_snapshot' => 'New' )
		);
		$arr = $p->to_array();
		$this->assertSame( 'https://example.com/page', $arr['url'] );
		$this->assertSame( Page_Change_Summary::CATEGORY_CHANGED, $arr['change_category'] );
		$this->assertSame( array( Page_Change_Summary::REASON_TITLE_CHANGED ), $arr['reason_codes'] );
		$this->assertSame( 'Old', $arr['prior_snapshot']['title_snapshot'] );
		$this->assertSame( 'New', $arr['new_snapshot']['title_snapshot'] );
	}

	public function test_added_category_has_no_prior_snapshot(): void {
		$p = new Page_Change_Summary(
			'https://example.com/new',
			Page_Change_Summary::CATEGORY_ADDED,
			array( Page_Change_Summary::REASON_ADDED ),
			null,
			array( 'url' => 'https://example.com/new' )
		);
		$this->assertNull( $p->prior_snapshot );
		$this->assertNotNull( $p->new_snapshot );
	}

	public function test_removed_category_has_no_new_snapshot(): void {
		$p = new Page_Change_Summary(
			'https://example.com/gone',
			Page_Change_Summary::CATEGORY_REMOVED,
			array( Page_Change_Summary::REASON_REMOVED ),
			array( 'url' => 'https://example.com/gone' ),
			null
		);
		$this->assertNotNull( $p->prior_snapshot );
		$this->assertNull( $p->new_snapshot );
	}
}
