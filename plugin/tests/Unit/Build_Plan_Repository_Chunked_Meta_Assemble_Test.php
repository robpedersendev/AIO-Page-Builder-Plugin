<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Verifies chunked `_aio_plan_definition__part_*` rows reassemble in {@see Build_Plan_Repository::get_plan_definition()}.
 */
final class Build_Plan_Repository_Chunked_Meta_Assemble_Test extends TestCase {

	public function test_get_plan_definition_reassembles_chunked_meta(): void {
		$post_id = 9200;
		$full    = '{"plan_id":"chunk_asm","status":"pending_review","plan_title":"C","steps":[]}';
		$mid     = (int) floor( strlen( $full ) / 2 );
		$p0      = substr( $full, 0, $mid );
		$p1      = substr( $full, $mid );

		$GLOBALS['_aio_post_meta']           = array(
			(string) $post_id => array(
				Build_Plan_Repository::META_PLAN_DEFINITION . '__chunk_count' => '2',
				Build_Plan_Repository::META_PLAN_DEFINITION . '__part_0'        => $p0,
				Build_Plan_Repository::META_PLAN_DEFINITION . '__part_1'        => $p1,
			),
		);
		$GLOBALS['_aio_get_post_type_by_id'] = array(
			$post_id => Object_Type_Keys::BUILD_PLAN,
		);

		$repo = new Build_Plan_Repository();
		$def  = $repo->get_plan_definition( $post_id );
		$this->assertSame( 'chunk_asm', (string) ( $def[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
	}
}
