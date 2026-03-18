<?php
/**
 * Unit tests for Industry_Bundle_Upload_Validator (SPR-001): size, MIME/extension, JSON and bundle structure.
 *
 * validate_upload() enforces size, extension, and MIME before any read; extension/MIME branches require
 * is_uploaded_file() to pass (not true in CLI). read_parse_validate_bundle() enforces max read size, JSON decode, and bundle structure.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Export\Industry_Bundle_Upload_Validator;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/tests/bootstrap.php';
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Bundle_Upload_Validator.php';

final class Industry_Bundle_Upload_Validator_Test extends TestCase {

	public function test_validate_upload_rejects_empty_file_array(): void {
		$result = Industry_Bundle_Upload_Validator::validate_upload( array() );
		$this->assertFalse( $result['ok'] );
		$this->assertSame( Industry_Bundle_Upload_Validator::LOG_REASON_NOT_UPLOADED, $result['log_reason'] );
		$this->assertNotEmpty( $result['user_message'] );
	}

	/** When tmp_name is empty, validator returns not_uploaded before checking error code. */
	public function test_validate_upload_rejects_upload_error(): void {
		$result = Industry_Bundle_Upload_Validator::validate_upload(
			array(
				'tmp_name' => '',
				'name'     => 'bundle.json',
				'size'     => 0,
				'error'    => \UPLOAD_ERR_INI_SIZE,
			)
		);
		$this->assertFalse( $result['ok'] );
		$this->assertSame( Industry_Bundle_Upload_Validator::LOG_REASON_NOT_UPLOADED, $result['log_reason'] );
	}

	public function test_read_parse_validate_bundle_rejects_nonexistent_path(): void {
		$path   = sys_get_temp_dir() . '/aio-nonexistent-' . uniqid( 'bundle', true ) . '.json';
		$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $path );
		$this->assertNull( $result['bundle'] );
		$this->assertSame( 'read_error', $result['log_reason'] );
		$this->assertNotEmpty( $result['user_message'] );
	}

	public function test_read_parse_validate_bundle_rejects_oversized_file(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'aio-bundle-' );
		$this->assertNotFalse( $tmp );
		try {
			$oversize = Industry_Bundle_Upload_Validator::MAX_BYTES + 1;
			$written  = file_put_contents( $tmp, str_repeat( 'x', $oversize ) );
			$this->assertSame( $oversize, $written );
			$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $tmp );
			$this->assertNull( $result['bundle'] );
			$this->assertSame( Industry_Bundle_Upload_Validator::LOG_REASON_TOO_LARGE, $result['log_reason'] );
			$this->assertStringContainsString( 'too large', $result['user_message'] );
		} finally {
			@unlink( $tmp );
		}
	}

	public function test_read_parse_validate_bundle_rejects_invalid_json(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'aio-bundle-' );
		$this->assertNotFalse( $tmp );
		try {
			file_put_contents( $tmp, '{ invalid json }' );
			$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $tmp );
			$this->assertNull( $result['bundle'] );
			$this->assertSame( 'invalid_json', $result['log_reason'] );
			$this->assertNotEmpty( $result['user_message'] );
		} finally {
			@unlink( $tmp );
		}
	}

	public function test_read_parse_validate_bundle_rejects_valid_json_invalid_bundle_structure(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'aio-bundle-' );
		$this->assertNotFalse( $tmp );
		try {
			$invalid_bundle = array( 'foo' => 'bar' );
			file_put_contents( $tmp, json_encode( $invalid_bundle ) );
			$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $tmp );
			$this->assertNull( $result['bundle'] );
			$this->assertSame( 'invalid_bundle_structure', $result['log_reason'] );
			$this->assertStringContainsString( 'Invalid bundle structure', $result['user_message'] );
		} finally {
			@unlink( $tmp );
		}
	}

	public function test_read_parse_validate_bundle_accepts_valid_minimal_bundle(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle( array(), $sources );
		$tmp     = tempnam( sys_get_temp_dir(), 'aio-bundle-' );
		$this->assertNotFalse( $tmp );
		try {
			file_put_contents( $tmp, json_encode( $bundle ) );
			$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $tmp );
			$this->assertNotNull( $result['bundle'] );
			$this->assertSame( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ], $result['bundle'][ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ] );
			$this->assertSame( '', $result['log_reason'] );
			$this->assertSame( '', $result['user_message'] );
		} finally {
			@unlink( $tmp );
		}
	}

	public function test_max_bytes_constant(): void {
		$this->assertSame( 10 * 1024 * 1024, Industry_Bundle_Upload_Validator::MAX_BYTES );
	}

	/** Rejection messages must be admin-safe (no paths or internal details). */
	public function test_rejection_user_messages_are_admin_safe(): void {
		$result = Industry_Bundle_Upload_Validator::validate_upload( array() );
		$this->assertFalse( $result['ok'] );
		$this->assertStringNotContainsString( '/', $result['user_message'] );
		$this->assertStringNotContainsString( 'tmp', $result['user_message'] );
		$path_result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( '/nonexistent/path.json' );
		$this->assertNull( $path_result['bundle'] );
		$this->assertStringNotContainsString( '/nonexistent', $path_result['user_message'] );
	}

	/** Oversized file rejection message includes max size (MB). */
	public function test_oversized_rejection_message_includes_max_mb(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'aio-bundle-' );
		$this->assertNotFalse( $tmp );
		try {
			file_put_contents( $tmp, str_repeat( 'x', Industry_Bundle_Upload_Validator::MAX_BYTES + 1 ) );
			$result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle( $tmp );
			$this->assertNull( $result['bundle'] );
			$this->assertSame( Industry_Bundle_Upload_Validator::LOG_REASON_TOO_LARGE, $result['log_reason'] );
			$this->assertStringContainsString( '10', $result['user_message'] );
		} finally {
			@unlink( $tmp );
		}
	}
}
