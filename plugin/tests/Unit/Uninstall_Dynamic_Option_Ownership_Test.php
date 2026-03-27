<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Dynamic_Option_Ownership;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Option_Registry;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Dynamic_Option_Ownership.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Option_Registry.php';

final class Uninstall_Dynamic_Option_Ownership_Test extends TestCase {

	/**
	 * @return list<array{0: string, 1: string, 2: bool}>
	 */
	private function monthly_spend_cases(): array {
		return array(
			array( 'aio_pb_monthly_spend_openai_2025_03', 'aio_pb_monthly_spend_', true ),
			array( 'aio_pb_monthly_spend_openai_2025_3', 'aio_pb_monthly_spend_', false ),
			array( 'aio_pb_monthly_spend_openai_extra_without_month', 'aio_pb_monthly_spend_', false ),
			array( 'aio_pb_monthly_spend_foreign_plugin_reserved', 'aio_pb_monthly_spend_', false ),
		);
	}

	public function test_monthly_spend_pattern_accepts_only_plugin_shaped_keys(): void {
		foreach ( $this->monthly_spend_cases() as $case ) {
			$this->assertSame(
				$case[2],
				Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key( $case[0], $case[1] ),
				$case[0]
			);
		}
	}

	public function test_spend_cap_accepts_sanitized_key_suffix_only(): void {
		$this->assertTrue(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key( 'aio_pb_spend_cap_openai', 'aio_pb_spend_cap_' )
		);
		$this->assertFalse(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key( 'aio_pb_spend_cap_', 'aio_pb_spend_cap_' )
		);
	}

	public function test_crawl_session_and_lock_patterns(): void {
		$this->assertTrue(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key(
				'aio_page_builder_crawl_session_abc123xyz',
				'aio_page_builder_crawl_session_'
			)
		);
		$this->assertFalse(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key(
				'aio_page_builder_crawl_session_' . str_repeat( 'x', 70 ),
				'aio_page_builder_crawl_session_'
			)
		);
		$this->assertTrue(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key(
				'aio_page_builder_crawl_lock_' . md5( 'example.test' ),
				'aio_page_builder_crawl_lock_'
			)
		);
		$this->assertFalse(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key(
				'aio_page_builder_crawl_lock_NOTHEX',
				'aio_page_builder_crawl_lock_'
			)
		);
	}

	public function test_unknown_prefix_returns_false(): void {
		$this->assertFalse(
			Uninstall_Dynamic_Option_Ownership::matches_plugin_owned_dynamic_key( 'aio_pb_monthly_spend_x_2025_03', 'aio_pb_unknown_' )
		);
	}

	public function test_registry_prefixes_are_all_handled_in_ownership_switch(): void {
		foreach ( Uninstall_Option_Registry::removable_dynamic_option_prefixes() as $prefix ) {
			$this->assertIsString( $prefix );
			$this->assertNotSame( '', $prefix );
		}
	}
}
