<?php
/**
 * Unit tests for Import/Export ZIP pre-move size limit (import-export-zip-size-limit-decision.md).
 *
 * Asserts constant value, size-allowed logic, and error code.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/tests/bootstrap.php';
require_once $plugin_root . '/src/Admin/Screens/ImportExport/Import_Export_Screen.php';

final class Import_Export_Zip_Size_Limit_Test extends TestCase {

	public function test_max_zip_upload_bytes_is_50_mb(): void {
		$this->assertSame( 52_428_800, Import_Export_Screen::MAX_ZIP_UPLOAD_BYTES );
	}

	public function test_oversized_file_rejected(): void {
		$over = Import_Export_Screen::MAX_ZIP_UPLOAD_BYTES + 1;
		$this->assertFalse( Import_Export_Screen::is_zip_upload_size_allowed( $over ) );
	}

	public function test_exactly_max_size_allowed(): void {
		$this->assertTrue( Import_Export_Screen::is_zip_upload_size_allowed( Import_Export_Screen::MAX_ZIP_UPLOAD_BYTES ) );
	}

	public function test_under_limit_allowed(): void {
		$this->assertTrue( Import_Export_Screen::is_zip_upload_size_allowed( 0 ) );
		$this->assertTrue( Import_Export_Screen::is_zip_upload_size_allowed( 1024 ) );
	}

	public function test_negative_size_rejected(): void {
		$this->assertFalse( Import_Export_Screen::is_zip_upload_size_allowed( -1 ) );
	}

	public function test_error_code_constant(): void {
		$this->assertSame( 'file_too_large', Import_Export_Screen::ERROR_CODE_FILE_TOO_LARGE );
	}
}
