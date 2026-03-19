<?php
/**
 * Unit tests: all concrete repositories implement Repository_Interface and expose required methods (spec §5.2, Prompt 019).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Repository_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Tables/Table_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Documentation_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Version_Snapshot_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Job_Queue_Repository.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Crawler/Snapshots/Crawl_Snapshot_Repository.php';

final class Repository_Interface_Conformance_Test extends TestCase {

	/** @var array<int, Repository_Interface> */
	private array $repositories;

	protected function setUp(): void {
		parent::setUp();
		$wpdb               = (object) array( 'prefix' => 'wp_' );
		$this->repositories = array(
			new Section_Template_Repository(),
			new Page_Template_Repository(),
			new Composition_Repository(),
			new Documentation_Repository(),
			new Version_Snapshot_Repository(),
			new Build_Plan_Repository(),
			new AI_Run_Repository(),
			new Job_Queue_Repository( $wpdb ),
			new Crawl_Snapshot_Repository( $wpdb ),
		);
	}

	public function test_all_concrete_repositories_implement_interface(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertInstanceOf( Repository_Interface::class, $repo );
		}
	}

	public function test_all_repositories_provide_get_by_id(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertTrue( method_exists( $repo, 'get_by_id' ) );
		}
	}

	public function test_all_repositories_provide_get_by_key(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertTrue( method_exists( $repo, 'get_by_key' ) );
		}
	}

	public function test_all_repositories_provide_list_by_status(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertTrue( method_exists( $repo, 'list_by_status' ) );
		}
	}

	public function test_all_repositories_provide_save(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertTrue( method_exists( $repo, 'save' ) );
		}
	}

	public function test_all_repositories_provide_exists(): void {
		foreach ( $this->repositories as $repo ) {
			$this->assertTrue( method_exists( $repo, 'exists' ) );
		}
	}

	public function test_stable_key_lookup_get_by_key_accepts_string_returns_null_when_not_found(): void {
		$repo = new Section_Template_Repository();
		$out  = $repo->get_by_key( 'nonexistent-key-12345' );
		$this->assertNull( $out );
	}

	public function test_get_by_id_returns_null_for_nonexistent_id(): void {
		$repo = new Section_Template_Repository();
		$this->assertNull( $repo->get_by_id( 999999 ) );
	}

	public function test_exists_returns_false_for_nonexistent_key(): void {
		$repo = new Section_Template_Repository();
		$this->assertFalse( $repo->exists( 'nonexistent-key' ) );
		$this->assertFalse( $repo->exists( 999999 ) );
	}

	public function test_list_by_status_returns_array(): void {
		$repo = new Section_Template_Repository();
		$list = $repo->list_by_status( 'draft', 10, 0 );
		$this->assertIsArray( $list );
	}
}
