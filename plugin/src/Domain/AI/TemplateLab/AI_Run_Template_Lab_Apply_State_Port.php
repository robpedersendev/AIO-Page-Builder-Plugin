<?php
/**
 * Persist idempotent template-lab canonical apply markers on AI run posts (secrets-free).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

interface AI_Run_Template_Lab_Apply_State_Port {

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_template_lab_canonical_apply_record( int $post_id ): ?array;

	/**
	 * @param array<string, mixed> $record
	 */
	public function save_template_lab_canonical_apply_record( int $post_id, array $record ): bool;
}
