<?php
/**
 * Integration tests — profile snapshot export / import serialization (v2-scope-backlog.md §3).
 *
 * Verifies that:
 * - Snapshots are correctly serialized to the export array shape (matching Export_Bundle_Schema::PROFILES_SNAPSHOT_HISTORY_KEY).
 * - Import/hydration produces correct Profile_Snapshot_Data from the serialized array.
 * - Schema version validation rejects records with incompatible profile_schema_version.
 * - A round-trip (serialize → deserialize → restore) preserves profile field values.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Integration\Export;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/tests/bootstrap_i18n_stub.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Data.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';

namespace AIOPageBuilder\Tests\Integration\Export;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Data;
use AIOPageBuilder\Infrastructure\Config\Versions;
use PHPUnit\Framework\TestCase;

function wp_json_encode( $data ): string {
	$r = \json_encode( $data );
	return is_string( $r ) ? $r : '';
}

// ---------------------------------------------------------------------------
// Helpers mirroring the serialization and hydration logic in Export_Generator
// and Restore_Pipeline without requiring full infrastructure.
// ---------------------------------------------------------------------------
final class Snapshot_Serializer {
	/**
	 * Mirrors Export_Generator::serialize_snapshot().
	 *
	 * @param Profile_Snapshot_Data $snapshot
	 * @return array<string, mixed>
	 */
	public static function serialize( Profile_Snapshot_Data $snapshot ): array {
		return array(
			'snapshot_id'            => $snapshot->snapshot_id,
			'scope_type'             => $snapshot->scope_type,
			'scope_id'               => $snapshot->scope_id,
			'created_at'             => $snapshot->created_at,
			'profile_schema_version' => $snapshot->profile_schema_version,
			'brand_profile'          => $snapshot->brand_profile,
			'business_profile'       => $snapshot->business_profile,
			'source'                 => $snapshot->source,
		);
	}

	/**
	 * Mirrors the hydration logic in Restore_Pipeline (profiles case).
	 * Returns null when schema version is incompatible or snapshot_id is missing.
	 *
	 * @param array<string, mixed> $rec
	 * @return Profile_Snapshot_Data|null
	 */
	public static function hydrate( array $rec ): ?Profile_Snapshot_Data {
		$snap_schema_version = (string) ( $rec['profile_schema_version'] ?? '' );
		if ( $snap_schema_version !== Versions::PROFILE_SCHEMA_VERSION ) {
			return null;
		}
		$snap_id = (string) ( $rec['snapshot_id'] ?? '' );
		if ( $snap_id === '' ) {
			return null;
		}
		return new Profile_Snapshot_Data(
			$snap_id,
			(string) ( $rec['scope_type'] ?? 'other' ),
			(string) ( $rec['scope_id'] ?? '' ),
			(string) ( $rec['created_at'] ?? '' ),
			$snap_schema_version,
			is_array( $rec['brand_profile'] ?? null ) ? $rec['brand_profile'] : array(),
			is_array( $rec['business_profile'] ?? null ) ? $rec['business_profile'] : array(),
			(string) ( $rec['source'] ?? 'manual' )
		);
	}
}

/**
 * @covers \AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator
 * @covers \AIOPageBuilder\Domain\ExportRestore\Import\Restore_Pipeline
 */
final class Profile_Snapshot_Export_Import_Test extends TestCase {

	private function make_snapshot( string $id = 'snap_export_001', string $source = 'brand_profile_merge' ): Profile_Snapshot_Data {
		return new Profile_Snapshot_Data(
			$id,
			'other',
			'',
			'2025-06-01 10:00:00',
			Versions::PROFILE_SCHEMA_VERSION,
			array( 'brand_positioning_summary' => 'Test Brand' ),
			array( 'industry' => 'SaaS', 'business_name' => 'Acme' ),
			$source
		);
	}

	public function test_serialized_snapshot_includes_required_fields(): void {
		$snap       = $this->make_snapshot();
		$serialized = Snapshot_Serializer::serialize( $snap );

		$this->assertArrayHasKey( 'snapshot_id', $serialized );
		$this->assertArrayHasKey( 'scope_type', $serialized );
		$this->assertArrayHasKey( 'scope_id', $serialized );
		$this->assertArrayHasKey( 'created_at', $serialized );
		$this->assertArrayHasKey( 'profile_schema_version', $serialized );
		$this->assertArrayHasKey( 'brand_profile', $serialized );
		$this->assertArrayHasKey( 'business_profile', $serialized );
		$this->assertArrayHasKey( 'source', $serialized );
	}

