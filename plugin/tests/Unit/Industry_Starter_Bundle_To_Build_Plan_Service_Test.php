<?php
/**
 * Unit tests for Industry_Starter_Bundle_To_Build_Plan_Service (Prompt 409).
 *
 * Covers: valid bundle → draft plan success path; empty/missing/inactive bundle key → failure.
 * Uses real Build_Plan_Generator; run with phpunit.xml.dist so bootstrap stubs wp_insert_post.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Omitted_Recommendation_Report.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Plan_Generation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Build_Plan_Item_Generator.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Build_Plan_Generator.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Starter_Bundle_To_Build_Plan_Service.php';

final class Industry_Starter_Bundle_To_Build_Plan_Service_Test extends TestCase {

	private function valid_bundle( string $bundle_key = 'realtor_starter', string $industry_key = 'realtor' ): array {
		$bundle = array(
			Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => $industry_key,
			Industry_Starter_Bundle_Registry::FIELD_LABEL  => 'Realtor Starter',
			Industry_Starter_Bundle_Registry::FIELD_SUMMARY => 'Curated starting set for real estate sites.',
			Industry_Starter_Bundle_Registry::FIELD_STATUS => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
			Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		);
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] = array( 'pt_home', 'pt_services' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ]       = array( 'hero_01', 'cta_01' );
		return $bundle;
	}

	private function create_service( Industry_Starter_Bundle_Registry $registry ): Industry_Starter_Bundle_To_Build_Plan_Service {
		$repo = new Build_Plan_Repository();
		$item = new Build_Plan_Item_Generator();
		$gen  = new Build_Plan_Generator( $repo, $item, null );
		return new Industry_Starter_Bundle_To_Build_Plan_Service( $registry, $gen );
	}

	public function test_convert_to_draft_with_empty_bundle_key_returns_failure(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle() ) );
		$service = $this->create_service( $registry );
		$result  = $service->convert_to_draft( '', array() );

		$this->assertFalse( $result->is_success() );
		$this->assertNull( $result->get_plan_id() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_convert_to_draft_with_missing_bundle_returns_failure(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_starter' ) ) );
		$service = $this->create_service( $registry );
		$result  = $service->convert_to_draft( 'nonexistent_bundle', array() );

		$this->assertFalse( $result->is_success() );
		$this->assertNull( $result->get_plan_id() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_convert_to_draft_with_inactive_bundle_returns_failure(): void {
		$bundle = $this->valid_bundle( 'draft_bundle' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] = Industry_Starter_Bundle_Registry::STATUS_DRAFT;
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $bundle ) );
		$service = $this->create_service( $registry );
		$result  = $service->convert_to_draft( 'draft_bundle', array() );

		$this->assertFalse( $result->is_success() );
		$this->assertNull( $result->get_plan_id() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_convert_to_draft_with_valid_bundle_persists_plan_and_returns_success(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 42;
		$registry                              = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_starter' ) ) );
		$service = $this->create_service( $registry );

		$result = $service->convert_to_draft( 'realtor_starter', array( 'profile_context_ref' => 'ctx-1' ) );

		$this->assertTrue( $result->is_success(), implode( ', ', $result->get_errors() ) );
		$this->assertNotNull( $result->get_plan_id() );
		$this->assertStringStartsWith( 'aio-plan-', $result->get_plan_id() );
		$this->assertSame( 42, $result->get_plan_post_id() );
		$payload = $result->get_plan_payload();
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_PLAN_ID, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE, $payload );
		$this->assertSame( 'realtor_starter', $payload[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] );
		$this->assertSame( Build_Plan_Schema::STATUS_PENDING_REVIEW, $payload[ Build_Plan_Schema::KEY_STATUS ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}
}
