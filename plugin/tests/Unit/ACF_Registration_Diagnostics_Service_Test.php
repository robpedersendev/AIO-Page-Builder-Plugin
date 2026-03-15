<?php
/**
 * Unit tests for ACF_Registration_Diagnostics_Service (Prompt 291): mode recording, no sensitive data.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Registration\ACF_Registration_Diagnostics_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Registration_Diagnostics_Service.php';

final class ACF_Registration_Diagnostics_Service_Test extends TestCase {

	private ACF_Registration_Diagnostics_Service $diagnostics;

	protected function setUp(): void {
		parent::setUp();
		// Record in tests (production uses is_admin() by default).
		$this->diagnostics = new ACF_Registration_Diagnostics_Service( function (): bool {
			return true;
		} );
	}

	public function test_get_last_registration_initially_null(): void {
		$this->assertNull( $this->diagnostics->get_last_registration() );
	}

	public function test_record_registration_stores_mode_and_counts(): void {
		$this->diagnostics->record_registration(
			ACF_Registration_Diagnostics_Service::MODE_EXISTING_PAGE,
			5,
			true,
			false
		);
		$last = $this->diagnostics->get_last_registration();
		$this->assertIsArray( $last );
		$this->assertSame( 'existing_page', $last['mode'] );
		$this->assertSame( 5, $last['section_key_count'] );
		$this->assertTrue( $last['cache_used'] );
		$this->assertFalse( $last['full_registration_invoked'] );
	}

	public function test_payload_contains_only_allowed_keys(): void {
		$this->diagnostics->record_registration(
			ACF_Registration_Diagnostics_Service::MODE_NEW_PAGE,
			3,
			false,
			false
		);
		$last = $this->diagnostics->get_last_registration();
		$allowed = array( 'mode', 'section_key_count', 'cache_used', 'full_registration_invoked' );
		$this->assertSame( $allowed, array_keys( $last ) );
	}

	public function test_request_cache_used_default_false(): void {
		$this->assertFalse( $this->diagnostics->get_request_cache_used() );
	}

	public function test_set_and_get_request_cache_used(): void {
		$this->diagnostics->set_request_cache_used( true );
		$this->assertTrue( $this->diagnostics->get_request_cache_used() );
	}
}
