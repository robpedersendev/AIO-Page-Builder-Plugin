<?php
/**
 * Unit tests for Section_Template_Repository: save/read cycle, list_by_status scaffolding (spec §10.1, Prompt 019).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';

final class Section_Template_Repository_Test extends TestCase {

	private Section_Template_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->repository = new Section_Template_Repository();
		$GLOBALS['_aio_post_meta'] = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_wp_update_post_return'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	public function test_save_insert_returns_id_and_meta_stored(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 123;
		$data = array(
			'internal_key' => 'fixture-section-key',
			'status'       => 'draft',
			'post_title'   => 'Fixture Section',
		);
		$id = $this->repository->save( $data );
		$this->assertSame( 123, $id );
		$this->assertArrayHasKey( '123', $GLOBALS['_aio_post_meta'] );
		$this->assertSame( 'fixture-section-key', $GLOBALS['_aio_post_meta']['123']['_aio_internal_key'] ?? '' );
		$this->assertSame( 'draft', $GLOBALS['_aio_post_meta']['123']['_aio_status'] ?? '' );
	}

	public function test_get_by_id_returns_record_when_post_and_meta_available(): void {
		$GLOBALS['_aio_get_post_return'] = new \WP_Post( array(
			'ID'          => 456,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Test Section',
			'post_status' => 'publish',
			'post_name'   => 'test-section',
		) );
		$GLOBALS['_aio_post_meta']['456'] = array(
			'_aio_internal_key' => 'test-section',
			'_aio_status'       => 'active',
		);
		$record = $this->repository->get_by_id( 456 );
		$this->assertIsArray( $record );
		$this->assertSame( 456, $record['id'] );
		$this->assertSame( 'Test Section', $record['post_title'] );
		$this->assertSame( 'test-section', $record['internal_key'] );
		$this->assertSame( 'active', $record['status'] );
	}

	public function test_get_by_key_returns_record_when_query_returns_post(): void {
		$post = new \WP_Post( array(
			'ID'          => 789,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Key Lookup Section',
			'post_status' => 'publish',
			'post_name'   => 'key-lookup-section',
		) );
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['789'] = array(
			'_aio_internal_key' => 'key-lookup-section',
			'_aio_status'       => 'draft',
		);
		$record = $this->repository->get_by_key( 'key-lookup-section' );
		$this->assertIsArray( $record );
		$this->assertSame( 789, $record['id'] );
		$this->assertSame( 'key-lookup-section', $record['internal_key'] );
		$this->assertSame( 'draft', $record['status'] );
	}

	public function test_list_by_status_returns_records_from_query_posts(): void {
		$post = new \WP_Post( array(
			'ID'          => 101,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Draft Section',
			'post_status' => 'publish',
			'post_name'   => 'draft-section',
		) );
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['101'] = array(
			'_aio_internal_key' => 'draft-section',
			'_aio_status'       => 'draft',
		);
		$list = $this->repository->list_by_status( 'draft', 10, 0 );
		$this->assertCount( 1, $list );
		$this->assertSame( 101, $list[0]['id'] );
		$this->assertSame( 'draft', $list[0]['status'] );
	}

	public function test_save_then_read_by_id_roundtrip(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 202;
		$id = $this->repository->save( array(
			'internal_key' => 'roundtrip-key',
			'status'       => 'active',
			'post_title'   => 'Roundtrip Title',
		) );
		$this->assertSame( 202, $id );
		$GLOBALS['_aio_get_post_return'] = new \WP_Post( array(
			'ID'          => 202,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Roundtrip Title',
			'post_status' => 'publish',
			'post_name'   => 'roundtrip-key',
		) );
		$record = $this->repository->get_by_id( 202 );
		$this->assertNotNull( $record );
		$this->assertSame( 'roundtrip-key', $record['internal_key'] );
		$this->assertSame( 'active', $record['status'] );
	}
}
