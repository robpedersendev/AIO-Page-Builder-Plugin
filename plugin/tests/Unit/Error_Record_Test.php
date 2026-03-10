<?php
/**
 * Unit tests for Error_Record: creation, validation, user/admin message formatting (spec §45.3–45.5).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Log_Severities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';
require_once $plugin_root . '/src/Support/Logging/Error_Record.php';

/**
 * Record shape, severity/category validation, redaction-safe message output.
 */
final class Error_Record_Test extends TestCase {

	public function test_creates_with_required_fields(): void {
		$record = new Error_Record(
			'id-1',
			Log_Categories::VALIDATION,
			Log_Severities::ERROR,
			'Sanitized message only.'
		);
		$this->assertSame( 'id-1', $record->id );
		$this->assertSame( Log_Categories::VALIDATION, $record->category );
		$this->assertSame( Log_Severities::ERROR, $record->severity );
		$this->assertSame( 'Sanitized message only.', $record->message );
		$this->assertNotSame( '', $record->timestamp );
		$this->assertSame( '', $record->actor_context );
		$this->assertSame( '', $record->remediation_hint );
	}

	public function test_throws_on_invalid_category(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid log category' );
		new Error_Record( 'id', 'invalid_category', Log_Severities::ERROR, 'Msg' );
	}

	public function test_throws_on_invalid_severity(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid log severity' );
		new Error_Record( 'id', Log_Categories::EXECUTION, 'invalid_severity', 'Msg' );
	}

	public function test_get_user_facing_message_returns_sanitized_message_only(): void {
		$msg = 'Operation failed. Please check settings.';
		$record = new Error_Record( 'r1', Log_Categories::EXECUTION, Log_Severities::ERROR, $msg );
		$this->assertSame( $msg, $record->get_user_facing_message() );
	}

	public function test_get_admin_facing_detail_includes_category_and_remediation(): void {
		$record = new Error_Record(
			'r1',
			Log_Categories::PROVIDER,
			Log_Severities::WARNING,
			'Provider rate limit.',
			'',
			'',
			'',
			'Retry after 60 seconds.'
		);
		$detail = $record->get_admin_facing_detail();
		$this->assertStringContainsString( 'provider', $detail );
		$this->assertStringContainsString( 'Provider rate limit.', $detail );
		$this->assertStringContainsString( 'Retry after 60 seconds.', $detail );
	}

	public function test_to_array_has_required_keys(): void {
		$record = new Error_Record( 'id-2', Log_Categories::SECURITY, Log_Severities::CRITICAL, 'Access denied.' );
		$arr = $record->to_array();
		$this->assertArrayHasKey( 'id', $arr );
		$this->assertArrayHasKey( 'category', $arr );
		$this->assertArrayHasKey( 'severity', $arr );
		$this->assertArrayHasKey( 'timestamp', $arr );
		$this->assertArrayHasKey( 'message', $arr );
		$this->assertSame( 'Access denied.', $arr['message'] );
	}

	/** Redaction-safe: user-facing output does not append or inject data beyond the sanitized message. */
	public function test_user_facing_message_contains_only_sanitized_message(): void {
		$safe = 'Validation failed for field X.';
		$record = new Error_Record( 'x', Log_Categories::VALIDATION, Log_Severities::ERROR, $safe, '', 'admin', 'plan-1', 'Fix field X.' );
		$user_msg = $record->get_user_facing_message();
		$this->assertSame( $safe, $user_msg );
		$this->assertStringNotContainsString( 'plan-1', $user_msg );
		$this->assertStringNotContainsString( 'admin', $user_msg );
	}
}
