<?php
/**
 * Unit tests for Industry_LPagery_Planning_Advisor and Industry_LPagery_Planning_Result (industry-lpagery-planning-contract, Prompt 347).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Planning_Advisor;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Planning_Result;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/LPagery/Industry_LPagery_Rule_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/LPagery/Industry_LPagery_Planning_Result.php';
require_once $plugin_root . '/src/Domain/Industry/LPagery/Industry_LPagery_Planning_Advisor.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';

final class Industry_LPagery_Planning_Advisor_Test extends TestCase {

	private function valid_rule( string $key, string $industry, string $posture ): array {
		return array(
			Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY => $key,
			Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY    => $industry,
			Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER   => Industry_LPagery_Rule_Registry::SUPPORTED_SCHEMA_VERSION,
			Industry_LPagery_Rule_Registry::FIELD_STATUS           => Industry_LPagery_Rule_Registry::STATUS_ACTIVE,
			Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE  => $posture,
		);
	}

	public function test_advise_returns_optional_when_no_registry(): void {
		$advisor = new Industry_LPagery_Planning_Advisor( null );
		$result = $advisor->advise( 'legal' );
		$this->assertInstanceOf( Industry_LPagery_Planning_Result::class, $result );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL, $result->get_lpagery_posture() );
		$this->assertSame( array(), $result->get_required_tokens() );
		$this->assertContains( 'no_lpagery_rules', $result->get_warning_flags() );
	}

	public function test_advise_returns_optional_when_empty_industry_key(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'legal_01', 'legal', Industry_LPagery_Rule_Registry::POSTURE_CENTRAL ) ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( '' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL, $result->get_lpagery_posture() );
		$this->assertContains( 'no_industry_key', $result->get_warning_flags() );
	}

	public function test_advise_returns_central_posture_when_rule_is_central(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'legal_01', 'legal', Industry_LPagery_Rule_Registry::POSTURE_CENTRAL ) ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( 'legal' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_CENTRAL, $result->get_lpagery_posture() );
		$this->assertSame( array(), $result->get_required_tokens() );
	}

	public function test_advise_returns_optional_posture_when_rule_is_optional(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'realtor_01', 'realtor', Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL ) ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( 'realtor' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL, $result->get_lpagery_posture() );
	}

	public function test_advise_returns_discouraged_posture_when_rule_is_discouraged(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'cosmetic_01', 'cosmetology', Industry_LPagery_Rule_Registry::POSTURE_DISCOURAGED ) ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( 'cosmetology' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_DISCOURAGED, $result->get_lpagery_posture() );
	}

	public function test_advise_aggregates_required_tokens_and_adds_warning_flag(): void {
		$rule = $this->valid_rule( 'legal_01', 'legal', Industry_LPagery_Rule_Registry::POSTURE_CENTRAL );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS ] = array( '{{location_name}}', '{{service_title}}' );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE ] = 'Use service-area hubs.';
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $rule ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( 'legal' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_CENTRAL, $result->get_lpagery_posture() );
		$this->assertSame( array( '{{location_name}}', '{{service_title}}' ), $result->get_required_tokens() );
		$this->assertSame( 'Use service-area hubs.', $result->get_hierarchy_guidance() );
		$this->assertContains( 'required_tokens_for_central_lpagery', $result->get_warning_flags() );
		$this->assertContains( 'service_area_hub', $result->get_suggested_page_families() );
	}

	public function test_advise_from_profile_uses_primary_industry_key(): void {
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $this->valid_rule( 'legal_01', 'legal', Industry_LPagery_Rule_Registry::POSTURE_CENTRAL ) ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'legal' );
		$result = $advisor->advise_from_profile( $profile );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_CENTRAL, $result->get_lpagery_posture() );
	}

	public function test_advise_from_profile_returns_optional_when_primary_empty(): void {
		$advisor = new Industry_LPagery_Planning_Advisor( null );
		$result = $advisor->advise_from_profile( array() );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL, $result->get_lpagery_posture() );
	}

	public function test_result_to_array_contains_all_fields(): void {
		$result = new Industry_LPagery_Planning_Result(
			Industry_LPagery_Rule_Registry::POSTURE_CENTRAL,
			array( '{{loc}}' ),
			array( '{{opt}}' ),
			array( 'hub' ),
			array( 'w1' ),
			'Guidance text',
			array( 'weak_type' )
		);
		$arr = $result->to_array();
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_CENTRAL, $arr['lpagery_posture'] );
		$this->assertSame( array( '{{loc}}' ), $arr['required_tokens'] );
		$this->assertSame( array( '{{opt}}' ), $arr['optional_tokens'] );
		$this->assertSame( array( 'hub' ), $arr['suggested_page_families'] );
		$this->assertSame( array( 'w1' ), $arr['warning_flags'] );
		$this->assertSame( 'Guidance text', $arr['hierarchy_guidance'] );
		$this->assertSame( array( 'weak_type' ), $arr['weak_page_warnings'] );
	}

	public function test_no_active_rules_returns_empty_result_with_flag(): void {
		$rule = $this->valid_rule( 'draft_01', 'legal', Industry_LPagery_Rule_Registry::POSTURE_CENTRAL );
		$rule[ Industry_LPagery_Rule_Registry::FIELD_STATUS ] = Industry_LPagery_Rule_Registry::STATUS_DRAFT;
		$registry = new Industry_LPagery_Rule_Registry();
		$registry->load( array( $rule ) );
		$advisor = new Industry_LPagery_Planning_Advisor( $registry );
		$result = $advisor->advise( 'legal' );
		$this->assertSame( Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL, $result->get_lpagery_posture() );
		$this->assertSame( array(), $result->get_required_tokens() );
		$this->assertContains( 'no_active_lpagery_rules', $result->get_warning_flags() );
	}
}
