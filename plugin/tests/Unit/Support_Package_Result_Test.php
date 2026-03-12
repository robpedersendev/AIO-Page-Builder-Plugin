<?php
/**
 * Unit tests for Support_Package_Result: payload shape, success/failure, redaction summary (spec §52.1, support-package-contract.md).
 *
 * Example support-package result payload (no pseudocode):
 * success: true
 * message: "Support package generated successfully."
 * package_filename: "aio-export-support_bundle-20250715-120000-site.zip"
 * support_package_type: "support_bundle"
 * included_support_categories: ["settings", "profiles", "registries", "compositions", "plans", "token_sets", "uninstall_restore_metadata"]
 * excluded_categories: ["raw_ai_artifacts", "normalized_ai_outputs", "crawl_snapshots", "rollback_snapshots", ...]
 * redaction_summary: { "applied": true, "keys_redacted": ["settings", "profiles"] }
 * package_reference: "aio-export-support_bundle-20250715-120000-site.zip"
 * generation_log_reference: "support-pkg-2025-07-15T12:00:00Z"
 * checksum_count: 12
 * package_size_bytes: 4096
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Export\Support_Package_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Support_Package_Result.php';

final class Support_Package_Result_Test extends TestCase {

	public function test_success_result_has_expected_payload_keys(): void {
		$r = Support_Package_Result::success(
			'/path/to/support.zip',
			'aio-export-support_bundle-20250715-120000-site.zip',
			array( 'settings', 'profiles', 'registries' ),
			array( 'raw_ai_artifacts' ),
			array( 'applied' => true, 'keys_redacted' => array( 'settings' ) ),
			5,
			1024,
			'support-pkg-1'
		);
		$this->assertTrue( $r->is_success() );
		$p = $r->to_payload();
		$this->assertArrayHasKey( 'success', $p );
		$this->assertArrayHasKey( 'message', $p );
		$this->assertArrayHasKey( 'package_filename', $p );
		$this->assertArrayHasKey( 'support_package_type', $p );
		$this->assertArrayHasKey( 'included_support_categories', $p );
		$this->assertArrayHasKey( 'excluded_categories', $p );
		$this->assertArrayHasKey( 'redaction_summary', $p );
		$this->assertArrayHasKey( 'package_reference', $p );
		$this->assertArrayHasKey( 'generation_log_reference', $p );
		$this->assertArrayHasKey( 'checksum_count', $p );
		$this->assertArrayHasKey( 'package_size_bytes', $p );
		$this->assertSame( Support_Package_Result::SUPPORT_PACKAGE_TYPE, $p['support_package_type'] );
	}

	public function test_redaction_summary_shape(): void {
		$r = Support_Package_Result::success(
			'/path',
			'file.zip',
			array(),
			array(),
			array( 'applied' => true, 'keys_redacted' => array( 'settings', 'profiles' ) ),
			0,
			0
		);
		$sum = $r->get_redaction_summary();
		$this->assertArrayHasKey( 'applied', $sum );
		$this->assertArrayHasKey( 'keys_redacted', $sum );
		$this->assertTrue( $sum['applied'] );
		$this->assertCount( 2, $sum['keys_redacted'] );
	}

	public function test_failure_result_has_no_package_data(): void {
		$r = Support_Package_Result::failure( 'Exports directory unavailable.', 'log-ref' );
		$this->assertFalse( $r->is_success() );
		$this->assertSame( '', $r->get_package_path() );
		$this->assertSame( '', $r->get_package_filename() );
		$this->assertSame( array(), $r->get_included_support_categories() );
		$this->assertFalse( $r->get_redaction_summary()['applied'] );
	}

	public function test_to_payload_contains_no_secrets(): void {
		$r = Support_Package_Result::success(
			'/var/secret/path/support.zip',
			'file.zip',
			array( 'settings' ),
			array(),
			array( 'applied' => true, 'keys_redacted' => array() ),
			1,
			100
		);
		$p = $r->to_payload();
		$this->assertArrayNotHasKey( 'package_path', $p );
	}
}
