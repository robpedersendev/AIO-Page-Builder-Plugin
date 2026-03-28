<?php
/**
 * Unit tests for uninstall export prompt, cleanup orchestration, and built-page preservation (spec §52.11, §53.6, §53.9, Prompt 099).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Export\Export_Token_Set_Reader;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Export_Prompt_Service;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Result;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Serializer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Result.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Cleanup_Service.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Export_Prompt_Service.php';

final class Uninstall_Export_And_Cleanup_Test extends TestCase {

	public function test_cancel_returns_cancelled_result_with_built_pages_preserved(): void {
		$result = Uninstall_Result::cancelled( 'log-1' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( Uninstall_Result::CHOICE_CANCEL, $result->get_export_choice() );
		$this->assertTrue( $result->built_pages_preserved() );
		$this->assertFalse( $result->plugin_data_removed() );
		$this->assertFalse( $result->scheduled_events_removed() );
		$this->assertSame( 'log-1', $result->get_log_reference() );
	}

	public function test_completed_result_has_required_payload_shape(): void {
		$result = Uninstall_Result::completed(
			Uninstall_Result::CHOICE_FULL_BACKUP,
			'/path/to/export.zip',
			Uninstall_Cleanup_Service::SCOPE_FULL,
			true,
			true,
			'uninstall-log-1',
			'Export completed. Plugin data has been removed. Built pages remain.'
		);
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->built_pages_preserved() );
		$this->assertTrue( $result->plugin_data_removed() );
		$this->assertTrue( $result->scheduled_events_removed() );

		$payload = $result->to_payload();
		$this->assertArrayHasKey( 'export_choice', $payload );
		$this->assertArrayHasKey( 'export_result_reference', $payload );
		$this->assertArrayHasKey( 'cleanup_scope', $payload );
		$this->assertArrayHasKey( 'scheduled_events_removed', $payload );
		$this->assertArrayHasKey( 'plugin_data_removed', $payload );
		$this->assertArrayHasKey( 'built_pages_preserved', $payload );
		$this->assertArrayHasKey( 'log_reference', $payload );
		$this->assertSame( Uninstall_Result::CHOICE_FULL_BACKUP, $payload['export_choice'] );
		$this->assertSame( '/path/to/export.zip', $payload['export_result_reference'] );
		$this->assertSame( Uninstall_Cleanup_Service::SCOPE_FULL, $payload['cleanup_scope'] );
		$this->assertTrue( $payload['built_pages_preserved'] );
	}

	/**
	 * Example uninstall result payload for export-and-cleanup path (spec §52.11, Prompt 099).
	 */
	public function test_example_uninstall_result_payload_export_and_cleanup_path(): void {
		$example = array(
			'success'                  => true,
			'message'                  => 'Export completed. Plugin data has been removed. Built pages remain.',
			'export_choice'            => Uninstall_Result::CHOICE_FULL_BACKUP,
			'export_result_reference'  => '/var/www/html/wp-content/uploads/aio-page-builder/exports/aio-export-pre_uninstall_backup-20250715-120000-mysite.zip',
			'cleanup_scope'            => Uninstall_Cleanup_Service::SCOPE_FULL,
			'scheduled_events_removed' => true,
			'plugin_data_removed'      => true,
			'built_pages_preserved'    => true,
			'log_reference'            => 'uninstall_2025-07-15T12:00:00Z',
		);
		$result  = Uninstall_Result::completed(
			$example['export_choice'],
			$example['export_result_reference'],
			$example['cleanup_scope'],
			$example['scheduled_events_removed'],
			$example['plugin_data_removed'],
			$example['log_reference'],
			$example['message']
		);
		$this->assertEquals( $example, $result->to_payload() );
	}

	public function test_four_choice_constants_defined_and_distinct(): void {
		$this->assertSame( 'full_backup', Uninstall_Result::CHOICE_FULL_BACKUP );
		$this->assertSame( 'settings_profile_only', Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY );
		$this->assertSame( 'skip_export', Uninstall_Result::CHOICE_SKIP_EXPORT );
		$this->assertSame( 'cancel', Uninstall_Result::CHOICE_CANCEL );
		$all = array(
			Uninstall_Result::CHOICE_FULL_BACKUP,
			Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY,
			Uninstall_Result::CHOICE_SKIP_EXPORT,
			Uninstall_Result::CHOICE_CANCEL,
		);
		$this->assertCount( 4, array_unique( $all ) );
	}

	public function test_get_choices_returns_four_required_options(): void {
		$cleanup = new Uninstall_Cleanup_Service( null );
		if ( ! class_exists( \AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator::class, false ) ) {
			$this->markTestSkipped( 'Export_Generator requires full plugin bootstrap.' );
		}
		$generator = new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator(
			new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager(),
			new \AIOPageBuilder\Infrastructure\Settings\Settings_Service(),
			new Profile_Store( new Settings_Service(), new Profile_Normalizer() ),
			new Registry_Export_Serializer(
				new Section_Template_Repository(),
				new Page_Template_Repository(),
				new Composition_Repository(),
				new Documentation_Repository(),
				new Version_Snapshot_Repository()
			),
			new Build_Plan_Repository(),
			new Export_Token_Set_Reader( new \wpdb() ),
			new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Manifest_Builder(),
			new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Zip_Packager( new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager() ),
			null
		);
		$service   = new Uninstall_Export_Prompt_Service( $generator, $cleanup );
		$choices   = $service->get_choices();
		$this->assertCount( 4, $choices );
		$values = array_column( $choices, 'value' );
		$this->assertContains( Uninstall_Result::CHOICE_FULL_BACKUP, $values );
		$this->assertContains( Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY, $values );
		$this->assertContains( Uninstall_Result::CHOICE_SKIP_EXPORT, $values );
		$this->assertContains( Uninstall_Result::CHOICE_CANCEL, $values );
		foreach ( $choices as $choice ) {
			$this->assertArrayHasKey( 'label', $choice );
			$this->assertArrayHasKey( 'description', $choice );
		}
	}

	public function test_built_pages_remain_message_is_non_empty(): void {
		$msg = Uninstall_Export_Prompt_Service::built_pages_remain_message();
		$this->assertNotEmpty( $msg );
		$this->assertStringContainsString( 'remain', strtolower( $msg ) );
	}

	public function test_run_uninstall_flow_cancel_returns_cancelled_without_cleanup(): void {
		if ( ! class_exists( \AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator::class, false ) ) {
			$this->markTestSkipped( 'Export_Generator requires full plugin bootstrap.' );
		}
		$cleanup   = new Uninstall_Cleanup_Service( null );
		$generator = new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Generator(
			new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager(),
			new \AIOPageBuilder\Infrastructure\Settings\Settings_Service(),
			new Profile_Store( new Settings_Service(), new Profile_Normalizer() ),
			new Registry_Export_Serializer(
				new Section_Template_Repository(),
				new Page_Template_Repository(),
				new Composition_Repository(),
				new Documentation_Repository(),
				new Version_Snapshot_Repository()
			),
			new Build_Plan_Repository(),
			new Export_Token_Set_Reader( new \wpdb() ),
			new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Manifest_Builder(),
			new \AIOPageBuilder\Domain\ExportRestore\Export\Export_Zip_Packager( new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager() ),
			null
		);
		$service   = new Uninstall_Export_Prompt_Service( $generator, $cleanup );
		$result    = $service->run_uninstall_flow( Uninstall_Result::CHOICE_CANCEL, 'log-cancel' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( Uninstall_Result::CHOICE_CANCEL, $result->get_export_choice() );
		$this->assertTrue( $result->built_pages_preserved() );
		$this->assertFalse( $result->plugin_data_removed() );
	}

	public function test_cleanup_scope_constant(): void {
		$this->assertSame( 'full_plugin_owned', Uninstall_Cleanup_Service::SCOPE_FULL );
	}

	/**
	 * Cleanup return shape includes ACF preservation contract: built_pages_preserved and acf_transients_removed (Prompt 316).
	 * Full cleanup run requires Heartbeat_Scheduler and WP environment; see acf-uninstall-retained-data-matrix.md.
	 */
	public function test_cleanup_return_shape_includes_acf_preservation_keys(): void {
		if ( ! class_exists( \AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler::class, false ) ) {
			$this->markTestSkipped( 'Cleanup requires Heartbeat_Scheduler; verify return shape in integration or see acf-uninstall-retained-data-matrix.md.' );
		}
		$cleanup = new Uninstall_Cleanup_Service( null );
		$result  = $cleanup->cleanup_plugin_owned_data( Uninstall_Cleanup_Service::SCOPE_FULL );

		$this->assertArrayHasKey( 'built_pages_preserved', $result );
		$this->assertTrue( $result['built_pages_preserved'], 'Uninstall must preserve built pages by default.' );
		$this->assertArrayHasKey( 'acf_transients_removed', $result );
		$this->assertIsInt( $result['acf_transients_removed'] );
	}
}
