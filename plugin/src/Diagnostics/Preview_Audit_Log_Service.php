<?php
/**
 * Redacted audit-style logging for template live preview (no raw tickets or full query strings).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Diagnostics;

use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Log_Severities;

defined( 'ABSPATH' ) || exit;

/**
 * Logs preview outcomes with hashed ticket identifiers only.
 */
final class Preview_Audit_Log_Service {

	/** @var Logger_Interface */
	private $logger;

	public function __construct( Logger_Interface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param array<string, mixed> $context Safe fields only (no raw ticket, HTML, query strings, cookies).
	 * @return void
	 */
	public function log_preview_event( array $context ): void {
		$line = \wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
		if ( $line === false ) {
			return;
		}
		$id = \function_exists( 'wp_generate_uuid4' ) ? \wp_generate_uuid4() : (string) \wp_rand();
		$this->logger->log(
			new Error_Record(
				$id,
				Log_Categories::SECURITY,
				Log_Severities::INFO,
				$line,
				'',
				isset( $context['user_id'] ) ? (string) (int) $context['user_id'] : '',
				isset( $context['template_key'] ) ? (string) $context['template_key'] : ''
			)
		);
	}
}
