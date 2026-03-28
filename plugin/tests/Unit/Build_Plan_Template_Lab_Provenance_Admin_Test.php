<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Template_Lab_Provenance_Admin;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Build_Plan_Template_Lab_Provenance_Admin_Test extends TestCase {

	public function test_lines_empty_without_context(): void {
		$this->assertSame( array(), Build_Plan_Template_Lab_Provenance_Admin::lines( array() ) );
	}

	public function test_lines_include_safe_fields_only(): void {
		$lines = Build_Plan_Template_Lab_Provenance_Admin::lines(
			array(
				Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT => array(
					'target_kind'            => 'composition',
					'canonical_internal_key' => 'comp_1',
					'run_post_id'            => 99,
					'secret_prompt'          => 'must_not_appear_in_lines',
				),
			)
		);
		$this->assertNotSame( array(), $lines );
		$joined = implode( ' ', $lines );
		$this->assertStringContainsString( 'composition', $joined );
		$this->assertStringContainsString( 'comp_1', $joined );
		$this->assertStringContainsString( '99', $joined );
		$this->assertStringNotContainsString( 'must_not_appear_in_lines', $joined );
	}
}
