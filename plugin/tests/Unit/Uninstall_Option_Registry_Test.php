<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Option_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

final class Uninstall_Option_Registry_Test extends TestCase {

	public function test_removable_declared_option_keys_covers_full_option_names_inventory(): void {
		$registry = Uninstall_Option_Registry::removable_declared_option_keys();
		$declared = Option_Names::declared_option_keys();
		$this->assertSame( $declared, $registry );
		$this->assertGreaterThan( count( Option_Names::all() ), count( $registry ) );
		$this->assertContains( Option_Names::PB_AI_PROVIDERS, $registry );
	}

	public function test_industry_bundle_prerequisite_keys_documented(): void {
		$keys = Uninstall_Option_Registry::industry_bundle_keys_removed_in_prerequisite_step();
		$this->assertContains( Option_Names::PB_INDUSTRY_BUNDLE_REGISTRY, $keys );
		$this->assertContains( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE, $keys );
	}

	public function test_dynamic_prefixes_cover_operational_option_patterns(): void {
		$prefixes = Uninstall_Option_Registry::removable_dynamic_option_prefixes();
		$this->assertContains( 'aio_pb_monthly_spend_', $prefixes );
		$this->assertContains( 'aio_pb_spend_cap_', $prefixes );
		$this->assertContains( 'aio_page_builder_crawl_session_', $prefixes );
		$this->assertContains( 'aio_page_builder_crawl_lock_', $prefixes );
		$this->assertSame( count( $prefixes ), count( array_unique( $prefixes ) ) );
	}
}
