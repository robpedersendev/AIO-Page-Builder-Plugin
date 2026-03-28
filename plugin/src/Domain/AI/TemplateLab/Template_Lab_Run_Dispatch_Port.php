<?php
/**
 * Optional dispatch mode for template-lab runs (sync remains the default contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

/**
 * * Future queue/resume adapters implement this; {@see \AIOPageBuilder\Domain\AI\Runs\AI_Run_Service} exposes the resolved mode only.
 */
interface Template_Lab_Run_Dispatch_Port {

	/**
	 * @return non-empty-string e.g. sync | queue_pending
	 */
	public function mode(): string;
}
