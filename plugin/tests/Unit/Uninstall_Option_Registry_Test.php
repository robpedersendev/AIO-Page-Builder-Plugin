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
}
