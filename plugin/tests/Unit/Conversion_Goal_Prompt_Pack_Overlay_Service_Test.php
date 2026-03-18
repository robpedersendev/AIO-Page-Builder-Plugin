<?php
/**
 * Unit tests for Conversion_Goal_Prompt_Pack_Overlay_Service (Prompt 533).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Prompt_Pack_Overlay_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Conversion_Goal_Prompt_Pack_Overlay_Service.php';

final class Conversion_Goal_Prompt_Pack_Overlay_Service_Test extends TestCase {

	public function test_get_overlay_returns_minimal_when_no_industry_context(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( array( 'schema_version' => '1' ), $overlay );
	}

	public function test_get_overlay_returns_minimal_when_primary_goal_empty(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'industry_profile' => array( 'primary_industry_key' => 'realtor' ),
				'primary_goal_key' => '',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_SCHEMA_VERSION, $overlay['schema_version'] ?? '' );
		$this->assertArrayNotHasKey( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_PRIMARY_GOAL_KEY, $overlay );
		$this->assertArrayNotHasKey( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_CONVERSION_GOAL_GUIDANCE_TEXT, $overlay );
	}

	public function test_get_overlay_includes_primary_goal_and_guidance(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'primary_goal_key' => 'bookings',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( '1', $overlay['schema_version'] ?? '' );
		$this->assertSame( 'bookings', $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_PRIMARY_GOAL_KEY ] ?? '' );
		$this->assertArrayNotHasKey( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_SECONDARY_GOAL_KEY, $overlay );
		$text = $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_CONVERSION_GOAL_GUIDANCE_TEXT ] ?? '';
		$this->assertStringContainsString( 'booking', $text );
		$this->assertStringContainsString( 'appointment', $text );
	}

	public function test_get_overlay_includes_primary_and_secondary_goal(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'primary_goal_key'   => 'bookings',
				'secondary_goal_key' => 'lead_capture',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( 'bookings', $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_PRIMARY_GOAL_KEY ] ?? '' );
		$this->assertSame( 'lead_capture', $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_SECONDARY_GOAL_KEY ] ?? '' );
		$text = $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_CONVERSION_GOAL_GUIDANCE_TEXT ] ?? '';
		$this->assertStringContainsString( 'Secondary objective', $text );
		$this->assertStringContainsString( 'lead', strtolower( $text ) );
	}

	public function test_get_overlay_returns_minimal_when_primary_goal_invalid(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'primary_goal_key' => 'unknown_goal',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( '1', $overlay['schema_version'] ?? '' );
		$this->assertArrayNotHasKey( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_PRIMARY_GOAL_KEY, $overlay );
	}

	public function test_get_overlay_omits_secondary_when_same_as_primary(): void {
		$service  = new Conversion_Goal_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'primary_goal_key'   => 'calls',
				'secondary_goal_key' => 'calls',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( 'calls', $overlay[ Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_PRIMARY_GOAL_KEY ] ?? '' );
		$this->assertArrayNotHasKey( Conversion_Goal_Prompt_Pack_Overlay_Service::OVERLAY_SECONDARY_GOAL_KEY, $overlay );
	}
}
