<?php
/**
 * Unit tests for Style_Validation_Result (Prompt 252): valid flag, bounded errors, sanitized payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Style_Validation_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Validation_Result.php';

final class Style_Validation_Result_Test extends TestCase {

	public function test_valid_result_has_sanitized_payload(): void {
		$sanitized = array( 'color' => array( 'primary' => '#0a0a0a' ) );
		$result    = new Style_Validation_Result( true, array(), $sanitized );
		$this->assertTrue( $result->is_valid() );
		$this->assertSame( array(), $result->get_errors() );
		$this->assertSame( $sanitized, $result->get_sanitized() );
	}

	public function test_invalid_result_has_empty_sanitized(): void {
		$result = new Style_Validation_Result( false, array( 'Invalid token group' ), array( 'color' => array() ) );
		$this->assertFalse( $result->is_valid() );
		$this->assertSame( array( 'Invalid token group' ), $result->get_errors() );
		$this->assertSame( array(), $result->get_sanitized() );
	}

	public function test_errors_are_bounded_in_length(): void {
		$long   = str_repeat( 'x', 400 );
		$result = new Style_Validation_Result( false, array( $long ) );
		$errors = $result->get_errors();
		$this->assertCount( 1, $errors );
		$this->assertLessThanOrEqual( Style_Validation_Result::MAX_ERROR_MESSAGE_LENGTH, strlen( $errors[0] ) );
	}

	public function test_errors_are_bounded_in_count(): void {
		$errors = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$errors[] = "Error $i";
		}
		$result = new Style_Validation_Result( false, $errors );
		$this->assertCount( Style_Validation_Result::MAX_ERRORS, $result->get_errors() );
	}

	public function test_deterministic_structure_valid(): void {
		$payload = array( 'typography' => array( 'heading' => 'Georgia, serif' ) );
		$result  = new Style_Validation_Result( true, array(), $payload );
		$this->assertIsBool( $result->is_valid() );
		$this->assertIsArray( $result->get_errors() );
		$this->assertIsArray( $result->get_sanitized() );
		$this->assertSame( $payload, $result->get_sanitized() );
	}
}
