<?php
/**
 * Unit tests for Industry_Subtype_Starter_Bundle_To_Build_Plan_Service (Prompt 462).
 *
 * Covers: subtype context passed to base service; parent fallback when bundle missing/inactive; empty key and no fallback → failure.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service;
use AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Starter_Bundle_To_Build_Plan_Service as SubtypeService;
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
require_once $plugin_root . '/src/Domain/Industry/AI/Build_Plan_Scoring_Interface.php';
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
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Subtype_Starter_Bundle_To_Build_Plan_Service.php';

final class Industry_Subtype_Starter_Bundle_To_Build_Plan_Service_Test extends TestCase {

	private function valid_bundle( string $bundle_key = 'realtor_starter', string $industry_key = 'realtor', string $subtype_key = '' ): array {
		$bundle = array(
			Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => $industry_key,
			Industry_Starter_Bundle_Registry::FIELD_LABEL        => 'Realtor Starter',
			Industry_Starter_Bundle_Registry::FIELD_SUMMARY      => 'Curated starting set.',
			Industry_Starter_Bundle_Registry::FIELD_STATUS       => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
			Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		);
		if ( $subtype_key !== '' ) {
			$bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] = $subtype_key;
		}
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] = array( 'pt_home' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ]     = array( 'hero_01' );
		return $bundle;
	}

	private function create_subtype_service( Industry_Starter_Bundle_Registry $registry ): SubtypeService {
		$repo = new Build_Plan_Repository();
		$item = new Build_Plan_Item_Generator();
		$gen  = new Build_Plan_Generator( $repo, $item, null );
		$base = new Industry_Starter_Bundle_To_Build_Plan_Service( $registry, $gen );
		return new SubtypeService( $registry, $base );
	}

	public function test_convert_to_draft_with_empty_bundle_key_returns_failure(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle() ) );
		$service = $this->create_subtype_service( $registry );
		$result  = $service->convert_to_draft( '', array() );

		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_convert_to_draft_with_missing_bundle_and_no_industry_key_returns_failure(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_starter' ) ) );
		$service = $this->create_subtype_service( $registry );
		$result  = $service->convert_to_draft( 'nonexistent_bundle', array() );

		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_convert_to_draft_with_valid_subtype_bundle_delegates_and_succeeds(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 43;
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_buyer_starter', 'realtor', 'realtor_buyer_agent' ) ) );
		$service = $this->create_subtype_service( $registry );
		$result  = $service->convert_to_draft( 'realtor_buyer_starter', array() );

		$this->assertTrue( $result->is_success(), implode( ', ', $result->get_errors() ) );
		$this->assertNotNull( $result->get_plan_id() );
		$payload = $result->get_plan_payload();
		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE, $payload );
		$this->assertSame( 'realtor_buyer_starter', $payload[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_SOURCE_INDUSTRY_SUBTYPE, $payload );
		$this->assertSame( 'realtor_buyer_agent', $payload[ Build_Plan_Schema::KEY_SOURCE_INDUSTRY_SUBTYPE ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_convert_to_draft_falls_back_to_parent_bundle_when_bundle_missing_but_industry_key_provided(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 44;
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_starter', 'realtor', '' ) ) );
		$service = $this->create_subtype_service( $registry );
		$result  = $service->convert_to_draft( 'nonexistent_subtype_bundle', array( 'industry_key' => 'realtor' ) );

		$this->assertTrue( $result->is_success(), implode( ', ', $result->get_errors() ) );
		$this->assertNotNull( $result->get_plan_id() );
		$payload = $result->get_plan_payload();
		$this->assertSame( 'realtor_starter', $payload[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] ?? '' );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}
}
