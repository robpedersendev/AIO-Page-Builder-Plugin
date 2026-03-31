<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
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

	public function test_get_plan_definition_accepts_wp_unserialized_array_meta(): void {
		$GLOBALS['_aio_post_meta']['8002'] = array(
			Build_Plan_Repository::META_PLAN_DEFINITION => array(
				Build_Plan_Schema::KEY_PLAN_ID => 'plan-array-meta',
				Build_Plan_Schema::KEY_STEPS   => array(
					array(
						Build_Plan_Item_Schema::KEY_STEP_ID => 'plan-array-meta_step_overview',
						Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
						Build_Plan_Item_Schema::KEY_TITLE => 'Overview',
						Build_Plan_Item_Schema::KEY_ORDER => 0,
						Build_Plan_Item_Schema::KEY_ITEMS => array(),
					),
				),
			),
		);
		$def                               = $this->repo->get_plan_definition( 8002 );
		$this->assertSame( 'plan-array-meta', (string) ( $def[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
		$this->assertCount( 1, $def[ Build_Plan_Schema::KEY_STEPS ] ?? array() );
	}

	public function test_get_plan_definition_decodes_addslashes_style_json_blob(): void {
		$canonical = \wp_json_encode(
			array(
				Build_Plan_Schema::KEY_PLAN_ID => 'slash-json',
				Build_Plan_Schema::KEY_STEPS   => array(
					array(
						Build_Plan_Item_Schema::KEY_STEP_ID => 'slash-json_step_1',
						Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
						Build_Plan_Item_Schema::KEY_TITLE => 'Overview',
						Build_Plan_Item_Schema::KEY_ORDER => 0,
						Build_Plan_Item_Schema::KEY_ITEMS => array(),
					),
				),
			)
		);
		$this->assertIsString( $canonical );
		$GLOBALS['_aio_post_meta']['8003'] = array(
			Build_Plan_Repository::META_PLAN_DEFINITION => addslashes( $canonical ),
		);
		$def                               = $this->repo->get_plan_definition( 8003 );
		$this->assertSame( 'slash-json', (string) ( $def[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
		$this->assertCount( 1, $def[ Build_Plan_Schema::KEY_STEPS ] ?? array() );
	}

	public function test_get_plan_definition_strips_utf8_bom_before_json_decode(): void {
		$canonical = \wp_json_encode(
			array(
				Build_Plan_Schema::KEY_PLAN_ID => 'bom-json',
				Build_Plan_Schema::KEY_STEPS   => array(),
			)
		);
		$this->assertIsString( $canonical );
		$GLOBALS['_aio_post_meta']['8004'] = array(
			Build_Plan_Repository::META_PLAN_DEFINITION => "\xEF\xBB\xBF" . $canonical,
		);
		$def                               = $this->repo->get_plan_definition( 8004 );
		$this->assertSame( 'bom-json', (string) ( $def[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
	}
}
