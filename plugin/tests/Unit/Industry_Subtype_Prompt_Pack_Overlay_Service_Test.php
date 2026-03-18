<?php
/**
 * Unit tests for Industry_Subtype_Prompt_Pack_Overlay_Service (industry-subtype-ai-overlay-contract; Prompt 430).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Prompt_Pack_Overlay_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Subtype_Prompt_Pack_Overlay_Service.php';

final class Industry_Subtype_Prompt_Pack_Overlay_Service_Test extends TestCase {

	public function test_get_overlay_returns_minimal_when_no_industry_context(): void {
		$service  = new Industry_Subtype_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( array( 'schema_version' => '1' ), $overlay );
	}

	public function test_get_overlay_returns_minimal_when_subtype_key_empty(): void {
		$service  = new Industry_Subtype_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'industry_profile'     => array( 'primary_industry_key' => 'realtor' ),
				'industry_subtype_key' => '',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( Industry_Subtype_Prompt_Pack_Overlay_Service::OVERLAY_SCHEMA_VERSION, $overlay['schema_version'] );
		$this->assertArrayNotHasKey( 'subtype_guidance_text', $overlay );
	}

	public function test_get_overlay_includes_subtype_guidance_when_snapshot_present(): void {
		$service  = new Industry_Subtype_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'industry_subtype_key'      => 'realtor_buyer_agent',
				'resolved_subtype_snapshot' => array(
					'label'   => 'Buyer Agent',
					'summary' => 'Focus on buyer-side representation and search support.',
				),
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( '1', $overlay['schema_version'] ?? '' );
		$this->assertStringContainsString( 'Buyer Agent', $overlay['subtype_guidance_text'] ?? '' );
		$this->assertStringContainsString( 'Focus on buyer-side', $overlay['subtype_guidance_text'] ?? '' );
	}

	public function test_get_overlay_includes_subtype_cta_priorities_when_cta_ref_present(): void {
		$service  = new Industry_Subtype_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'industry_subtype_key'      => 'plumber_residential',
				'resolved_subtype_snapshot' => array(
					'label'   => 'Residential',
					'summary' => 'Homeowner focus.',
				),
				'subtype_cta_posture_ref'   => 'call_now',
			),
		);
		$overlay  = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( array( 'call_now' ), $overlay['subtype_cta_priorities'] ?? array() );
	}
}
