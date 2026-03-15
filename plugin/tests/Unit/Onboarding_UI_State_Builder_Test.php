<?php
/**
 * Unit tests for Onboarding_UI_State_Builder: build_for_screen, step labels, blocked when no provider (onboarding-state-machine.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Profile/Template_Preference_Profile.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Draft_Service.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Prefill_Service.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_UI_State_Builder.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Onboarding/Industry_Question_Pack_Definitions.php';

final class Onboarding_UI_State_Builder_Test extends TestCase {

	private function get_builder(): Onboarding_UI_State_Builder {
		$settings = new Settings_Service();
		$normalizer = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$draft_svc = new Onboarding_Draft_Service( $settings );
		$prefill_svc = new Onboarding_Prefill_Service( $profile_store, $settings, null );
		return new Onboarding_UI_State_Builder( $draft_svc, $prefill_svc );
	}

	public function test_step_labels_contain_all_step_keys(): void {
		$labels = Onboarding_UI_State_Builder::step_labels();
		foreach ( Onboarding_Step_Keys::ordered() as $key ) {
			$this->assertArrayHasKey( $key, $labels );
			$this->assertIsString( $labels[ $key ] );
		}
	}

	public function test_build_for_screen_returns_required_keys(): void {
		$builder = $this->get_builder();
		$state = $builder->build_for_screen();
		$this->assertArrayHasKey( 'current_step_key', $state );
		$this->assertArrayHasKey( 'steps', $state );
		$this->assertArrayHasKey( 'overall_status', $state );
		$this->assertArrayHasKey( 'is_blocked', $state );
		$this->assertArrayHasKey( 'blockers', $state );
		$this->assertArrayHasKey( 'prefill', $state );
		$this->assertArrayHasKey( 'nonce', $state );
		$this->assertArrayHasKey( 'is_provider_ready', $state );
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $state['current_step_key'] );
		$this->assertCount( 12, $state['steps'] );
		$this->assertArrayHasKey( 'profile', $state['prefill'] );
		$this->assertArrayHasKey( Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE, $state['prefill']['profile'] );
	}

	public function test_at_review_without_provider_sets_blocked(): void {
		$settings = new Settings_Service();
		$draft_svc = new Onboarding_Draft_Service( $settings );
		$draft = $draft_svc->get_draft();
		$draft['current_step_key'] = Onboarding_Step_Keys::REVIEW;
		$draft['overall_status'] = Onboarding_Statuses::IN_PROGRESS;
		$draft['step_statuses'][ Onboarding_Step_Keys::REVIEW ] = Onboarding_Statuses::STEP_IN_PROGRESS;
		$draft_svc->save_draft( $draft );
		$normalizer = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$prefill_svc = new Onboarding_Prefill_Service( $profile_store, $settings, null );
		$builder = new Onboarding_UI_State_Builder( $draft_svc, $prefill_svc );
		$state = $builder->build_for_screen();
		$this->assertTrue( $state['is_blocked'] );
		$this->assertNotEmpty( $state['blockers'] );
		$this->assertFalse( $state['is_provider_ready'] );
	}

	public function test_prefill_contains_no_secret_keys(): void {
		$builder = $this->get_builder();
		$state = $builder->build_for_screen();
		$prefill = $state['prefill'];
		$this->assertArrayHasKey( 'provider_refs', $prefill );
		foreach ( $prefill['provider_refs'] as $ref ) {
			$this->assertArrayHasKey( 'provider_id', $ref );
			$this->assertArrayHasKey( 'credential_state', $ref );
			$this->assertArrayNotHasKey( 'api_key', $ref );
			$this->assertArrayNotHasKey( 'secret', $ref );
		}
	}

	public function test_build_for_screen_includes_industry_question_pack_state_when_no_repo(): void {
		$builder = $this->get_builder();
		$state = $builder->build_for_screen();
		$this->assertArrayHasKey( 'industry_question_pack', $state );
		$this->assertArrayHasKey( 'industry_question_pack_answers', $state );
		$this->assertNull( $state['industry_question_pack'] );
		$this->assertSame( array(), $state['industry_question_pack_answers'] );
	}

	public function test_build_for_screen_includes_industry_question_pack_when_primary_set_and_supported(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS => array( 'realtor' => array( 'market_focus' => 'residential' ) ),
		) );
		$qp_registry = new Industry_Question_Pack_Registry();
		$qp_registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$normalizer = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$draft_svc = new Onboarding_Draft_Service( $settings );
		$prefill_svc = new Onboarding_Prefill_Service( $profile_store, $settings, null );
		$builder = new Onboarding_UI_State_Builder( $draft_svc, $prefill_svc, $repo, $qp_registry );
		$state = $builder->build_for_screen();
		$this->assertIsArray( $state['industry_question_pack'] );
		$this->assertSame( 'realtor', $state['industry_question_pack']['industry_key'] ?? '' );
		$this->assertSame( array( 'market_focus' => 'residential' ), $state['industry_question_pack_answers'] );
	}

	public function test_build_for_screen_industry_question_pack_null_when_primary_unsupported(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->merge_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'unknown_vertical' ) );
		$qp_registry = new Industry_Question_Pack_Registry();
		$qp_registry->load( Industry_Question_Pack_Definitions::default_packs() );
		$normalizer = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$draft_svc = new Onboarding_Draft_Service( $settings );
		$prefill_svc = new Onboarding_Prefill_Service( $profile_store, $settings, null );
		$builder = new Onboarding_UI_State_Builder( $draft_svc, $prefill_svc, $repo, $qp_registry );
		$state = $builder->build_for_screen();
		$this->assertNull( $state['industry_question_pack'] );
		$this->assertSame( array(), $state['industry_question_pack_answers'] );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}
}
