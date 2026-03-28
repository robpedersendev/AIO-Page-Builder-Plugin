<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Build_Plan_Repository_Template_Lab_Sanitize_Test extends TestCase {

	private Build_Plan_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                = new Build_Plan_Repository();
		$GLOBALS['_aio_post_meta'] = array();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	public function test_save_plan_definition_sanitizes_template_lab_context(): void {
		$this->assertTrue(
			$this->repo->save_plan_definition(
				7001,
				array(
					Build_Plan_Schema::KEY_PLAN_ID => 'plan_tl',
					Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT => array(
						Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID => 55,
						'transcript' => 'no',
					),
				)
			)
		);
		$def = $this->repo->get_plan_definition( 7001 );
		$ctx = $def[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ] ?? array();
		$this->assertSame( 55, (int) ( $ctx[ Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID ] ?? 0 ) );
		$this->assertArrayNotHasKey( 'transcript', $ctx );
	}
}
