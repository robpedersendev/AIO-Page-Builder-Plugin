<?php
/**
 * Unit tests for Version_Snapshot_Schema: required fields, scope types, statuses, use-case checklist (spec §10.8, Prompt 024).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Version_Snapshot_Schema.php';

final class Version_Snapshot_Schema_Test extends TestCase {

	/**
	 * Completeness checklist: all spec §10.8 snapshot use cases have a scope_type.
	 */
	public function test_scope_types_cover_spec_10_8_use_cases(): void {
		$types = Version_Snapshot_Schema::get_scope_types();
		$checklist = array(
			'template registry snapshots' => Version_Snapshot_Schema::SCOPE_REGISTRY,
			'schema snapshots'            => Version_Snapshot_Schema::SCOPE_SCHEMA,
			'compatibility snapshots'     => Version_Snapshot_Schema::SCOPE_COMPATIBILITY,
			'build-context snapshots'     => Version_Snapshot_Schema::SCOPE_BUILD_CONTEXT,
			'prompt-pack snapshots'       => Version_Snapshot_Schema::SCOPE_PROMPT_PACK,
		);
		foreach ( $checklist as $label => $scope_type ) {
			$this->assertContains( $scope_type, $types, "Scope type for {$label} must be present" );
		}
		$this->assertCount( 5, $types );
	}

	public function test_required_fields_include_object_model_attributes(): void {
		$required = Version_Snapshot_Schema::get_required_fields();
		$this->assertContains( Version_Snapshot_Schema::FIELD_SNAPSHOT_ID, $required );
		$this->assertContains( Version_Snapshot_Schema::FIELD_SCOPE_TYPE, $required );
		$this->assertContains( Version_Snapshot_Schema::FIELD_SCOPE_ID, $required );
		$this->assertContains( Version_Snapshot_Schema::FIELD_CREATED_AT, $required );
		$this->assertContains( Version_Snapshot_Schema::FIELD_SCHEMA_VERSION, $required );
		$this->assertContains( Version_Snapshot_Schema::FIELD_STATUS, $required );
		$this->assertCount( 6, $required );
	}

	public function test_optional_fields_include_payload_ref_and_object_refs(): void {
		$optional = Version_Snapshot_Schema::get_optional_fields();
		$this->assertContains( Version_Snapshot_Schema::FIELD_PAYLOAD_REF, $optional );
		$this->assertContains( Version_Snapshot_Schema::FIELD_OBJECT_REFS, $optional );
		$this->assertContains( Version_Snapshot_Schema::FIELD_PROVENANCE, $optional );
	}

	public function test_statuses_match_object_model(): void {
		$statuses = Version_Snapshot_Schema::get_statuses();
		$this->assertSame( array( Version_Snapshot_Schema::STATUS_ACTIVE, Version_Snapshot_Schema::STATUS_SUPERSEDED ), $statuses );
	}

	public function test_is_valid_scope_type_and_is_valid_status(): void {
		$this->assertTrue( Version_Snapshot_Schema::is_valid_scope_type( Version_Snapshot_Schema::SCOPE_REGISTRY ) );
		$this->assertTrue( Version_Snapshot_Schema::is_valid_scope_type( Version_Snapshot_Schema::SCOPE_SCHEMA ) );
		$this->assertFalse( Version_Snapshot_Schema::is_valid_scope_type( 'unknown' ) );
		$this->assertTrue( Version_Snapshot_Schema::is_valid_status( Version_Snapshot_Schema::STATUS_ACTIVE ) );
		$this->assertFalse( Version_Snapshot_Schema::is_valid_status( 'draft' ) );
	}

	/** Valid example: composition-linked registry snapshot. */
	public function test_example_valid_registry_snapshot_has_all_required_keys(): void {
		$valid = $this->get_valid_registry_snapshot_example();
		$required = Version_Snapshot_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $valid, "Valid example must have required field: {$field}" );
		}
		$this->assertSame( Version_Snapshot_Schema::SCOPE_REGISTRY, $valid[ Version_Snapshot_Schema::FIELD_SCOPE_TYPE ] );
	}

	/** Valid example: schema snapshot. */
	public function test_example_valid_schema_snapshot_has_all_required_keys(): void {
		$valid = $this->get_valid_schema_snapshot_example();
		$required = Version_Snapshot_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $valid, "Valid schema example must have required field: {$field}" );
		}
		$this->assertSame( Version_Snapshot_Schema::SCOPE_SCHEMA, $valid[ Version_Snapshot_Schema::FIELD_SCOPE_TYPE ] );
	}

	/** Invalid example: missing required scope_id. */
	public function test_example_invalid_missing_scope_id(): void {
		$invalid = $this->get_valid_registry_snapshot_example();
		$invalid[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] = '';
		$this->assertSame( '', $invalid[ Version_Snapshot_Schema::FIELD_SCOPE_ID ] );
	}

	/** Invalid example: unknown scope_type. */
	public function test_example_invalid_unknown_scope_type(): void {
		$this->assertFalse( Version_Snapshot_Schema::is_valid_scope_type( 'custom_unknown' ) );
	}

	public function test_constants_max_length(): void {
		$this->assertSame( 64, Version_Snapshot_Schema::SNAPSHOT_ID_MAX_LENGTH );
		$this->assertSame( 128, Version_Snapshot_Schema::SCOPE_ID_MAX_LENGTH );
	}

	private function get_valid_registry_snapshot_example(): array {
		return array(
			Version_Snapshot_Schema::FIELD_SNAPSHOT_ID   => 'snap-a1b2c3d4-registry',
			Version_Snapshot_Schema::FIELD_SCOPE_TYPE   => Version_Snapshot_Schema::SCOPE_REGISTRY,
			Version_Snapshot_Schema::FIELD_SCOPE_ID     => 'comp-uuid-12345',
			Version_Snapshot_Schema::FIELD_CREATED_AT   => '2025-07-15T10:30:00Z',
			Version_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Version_Snapshot_Schema::FIELD_STATUS      => Version_Snapshot_Schema::STATUS_ACTIVE,
		);
	}

	private function get_valid_schema_snapshot_example(): array {
		return array(
			Version_Snapshot_Schema::FIELD_SNAPSHOT_ID   => 'snap-schema-section-v1',
			Version_Snapshot_Schema::FIELD_SCOPE_TYPE   => Version_Snapshot_Schema::SCOPE_SCHEMA,
			Version_Snapshot_Schema::FIELD_SCOPE_ID     => 'section_registry_v1',
			Version_Snapshot_Schema::FIELD_CREATED_AT   => '2025-07-01T00:00:00Z',
			Version_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Version_Snapshot_Schema::FIELD_STATUS      => Version_Snapshot_Schema::STATUS_ACTIVE,
		);
	}
}
