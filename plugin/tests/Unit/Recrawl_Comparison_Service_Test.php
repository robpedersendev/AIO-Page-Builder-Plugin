<?php
/**
 * Unit tests for Recrawl_Comparison_Service: added/removed/changed/unchanged/reclassified (spec §24.17).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Comparison\Page_Change_Summary;
use AIOPageBuilder\Domain\Crawler\Comparison\Recrawl_Comparison_Service;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Service;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Page_Change_Summary.php';
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Session_Comparison_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Comparison/Recrawl_Comparison_Service.php';
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Keys.php';
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Repository.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Service.php';

final class Recrawl_Comparison_Service_Test extends TestCase {

	private function make_repository_returning_per_run( array $prior_rows, array $new_rows ): Crawl_Snapshot_Repository {
		$wpdb = new class( $prior_rows, $new_rows ) {
			public string $prefix = 'wp_';
			private array $prior_rows;
			private array $new_rows;
			public string $last_run_id = '';

			public function __construct( array $prior_rows, array $new_rows ) {
				$this->prior_rows = $prior_rows;
				$this->new_rows   = $new_rows;
			}

			public function get_row( string $q, $o = OBJECT ) {
				return null;
			}

			public function get_results( string $q, $o = OBJECT ) {
				return $this->last_run_id === 'new-run' ? $this->new_rows : $this->prior_rows;
			}

			public function query( string $q ) {
				return 0;
			}

			public function prepare( string $q, ...$a ) {
				$this->last_run_id = (string) ( $a[0] ?? '' );
				return $q;
			}

			public function update( $t, $d, $w, $f = null, $wf = null ) {
				return 0;
			}
		};
		return new Crawl_Snapshot_Repository( $wpdb );
	}

	private function page_record( string $url, string $classification = 'meaningful', string $title = 'Page', ?string $canonical = null, int $nav = 0, ?string $content_hash = null ): array {
		return array(
			'url'                      => $url,
			'title_snapshot'           => $title,
			'page_classification'     => $classification,
			'canonical_url'            => $canonical ?? $url,
			'navigation_participation' => $nav,
			'content_hash'             => $content_hash ?? 'hash',
		);
	}

	public function test_compare_detects_added_and_removed(): void {
		$prior = array(
			(object) $this->page_record( 'https://example.com/only-prior', 'meaningful' ),
		);
		$new = array(
			(object) $this->page_record( 'https://example.com/only-new', 'meaningful' ),
		);
		$repo = $this->make_repository_returning_per_run( $prior, $new );
		$svc  = new Crawl_Snapshot_Service( $repo, new Crawl_Profile_Service() );
		$comparison = new Recrawl_Comparison_Service( $svc );
		$result = $comparison->compare( 'prior-run', 'new-run' );
		$this->assertSame( 1, $result->added_count );
		$this->assertSame( 1, $result->removed_count );
		$this->assertSame( 0, $result->changed_count );
		$this->assertSame( 0, $result->unchanged_count );
		$added = array_filter( $result->page_changes, function ( Page_Change_Summary $p ) { return $p->change_category === Page_Change_Summary::CATEGORY_ADDED; } );
		$removed = array_filter( $result->page_changes, function ( Page_Change_Summary $p ) { return $p->change_category === Page_Change_Summary::CATEGORY_REMOVED; } );
		$this->assertCount( 1, $added );
		$this->assertCount( 1, $removed );
		$this->assertSame( 'https://example.com/only-new', array_values( $added )[0]->url );
		$this->assertSame( 'https://example.com/only-prior', array_values( $removed )[0]->url );
	}

	public function test_compare_detects_unchanged_when_identical(): void {
		$row = (object) $this->page_record( 'https://example.com/same', 'meaningful', 'Same', null, 1, 'h1' );
		$prior = array( $row );
		$new   = array( clone $row );
		$repo = $this->make_repository_returning_per_run( $prior, $new );
		$svc  = new Crawl_Snapshot_Service( $repo, new Crawl_Profile_Service() );
		$comparison = new Recrawl_Comparison_Service( $svc );
		$result = $comparison->compare( 'prior-run', 'new-run' );
		$this->assertSame( 0, $result->added_count );
		$this->assertSame( 0, $result->removed_count );
		$this->assertSame( 0, $result->changed_count );
		$this->assertSame( 1, $result->unchanged_count );
		$this->assertSame( Page_Change_Summary::CATEGORY_UNCHANGED, $result->page_changes[0]->change_category );
	}

	public function test_compare_detects_classification_change_as_reclassified(): void {
		$prior = array( (object) $this->page_record( 'https://example.com/page', 'low_value', 'Page' ) );
		$new   = array( (object) $this->page_record( 'https://example.com/page', 'meaningful', 'Page' ) );
		$repo = $this->make_repository_returning_per_run( $prior, $new );
		$svc  = new Crawl_Snapshot_Service( $repo, new Crawl_Profile_Service() );
		$comparison = new Recrawl_Comparison_Service( $svc );
		$result = $comparison->compare( 'prior-run', 'new-run' );
		$this->assertSame( 1, $result->changed_count );
		$this->assertSame( 1, $result->reclassified_count );
		$this->assertSame( Page_Change_Summary::CATEGORY_RECLASSIFIED, $result->page_changes[0]->change_category );
		$this->assertContains( Page_Change_Summary::REASON_CLASSIFICATION_CHANGED, $result->page_changes[0]->reason_codes );
	}

	public function test_compare_detects_title_change(): void {
		$prior = array( (object) $this->page_record( 'https://example.com/page', 'meaningful', 'Old Title' ) );
		$new   = array( (object) $this->page_record( 'https://example.com/page', 'meaningful', 'New Title' ) );
		$repo = $this->make_repository_returning_per_run( $prior, $new );
		$svc  = new Crawl_Snapshot_Service( $repo, new Crawl_Profile_Service() );
		$comparison = new Recrawl_Comparison_Service( $svc );
		$result = $comparison->compare( 'prior-run', 'new-run' );
		$this->assertSame( 1, $result->changed_count );
		$this->assertContains( Page_Change_Summary::REASON_TITLE_CHANGED, $result->page_changes[0]->reason_codes );
	}

	public function test_compare_meaningful_counts(): void {
		$prior = array(
			(object) $this->page_record( 'https://example.com/a', 'meaningful' ),
			(object) $this->page_record( 'https://example.com/b', 'low_value' ),
		);
		$new = array(
			(object) $this->page_record( 'https://example.com/a', 'meaningful' ),
			(object) $this->page_record( 'https://example.com/b', 'meaningful' ),
		);
		$repo = $this->make_repository_returning_per_run( $prior, $new );
		$svc  = new Crawl_Snapshot_Service( $repo, new Crawl_Profile_Service() );
		$comparison = new Recrawl_Comparison_Service( $svc );
		$result = $comparison->compare( 'prior-run', 'new-run' );
		$this->assertSame( 1, $result->meaningful_count_prior );
		$this->assertSame( 2, $result->meaningful_count_new );
	}
}
