<?php
/**
 * Read-only artifact access for domain services (e.g. template-lab apply).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Runs;

defined( 'ABSPATH' ) || exit;

interface AI_Run_Artifact_Read_Port {

	public function get( int $run_post_id, string $category ): mixed;
}
