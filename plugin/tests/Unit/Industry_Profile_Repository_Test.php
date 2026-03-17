<?php
/**
 * Unit tests for Industry_Profile_Repository: get/set/merge, empty state, exportable structure (Prompt 321).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';

final class Industry_Profile_Repository_Test extends TestCase {

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Industry_Profile_Repository */
	private Industry_Profile_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->settings   = new Settings_Service();
		$this->repository = new Industry_Profile_Repository( $this->settings );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_get_profile_returns_empty_when_not_set(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		$profile = $this->repository->get_profile();
		$this->assertSame( Industry_Profile_Schema::SUPPORTED_SCHEMA_VERSION, $profile[ Industry_Profile_Schema::FIELD_SCHEMA_VERSION ] );
		$this->assertSame( '', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array(), $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
	}

	public function test_get_empty_profile_returns_schema_default(): void {
		$empty = $this->repository->get_empty_profile();
		$this->assertEquals( Industry_Profile_Schema::get_empty_profile(), $empty );
	}

	public function test_set_profile_persists_and_get_returns_it(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION        => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'healthcare' ),
			Industry_Profile_Schema::FIELD_SUBTYPE                => 'law_firm',
			Industry_Profile_Schema::FIELD_SERVICE_MODEL          => 'b2b',
			Industry_Profile_Schema::FIELD_GEO_MODEL              => 'regional',
		);
		$this->repository->set_profile( $profile );
		$retrieved = $this->repository->get_profile();
		$this->assertSame( 'legal', $retrieved[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array( 'healthcare' ), $retrieved[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
		$this->assertSame( 'law_firm', $retrieved[ Industry_Profile_Schema::FIELD_SUBTYPE ] );
		$this->assertSame( 'b2b', $retrieved[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] );
		$this->assertSame( 'regional', $retrieved[ Industry_Profile_Schema::FIELD_GEO_MODEL ] );
	}

	public function test_merge_profile_updates_only_provided_keys(): void {
		$this->repository->set_profile( Industry_Profile_Schema::get_empty_profile() );
		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'cosmetology',
			Industry_Profile_Schema::FIELD_GEO_MODEL              => 'local',
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( 'cosmetology', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( 'local', $profile[ Industry_Profile_Schema::FIELD_GEO_MODEL ] );
		$this->assertSame( array(), $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
	}

	public function test_multi_industry_storage_round_trips(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION        => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'realtor',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'legal', 'disaster_recovery' ),
		);
		$this->repository->set_profile( $profile );
		$retrieved = $this->repository->get_profile();
		$this->assertSame( 'realtor', $retrieved[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array( 'legal', 'disaster_recovery' ), $retrieved[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
	}

	/**
	 * Onboarding field persistence (industry-onboarding-field-contract): all five industry keys
	 * merged via merge_profile persist and are returned by get_profile.
	 */
	public function test_onboarding_industry_fields_persist_via_merge_profile(): void {
		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'plumber',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'disaster_recovery' ),
			Industry_Profile_Schema::FIELD_SUBTYPE                => 'residential',
			Industry_Profile_Schema::FIELD_SERVICE_MODEL          => 'local_service',
			Industry_Profile_Schema::FIELD_GEO_MODEL              => 'local',
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( 'plumber', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array( 'disaster_recovery' ), $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
		$this->assertSame( 'residential', $profile[ Industry_Profile_Schema::FIELD_SUBTYPE ] );
		$this->assertSame( 'local_service', $profile[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] );
		$this->assertSame( 'local', $profile[ Industry_Profile_Schema::FIELD_GEO_MODEL ] );
	}

	/**
	 * Question-pack answers merge (industry-question-pack-contract): merge_profile with question_pack_answers
	 * persists and get_profile returns them; merge by industry_key does not overwrite other industries.
	 */
	public function test_merge_profile_question_pack_answers_persists_and_merges_by_industry(): void {
		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS => array(
				'realtor' => array( 'market_focus' => 'residential', 'service_areas' => 'Boston' ),
			),
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( array( 'market_focus' => 'residential', 'service_areas' => 'Boston' ), $profile[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]['realtor'] ?? null );

		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS => array(
				'realtor' => array( 'listing_types' => 'both' ),
				'plumber' => array( 'service_scope' => 'residential' ),
			),
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( array( 'market_focus' => 'residential', 'service_areas' => 'Boston', 'listing_types' => 'both' ), $profile[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]['realtor'] ?? null );
		$this->assertSame( array( 'service_scope' => 'residential' ), $profile[ Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS ]['plumber'] ?? null );
	}

	/** Prompt 414: industry_subtype_key persists via merge_profile; normalized and length-capped. */
	public function test_merge_profile_industry_subtype_key_persists(): void {
		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( 'realtor_buyer_agent', $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] );

		$this->repository->merge_profile( array( Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => '' ) );
		$profile = $this->repository->get_profile();
		$this->assertSame( '', $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] );
	}

	/** Prompt 388: selected_starter_bundle_key persists via merge_profile; safe for recommendation consumers. */
	public function test_merge_profile_selected_starter_bundle_key_persists(): void {
		$this->repository->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'realtor_starter',
		) );
		$profile = $this->repository->get_profile();
		$this->assertSame( 'realtor_starter', $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] );

		$this->repository->merge_profile( array( Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => '' ) );
		$profile = $this->repository->get_profile();
		$this->assertSame( '', $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] );
	}
}
