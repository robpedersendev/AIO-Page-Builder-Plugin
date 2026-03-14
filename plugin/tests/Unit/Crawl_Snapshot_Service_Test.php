<?php
/**
 * Unit tests for Crawl_Snapshot_Service: schema version, store/list with repository stub (spec §24.15, Prompt 050).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use AIOPageBuilder\Domain\Crawler\Extraction\Extraction_Result;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Keys;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Service;
use AIOPageBuilder\Domain\Crawler\Classification\Crawl_Template_Family_Matcher;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Classification/Classification_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Extraction/Extraction_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Keys.php';
require_once $plugin_root . '/src/Domain/Crawler/Profiles/Crawl_Profile_Service.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Crawler/Classification/Crawl_Template_Family_Matcher.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Repository.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Service.php';

	final class Crawl_Snapshot_Service_Test extends TestCase {

	private function profile_service(): Crawl_Profile_Service {
		return new Crawl_Profile_Service();
	}

	private function template_family_matcher(): Crawl_Template_Family_Matcher {
		return new Crawl_Template_Family_Matcher();
	}

	public function test_get_schema_version_returns_one(): void {
		$this->assertSame( '1', Crawl_Snapshot_Service::get_schema_version() );
	}

	public function test_list_pages_by_run_delegates_to_repository(): void {
		$repo = $this->create_repository_stub_list( array() );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$this->assertSame( array(), $svc->list_pages_by_run( 'run-1' ) );
	}

	public function test_list_pages_by_status_delegates_to_repository(): void {
		$repo = $this->create_repository_stub_list( array() );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$this->assertSame( array(), $svc->list_pages_by_status( 'pending' ) );
	}

	public function test_get_page_by_run_and_url_returns_null_when_repo_returns_null(): void {
		$repo = $this->create_repository_stub_get( null );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$this->assertNull( $svc->get_page_by_run_and_url( 'run-1', 'https://example.com/' ) );
	}

	public function test_store_page_record_returns_id_from_repository(): void {
		$repo = $this->create_repository_stub_save( 100 );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$id   = $svc->store_page_record( 'run-1', 'https://example.com/' );
		$this->assertSame( 100, $id );
	}

	public function test_record_classification_returns_id_from_repository(): void {
		$repo = $this->create_repository_stub_save( 201 );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$result = new Classification_Result(
			Classification_Result::CLASSIFICATION_MEANINGFUL,
			array( Classification_Result::REASON_CONTENT_WEIGHT ),
			null,
			array( 'has_h1' => true, 'word_count' => 200, 'in_nav' => false, 'link_count' => 0 ),
			Classification_Result::RETENTION_RETAIN,
			'content_hash_abc'
		);
		$id = $svc->record_classification( 'run-1', 'https://example.com/page', $result );
		$this->assertSame( 201, $id );
	}

	public function test_record_classification_with_title_snapshot_returns_id(): void {
		$repo = $this->create_repository_stub_save( 202 );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$result = new Classification_Result(
			Classification_Result::CLASSIFICATION_MEANINGFUL,
			array( Classification_Result::REASON_LIKELY_ROLE ),
			null,
			array(),
			Classification_Result::RETENTION_RETAIN,
			null
		);
		$id = $svc->record_classification( 'run-1', 'https://example.com/about', $result, 'About Us' );
		$this->assertSame( 202, $id );
	}

	public function test_record_extraction_returns_id_from_repository(): void {
		$repo = $this->create_repository_stub_save( 301 );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$page_summary = array(
			'title'                => 'Extracted Title',
			'meta_description'     => 'Meta desc',
			'h1'                  => 'H1',
			'h2_outline'          => array(),
			'word_count'          => 50,
			'content_excerpt'     => 'Excerpt.',
			'internal_link_count' => 2,
		);
		$result = new Extraction_Result( $page_summary, array(), array(), array() );
		$id = $svc->record_extraction( 'run-1', 'https://example.com/page', $result );
		$this->assertSame( 301, $id );
	}

	/** Prompt 209: enrich_page_with_template_hint returns 0 when page not found. */
	public function test_enrich_page_with_template_hint_returns_zero_when_page_not_found(): void {
		$repo = $this->create_repository_stub_get( null );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$id   = $svc->enrich_page_with_template_hint( 'run-1', 'https://example.com/missing' );
		$this->assertSame( 0, $id );
	}

	/** Prompt 209: enrich_page_with_template_hint returns save id when page found and hint persisted. */
	public function test_enrich_page_with_template_hint_returns_id_when_page_found(): void {
		$page_record = array(
			'id' => 1,
			'crawl_run_id' => 'run-1',
			'url' => 'https://example.com/about',
			'title_snapshot' => 'About',
			'page_classification' => Classification_Result::CLASSIFICATION_MEANINGFUL,
			'summary_data' => json_encode( array(
				'page_summary' => array( 'word_count' => 300, 'h1' => 'About', 'title' => 'About', 'meta_description' => '', 'content_excerpt' => '', 'internal_link_count' => 5 ),
				'heading_outline' => array( array( 'level' => 1, 'text' => 'About' ) ),
			) ),
			'hierarchy_clues' => null,
			'navigation_participation' => 1,
		);
		$repo = $this->create_repository_stub_get_and_save( $page_record, 302 );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$id   = $svc->enrich_page_with_template_hint( 'run-1', 'https://example.com/about' );
		$this->assertSame( 302, $id );
	}

	public function test_create_session_returns_non_empty_run_id_and_get_session_returns_payload(): void {
		$wpdb = new class() {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			public function get_row( string $q, $o = OBJECT ) { return null; }
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 0; }
		};
		$repo = new Crawl_Snapshot_Repository( $wpdb );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$run_id = $svc->create_session( 'example.com', array() );
		$this->assertNotSame( '', $run_id );
		$session = $svc->get_session( $run_id );
		$this->assertIsArray( $session );
		$this->assertArrayHasKey( 'crawl_run_id', $session );
		$this->assertArrayHasKey( 'site_host', $session );
		$this->assertArrayHasKey( 'crawl_profile_key', $session );
		$this->assertArrayHasKey( 'final_status', $session );
		$this->assertSame( $run_id, $session['crawl_run_id'] );
		$this->assertSame( 'example.com', $session['site_host'] );
		$this->assertSame( Crawl_Profile_Keys::DEFAULT, $session['crawl_profile_key'] );
	}

	public function test_create_session_with_approved_profile_stores_profile_in_session(): void {
		$wpdb = new class() {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			public function get_row( string $q, $o = OBJECT ) { return null; }
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 0; }
		};
		$repo = new Crawl_Snapshot_Repository( $wpdb );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$run_id = $svc->create_session( 'example.com', array( 'crawl_profile_key' => 'quick_context_refresh' ) );
		$this->assertNotSame( '', $run_id );
		$session = $svc->get_session( $run_id );
		$this->assertSame( 'quick_context_refresh', $session['crawl_profile_key'] );
	}

	public function test_create_session_with_unsupported_profile_resolves_to_default(): void {
		$wpdb = new class() {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			public function get_row( string $q, $o = OBJECT ) { return null; }
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 0; }
		};
		$repo = new Crawl_Snapshot_Repository( $wpdb );
		$svc  = new Crawl_Snapshot_Service( $repo, $this->profile_service(), $this->template_family_matcher() );
		$run_id = $svc->create_session( 'example.com', array( 'crawl_profile_key' => 'custom_unbounded' ) );
		$this->assertNotSame( '', $run_id );
		$session = $svc->get_session( $run_id );
		$this->assertSame( Crawl_Profile_Keys::DEFAULT, $session['crawl_profile_key'] );
	}

	private function create_repository_stub_list( array $rows ): Crawl_Snapshot_Repository {
		$wpdb = new class( $rows ) {
			public string $prefix = 'wp_';
			private array $rows;
			public function __construct( array $rows ) {
				$this->rows = $rows;
			}
			public function get_row( string $q, $o = OBJECT ) { return null; }
			public function get_results( string $q, $o = OBJECT ) { return $this->rows; }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 0; }
		};
		return new Crawl_Snapshot_Repository( $wpdb );
	}

	private function create_repository_stub_get( ?array $record ): Crawl_Snapshot_Repository {
		$wpdb = new class( $record ) {
			public string $prefix = 'wp_';
			private ?array $record;
			public function __construct( ?array $record ) {
				$this->record = $record;
			}
			public function get_row( string $q, $o = OBJECT ) {
				return $this->record !== null ? (object) $this->record : null;
			}
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 0; }
		};
		return new Crawl_Snapshot_Repository( $wpdb );
	}

	private function create_repository_stub_save( int $return_id ): Crawl_Snapshot_Repository {
		$wpdb = new class( $return_id ) {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			private int $return_id;
			public function __construct( int $return_id ) {
				$this->return_id = $return_id;
			}
			public function get_row( string $q, $o = OBJECT ) { return null; }
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) {
				$this->insert_id = $this->return_id;
				return 1;
			}
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 1; }
		};
		return new Crawl_Snapshot_Repository( $wpdb );
	}

	/** Stub that returns a page record from get_row and accepts save (update) returning given id. */
	private function create_repository_stub_get_and_save( array $page_record, int $return_id ): Crawl_Snapshot_Repository {
		$row = (object) array_merge( $page_record, array( 'id' => $return_id ) );
		$wpdb = new class( $row ) {
			public string $prefix = 'wp_';
			public int $insert_id = 0;
			private object $row;
			public function __construct( object $row ) {
				$this->row = $row;
			}
			public function get_row( string $q, $o = OBJECT ) { return $this->row; }
			public function get_results( string $q, $o = OBJECT ) { return array(); }
			public function query( string $q ) { return 0; }
			public function prepare( string $q, ...$a ) { return $q; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 1; }
		};
		return new Crawl_Snapshot_Repository( $wpdb );
	}
}
