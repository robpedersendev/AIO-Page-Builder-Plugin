<?php
/**
 * Unit tests for Session_Comparison_Result (spec §24.17).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Comparison\Page_Change_Summary;
use AIOPageBuilder\Domain\Crawler\Comparison\Session_Comparison_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Page_Change_Summary.php';
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Session_Comparison_Result.php';

final class Session_Comparison_Result_Test extends TestCase {

	public function test_to_array_includes_counts_and_page_changes(): void {
		$changes = array(
			new Page_Change_Summary( 'https://example.com/a', Page_Change_Summary::CATEGORY_ADDED, array( Page_Change_Summary::REASON_ADDED ), null, array() ),
		);
		$r = new Session_Comparison_Result( 'run-1', 'run-2', 1, 0, 0, 0, 0, 2, 3, $changes );
		$arr = $r->to_array();
		$this->assertSame( 'run-1', $arr['prior_run_id'] );
		$this->assertSame( 'run-2', $arr['new_run_id'] );
		$this->assertSame( 1, $arr['added_count'] );
		$this->assertSame( 0, $arr['removed_count'] );
		$this->assertSame( 2, $arr['meaningful_count_prior'] );
		$this->assertSame( 3, $arr['meaningful_count_new'] );
		$this->assertCount( 1, $arr['page_changes'] );
		$this->assertSame( 'https://example.com/a', $arr['page_changes'][0]['url'] );
	}

	/** Example session comparison result (spec §24.17). */
	public function test_example_session_comparison_result(): void {
		$page_changes = array(
			new Page_Change_Summary( 'https://example.com/new-page', Page_Change_Summary::CATEGORY_ADDED, array( Page_Change_Summary::REASON_ADDED ), null, array( 'page_classification' => 'meaningful' ) ),
			new Page_Change_Summary( 'https://example.com/removed', Page_Change_Summary::CATEGORY_REMOVED, array( Page_Change_Summary::REASON_REMOVED ), array( 'page_classification' => 'meaningful' ), null ),
			new Page_Change_Summary( 'https://example.com/about', Page_Change_Summary::CATEGORY_RECLASSIFIED, array( Page_Change_Summary::REASON_CLASSIFICATION_CHANGED ), array( 'page_classification' => 'low_value' ), array( 'page_classification' => 'meaningful' ) ),
			new Page_Change_Summary( 'https://example.com/contact', Page_Change_Summary::CATEGORY_UNCHANGED, array(), array( 'title_snapshot' => 'Contact' ), array( 'title_snapshot' => 'Contact' ) ),
		);
		$result = new Session_Comparison_Result( 'prior-uuid', 'new-uuid', 1, 1, 1, 1, 1, 3, 4, $page_changes );
		$arr = $result->to_array();
		$this->assertSame( 1, $arr['added_count'] );
		$this->assertSame( 1, $arr['removed_count'] );
		$this->assertSame( 1, $arr['changed_count'] );
		$this->assertSame( 1, $arr['unchanged_count'] );
		$this->assertSame( 1, $arr['reclassified_count'] );
		$this->assertSame( 3, $arr['meaningful_count_prior'] );
		$this->assertSame( 4, $arr['meaningful_count_new'] );
		$this->assertCount( 4, $arr['page_changes'] );
	}
}
