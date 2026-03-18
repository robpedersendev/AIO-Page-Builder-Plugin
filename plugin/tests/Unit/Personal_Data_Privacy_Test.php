<?php
/**
 * Unit tests for WordPress privacy exporter/eraser and export/erase scenarios (SPR-004).
 * Registration is in Plugin::run() via wp_privacy_personal_data_exporters/erasers;
 * these tests assert the exporter/eraser callback contract and one export/erase scenario each.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Exporter;
use AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Eraser;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Privacy/Personal_Data_Exporter.php';
require_once $plugin_root . '/src/Infrastructure/Privacy/Personal_Data_Eraser.php';

final class Personal_Data_Privacy_Test extends TestCase {

	public function test_export_returns_data_and_done_when_user_unknown(): void {
		$GLOBALS['_aio_get_user_by_return'] = null;
		$result = Personal_Data_Exporter::export( 'nobody@example.com', 1 );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertIsArray( $result['data'] );
		$this->assertTrue( $result['done'] );
		$this->assertSame( array(), $result['data'] );
	}

	public function test_erase_returns_required_keys_when_user_unknown(): void {
		$GLOBALS['_aio_get_user_by_return'] = null;
		$result = Personal_Data_Eraser::erase( 'nobody@example.com', 1 );
		$this->assertArrayHasKey( 'items_removed', $result );
		$this->assertArrayHasKey( 'items_retained', $result );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertTrue( $result['done'] );
		$this->assertFalse( $result['items_removed'] );
		$this->assertIsArray( $result['messages'] );
	}

	/** Asserts exporter fulfills WP privacy exporter callback contract (data + done). */
	public function test_exporter_fulfills_wp_privacy_exporter_contract(): void {
		$GLOBALS['_aio_get_user_by_return'] = null;
		$result = Personal_Data_Exporter::export( 'contract@example.com', 1 );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertIsArray( $result['data'] );
		$this->assertIsBool( $result['done'] );
	}

	/** Asserts eraser fulfills WP privacy eraser callback contract (items_removed, items_retained, messages, done). */
	public function test_eraser_fulfills_wp_privacy_eraser_contract(): void {
		$GLOBALS['_aio_get_user_by_return'] = null;
		$result = Personal_Data_Eraser::erase( 'contract@example.com', 1 );
		$this->assertArrayHasKey( 'items_removed', $result );
		$this->assertArrayHasKey( 'items_retained', $result );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertIsBool( $result['done'] );
	}
}
