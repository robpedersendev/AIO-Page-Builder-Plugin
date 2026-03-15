<?php
/**
 * Unit tests for Industry_Pack_Schema: required/optional fields, status and version rules,
 * validate_pack for valid and invalid pack objects (industry-pack-schema.md; Prompt 320).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';

final class Industry_Pack_Schema_Test extends TestCase {

	public function test_required_fields_include_industry_key_name_summary_status_version_marker(): void {
		$required = Industry_Pack_Schema::get_required_fields();
		$this->assertContains( Industry_Pack_Schema::FIELD_INDUSTRY_KEY, $required );
		$this->assertContains( Industry_Pack_Schema::FIELD_NAME, $required );
		$this->assertContains( Industry_Pack_Schema::FIELD_SUMMARY, $required );
		$this->assertContains( Industry_Pack_Schema::FIELD_STATUS, $required );
		$this->assertContains( Industry_Pack_Schema::FIELD_VERSION_MARKER, $required );
		$this->assertCount( 5, $required );
	}

	public function test_optional_fields_list_includes_refs_and_key_arrays(): void {
		$optional = Industry_Pack_Schema::get_optional_fields();
		$this->assertNotEmpty( $optional );
		$this->assertContains( Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES, $optional );
		$this->assertContains( Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS, $optional );
		$this->assertContains( Industry_Pack_Schema::FIELD_HELPER_OVERLAY_REFS, $optional );
		$this->assertContains( Industry_Pack_Schema::FIELD_AI_RULE_REF, $optional );
		$this->assertContains( Industry_Pack_Schema::FIELD_METADATA, $optional );
	}

	public function test_allowed_statuses_include_active_draft_deprecated(): void {
		$statuses = Industry_Pack_Schema::get_allowed_statuses();
		$this->assertContains( Industry_Pack_Schema::STATUS_ACTIVE, $statuses );
		$this->assertContains( Industry_Pack_Schema::STATUS_DRAFT, $statuses );
		$this->assertContains( Industry_Pack_Schema::STATUS_DEPRECATED, $statuses );
		$this->assertCount( 3, $statuses );
	}

	public function test_is_allowed_status_accepts_valid_rejects_invalid(): void {
		$this->assertTrue( Industry_Pack_Schema::is_allowed_status( Industry_Pack_Schema::STATUS_ACTIVE ) );
		$this->assertTrue( Industry_Pack_Schema::is_allowed_status( Industry_Pack_Schema::STATUS_DRAFT ) );
		$this->assertFalse( Industry_Pack_Schema::is_allowed_status( 'archived' ) );
		$this->assertFalse( Industry_Pack_Schema::is_allowed_status( '' ) );
	}

	public function test_is_supported_version_accepts_1_rejects_other(): void {
		$this->assertTrue( Industry_Pack_Schema::is_supported_version( Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION ) );
		$this->assertTrue( Industry_Pack_Schema::is_supported_version( '1' ) );
		$this->assertFalse( Industry_Pack_Schema::is_supported_version( '2' ) );
		$this->assertFalse( Industry_Pack_Schema::is_supported_version( '' ) );
	}

	/** Valid minimal pack has all required keys; validate_pack returns empty. */
	public function test_validate_pack_returns_empty_for_valid_minimal_pack(): void {
		$pack = $this->get_valid_minimal_pack();
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertSame( array(), $errors );
	}

	/** Missing required field yields missing_required error. */
	public function test_validate_pack_returns_missing_required_when_field_absent(): void {
		$pack = $this->get_valid_minimal_pack();
		unset( $pack[ Industry_Pack_Schema::FIELD_SUMMARY ] );
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'missing_required', $codes );
	}

	/** Invalid status yields invalid_status error. */
	public function test_validate_pack_returns_invalid_status_for_bad_status(): void {
		$pack = $this->get_valid_minimal_pack();
		$pack[ Industry_Pack_Schema::FIELD_STATUS ] = 'archived';
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'invalid_status', $codes );
	}

	/** Unsupported version_marker yields unsupported_version error. */
	public function test_validate_pack_returns_unsupported_version_for_bad_version(): void {
		$pack = $this->get_valid_minimal_pack();
		$pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] = '2';
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'unsupported_version', $codes );
	}

	/** Invalid industry_key pattern yields industry_key_invalid_pattern error. */
	public function test_validate_pack_returns_error_for_invalid_industry_key_pattern(): void {
		$pack = $this->get_valid_minimal_pack();
		$pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] = 'Invalid Key!';
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'industry_key_invalid_pattern', $codes );
	}

	/** Empty industry_key yields missing_required (required-field check runs first). */
	public function test_validate_pack_returns_missing_required_for_empty_industry_key(): void {
		$pack = $this->get_valid_minimal_pack();
		$pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] = '   ';
		$errors = Industry_Pack_Schema::validate_pack( $pack );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'missing_required', $codes );
	}

	private function get_valid_minimal_pack(): array {
		return array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'legal',
			Industry_Pack_Schema::FIELD_NAME          => 'Legal',
			Industry_Pack_Schema::FIELD_SUMMARY       => 'Legal services industry pack.',
			Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
			Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
		);
	}
}
