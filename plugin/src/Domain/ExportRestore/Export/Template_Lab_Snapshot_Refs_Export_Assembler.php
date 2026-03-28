<?php
/**
 * Builds a redacted export fragment for template-lab approved snapshot references (no transcripts or secrets).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;

final class Template_Lab_Snapshot_Refs_Export_Assembler {

	/**
	 * @return array<string, mixed>
	 */
	public static function build( AI_Chat_Session_Repository_Interface $chat_sessions ): array {
		return array(
			'export_schema_version' => '1',
			'kind'                  => 'template_lab_approved_snapshot_refs',
			'generated_at'          => \gmdate( 'c' ),
			'sessions'              => $chat_sessions->list_export_safe_approved_snapshot_rows( 500 ),
			'disclaimer'            => 'Portable snapshot linkage metadata only. No automatic apply on import; canonical registry remains source of truth.',
		);
	}
}
