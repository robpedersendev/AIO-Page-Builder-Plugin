<?php
/**
 * Persists translated canonical definitions for template-lab apply (composition / page / section).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

interface Template_Lab_Canonical_Registry_Persist_Port {

	/**
	 * @param array<string, mixed> $definition
	 * @return array{internal_key: string, post_id: int}
	 */
	public function persist_definition( string $target_kind, array $definition ): array;
}
