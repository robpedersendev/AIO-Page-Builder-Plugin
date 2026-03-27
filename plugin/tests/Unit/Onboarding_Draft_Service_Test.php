<?php
/**
 * Unit tests for Onboarding_Draft_Service: save/load, default draft, normalize (onboarding-state-machine.md §7).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Onboarding/Onboarding_Draft_Service.php';

final class Onboarding_Draft_Service_Test extends TestCase {

	private function get_settings(): Settings_Service {
		return new Settings_Service();
	}

	private function get_service(): Onboarding_Draft_Service {
		return new Onboarding_Draft_Service( $this->get_settings() );
	}

	public function test_default_draft_has_required_shape(): void {
		$svc   = $this->get_service();
		$draft = $svc->default_draft();
		$this->assertSame( Onboarding_Draft_Service::DRAFT_VERSION, $draft['version'] );
		$this->assertSame( Onboarding_Statuses::NOT_STARTED, $draft['overall_status'] );
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $draft['current_step_key'] );
		$this->assertSame( 0, $draft['furthest_step_index'] );
		$this->assertIsArray( $draft['step_statuses'] );
		$this->assertArrayHasKey( Onboarding_Step_Keys::REVIEW, $draft['step_statuses'] );
		$this->assertArrayNotHasKey( 'api_key', $draft );
		$this->assertArrayNotHasKey( 'secret', $draft );
		$this->assertNull( $draft['linked_build_plan_post_id'] );
		$this->assertNull( $draft['linked_build_plan_key'] );
	}

	public function test_get_draft_returns_normalized_draft(): void {
		$svc   = $this->get_service();
		$draft = $svc->get_draft();
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $draft['current_step_key'] );
		$this->assertIsArray( $draft['provider_refs'] );
	}

	public function test_save_draft_persists_and_loads(): void {
		$svc                       = $this->get_service();
		$draft                     = $svc->default_draft();
		$draft['overall_status']   = Onboarding_Statuses::DRAFT_SAVED;
		$draft['current_step_key'] = Onboarding_Step_Keys::BRAND_PROFILE;
		$draft['step_statuses'][ Onboarding_Step_Keys::WELCOME ]          = Onboarding_Statuses::STEP_COMPLETED;
		$draft['step_statuses'][ Onboarding_Step_Keys::BUSINESS_PROFILE ] = Onboarding_Statuses::STEP_COMPLETED;
		$draft['step_statuses'][ Onboarding_Step_Keys::BRAND_PROFILE ]    = Onboarding_Statuses::STEP_IN_PROGRESS;
		$svc->save_draft( $draft );
		$loaded = $svc->get_draft();
		$this->assertSame( Onboarding_Statuses::DRAFT_SAVED, $loaded['overall_status'] );
		$this->assertSame( Onboarding_Step_Keys::BRAND_PROFILE, $loaded['current_step_key'] );
		$this->assertSame( Onboarding_Statuses::STEP_COMPLETED, $loaded['step_statuses'][ Onboarding_Step_Keys::WELCOME ] );
	}

	public function test_save_draft_excludes_secrets(): void {
		$svc              = $this->get_service();
		$draft            = $svc->get_draft();
		$draft['api_key'] = 'sk-should-not-persist';
		$svc->save_draft( $draft );
		$loaded = $svc->get_draft();
		$this->assertArrayNotHasKey( 'api_key', $loaded );
	}

	public function test_clear_draft_resets_to_default_shape(): void {
		$svc                       = $this->get_service();
		$draft                     = $svc->get_draft();
		$draft['current_step_key'] = Onboarding_Step_Keys::PROVIDER_SETUP;
		$svc->save_draft( $draft );
		$svc->clear_draft();
		$loaded = $svc->get_draft();
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $loaded['current_step_key'] );
	}
}
