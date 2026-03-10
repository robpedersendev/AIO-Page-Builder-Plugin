<?php
/**
 * Unit tests for Profile_Validation_Result (spec §22.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Validation_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Validation_Result.php';

final class Profile_Validation_Result_Test extends TestCase {

	public function test_success_has_valid_true_and_sanitized_payload(): void {
		$payload = array( 'brand_profile' => array() );
		$result  = Profile_Validation_Result::success( $payload );
		$this->assertTrue( $result->valid );
		$this->assertSame( array(), $result->errors );
		$this->assertSame( $payload, $result->sanitized_payload );
	}

	public function test_failure_has_valid_false_and_errors(): void {
		$errors = array( 'Invalid URL' );
		$result = Profile_Validation_Result::failure( $errors );
		$this->assertFalse( $result->valid );
		$this->assertSame( $errors, $result->errors );
		$this->assertNull( $result->sanitized_payload );
	}

	public function test_failure_can_include_sanitized_payload(): void {
		$payload = array( 'business_name' => 'Acme' );
		$result  = Profile_Validation_Result::failure( array( 'URL invalid' ), $payload );
		$this->assertFalse( $result->valid );
		$this->assertSame( $payload, $result->sanitized_payload );
	}
}
