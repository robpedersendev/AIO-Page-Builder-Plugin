<?php
/**
 * Unit tests for Industry_Subtype_Resolver (Prompt 414).
 *
 * Covers: valid subtype selection; mismatched parent/subtype; fallback when subtype missing or invalid.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Subtype_Resolver.php';

final class Industry_Subtype_Resolver_Test extends TestCase {

	private function subtype_def( string $subtype_key, string $parent_industry_key, string $status = 'active' ): array {
		return array(
			Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => $subtype_key,
			Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => $parent_industry_key,
			Industry_Subtype_Registry::FIELD_LABEL              => $subtype_key,
			Industry_Subtype_Registry::FIELD_SUMMARY            => 'Summary',
			Industry_Subtype_Registry::FIELD_STATUS             => $status,
			Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		);
	}

	protected function setUp(): void {
		parent::setUp();
		\delete_option( Option_Names::INDUSTRY_PROFILE );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_resolve_from_profile_with_valid_subtype_returns_has_valid_subtype_true(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array(
			$this->subtype_def( 'realtor_buyer_agent', 'realtor' ),
		) );
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );

		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
		);
		$ctx = $resolver->resolve_from_profile( $profile );

		$this->assertSame( 'realtor', $ctx['primary_industry_key'] );
		$this->assertSame( 'realtor_buyer_agent', $ctx['industry_subtype_key'] );
		$this->assertTrue( $ctx['has_valid_subtype'] );
		$this->assertIsArray( $ctx['resolved_subtype'] );
		$this->assertSame( 'realtor_buyer_agent', $ctx['resolved_subtype'][ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'realtor', $ctx['resolved_subtype'][ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] );
	}

	public function test_resolve_from_profile_with_mismatched_parent_returns_has_valid_subtype_false(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array(
			$this->subtype_def( 'realtor_buyer_agent', 'realtor' ),
		) );
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );

		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'plumber',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
		);
		$ctx = $resolver->resolve_from_profile( $profile );

		$this->assertSame( 'plumber', $ctx['primary_industry_key'] );
		$this->assertSame( '', $ctx['industry_subtype_key'] );
		$this->assertFalse( $ctx['has_valid_subtype'] );
		$this->assertNull( $ctx['resolved_subtype'] );
	}

	public function test_resolve_from_profile_with_no_subtype_key_fallback_to_parent_only(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );

		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' );
		$ctx = $resolver->resolve_from_profile( $profile );

		$this->assertSame( 'realtor', $ctx['primary_industry_key'] );
		$this->assertSame( '', $ctx['industry_subtype_key'] );
		$this->assertFalse( $ctx['has_valid_subtype'] );
		$this->assertNull( $ctx['resolved_subtype'] );
	}

	public function test_resolve_from_profile_with_unknown_subtype_key_fallback_to_parent_only(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );

		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'unknown_subtype',
		);
		$ctx = $resolver->resolve_from_profile( $profile );

		$this->assertSame( 'realtor', $ctx['primary_industry_key'] );
		$this->assertSame( '', $ctx['industry_subtype_key'] );
		$this->assertFalse( $ctx['has_valid_subtype'] );
		$this->assertNull( $ctx['resolved_subtype'] );
	}

	public function test_resolve_without_registry_always_fallback_to_parent_only(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_buyer_agent',
		) );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, null );

		$ctx = $resolver->resolve();

		$this->assertSame( 'realtor', $ctx['primary_industry_key'] );
		$this->assertSame( '', $ctx['industry_subtype_key'] );
		$this->assertFalse( $ctx['has_valid_subtype'] );
		$this->assertNull( $ctx['resolved_subtype'] );
	}

	public function test_resolve_from_profile_deprecated_subtype_not_resolved(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_old', 'realtor', 'deprecated' ) ) );
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$resolver = new Industry_Subtype_Resolver( $profile_repo, $registry );

		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY  => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'realtor_old',
		);
		$ctx = $resolver->resolve_from_profile( $profile );

		$this->assertSame( 'realtor', $ctx['primary_industry_key'] );
		$this->assertSame( '', $ctx['industry_subtype_key'] );
		$this->assertFalse( $ctx['has_valid_subtype'] );
		$this->assertNull( $ctx['resolved_subtype'] );
	}
}
