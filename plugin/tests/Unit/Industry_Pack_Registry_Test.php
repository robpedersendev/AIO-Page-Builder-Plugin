<?php
/**
 * Unit tests for Industry_Pack_Registry and Industry_Pack_Validator: valid load, duplicate/invalid keys,
 * invalid schema payloads, read-only lookup (Prompt 322).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';

final class Industry_Pack_Registry_Test extends TestCase {

	private function valid_pack( string $key = 'legal' ): array {
		return array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => $key,
			Industry_Pack_Schema::FIELD_NAME           => 'Legal',
			Industry_Pack_Schema::FIELD_SUMMARY        => 'Legal services.',
			Industry_Pack_Schema::FIELD_STATUS         => Industry_Pack_Schema::STATUS_ACTIVE,
			Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
		);
	}

	public function test_registry_loads_valid_pack_and_get_returns_it(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array( $this->valid_pack( 'legal' ) ) );
		$pack = $registry->get( 'legal' );
		$this->assertNotNull( $pack );
		$this->assertSame( 'legal', $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( Industry_Pack_Schema::STATUS_ACTIVE, $pack[ Industry_Pack_Schema::FIELD_STATUS ] );
	}

	public function test_registry_get_returns_null_for_unknown_key(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array( $this->valid_pack( 'legal' ) ) );
		$this->assertNull( $registry->get( 'unknown' ) );
		$this->assertNull( $registry->get( '' ) );
	}

	public function test_registry_list_by_status_returns_only_matching(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array(
			$this->valid_pack( 'legal' ),
			array_merge( $this->valid_pack( 'healthcare' ), array( Industry_Pack_Schema::FIELD_STATUS => Industry_Pack_Schema::STATUS_DRAFT ) ),
		) );
		$active = $registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
		$this->assertCount( 1, $active );
		$this->assertSame( 'legal', $active[0][ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] );
		$draft = $registry->list_by_status( Industry_Pack_Schema::STATUS_DRAFT );
		$this->assertCount( 1, $draft );
		$this->assertSame( 'healthcare', $draft[0][ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] );
	}

	public function test_registry_skips_invalid_pack_and_loads_valid(): void {
		$registry = new Industry_Pack_Registry();
		$invalid = array( Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'bad', Industry_Pack_Schema::FIELD_NAME => 'Bad' );
		$registry->load( array( $invalid, $this->valid_pack( 'legal' ) ) );
		$this->assertNull( $registry->get( 'bad' ) );
		$this->assertNotNull( $registry->get( 'legal' ) );
		$this->assertCount( 1, $registry->get_all() );
	}

	public function test_registry_skips_duplicate_key_first_wins(): void {
		$registry = new Industry_Pack_Registry();
		$first   = $this->valid_pack( 'legal' );
		$first[ Industry_Pack_Schema::FIELD_NAME ] = 'First';
		$second  = $this->valid_pack( 'legal' );
		$second[ Industry_Pack_Schema::FIELD_NAME ] = 'Second';
		$registry->load( array( $first, $second ) );
		$pack = $registry->get( 'legal' );
		$this->assertNotNull( $pack );
		$this->assertSame( 'First', $pack[ Industry_Pack_Schema::FIELD_NAME ] );
		$this->assertCount( 1, $registry->get_all() );
	}

	public function test_validator_bulk_reports_duplicate_keys(): void {
		$validator = new Industry_Pack_Validator();
		$result   = $validator->validate_bulk( array(
			$this->valid_pack( 'legal' ),
			$this->valid_pack( 'legal' ),
			$this->valid_pack( 'healthcare' ),
		) );
		$this->assertCount( 2, $result['valid'] );
		$this->assertContains( 'legal', $result['duplicate_keys'] );
	}

	public function test_validator_bulk_reports_invalid_schema_payloads(): void {
		$validator = new Industry_Pack_Validator();
		$result   = $validator->validate_bulk( array(
			$this->valid_pack( 'legal' ),
			array( Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'x', Industry_Pack_Schema::FIELD_STATUS => 'invalid_status' ),
		) );
		$this->assertCount( 1, $result['valid'] );
		$this->assertCount( 1, $result['invalid'] );
		$this->assertSame( 1, $result['invalid'][0]['index'] );
		$codes = array_column( $result['invalid'][0]['errors'], 'code' );
		$this->assertContains( 'invalid_status', $codes );
	}

	public function test_registry_has_any_false_when_empty(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array() );
		$this->assertFalse( $registry->has_any() );
	}

	public function test_registry_get_version_metadata_returns_null_for_unknown(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array( $this->valid_pack( 'legal' ) ) );
		$this->assertNull( $registry->get_version_metadata( 'unknown' ) );
	}

	public function test_registry_get_version_metadata_returns_marker_and_status(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array( $this->valid_pack( 'legal' ) ) );
		$meta = $registry->get_version_metadata( 'legal' );
		$this->assertNotNull( $meta );
		$this->assertSame( Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION, $meta['version_marker'] );
		$this->assertSame( Industry_Pack_Schema::STATUS_ACTIVE, $meta['status'] );
	}
}
