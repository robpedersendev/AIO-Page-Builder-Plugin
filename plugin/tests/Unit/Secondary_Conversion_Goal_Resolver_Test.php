<?php
/**
 * Unit tests for Secondary_Conversion_Goal_Resolver (Prompt 529). Valid and invalid secondary-goal resolution.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Secondary_Conversion_Goal_Resolver;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Secondary_Conversion_Goal_Resolver.php';

final class Secondary_Conversion_Goal_Resolver_Test extends TestCase {

	public function test_resolve_empty_profile_returns_empty_goals(): void {
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( array() );
		$this->assertSame( '', $result['primary_goal_key'] );
		$this->assertSame( '', $result['secondary_goal_key'] );
	}

	public function test_resolve_primary_only_returns_primary_only(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'bookings',
			Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY => '',
		);
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( $profile );
		$this->assertSame( 'bookings', $result['primary_goal_key'] );
		$this->assertSame( '', $result['secondary_goal_key'] );
	}

	public function test_resolve_primary_and_valid_secondary_returns_both(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'bookings',
			Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY => 'lead_capture',
		);
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( $profile );
		$this->assertSame( 'bookings', $result['primary_goal_key'] );
		$this->assertSame( 'lead_capture', $result['secondary_goal_key'] );
	}

	public function test_resolve_secondary_same_as_primary_returns_secondary_empty(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'calls',
			Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY => 'calls',
		);
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( $profile );
		$this->assertSame( 'calls', $result['primary_goal_key'] );
		$this->assertSame( '', $result['secondary_goal_key'] );
	}

	public function test_resolve_invalid_secondary_returns_secondary_empty(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'bookings',
			Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY => 'invalid_goal',
		);
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( $profile );
		$this->assertSame( 'bookings', $result['primary_goal_key'] );
		$this->assertSame( '', $result['secondary_goal_key'] );
	}

	public function test_resolve_no_primary_ignores_secondary(): void {
		$profile = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => '',
			Industry_Profile_Schema::FIELD_SECONDARY_CONVERSION_GOAL_KEY => 'lead_capture',
		);
		$resolver = new Secondary_Conversion_Goal_Resolver( null );
		$result = $resolver->resolve( $profile );
		$this->assertSame( '', $result['primary_goal_key'] );
		$this->assertSame( '', $result['secondary_goal_key'] );
	}
}
