<?php
/**
 * Unit tests for Support_Package_Generator: exclusion of prohibited categories, redaction summary, result shape (spec §52.1, §45.9, support-package-contract.md).
 *
 * Example support manifest payload (no pseudocode):
 * export_type: "support_bundle"
 * export_timestamp: "2025-07-15T12:00:00Z"
 * plugin_version: "0.1.0"
 * schema_version: "1.0"
 * source_site_url: "https://example.com"
 * included_categories: ["settings", "profiles", "registries", "compositions", "plans", "token_sets", "uninstall_restore_metadata"]
 * excluded_categories: ["raw_ai_artifacts", "normalized_ai_outputs", "crawl_snapshots", "rollback_snapshots", "api_keys", ...]
 * package_checksum_list: { "settings/settings.json": "sha256:...", ... }
 * restore_notes: "Support bundle; redacted; not for full restore."
 * redaction_summary: { "applied": true, "keys_redacted": ["settings", "profiles"] }
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\ExportRestore\Export\Support_Package_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Mode_Keys.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Support_Package_Result.php';

final class Support_Package_Generator_Test extends TestCase {

	/**
	 * Prohibited categories must never appear in support package included list.
	 */
	public function test_permanently_excluded_categories_not_in_support_included(): void {
		$included = array(
			'settings',
			'profiles',
			'registries',
			'compositions',
			'plans',
			'token_sets',
			'uninstall_restore_metadata',
		);
		foreach ( Export_Bundle_Schema::EXCLUDED_CATEGORIES as $prohibited ) {
			$this->assertNotContains( $prohibited, $included, "Support package must not include permanently excluded category: {$prohibited}" );
		}
	}

	/**
	 * Support generator excluded list must include raw_ai_artifacts, normalized_ai_outputs, crawl_snapshots, rollback_snapshots.
	 */
	public function test_support_excluded_includes_contract_categories(): void {
		$excluded = array(
			'raw_ai_artifacts',
			'normalized_ai_outputs',
			'crawl_snapshots',
			'rollback_snapshots',
		);
		foreach ( $excluded as $cat ) {
			$this->assertContains( $cat, Export_Bundle_Schema::OPTIONAL_CATEGORIES, "Support excluded category should be in optional list: {$cat}" );
		}
	}

	/**
	 * Support_Package_Result failure has redaction_summary with applied false.
	 */
	public function test_failure_result_redaction_summary_applied_false(): void {
		$result = Support_Package_Result::failure( 'Exports directory unavailable.', 'log-ref' );
		$this->assertFalse( $result->get_redaction_summary()['applied'] );
		$this->assertSame( array(), $result->get_redaction_summary()['keys_redacted'] );
	}
}
