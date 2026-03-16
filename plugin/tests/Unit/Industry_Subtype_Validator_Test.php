<?php
/**
 * Unit tests for Industry_Subtype_Validator (Prompt 421).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Validator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || exit;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Validator.php';

/**
 * @covers \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Validator
 */
final class Industry_Subtype_Validator_Test extends TestCase {

	private function valid_def(): array {
		return array(
			Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => 'realtor_buyer_agent',
			Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
			Industry_Subtype_Registry::FIELD_LABEL              => 'Buyer Agent',
			Industry_Subtype_Registry::FIELD_SUMMARY            => 'Summary',
			Industry_Subtype_Registry::FIELD_STATUS              => Industry_Subtype_Registry::STATUS_ACTIVE,
			Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		);
	}

	public function test_validate_valid_definition_returns_empty_errors(): void {
		$validator = new Industry_Subtype_Validator();
		$errors = $validator->validate( $this->valid_def(), null );
		$this->assertSame( array(), $errors );
	}

	public function test_validate_valid_definition_with_parent_check_passes_when_parent_in_set(): void {
		$validator = new Industry_Subtype_Validator();
		$valid_parents = array( 'realtor' => true, 'plumber' => true );
		$errors = $validator->validate( $this->valid_def(), $valid_parents );
		$this->assertSame( array(), $errors );
	}

	public function test_validate_invalid_parent_industry_ref_returns_error_when_parent_check_provided(): void {
		$validator = new Industry_Subtype_Validator();
		$valid_parents = array( 'plumber' => true );
		$def = $this->valid_def();
		$def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] = 'realtor';
		$errors = $validator->validate( $def, $valid_parents );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'parent_industry_key', implode( ' ', $errors ) );
	}

	public function test_validate_missing_subtype_key_returns_error(): void {
		$validator = new Industry_Subtype_Validator();
		$def = $this->valid_def();
		unset( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] );
		$errors = $validator->validate( $def, null );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'subtype_key', implode( ' ', $errors ) );
	}

	public function test_validate_missing_parent_industry_key_returns_error(): void {
		$validator = new Industry_Subtype_Validator();
		$def = $this->valid_def();
		unset( $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] );
		$errors = $validator->validate( $def, null );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'parent_industry_key', implode( ' ', $errors ) );
	}

	public function test_validate_invalid_status_returns_error(): void {
		$validator = new Industry_Subtype_Validator();
		$def = $this->valid_def();
		$def[ Industry_Subtype_Registry::FIELD_STATUS ] = 'invalid';
		$errors = $validator->validate( $def, null );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'status', implode( ' ', $errors ) );
	}

	public function test_validate_unsupported_version_marker_returns_error(): void {
		$validator = new Industry_Subtype_Validator();
		$def = $this->valid_def();
		$def[ Industry_Subtype_Registry::FIELD_VERSION_MARKER ] = '99';
		$errors = $validator->validate( $def, null );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'version_marker', implode( ' ', $errors ) );
	}

	public function test_validate_subtype_key_invalid_pattern_returns_error(): void {
		$validator = new Industry_Subtype_Validator();
		$def = $this->valid_def();
		$def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] = 'Invalid Key';
		$errors = $validator->validate( $def, null );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'subtype_key', implode( ' ', $errors ) );
	}

	public function test_registry_load_skips_invalid_definitions_safe_fallback(): void {
		$registry = new Industry_Subtype_Registry();
		$defs = array(
			$this->valid_def(),
			array( Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => 'bad', Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => '' ),
			array( Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => 'realtor_listing_agent', Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor', Industry_Subtype_Registry::FIELD_LABEL => 'Listing', Industry_Subtype_Registry::FIELD_SUMMARY => 'S', Industry_Subtype_Registry::FIELD_STATUS => 'active', Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1' ),
		);
		$registry->load( $defs );
		$this->assertNotNull( $registry->get( 'realtor_buyer_agent' ) );
		$this->assertNull( $registry->get( 'bad' ) );
		$this->assertNotNull( $registry->get( 'realtor_listing_agent' ) );
	}
}
