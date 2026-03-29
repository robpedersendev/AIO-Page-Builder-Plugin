<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Export\Template_Lab_Snapshot_Refs_Export_Assembler;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

require_once dirname( __DIR__, 2 ) . '/src/Domain/ExportRestore/Export/Template_Lab_Snapshot_Refs_Export_Assembler.php';

final class Template_Lab_Snapshot_Refs_Export_Assembler_Test extends TestCase {

	public function test_build_contains_sessions_and_no_raw_transcript_keys(): void {
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'list_export_safe_approved_snapshot_rows' )->willReturn(
			array(
				array(
					'session_id'            => 'acs_test',
					'task_type'             => 'template_lab',
					'approved_snapshot_ref' => array(
						'run_post_id'          => 1,
						'artifact_fingerprint' => 'ab',
					),
					'post_modified_gmt'     => '2026-03-01 12:00:00',
				),
			)
		);
		$out = Template_Lab_Snapshot_Refs_Export_Assembler::build( $chat );
		$this->assertSame( 'template_lab_approved_snapshot_refs', $out['kind'] );
		$this->assertArrayHasKey( 'sessions', $out );
		$this->assertCount( 1, $out['sessions'] );
		$this->assertArrayNotHasKey( 'messages', $out['sessions'][0] );
	}
}
