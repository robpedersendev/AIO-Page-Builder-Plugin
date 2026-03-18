<?php
/**
 * Unit tests for composition validation state machine: statuses, validation result, validation codes, scenario matrix (spec §14.7, Prompt 023).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Statuses;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Codes;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Statuses.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Codes.php';

final class Composition_Validation_State_Machine_Test extends TestCase {

	public function test_lifecycle_statuses_match_object_model(): void {
		$statuses = Composition_Statuses::get_lifecycle_statuses();
		$this->assertContains( Composition_Statuses::DRAFT, $statuses );
		$this->assertContains( Composition_Statuses::ACTIVE, $statuses );
		$this->assertContains( Composition_Statuses::ARCHIVED, $statuses );
		$this->assertCount( 3, $statuses );
	}

	public function test_is_valid_lifecycle_status(): void {
		$this->assertTrue( Composition_Statuses::is_valid_lifecycle_status( Composition_Statuses::DRAFT ) );
		$this->assertTrue( Composition_Statuses::is_valid_lifecycle_status( Composition_Statuses::ACTIVE ) );
		$this->assertFalse( Composition_Statuses::is_valid_lifecycle_status( 'invalid' ) );
	}

	public function test_validation_result_allows_activation_only_for_valid_and_warning(): void {
		$this->assertTrue( Composition_Validation_Result::allows_activation( Composition_Validation_Result::VALID ) );
		$this->assertTrue( Composition_Validation_Result::allows_activation( Composition_Validation_Result::WARNING ) );
		$this->assertFalse( Composition_Validation_Result::allows_activation( Composition_Validation_Result::PENDING_VALIDATION ) );
		$this->assertFalse( Composition_Validation_Result::allows_activation( Composition_Validation_Result::VALIDATION_FAILED ) );
		$this->assertFalse( Composition_Validation_Result::allows_activation( Composition_Validation_Result::DEPRECATED_CONTEXT ) );
	}

	public function test_validation_codes_section_missing_is_blocking(): void {
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::SECTION_MISSING ) );
		$this->assertSame( Composition_Validation_Codes::SEVERITY_BLOCKING, Composition_Validation_Codes::get_severity( Composition_Validation_Codes::SECTION_MISSING ) );
	}

	public function test_validation_codes_deprecated_has_replacement_is_warning(): void {
		$this->assertFalse( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT ) );
		$this->assertSame( Composition_Validation_Codes::SEVERITY_WARNING, Composition_Validation_Codes::get_severity( Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT ) );
	}

	public function test_scenario_matrix_missing_section(): void {
		$expected_result = Composition_Validation_Result::VALIDATION_FAILED;
		$blocking_codes  = array( Composition_Validation_Codes::SECTION_MISSING );
		$this->assertContains( Composition_Validation_Codes::SECTION_MISSING, Composition_Validation_Codes::get_blocking_codes() );
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::SECTION_MISSING ) );
		$this->assertFalse( Composition_Validation_Result::allows_activation( $expected_result ) );
	}

	public function test_scenario_matrix_deprecated_section_with_replacement(): void {
		$warning_codes = array( Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT );
		$this->assertContains( Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT, Composition_Validation_Codes::get_warning_codes() );
		$this->assertFalse( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT ) );
	}

	public function test_scenario_matrix_incompatible_adjacency(): void {
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::COMPATIBILITY_ADJACENCY ) );
		$this->assertContains( Composition_Validation_Codes::COMPATIBILITY_ADJACENCY, Composition_Validation_Codes::get_blocking_codes() );
	}

	public function test_scenario_matrix_structural_anchor_missing(): void {
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::STRUCTURAL_ANCHOR_MISSING ) );
	}

	public function test_scenario_matrix_one_pager_failure(): void {
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::ONE_PAGER_GENERATION_FAILED ) );
	}

	public function test_scenario_matrix_field_derivation_failure(): void {
		$this->assertTrue( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::FIELD_GROUP_DERIVATION_FAILED ) );
	}

	public function test_scenario_matrix_snapshot_drift_is_warning(): void {
		$this->assertFalse( Composition_Validation_Codes::is_blocking( Composition_Validation_Codes::SNAPSHOT_DRIFT ) );
		$this->assertContains( Composition_Validation_Codes::SNAPSHOT_DRIFT, Composition_Validation_Codes::get_warning_codes() );
	}

	public function test_all_blocking_codes_have_severity_blocking(): void {
		foreach ( Composition_Validation_Codes::get_blocking_codes() as $code ) {
			$this->assertSame( Composition_Validation_Codes::SEVERITY_BLOCKING, Composition_Validation_Codes::get_severity( $code ), "Code {$code} must be blocking" );
		}
	}

	public function test_all_warning_codes_have_severity_warning(): void {
		foreach ( Composition_Validation_Codes::get_warning_codes() as $code ) {
			$this->assertSame( Composition_Validation_Codes::SEVERITY_WARNING, Composition_Validation_Codes::get_severity( $code ), "Code {$code} must be warning" );
		}
	}

	public function test_known_codes_cover_contract_codes(): void {
		$contract_codes = array(
			Composition_Validation_Codes::SECTION_MISSING,
			Composition_Validation_Codes::SECTION_DEPRECATED_NO_REPLACEMENT,
			Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT,
			Composition_Validation_Codes::ORDERING_INVALID,
			Composition_Validation_Codes::COMPATIBILITY_ADJACENCY,
			Composition_Validation_Codes::COMPATIBILITY_DUPLICATE_PURPOSE,
			Composition_Validation_Codes::VARIANT_CONFLICT,
			Composition_Validation_Codes::STRUCTURAL_ANCHOR_MISSING,
			Composition_Validation_Codes::HELPER_GENERATION_FAILED,
			Composition_Validation_Codes::FIELD_GROUP_DERIVATION_FAILED,
			Composition_Validation_Codes::ONE_PAGER_GENERATION_FAILED,
			Composition_Validation_Codes::SNAPSHOT_DRIFT,
			Composition_Validation_Codes::SNAPSHOT_MISSING,
			Composition_Validation_Codes::SOURCE_TEMPLATE_UNAVAILABLE,
			Composition_Validation_Codes::EMPTY_SECTION_LIST,
		);
		foreach ( $contract_codes as $code ) {
			$this->assertTrue( Composition_Validation_Codes::is_known_code( $code ), "Contract code must be known: {$code}" );
		}
	}
}
