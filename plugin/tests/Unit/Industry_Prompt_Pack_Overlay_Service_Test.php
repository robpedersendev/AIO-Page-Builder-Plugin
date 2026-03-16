<?php
/**
 * Unit tests for Industry_Prompt_Pack_Overlay_Service (industry-prompt-pack-overlay-contract; Prompt 332).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Prompt_Pack_Overlay_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';

final class Industry_Prompt_Pack_Overlay_Service_Test extends TestCase {

	public function test_get_overlay_returns_minimal_when_no_industry_context(): void {
		$service = new Industry_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1' ),
		);
		$overlay = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( array( 'schema_version' => '1' ), $overlay );
	}

	public function test_get_overlay_returns_minimal_when_readiness_none(): void {
		$service = new Industry_Prompt_Pack_Overlay_Service();
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'readiness' => array( 'state' => 'none', 'score' => 0 ),
			),
		);
		$overlay = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( Industry_Prompt_Pack_Overlay_Service::OVERLAY_SCHEMA_VERSION, $overlay['schema_version'] );
		$this->assertArrayNotHasKey( 'active_industry_key', $overlay );
	}

	public function test_get_overlay_includes_guidance_when_pack_has_summary(): void {
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'realtor',
				Industry_Pack_Schema::FIELD_NAME => 'Realtor',
				Industry_Pack_Schema::FIELD_SUMMARY => 'Real estate and listing focus.',
				Industry_Pack_Schema::FIELD_STATUS => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
			),
		) );
		$service = new Industry_Prompt_Pack_Overlay_Service( $pack_registry );
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'readiness' => array( 'state' => 'ready', 'score' => 100 ),
				'industry_profile' => array( 'primary_industry_key' => 'realtor' ),
			),
		);
		$overlay = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( 'realtor', $overlay['active_industry_key'] ?? '' );
		$this->assertSame( 'Real estate and listing focus.', $overlay['industry_guidance_text'] ?? '' );
	}

	public function test_get_overlay_includes_required_page_families_when_pack_has_supported(): void {
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'plumber',
				Industry_Pack_Schema::FIELD_NAME => 'Plumber',
				Industry_Pack_Schema::FIELD_SUMMARY => '',
				Industry_Pack_Schema::FIELD_STATUS => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				Industry_Pack_Schema::FIELD_SUMMARY => 'Plumbing and local service.',
				Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES => array( 'service', 'local' ),
			),
		) );
		$service = new Industry_Prompt_Pack_Overlay_Service( $pack_registry );
		$artifact = array(
			Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
				'readiness' => array( 'state' => 'partial' ),
				'industry_profile' => array( 'primary_industry_key' => 'plumber' ),
			),
		);
		$overlay = $service->get_overlay_for_artifact( $artifact );
		$this->assertSame( array( 'service', 'local' ), $overlay['required_page_families'] ?? array() );
	}

	/**
	 * Evaluation fixtures (Prompt 416): all launch industries produce structured overlay with schema_version,
	 * active_industry_key, and at least one of required_page_families or cta_priorities when pack is present.
	 */
	public function test_launch_industries_produce_structured_overlay_per_fixtures(): void {
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( Industry_Pack_Registry::get_builtin_pack_definitions() );
		$service = new Industry_Prompt_Pack_Overlay_Service( $pack_registry );
		$launch = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );
		foreach ( $launch as $primary ) {
			$artifact = array(
				Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT => array(
					'readiness' => array( 'state' => 'ready', 'score' => 100 ),
					'industry_profile' => array( 'primary_industry_key' => $primary ),
				),
			);
			$overlay = $service->get_overlay_for_artifact( $artifact );
			$this->assertSame( '1', $overlay['schema_version'] ?? '', "{$primary}: schema_version" );
			$this->assertSame( $primary, $overlay['active_industry_key'] ?? '', "{$primary}: active_industry_key" );
			$has_families = ! empty( $overlay['required_page_families'] );
			$has_cta = ! empty( $overlay['cta_priorities'] );
			$this->assertTrue( $has_families || $has_cta, "{$primary}: overlay must have required_page_families or cta_priorities when pack present" );
		}
	}
}
