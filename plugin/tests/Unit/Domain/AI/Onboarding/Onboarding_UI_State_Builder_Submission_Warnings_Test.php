<?php
/**
 * Submission-step warnings: profile vs last planning run, stale crawl threshold.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Onboarding;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WP_Post;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../../../wordpress/' );

final class Onboarding_UI_State_Builder_Submission_Warnings_Test extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private function valid_draft_base(): array {
		return array(
			'goal_or_intent_text' => \str_repeat( 'x', 40 ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function valid_prefill_base(): array {
		return array(
			'profile' => array(
				'business_profile' => array(
					'business_name' => 'Test Business',
				),
			),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_get_post_by_id'] );
		parent::tearDown();
	}

	/**
	 * @param Profile_Snapshot_Repository_Interface $repo
	 * @return array<int, array{code: string, message: string}>
	 */
	private function invoke_warnings(
		Onboarding_UI_State_Builder $builder,
		array $draft,
		array $prefill
	): array {
		$m      = new ReflectionMethod( Onboarding_UI_State_Builder::class, 'build_submission_warnings' );
		$result = $m->invoke( $builder, $draft, $prefill );
		$this->assertIsArray( $result );
		return $result;
	}

	private function stub_secret_store_absent(): Provider_Secret_Store_Interface {
		$store = $this->createMock( Provider_Secret_Store_Interface::class );
		$store->method( 'get_credential_state' )->willReturn( Provider_Secret_Store_Interface::STATE_ABSENT );
		$store->method( 'has_credential' )->willReturn( false );
		return $store;
	}

	private function base_builder( Profile_Snapshot_Repository_Interface $repo, Settings_Service $settings ): Onboarding_UI_State_Builder {
		$normalizer    = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$draft_svc     = new Onboarding_Draft_Service( $settings );
		$prefill_svc   = new Onboarding_Prefill_Service( $profile_store, $settings, null, $this->stub_secret_store_absent() );
		return new Onboarding_UI_State_Builder( $draft_svc, $prefill_svc, null, null, $repo, $settings );
	}

	public function test_empty_when_profile_and_crawl_are_fresh(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn(
			array(
				new Profile_Snapshot_Data(
					's1',
					'other',
					'',
					'2025-05-01T12:00:00Z',
					'1.0',
					array(),
					array(),
					'brand_profile_merge'
				),
			)
		);
		$builder                        = $this->base_builder( $repo, $settings );
		$GLOBALS['_aio_get_post_by_id'] = array(
			10 => new WP_Post(
				array(
					'ID'                => 10,
					'post_modified_gmt' => '2025-06-01 12:00:00',
				)
			),
		);
		$draft                          = \array_merge( $this->valid_draft_base(), array( 'last_planning_run_post_id' => 10 ) );
		$prefill                        = \array_merge( $this->valid_prefill_base(), array( 'latest_crawl_session_timestamp' => gmdate( 'c', time() - 86400 ) ) );
		$w                              = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertSame( array(), $w );
	}

	public function test_profile_updated_warning_when_merge_snapshot_newer_than_run(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn(
			array(
				new Profile_Snapshot_Data(
					's1',
					'other',
					'',
					'2025-06-15T12:00:00Z',
					'1.0',
					array(),
					array(),
					'business_profile_merge'
				),
			)
		);
		$builder                        = $this->base_builder( $repo, $settings );
		$GLOBALS['_aio_get_post_by_id'] = array(
			10 => new WP_Post(
				array(
					'ID'                => 10,
					'post_modified_gmt' => '2025-01-15 10:00:00',
				)
			),
		);
		$draft                          = \array_merge( $this->valid_draft_base(), array( 'last_planning_run_post_id' => 10 ) );
		$prefill                        = \array_merge( $this->valid_prefill_base(), array( 'latest_crawl_session_timestamp' => gmdate( 'c', time() - 86400 ) ) );
		$w                              = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertCount( 1, $w );
		$this->assertSame( 'profile_updated_since_last_run', $w[0]['code'] );
	}

	public function test_stale_crawl_warning_when_session_older_than_threshold(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn( array() );
		$builder = $this->base_builder( $repo, $settings );
		$draft   = \array_merge( $this->valid_draft_base(), array( 'last_planning_run_post_id' => 0 ) );
		$prefill = \array_merge( $this->valid_prefill_base(), array( 'latest_crawl_session_timestamp' => '1990-01-01T00:00:00Z' ) );
		$w       = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertCount( 1, $w );
		$this->assertSame( 'stale_crawl_context', $w[0]['code'] );
	}

	public function test_both_warnings_when_both_conditions_hold(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn(
			array(
				new Profile_Snapshot_Data(
					's1',
					'other',
					'',
					'2025-08-01T00:00:00Z',
					'1.0',
					array(),
					array(),
					'brand_profile_merge'
				),
			)
		);
		$builder                        = $this->base_builder( $repo, $settings );
		$GLOBALS['_aio_get_post_by_id'] = array(
			10 => new WP_Post(
				array(
					'ID'                => 10,
					'post_modified_gmt' => '2025-01-01T00:00:00',
				)
			),
		);
		$draft                          = \array_merge( $this->valid_draft_base(), array( 'last_planning_run_post_id' => 10 ) );
		$prefill                        = \array_merge( $this->valid_prefill_base(), array( 'latest_crawl_session_timestamp' => '1990-01-01T00:00:00Z' ) );
		$w                              = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertCount( 2, $w );
		$codes = array( $w[0]['code'], $w[1]['code'] );
		$this->assertContains( 'profile_updated_since_last_run', $codes );
		$this->assertContains( 'stale_crawl_context', $codes );
	}

	public function test_no_invented_warnings_when_supporting_data_absent(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn( array() );
		$builder = $this->base_builder( $repo, $settings );
		$draft   = $this->valid_draft_base();
		$prefill = $this->valid_prefill_base();
		$w       = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertSame( array(), $w );
	}

	public function test_planning_context_incomplete_when_goal_too_short(): void {
		$settings = new Settings_Service();
		$repo     = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn( array() );
		$builder = $this->base_builder( $repo, $settings );
		$draft   = \array_merge( $this->valid_draft_base(), array( 'goal_or_intent_text' => 'short' ) );
		$prefill = $this->valid_prefill_base();
		$w       = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertCount( 1, $w );
		$this->assertSame( 'planning_context_incomplete', $w[0]['code'] );
	}

	public function test_custom_stale_threshold_from_main_settings(): void {
		$settings = new Settings_Service();
		$settings->set(
			Option_Names::MAIN_SETTINGS,
			array( 'onboarding_stale_crawl_warning_days' => 3650 )
		);
		$repo = $this->createMock( Profile_Snapshot_Repository_Interface::class );
		$repo->method( 'get_all' )->willReturn( array() );
		$builder = $this->base_builder( $repo, $settings );
		$draft   = $this->valid_draft_base();
		// * 200 days ago — still "fresh" when threshold is 3650 days.
		$prefill = \array_merge( $this->valid_prefill_base(), array( 'latest_crawl_session_timestamp' => gmdate( 'c', time() - 200 * 86400 ) ) );
		$w       = $this->invoke_warnings( $builder, $draft, $prefill );
		$this->assertSame( array(), $w );
	}
}
