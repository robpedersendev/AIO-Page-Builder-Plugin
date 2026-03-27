<?php
/**
 * Build Plan workspace JSON export: payload redaction and regression guard (no placeholder export UI).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin\BuildPlan;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );

final class Build_Plan_Export_Action_Test extends TestCase {

	public function test_export_payload_redacts_sensitive_keys_in_definition_and_rail(): void {
		$screen  = new Build_Plan_Workspace_Screen( new Service_Container() );
		$state   = array(
			'plan_id'         => 'plan-abc',
			'plan_post_id'    => 7,
			'plan_definition' => array(
				'child_api_key' => 'x',
				'steps'         => array(),
			),
			'context_rail'    => array( 'token_secret' => 'y' ),
			'stepper_steps'   => array( array( 'step_type' => 'overview' ) ),
		);
		$m       = new ReflectionMethod( Build_Plan_Workspace_Screen::class, 'build_export_payload_for_download' );
		$payload = $m->invoke( $screen, $state );
		$this->assertSame( 1, $payload['export_version'] );
		$this->assertSame( 'plan-abc', $payload['plan_id'] );
		$this->assertSame( '[redacted]', $payload['plan_definition']['child_api_key'] );
		$this->assertSame( '[redacted]', $payload['context_rail']['token_secret'] );
	}

	public function test_workspace_screen_source_has_no_coming_soon_placeholder(): void {
		$path = dirname( __DIR__, 4 ) . '/src/Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php';
		$this->assertFileExists( $path );
		$src = (string) file_get_contents( $path );
		$this->assertStringNotContainsString( 'Coming soon', $src );
	}
}
