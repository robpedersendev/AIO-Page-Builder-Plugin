<?php
/**
 * Unit tests for Industry_Pack_Import_Conflict_Service: analyze duplicate keys, resolve, has_unresolved_errors (Prompt 395).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Import_Conflict_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Import_Conflict_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';

final class Industry_Pack_Import_Conflict_Service_Test extends TestCase {

	public function test_analyze_returns_empty_when_no_duplicates(): void {
		$bundle_service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(
				array( Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'new_industry', Industry_Pack_Schema::FIELD_VERSION_MARKER => '1', Industry_Pack_Schema::FIELD_NAME => 'New', Industry_Pack_Schema::FIELD_SUMMARY => 'S', Industry_Pack_Schema::FIELD_STATUS => 'active' ),
			),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle = $bundle_service->build_bundle( array(), $sources );
		$local_state = array( 'packs' => array( 'realtor' ) );
		$service = new Industry_Pack_Import_Conflict_Service();
		$conflicts = $service->analyze( $bundle, $local_state );
		$this->assertIsArray( $conflicts );
		$this->assertCount( 0, $conflicts );
	}

	public function test_analyze_detects_duplicate_key(): void {
		$bundle_service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(
				array( Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'realtor', Industry_Pack_Schema::FIELD_VERSION_MARKER => '1', Industry_Pack_Schema::FIELD_NAME => 'R', Industry_Pack_Schema::FIELD_SUMMARY => 'S', Industry_Pack_Schema::FIELD_STATUS => 'active' ),
			),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle = $bundle_service->build_bundle( array(), $sources );
		$local_state = array( 'packs' => array( 'realtor' ) );
		$service = new Industry_Pack_Import_Conflict_Service();
		$conflicts = $service->analyze( $bundle, $local_state );
		$this->assertCount( 1, $conflicts );
		$this->assertSame( 'realtor', $conflicts[0][ Industry_Pack_Import_Conflict_Service::RESULT_OBJECT_KEY ] );
		$this->assertSame( Industry_Pack_Import_Conflict_Service::CONFLICT_DUPLICATE_KEY, $conflicts[0][ Industry_Pack_Import_Conflict_Service::RESULT_CONFLICT_TYPE ] );
	}

	public function test_resolve_sets_final_outcome(): void {
		$service = new Industry_Pack_Import_Conflict_Service();
		$conflicts = array(
			array(
				Industry_Pack_Import_Conflict_Service::RESULT_OBJECT_KEY => 'realtor',
				Industry_Pack_Import_Conflict_Service::RESULT_CATEGORY => Industry_Pack_Bundle_Service::PAYLOAD_PACKS,
				Industry_Pack_Import_Conflict_Service::RESULT_CONFLICT_TYPE => Industry_Pack_Import_Conflict_Service::CONFLICT_DUPLICATE_KEY,
				Industry_Pack_Import_Conflict_Service::RESULT_PROPOSED_RESOLUTION => Industry_Pack_Import_Conflict_Service::RESOLUTION_SKIP,
				Industry_Pack_Import_Conflict_Service::RESULT_FINAL_OUTCOME => null,
				Industry_Pack_Import_Conflict_Service::RESULT_WARNING_SEVERITY => Industry_Pack_Import_Conflict_Service::SEVERITY_WARNING,
				Industry_Pack_Import_Conflict_Service::RESULT_MESSAGE => 'Duplicate',
			),
		);
		$resolved = $service->resolve( $conflicts, array() );
		$this->assertSame( Industry_Pack_Import_Conflict_Service::OUTCOME_SKIPPED, $resolved[0][ Industry_Pack_Import_Conflict_Service::RESULT_FINAL_OUTCOME ] );
		$resolved_replace = $service->resolve( $conflicts, array( 'packs|realtor' => Industry_Pack_Import_Conflict_Service::RESOLUTION_REPLACE ) );
		$this->assertSame( Industry_Pack_Import_Conflict_Service::OUTCOME_APPLIED, $resolved_replace[0][ Industry_Pack_Import_Conflict_Service::RESULT_FINAL_OUTCOME ] );
	}

	public function test_has_unresolved_errors_returns_false_when_no_errors(): void {
		$service = new Industry_Pack_Import_Conflict_Service();
		$resolved = array(
			array( Industry_Pack_Import_Conflict_Service::RESULT_WARNING_SEVERITY => Industry_Pack_Import_Conflict_Service::SEVERITY_WARNING, Industry_Pack_Import_Conflict_Service::RESULT_FINAL_OUTCOME => Industry_Pack_Import_Conflict_Service::OUTCOME_SKIPPED ),
		);
		$this->assertFalse( $service->has_unresolved_errors( $resolved ) );
	}
}
