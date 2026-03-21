<?php
/**
 * Unit tests for Industry_Profile_Validator and Industry_Profile_Readiness_Result (industry-profile-validation-contract; Prompt 330).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Readiness_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Readiness_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Definitions.php';

final class Industry_Profile_Validator_Test extends TestCase {

	public function test_validate_accepts_valid_profile_with_primary(): void {
		$validator = new Industry_Profile_Validator();
		$profile   = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
		);
		$profile   = Industry_Profile_Schema::normalize( $profile );
		$this->assertTrue( $validator->validate( $profile ) );
		$this->assertSame( array(), $validator->get_last_validation_errors() );
	}

	public function test_unsupported_schema_version_normalizes_to_empty_profile(): void {
		$validator = new Industry_Profile_Validator();
		$profile   = array( Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '2' );
		$normalized = Industry_Profile_Schema::normalize( $profile );
		$this->assertSame( Industry_Profile_Schema::get_empty_profile(), $normalized );
		$this->assertTrue( $validator->validate( $profile ) );
		$this->assertSame( array(), $validator->get_last_validation_errors() );
	}

	public function test_get_readiness_minimal_after_unsupported_schema_strips_to_empty(): void {
		$validator = new Industry_Profile_Validator();
		$profile   = array( Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '2' );
		$result    = $validator->get_readiness( $profile );
		$this->assertSame( Industry_Profile_Readiness_Result::STATE_MINIMAL, $result->get_state() );
		$this->assertSame( Industry_Profile_Readiness_Result::SCORE_MINIMAL, $result->get_score() );
		$this->assertFalse( $result->has_errors() );
	}

	public function test_get_readiness_minimal_when_primary_empty(): void {
		$validator = new Industry_Profile_Validator();
		$profile   = Industry_Profile_Schema::get_empty_profile();
		$result    = $validator->get_readiness( $profile );
		$this->assertSame( Industry_Profile_Readiness_Result::STATE_MINIMAL, $result->get_state() );
		$this->assertSame( Industry_Profile_Readiness_Result::SCORE_MINIMAL, $result->get_score() );
		$this->assertFalse( $result->is_ready() );
		$details = $result->get_details();
		$this->assertFalse( $details['primary_set'] ?? true );
	}

	public function test_get_readiness_ready_when_primary_set_and_question_pack_has_answer(): void {
		$validator   = new Industry_Profile_Validator();
		$qp_registry = new Industry_Question_Pack_Registry();
		$qp_registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$profile = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS => array( 'realtor' => array( 'market_focus' => 'residential' ) ),
		);
		$profile = Industry_Profile_Schema::normalize( $profile );
		$result  = $validator->get_readiness( $profile, null, $qp_registry );
		$this->assertSame( Industry_Profile_Readiness_Result::STATE_READY, $result->get_state() );
		$this->assertSame( Industry_Profile_Readiness_Result::SCORE_READY, $result->get_score() );
		$this->assertTrue( $result->is_ready() );
		$this->assertTrue( $result->get_details()['primary_set'] ?? false );
		$this->assertTrue( $result->get_details()['question_pack_complete'] ?? false );
	}

	public function test_get_readiness_partial_when_primary_set_but_no_question_pack_answers(): void {
		$validator   = new Industry_Profile_Validator();
		$qp_registry = new Industry_Question_Pack_Registry();
		$qp_registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$profile = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'plumber',
		);
		$profile = Industry_Profile_Schema::normalize( $profile );
		$result  = $validator->get_readiness( $profile, null, $qp_registry );
		$this->assertSame( Industry_Profile_Readiness_Result::STATE_PARTIAL, $result->get_state() );
		$this->assertSame( Industry_Profile_Readiness_Result::SCORE_PARTIAL, $result->get_score() );
		$this->assertFalse( $result->is_ready() );
		$this->assertTrue( $result->get_details()['primary_set'] ?? false );
		$this->assertFalse( $result->get_details()['question_pack_complete'] ?? true );
	}

	public function test_readiness_result_to_array(): void {
		$result = new Industry_Profile_Readiness_Result(
			Industry_Profile_Readiness_Result::STATE_READY,
			100,
			array(),
			array(),
			array( 'primary_set' => true )
		);
		$arr    = $result->to_array();
		$this->assertSame( 'ready', $arr['state'] );
		$this->assertSame( 100, $arr['score'] );
		$this->assertArrayHasKey( 'details', $arr );
	}

	/** Prompt 414: subtype_registry validates industry_subtype_key; matching parent yields no subtype warning. */
	public function test_validate_with_subtype_registry_matching_parent_no_warning(): void {
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load(
			array(
				array(
					Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
					Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
					Industry_Subtype_Registry::FIELD_LABEL => 'Buyer Agent',
					Industry_Subtype_Registry::FIELD_SUMMARY => 'Summary',
					Industry_Subtype_Registry::FIELD_STATUS => 'active',
					Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$validator = new Industry_Profile_Validator();
		$profile   = Industry_Profile_Schema::normalize(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
				Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
			)
		);
		$this->assertTrue( $validator->validate( $profile, null, null, $subtype_registry ) );
		$warnings = $validator->get_last_validation_warnings();
		foreach ( $warnings as $w ) {
			$this->assertStringNotContainsString( 'industry_subtype_key', $w );
		}
	}

	/** Prompt 414: subtype_registry with parent mismatch adds warning. */
	public function test_validate_with_subtype_registry_parent_mismatch_adds_warning(): void {
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load(
			array(
				array(
					Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
					Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
					Industry_Subtype_Registry::FIELD_LABEL => 'Buyer Agent',
					Industry_Subtype_Registry::FIELD_SUMMARY => 'Summary',
					Industry_Subtype_Registry::FIELD_STATUS => 'active',
					Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$validator = new Industry_Profile_Validator();
		$profile   = Industry_Profile_Schema::normalize(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'plumber',
				Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
			)
		);
		$this->assertTrue( $validator->validate( $profile, null, null, $subtype_registry ) );
		$warnings = $validator->get_last_validation_warnings();
		$this->assertNotEmpty( $warnings );
		$found = false;
		foreach ( $warnings as $w ) {
			if ( strpos( $w, 'industry_subtype_key_parent_mismatch' ) === 0 ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected industry_subtype_key_parent_mismatch warning' );
	}
}
