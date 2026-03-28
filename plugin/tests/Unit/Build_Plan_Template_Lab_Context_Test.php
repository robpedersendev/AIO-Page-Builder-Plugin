<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Build_Plan_Template_Lab_Context_Test extends TestCase {

	public function test_sanitize_strips_unknown_and_secrets_like_keys(): void {
		$out = Build_Plan_Template_Lab_Context::sanitize(
			array(
				Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID => 900,
				Build_Plan_Template_Lab_Context::FIELD_TARGET_KIND => 'composition',
				Build_Plan_Template_Lab_Context::FIELD_CANONICAL_INTERNAL_KEY => 'comp_x',
				'user_prompt'       => 'SECRET',
				'raw_provider_body' => 'SECRET2',
				'messages'          => array( 'x' ),
			)
		);
		$this->assertSame( 900, $out[ Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID ] ?? 0 );
		$this->assertSame( 'composition', $out[ Build_Plan_Template_Lab_Context::FIELD_TARGET_KIND ] ?? '' );
		$this->assertSame( 'comp_x', $out[ Build_Plan_Template_Lab_Context::FIELD_CANONICAL_INTERNAL_KEY ] ?? '' );
		$this->assertArrayNotHasKey( 'user_prompt', $out );
		$this->assertArrayNotHasKey( 'messages', $out );
	}

	public function test_merge_into_definition_uses_schema_key(): void {
		$def = array( Build_Plan_Schema::KEY_PLAN_ID => 'p1' );
		$def = Build_Plan_Template_Lab_Context::merge_into_definition(
			$def,
			array( Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID => 1 )
		);
		$this->assertSame( 1, (int) ( $def[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ][ Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID ] ?? 0 ) );
	}
}
