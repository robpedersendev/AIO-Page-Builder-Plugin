<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Run_Repository_Run_Surface_Test extends TestCase {

	private AI_Run_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                = new AI_Run_Repository();
		$GLOBALS['_aio_post_meta'] = array();
		unset( $GLOBALS['_aio_wp_query_posts'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_save_run_metadata_sets_template_lab_surface(): void {
		$this->repo->save_run_metadata(
			501,
			array(
				'actor'        => 'u1',
				'template_lab' => array( 'x' => 1 ),
			)
		);
		$meta = $GLOBALS['_aio_post_meta']['501'] ?? array();
		$this->assertSame( 'template_lab', $meta[ AI_Run_Repository::META_RUN_SURFACE ] ?? '' );
	}

	public function test_save_run_metadata_sets_build_plan_surface(): void {
		$this->repo->save_run_metadata(
			502,
			array(
				'actor'          => 'u1',
				'build_plan_ref' => 'bp_key',
			)
		);
		$meta = $GLOBALS['_aio_post_meta']['502'] ?? array();
		$this->assertSame( 'build_plan', $meta[ AI_Run_Repository::META_RUN_SURFACE ] ?? '' );
	}

	public function test_list_recent_post_ids_by_surface_filters_meta(): void {
		$this->repo->save_run_metadata( 10, array( 'template_lab' => array() ) );
		$this->repo->save_run_metadata( 11, array( 'build_plan_ref' => 'x' ) );
		$p10                            = new \WP_Post(
			array(
				'ID'          => 10,
				'post_type'   => Object_Type_Keys::AI_RUN,
				'post_status' => 'publish',
			)
		);
		$p11                            = new \WP_Post(
			array(
				'ID'          => 11,
				'post_type'   => Object_Type_Keys::AI_RUN,
				'post_status' => 'publish',
			)
		);
		$GLOBALS['_aio_wp_query_posts'] = array( $p10, $p11 );
		$tl_ids                         = $this->repo->list_recent_post_ids_by_surface( 'template_lab', 10 );
		$this->assertSame( array( 10 ), $tl_ids );
		$bp_ids = $this->repo->list_recent_post_ids_by_surface( 'build_plan', 10 );
		$this->assertSame( array( 11 ), $bp_ids );
	}
}
