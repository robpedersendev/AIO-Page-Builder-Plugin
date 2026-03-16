<?php
/**
 * Unit tests for Industry_Definition_Linter (Prompt 438). Lint result shape, duplicate keys, subtype parent.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Definition_Linter;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Definition_Linter.php';

final class Industry_Definition_Linter_Test extends TestCase {

	public function test_lint_returns_structure_with_empty_errors_when_all_null(): void {
		$linter = new Industry_Definition_Linter( null, null, null, null, null );
		$result = $linter->lint();
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertArrayHasKey( 'warnings', $result );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertIsArray( $result['warnings'] );
		$this->assertSame( 0, $result['summary']['error_count'] );
		$this->assertSame( 0, $result['summary']['warning_count'] );
	}

	public function test_lint_includes_health_check_errors(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY    => 'test_industry',
				Industry_Pack_Schema::FIELD_NAME            => 'Test',
				Industry_Pack_Schema::FIELD_SUMMARY         => 'Test pack',
				Industry_Pack_Schema::FIELD_STATUS         => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
				Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF => 'missing_preset',
			),
		) );
		$health = new Industry_Health_Check_Service( null, $pack_registry, null, null, null, new \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry(), null, null, null, null, null );
		$linter = new Industry_Definition_Linter( $pack_registry, null, $health, null, null );
		$result = $linter->lint();
		$this->assertGreaterThanOrEqual( 1, $result['summary']['error_count'] );
		$codes = array_column( $result['errors'], 'code' );
		$this->assertContains( 'ref_not_resolved', $codes );
	}

	public function test_lint_detects_subtype_parent_missing(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'existing_pack',
				Industry_Pack_Schema::FIELD_NAME          => 'Existing',
				Industry_Pack_Schema::FIELD_SUMMARY        => 'Summary',
				Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
			),
		) );
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load( array(
			array(
				Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => 'sub_a',
				Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'existing_pack',
				Industry_Subtype_Registry::FIELD_LABEL               => 'Sub A',
				Industry_Subtype_Registry::FIELD_SUMMARY             => '',
				Industry_Subtype_Registry::FIELD_STATUS              => 'active',
				Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
			),
			array(
				Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => 'orphan_sub',
				Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'nonexistent_parent',
				Industry_Subtype_Registry::FIELD_LABEL               => 'Orphan',
				Industry_Subtype_Registry::FIELD_SUMMARY             => '',
				Industry_Subtype_Registry::FIELD_STATUS              => 'active',
				Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
			),
		) );
		$linter = new Industry_Definition_Linter( $pack_registry, null, null, null, $subtype_registry );
		$result = $linter->lint();
		$this->assertGreaterThanOrEqual( 1, $result['summary']['error_count'] );
		$subtype_errors = array_filter( $result['errors'], function ( array $e ): bool {
			return ( $e['code'] ?? '' ) === 'subtype_parent_missing' && ( $e['object_type'] ?? '' ) === Industry_Definition_Linter::OBJECT_TYPE_SUBTYPE;
		} );
		$this->assertCount( 1, $subtype_errors );
		$this->assertSame( 'orphan_sub', array_values( $subtype_errors )[0]['key'] );
	}

	public function test_get_all_issues_returns_flat_list(): void {
		$linter = new Industry_Definition_Linter( null, null, null, null, null );
		$issues = $linter->get_all_issues();
		$this->assertIsArray( $issues );
		$this->assertCount( 0, $issues );
	}
}
