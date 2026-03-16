<?php
/**
 * Unit tests for Industry_Section_Override_Service (Prompt 367). record_override, get_override, list_overrides.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Section_Override_Service.php';

final class Industry_Section_Override_Service_Test extends TestCase {

	private function clear_option(): void {
		if ( isset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_SECTION_OVERRIDES ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::INDUSTRY_SECTION_OVERRIDES ] );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_option();
	}

	protected function tearDown(): void {
		$this->clear_option();
		parent::tearDown();
	}

	public function test_record_override_saves_and_get_override_returns_it(): void {
		$service = new Industry_Section_Override_Service();
		$ok = $service->record_override( 'hero_01', Industry_Override_Schema::STATE_ACCEPTED, 'Client chose this.' );
		$this->assertTrue( $ok );
		$override = $service->get_override( 'hero_01' );
		$this->assertIsArray( $override );
		$this->assertSame( Industry_Override_Schema::TARGET_TYPE_SECTION, $override[ Industry_Override_Schema::FIELD_TARGET_TYPE ] ?? '' );
		$this->assertSame( 'hero_01', $override[ Industry_Override_Schema::FIELD_TARGET_KEY ] ?? '' );
		$this->assertSame( Industry_Override_Schema::STATE_ACCEPTED, $override[ Industry_Override_Schema::FIELD_STATE ] ?? '' );
		$this->assertSame( 'Client chose this.', $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
	}

	public function test_get_override_returns_null_for_unknown_key(): void {
		$service = new Industry_Section_Override_Service();
		$this->assertNull( $service->get_override( 'nonexistent' ) );
	}

	public function test_list_overrides_returns_all_recorded(): void {
		$service = new Industry_Section_Override_Service();
		$service->record_override( 's1', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$service->record_override( 's2', Industry_Override_Schema::STATE_REJECTED, 'No.' );
		$list = $service->list_overrides();
		$this->assertCount( 2, $list );
		$this->assertArrayHasKey( 's1', $list );
		$this->assertArrayHasKey( 's2', $list );
	}

	public function test_record_override_returns_false_for_empty_section_key(): void {
		$service = new Industry_Section_Override_Service();
		$this->assertFalse( $service->record_override( '', Industry_Override_Schema::STATE_ACCEPTED, '' ) );
		$this->assertFalse( $service->record_override( '   ', Industry_Override_Schema::STATE_ACCEPTED, '' ) );
	}

	public function test_record_override_sanitizes_reason(): void {
		$service = new Industry_Section_Override_Service();
		$service->record_override( 'hero_02', Industry_Override_Schema::STATE_ACCEPTED, '  <script>x</script> ok  ' );
		$override = $service->get_override( 'hero_02' );
		$this->assertNotNull( $override );
		$reason = (string) ( $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
		$this->assertStringNotContainsString( '<', $reason );
		$this->assertStringNotContainsString( 'script', $reason );
		$this->assertSame( 'x ok', $reason );
	}

	public function test_record_override_overwrites_existing(): void {
		$service = new Industry_Section_Override_Service();
		$service->record_override( 'same', Industry_Override_Schema::STATE_ACCEPTED, 'First' );
		$service->record_override( 'same', Industry_Override_Schema::STATE_REJECTED, 'Second' );
		$override = $service->get_override( 'same' );
		$this->assertSame( Industry_Override_Schema::STATE_REJECTED, $override[ Industry_Override_Schema::FIELD_STATE ] ?? '' );
		$this->assertSame( 'Second', $override[ Industry_Override_Schema::FIELD_REASON ] ?? '' );
	}

	public function test_remove_override_removes_and_returns_true(): void {
		$service = new Industry_Section_Override_Service();
		$service->record_override( 'to_remove', Industry_Override_Schema::STATE_ACCEPTED, '' );
		$this->assertNotNull( $service->get_override( 'to_remove' ) );
		$ok = $service->remove_override( 'to_remove' );
		$this->assertTrue( $ok );
		$this->assertNull( $service->get_override( 'to_remove' ) );
	}

	public function test_remove_override_returns_true_when_key_absent(): void {
		$service = new Industry_Section_Override_Service();
		$this->assertTrue( $service->remove_override( 'nonexistent' ) );
	}
}
