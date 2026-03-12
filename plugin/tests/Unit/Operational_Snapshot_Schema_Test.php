<?php
/**
 * Unit tests for Operational_Snapshot_Schema: required fields, snapshot type, object family,
 * retention and rollback enums, validate_root (spec §41.1–41.3, §41.8, §11.5; Prompt 085).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';

final class Operational_Snapshot_Schema_Test extends TestCase {

	public function test_required_root_fields_include_snapshot_id_type_family_target_created_schema_version(): void {
		$required = Operational_Snapshot_Schema::get_required_root_fields();
		$this->assertContains( Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID, $required );
		$this->assertContains( Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE, $required );
		$this->assertContains( Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY, $required );
		$this->assertContains( Operational_Snapshot_Schema::FIELD_TARGET_REF, $required );
		$this->assertContains( Operational_Snapshot_Schema::FIELD_CREATED_AT, $required );
		$this->assertContains( Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION, $required );
		$this->assertCount( 6, $required );
	}

	public function test_snapshot_types_are_pre_change_and_post_change(): void {
		$types = Operational_Snapshot_Schema::get_snapshot_types();
		$this->assertContains( Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE, $types );
		$this->assertContains( Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE, $types );
		$this->assertCount( 2, $types );
	}

	public function test_is_valid_snapshot_type(): void {
		$this->assertTrue( Operational_Snapshot_Schema::is_valid_snapshot_type( Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE ) );
		$this->assertTrue( Operational_Snapshot_Schema::is_valid_snapshot_type( Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE ) );
		$this->assertFalse( Operational_Snapshot_Schema::is_valid_snapshot_type( 'during_change' ) );
		$this->assertFalse( Operational_Snapshot_Schema::is_valid_snapshot_type( '' ) );
	}

	public function test_object_families_cover_spec_41_1_scope(): void {
		$families = Operational_Snapshot_Schema::get_object_families();
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, $families );
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE_METADATA, $families );
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_HIERARCHY, $families );
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_MENU, $families );
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET, $families );
		$this->assertContains( Operational_Snapshot_Schema::OBJECT_FAMILY_BUILD_PLAN_TRANSITION, $families );
		$this->assertCount( 6, $families );
	}

	public function test_is_valid_object_family(): void {
		$this->assertTrue( Operational_Snapshot_Schema::is_valid_object_family( Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE ) );
		$this->assertTrue( Operational_Snapshot_Schema::is_valid_object_family( Operational_Snapshot_Schema::OBJECT_FAMILY_MENU ) );
		$this->assertFalse( Operational_Snapshot_Schema::is_valid_object_family( 'widget' ) );
	}

	public function test_retention_classes_and_rollback_statuses(): void {
		$ret = Operational_Snapshot_Schema::get_retention_classes();
		$this->assertContains( Operational_Snapshot_Schema::RETENTION_CLASS_PLAN_LINKED, $ret );
		$this->assertContains( Operational_Snapshot_Schema::RETENTION_CLASS_MEDIUM, $ret );
		$statuses = Operational_Snapshot_Schema::get_rollback_statuses();
		$this->assertContains( Operational_Snapshot_Schema::ROLLBACK_STATUS_AVAILABLE, $statuses );
		$this->assertContains( Operational_Snapshot_Schema::ROLLBACK_STATUS_EXPIRED, $statuses );
	}

	public function test_validate_root_accepts_valid_pre_change_snapshot(): void {
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID    => 'op-snap-pre-1',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF    => '42',
			Operational_Snapshot_Schema::FIELD_CREATED_AT   => '2025-03-12T10:00:00Z',
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE   => array( 'captured_at' => '2025-03-12T10:00:00Z', 'state_snapshot' => array( 'post_id' => 42 ) ),
		);
		$errors = Operational_Snapshot_Schema::validate_root( $snapshot );
		$this->assertSame( array(), $errors );
	}

	public function test_validate_root_accepts_valid_post_change_snapshot(): void {
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID    => 'op-snap-post-1',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
			Operational_Snapshot_Schema::FIELD_TARGET_REF    => 'design-tokens-primary',
			Operational_Snapshot_Schema::FIELD_CREATED_AT   => '2025-03-12T10:05:00Z',
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE  => array( 'captured_at' => '2025-03-12T10:05:00Z', 'outcome' => 'success' ),
		);
		$errors = Operational_Snapshot_Schema::validate_root( $snapshot );
		$this->assertSame( array(), $errors );
	}

	public function test_validate_root_rejects_missing_required_field(): void {
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID    => 'op-snap-bad',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF    => '42',
			Operational_Snapshot_Schema::FIELD_CREATED_AT   => '2025-03-12T10:00:00Z',
			// missing schema_version and pre_change
		);
		$errors = Operational_Snapshot_Schema::validate_root( $snapshot );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( 'missing_required', $codes );
		$this->assertContains( 'missing_pre_change_block', $codes );
	}

	public function test_validate_root_rejects_invalid_snapshot_type(): void {
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID    => 'op-snap-bad2',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => 'during_change',
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF    => '42',
			Operational_Snapshot_Schema::FIELD_CREATED_AT   => '2025-03-12T10:00:00Z',
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
		);
		$errors = Operational_Snapshot_Schema::validate_root( $snapshot );
		$this->assertNotEmpty( $errors );
		$this->assertSame( 'invalid_snapshot_type', $errors[0]['code'] ?? '' );
	}

	public function test_validate_root_rejects_invalid_object_family(): void {
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID    => 'op-snap-bad3',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => 'widget',
			Operational_Snapshot_Schema::FIELD_TARGET_REF    => '99',
			Operational_Snapshot_Schema::FIELD_CREATED_AT   => '2025-03-12T10:00:00Z',
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE   => array( 'captured_at' => '2025-03-12T10:00:00Z' ),
		);
		$errors = Operational_Snapshot_Schema::validate_root( $snapshot );
		$this->assertNotEmpty( $errors );
		$this->assertSame( 'invalid_object_family', $errors[0]['code'] ?? '' );
	}
}