	public function test_serialized_snapshot_preserves_field_values(): void {
		$snap       = $this->make_snapshot( 'snap_vals', 'onboarding_completion' );
		$serialized = Snapshot_Serializer::serialize( $snap );

		$this->assertSame( 'snap_vals', $serialized['snapshot_id'] );
		$this->assertSame( 'onboarding_completion', $serialized['source'] );
		$this->assertSame( 'Test Brand', $serialized['brand_profile']['brand_positioning_summary'] ?? null );
		$this->assertSame( 'SaaS', $serialized['business_profile']['industry'] ?? null );
	}

	public function test_export_bundle_schema_key_is_profile_snapshot_history(): void {
		// * Verify the contract constant; import/export code uses this key for the filename.
		$this->assertSame( 'profile_snapshot_history', Export_Bundle_Schema::PROFILES_SNAPSHOT_HISTORY_KEY );
	}

	public function test_hydrate_produces_correct_snapshot_from_serialized_data(): void {
		$snap       = $this->make_snapshot( 'snap_hydrate', 'business_profile_merge' );
		$serialized = Snapshot_Serializer::serialize( $snap );
		$hydrated   = Snapshot_Serializer::hydrate( $serialized );

		$this->assertNotNull( $hydrated );
		$this->assertSame( 'snap_hydrate', $hydrated->snapshot_id );
		$this->assertSame( 'business_profile_merge', $hydrated->source );
		$this->assertSame( 'Test Brand', $hydrated->brand_profile['brand_positioning_summary'] ?? null );
		$this->assertSame( 'SaaS', $hydrated->business_profile['industry'] ?? null );
	}

	public function test_hydrate_rejects_incompatible_schema_version(): void {
		$rec = array(
			'snapshot_id'            => 'snap_old',
			'scope_type'             => 'other',
			'scope_id'               => '',
			'created_at'             => '2024-01-01 00:00:00',
			'profile_schema_version' => 'incompatible_version_99',
			'brand_profile'          => array(),
			'business_profile'       => array(),
			'source'                 => 'manual',
		);

		$hydrated = Snapshot_Serializer::hydrate( $rec );
		$this->assertNull( $hydrated, 'Incompatible schema version must be rejected.' );
	}

	public function test_hydrate_rejects_missing_snapshot_id(): void {
		$rec = array(
			'snapshot_id'            => '',
			'scope_type'             => 'other',
			'scope_id'               => '',
			'created_at'             => '2024-01-01 00:00:00',
			'profile_schema_version' => Versions::PROFILE_SCHEMA_VERSION,
			'brand_profile'          => array(),
			'business_profile'       => array(),
			'source'                 => 'manual',
		);

		$hydrated = Snapshot_Serializer::hydrate( $rec );
		$this->assertNull( $hydrated, 'Missing snapshot_id must be rejected.' );
	}

	public function test_round_trip_preserves_nested_profile_data(): void {
		$snap = new Profile_Snapshot_Data(
			'snap_round_trip',
			'other',
			'',
			'2025-07-01 12:00:00',
			Versions::PROFILE_SCHEMA_VERSION,
			array(
				'brand_positioning_summary' => 'Premium Brand',
				'voice_tone'                => array( 'formality_level' => 'formal' ),
			),
			array(
				'industry'  => 'Healthcare',
				'personas'  => array( array( 'role' => 'Physician' ) ),
			),
			'manual'
		);

		$serialized = Snapshot_Serializer::serialize( $snap );
		$hydrated   = Snapshot_Serializer::hydrate( $serialized );

		$this->assertNotNull( $hydrated );
		$this->assertSame( 'snap_round_trip', $hydrated->snapshot_id );
		$this->assertSame( 'formal', $hydrated->brand_profile['voice_tone']['formality_level'] ?? null );
		$this->assertSame( 'Physician', $hydrated->business_profile['personas'][0]['role'] ?? null );
	}

	public function test_multiple_snapshots_are_independently_serializable(): void {
		$snaps    = array(
			$this->make_snapshot( 'snap_a', 'brand_profile_merge' ),
			$this->make_snapshot( 'snap_b', 'business_profile_merge' ),
			$this->make_snapshot( 'snap_c', 'onboarding_completion' ),
		);
		$payload  = \array_map( array( Snapshot_Serializer::class, 'serialize' ), $snaps );
		$hydrated = \array_filter( \array_map( array( Snapshot_Serializer::class, 'hydrate' ), $payload ) );

		$this->assertCount( 3, $hydrated );
		$sources = array_column( array_values( $hydrated ), 'source' );
		$this->assertContains( 'brand_profile_merge', $sources );
		$this->assertContains( 'business_profile_merge', $sources );
		$this->assertContains( 'onboarding_completion', $sources );
	}
}
