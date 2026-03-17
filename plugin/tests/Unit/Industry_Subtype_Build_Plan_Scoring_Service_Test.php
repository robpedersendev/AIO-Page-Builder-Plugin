<?php
/**
 * Unit tests for Industry_Subtype_Build_Plan_Scoring_Service (industry-build-plan-scoring-contract; Prompt 431).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\AI\Build_Plan_Scoring_Interface;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Page_Template_Recommendation_Extender;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/AI/Build_Plan_Scoring_Interface.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Subtype_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Page_Template_Recommendation_Extender.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Subtype_Resolver.php';

/**
 * Stub that records the last context passed to enrich_output for assertion.
 */
final class Industry_Subtype_Build_Plan_Scoring_Service_Test_Stub implements Build_Plan_Scoring_Interface {

	/** @var array<string, mixed> */
	public $last_context = array();

	public function enrich_output( array $normalized_output, array $context = array() ): array {
		$this->last_context = $context;
		return $normalized_output;
	}
}

final class Industry_Subtype_Build_Plan_Scoring_Service_Test extends TestCase {

	private function subtype_def( string $subtype_key, string $parent_industry_key ): array {
		return array(
			Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => $subtype_key,
			Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => $parent_industry_key,
			Industry_Subtype_Registry::FIELD_LABEL              => $subtype_key,
			Industry_Subtype_Registry::FIELD_SUMMARY            => 'Summary',
			Industry_Subtype_Registry::FIELD_STATUS              => 'active',
			Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		);
	}

	public function test_enrich_output_passes_subtype_definition_and_extender_when_profile_has_valid_subtype(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$profile_repo = $this->createMock( Industry_Profile_Repository::class );
		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
		);
		$profile_repo->method( 'get_profile' )->willReturn( $profile );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );
		$extender = $this->createMock( Industry_Subtype_Page_Template_Recommendation_Extender::class );

		$stub = new Industry_Subtype_Build_Plan_Scoring_Service_Test_Stub();
		$service = new Industry_Subtype_Build_Plan_Scoring_Service( $stub, $resolver, $profile_repo, $extender );

		$output = array( 'pages' => array() );
		$result = $service->enrich_output( $output, array() );

		$this->assertSame( $output, $result );
		$this->assertArrayHasKey( 'subtype_definition', $stub->last_context );
		$this->assertSame( 'realtor_buyer_agent', $stub->last_context['subtype_definition'][ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' );
		$this->assertArrayHasKey( 'subtype_extender', $stub->last_context );
		$this->assertSame( $extender, $stub->last_context['subtype_extender'] );
	}

	public function test_enrich_output_does_not_add_subtype_context_when_profile_has_no_subtype(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$profile_repo = $this->createMock( Industry_Profile_Repository::class );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' );
		$profile_repo->method( 'get_profile' )->willReturn( $profile );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );
		$extender = $this->createMock( Industry_Subtype_Page_Template_Recommendation_Extender::class );

		$stub = new Industry_Subtype_Build_Plan_Scoring_Service_Test_Stub();
		$service = new Industry_Subtype_Build_Plan_Scoring_Service( $stub, $resolver, $profile_repo, $extender );

		$output = array( 'pages' => array() );
		$service->enrich_output( $output, array() );

		$this->assertArrayNotHasKey( 'subtype_definition', $stub->last_context );
		$this->assertArrayNotHasKey( 'subtype_extender', $stub->last_context );
	}

	public function test_enrich_output_uses_context_industry_profile_when_provided(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'plumber_residential', 'plumber' ) ) );
		$profile_repo = $this->createMock( Industry_Profile_Repository::class );
		$profile_repo->method( 'get_profile' )->willReturn( array() );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );
		$extender = $this->createMock( Industry_Subtype_Page_Template_Recommendation_Extender::class );

		$stub = new Industry_Subtype_Build_Plan_Scoring_Service_Test_Stub();
		$service = new Industry_Subtype_Build_Plan_Scoring_Service( $stub, $resolver, $profile_repo, $extender );

		$context_profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'plumber',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'plumber_residential',
		);
		$service->enrich_output( array(), array( Industry_Build_Plan_Scoring_Service::CONTEXT_INDUSTRY_PROFILE => $context_profile ) );

		$this->assertArrayHasKey( 'subtype_definition', $stub->last_context );
		$this->assertSame( 'plumber_residential', $stub->last_context['subtype_definition'][ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' );
	}
}
