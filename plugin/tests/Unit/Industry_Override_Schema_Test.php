<?php
/**
 * Unit tests for Industry_Override_Schema (industry-override-contract, Prompt 366).
 * Valid/invalid override objects, sanitize_reason, is_valid.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';

final class Industry_Override_Schema_Test extends TestCase {

	private function valid_override( array $overrides = array() ): array {
		return array_merge( array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_SECTION,
			Industry_Override_Schema::FIELD_TARGET_KEY  => 'hero_intro_01',
			Industry_Override_Schema::FIELD_STATE       => Industry_Override_Schema::STATE_ACCEPTED,
			Industry_Override_Schema::FIELD_REASON      => 'Client requested this section.',
		), $overrides );
	}

	public function test_validate_returns_empty_for_valid_override(): void {
		$override = $this->valid_override();
		$this->assertSame( array(), Industry_Override_Schema::validate( $override ) );
		$this->assertTrue( Industry_Override_Schema::is_valid( $override ) );
	}

	public function test_validate_rejects_invalid_target_type(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_TARGET_TYPE => 'invalid' ) );
		$errors = Industry_Override_Schema::validate( $override );
		$this->assertContains( 'invalid_target_type', $errors );
		$this->assertFalse( Industry_Override_Schema::is_valid( $override ) );
	}

	public function test_validate_rejects_missing_target_key(): void {
		$override = $this->valid_override();
		unset( $override[ Industry_Override_Schema::FIELD_TARGET_KEY ] );
		$errors = Industry_Override_Schema::validate( $override );
		$this->assertContains( 'missing_target_key', $errors );
	}

	public function test_validate_rejects_empty_target_key(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_TARGET_KEY => '' ) );
		$errors = Industry_Override_Schema::validate( $override );
		$this->assertContains( 'missing_target_key', $errors );
	}

	public function test_validate_rejects_invalid_state(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_STATE => 'custom' ) );
		$errors = Industry_Override_Schema::validate( $override );
		$this->assertContains( 'invalid_state', $errors );
	}

	public function test_validate_rejects_reason_too_long(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_REASON => str_repeat( 'x', Industry_Override_Schema::REASON_MAX_LENGTH + 1 ) ) );
		$errors = Industry_Override_Schema::validate( $override );
		$this->assertContains( 'reason_too_long', $errors );
	}

	public function test_sanitize_reason_strips_tags_and_trims(): void {
		$raw = '  <b>Bold</b> and normal text.  ';
		$out = Industry_Override_Schema::sanitize_reason( $raw );
		$this->assertStringNotContainsString( '<', $out );
		$this->assertSame( 'Bold and normal text.', $out );
	}

	public function test_sanitize_reason_truncates_to_max_length(): void {
		$raw = str_repeat( 'a', Industry_Override_Schema::REASON_MAX_LENGTH + 10 );
		$out = Industry_Override_Schema::sanitize_reason( $raw );
		$this->assertSame( Industry_Override_Schema::REASON_MAX_LENGTH, strlen( $out ) );
	}

	public function test_valid_override_accepted_state(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_STATE => Industry_Override_Schema::STATE_ACCEPTED ) );
		$this->assertTrue( Industry_Override_Schema::is_valid( $override ) );
	}

	public function test_valid_override_rejected_state(): void {
		$override = $this->valid_override( array( Industry_Override_Schema::FIELD_STATE => Industry_Override_Schema::STATE_REJECTED ) );
		$this->assertTrue( Industry_Override_Schema::is_valid( $override ) );
	}

	public function test_valid_override_page_template_target_type(): void {
		$override = $this->valid_override( array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE,
			Industry_Override_Schema::FIELD_TARGET_KEY  => 'hub_services_01',
		) );
		$this->assertSame( array(), Industry_Override_Schema::validate( $override ) );
	}

	public function test_valid_override_build_plan_item_target_type(): void {
		$override = $this->valid_override( array(
			Industry_Override_Schema::FIELD_TARGET_TYPE => Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM,
			Industry_Override_Schema::FIELD_TARGET_KEY  => 'item-uuid-123',
		) );
		$this->assertSame( array(), Industry_Override_Schema::validate( $override ) );
	}
}
