<?php
/**
 * Unit tests for Import_Export_State_Builder: state shape, export mode options, export history, validation/restore state (spec §49.4, §52, §59.13).
 *
 * Example export-screen state payload (no pseudocode):
 * export_mode_options: [ { value: "full_operational_backup", label: "Full operational backup" }, ... ]
 * export_history_rows: [ { filename: "aio-export-full_operational_backup-20250715-120000-site.zip", size_bytes: 1024, modified_at: "2025-07-15 12:00:00" } ]
 * import_validation_summary: null
 * restore_conflict_rows: []
 * restore_action_state: { can_restore: false, resolution_modes: [ { value: "overwrite", label: "Overwrite..." }, ... ], message: "", last_restore_payload: null }
 * can_export: true
 * can_import: true
 * privacy_screen_url: "http://example.com/wp-admin/admin.php?page=aio-page-builder-privacy-reporting"
 *
 * Example restore-validation screen payload (after validate, with conflicts):
 * import_validation_summary: { validation_passed: true, blocking_failures: [], conflicts: [ { category: "compositions", key: "my-comp", message: "Exists" } ], warnings: [], checksum_verified: true }
 * restore_conflict_rows: [ { category: "compositions", key: "my-comp", message: "Exists" } ]
 * restore_action_state: { can_restore: true, resolution_modes: [...], message: "Validation passed. Resolve conflicts below...", last_restore_payload: null }
 *
 * Manual verification checklist (Import / Export screen):
 * - Export mode selection: dropdown lists all six approved modes; Create export submits and redirects with success or error.
 * - Export history: table shows filename, size, modified date; Download link returns ZIP (nonce + aio_export_data).
 * - Import validation: upload ZIP, Validate shows validation result (pass/fail, failures, warnings); no path exposed.
 * - Conflict summary: when validation passes with conflicts, table shows category, key, message.
 * - Restore confirmation: resolution dropdown and Run restore only when can_restore; restore runs after validation.
 * - Permission gating: without aio_export_data no export section; without aio_import_data no restore section.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\ExportRestore\UI\Import_Export_State_Builder;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Mode_Keys.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Restore_Scope_Keys.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Import/Conflict_Resolution_Service.php';
require_once $plugin_root . '/src/Infrastructure/Files/Plugin_Path_Manager.php';
require_once $plugin_root . '/src/Domain/ExportRestore/UI/Import_Export_State_Builder.php';

final class Import_Export_State_Builder_Test extends TestCase {

	private function path_manager(): Plugin_Path_Manager {
		return new Plugin_Path_Manager();
	}

	public function test_build_returns_expected_keys(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded; current_user_can required.' );
		}
		$builder = new Import_Export_State_Builder( $this->path_manager() );
		$state   = $builder->build( null, null );
		$this->assertArrayHasKey( 'export_mode_options', $state );
		$this->assertArrayHasKey( 'export_history_rows', $state );
		$this->assertArrayHasKey( 'import_validation_summary', $state );
		$this->assertArrayHasKey( 'import_package_preview', $state );
		$this->assertArrayHasKey( 'restore_conflict_rows', $state );
		$this->assertArrayHasKey( 'restore_action_state', $state );
		$this->assertArrayHasKey( 'restore_scope_options', $state );
		$this->assertArrayHasKey( 'can_export', $state );
		$this->assertArrayHasKey( 'can_import', $state );
		$this->assertArrayHasKey( 'privacy_screen_url', $state );
	}

	public function test_export_mode_options_contains_all_approved_modes(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$builder = new Import_Export_State_Builder( $this->path_manager() );
		$state   = $builder->build( null, null );
		$opts    = $state['export_mode_options'];
		$this->assertIsArray( $opts );
		$this->assertCount( count( Export_Mode_Keys::all() ), $opts );
		foreach ( $opts as $opt ) {
			$this->assertArrayHasKey( 'value', $opt );
			$this->assertArrayHasKey( 'label', $opt );
			$this->assertNotEmpty( $opt['value'] );
			$this->assertNotEmpty( $opt['label'] );
		}
		$values = array_column( $opts, 'value' );
		$this->assertContains( Export_Mode_Keys::FULL_OPERATIONAL_BACKUP, $values );
		$this->assertContains( Export_Mode_Keys::PRE_UNINSTALL_BACKUP, $values );
	}

	public function test_export_history_rows_shape_when_empty(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$builder = new Import_Export_State_Builder( $this->path_manager() );
		$state   = $builder->build( null, null );
		$this->assertIsArray( $state['export_history_rows'] );
		$this->assertEmpty( $state['export_history_rows'] );
	}

	public function test_restore_action_state_has_resolution_modes_and_can_restore(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$builder = new Import_Export_State_Builder( $this->path_manager() );
		$state   = $builder->build( null, null );
		$ras     = $state['restore_action_state'];
		$this->assertArrayHasKey( 'can_restore', $ras );
		$this->assertArrayHasKey( 'resolution_modes', $ras );
		$this->assertArrayHasKey( 'message', $ras );
		$this->assertArrayHasKey( 'last_restore_payload', $ras );
		$this->assertIsArray( $ras['resolution_modes'] );
		$this->assertGreaterThanOrEqual( 4, count( $ras['resolution_modes'] ) );
	}

	public function test_with_validation_payload_sets_import_summary_and_conflict_rows(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$validation_payload = array(
			'validation_passed' => true,
			'blocking_failures' => array(),
			'conflicts'         => array(
				array(
					'category' => 'compositions',
					'key'      => 'my-comp',
					'message'  => 'Already exists.',
				),
			),
			'warnings'          => array(),
			'checksum_verified' => true,
		);
		$builder            = new Import_Export_State_Builder( $this->path_manager() );
		$state              = $builder->build( $validation_payload, null );
		$this->assertNotNull( $state['import_validation_summary'] );
		$this->assertTrue( $state['import_validation_summary']['validation_passed'] );
		$this->assertCount( 1, $state['restore_conflict_rows'] );
		$this->assertSame( 'compositions', $state['restore_conflict_rows'][0]['category'] );
		$this->assertArrayHasKey( 'can_restore', $state['restore_action_state'] );
	}

	public function test_privacy_screen_url_contains_slug(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$builder = new Import_Export_State_Builder( $this->path_manager() );
		$state   = $builder->build( null, null );
		$this->assertStringContainsString( 'aio-page-builder-privacy-reporting', $state['privacy_screen_url'] );
	}

	public function test_with_manifest_builds_import_package_preview_and_scope_options(): void {
		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}
		$manifest = array(
			'export_type'         => 'full_operational_backup',
			'export_timestamp'    => '2026-03-01T12:00:00Z',
			'plugin_version'      => '1.2.3',
			'schema_version'      => '1',
			'source_site_url'     => 'https://example.com',
			'included_categories' => array( 'settings', 'styling', 'profiles', 'registries' ),
			'excluded_categories' => array( 'secrets' ),
		);
		$builder  = new Import_Export_State_Builder( $this->path_manager() );
		$state    = $builder->build( null, null, $manifest );
		$this->assertIsArray( $state['import_package_preview'] );
		$this->assertSame( 'full_operational_backup', $state['import_package_preview']['export_type'] );
		$this->assertIsArray( $state['restore_scope_options'] );
		$this->assertNotEmpty( $state['restore_scope_options'] );
		$values = array_column( $state['restore_scope_options'], 'value' );
		$this->assertContains( \AIOPageBuilder\Domain\ExportRestore\Contracts\Restore_Scope_Keys::SETTINGS_PROFILE_ONLY, $values );
		$this->assertContains( \AIOPageBuilder\Domain\ExportRestore\Contracts\Restore_Scope_Keys::FULL_AIO_BACKUP, $values );
	}
}
