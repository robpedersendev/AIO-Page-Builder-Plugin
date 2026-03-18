<?php
/**
 * Unit tests for Privacy_Settings_State_Builder (spec §49.12, §46.11).
 *
 * Example screen-state payload (no pseudocode):
 * reporting_disclosure: [ { heading: "Private distribution reporting", content: "This plugin is privately distributed..." }, { heading: "What may be sent", content: "Included: site identifier..." } ]
 * retention_state: { reporting_log_summary: "Reporting log: N entry(ies)...", retention_note: "Log entries are retained..." }
 * uninstall_export_state: { choices: [ { value, label, description } x4 ], prefs_summary: "...", built_pages_message: "..." }
 * environment_summary: { php_version: "...", wp_version: "..." }
 * version_summary: { plugin_version: "0.1.0" }
 * report_destination_summary: { transport_type: "Email", description: "Install notification..." }  (no raw email)
 * privacy_helper_text: "The AIO Page Builder plugin stores..."
 * diagnostics_verbosity_allowed: false
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\UI\Privacy_Settings_State_Builder;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Result.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Privacy_Settings_State_Builder.php';

final class Privacy_Settings_State_Builder_Test extends TestCase {

	public function test_build_returns_expected_keys(): void {
		$state = $this->build_state();
		$this->assertArrayHasKey( 'reporting_disclosure', $state );
		$this->assertArrayHasKey( 'retention_state', $state );
		$this->assertArrayHasKey( 'uninstall_export_state', $state );
		$this->assertArrayHasKey( 'environment_summary', $state );
		$this->assertArrayHasKey( 'version_summary', $state );
		$this->assertArrayHasKey( 'report_destination_summary', $state );
		$this->assertArrayHasKey( 'privacy_helper_text', $state );
		$this->assertArrayHasKey( 'diagnostics_verbosity_allowed', $state );
	}

	public function test_reporting_disclosure_has_mandatory_blocks(): void {
		$state = $this->build_state();
		$this->assertNotEmpty( $state['reporting_disclosure'] );
		$headings = array_column( $state['reporting_disclosure'], 'heading' );
		$this->assertNotEmpty( $headings );
		foreach ( $state['reporting_disclosure'] as $block ) {
			$this->assertArrayHasKey( 'heading', $block );
			$this->assertArrayHasKey( 'content', $block );
		}
	}

	public function test_report_destination_contains_no_raw_email(): void {
		$state = $this->build_state();
		$dest  = $state['report_destination_summary'];
		$this->assertArrayHasKey( 'transport_type', $dest );
		$this->assertArrayHasKey( 'description', $dest );
		$text = $dest['transport_type'] . ' ' . $dest['description'];
		$this->assertStringNotContainsString( '@', $text, 'Report destination must not expose email address' );
	}

	public function test_uninstall_export_state_has_four_choices(): void {
		$state   = $this->build_state();
		$choices = $state['uninstall_export_state']['choices'];
		$this->assertCount( 4, $choices );
		foreach ( $choices as $c ) {
			$this->assertArrayHasKey( 'value', $c );
			$this->assertArrayHasKey( 'label', $c );
			$this->assertArrayHasKey( 'description', $c );
		}
	}

	public function test_version_summary_has_plugin_version(): void {
		$state = $this->build_state();
		$this->assertArrayHasKey( 'plugin_version', $state['version_summary'] );
		$this->assertNotEmpty( $state['version_summary']['plugin_version'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_state(): array {
		$settings = new Settings_Service();
		$builder  = new Privacy_Settings_State_Builder( $settings );
		return $builder->build();
	}
}
