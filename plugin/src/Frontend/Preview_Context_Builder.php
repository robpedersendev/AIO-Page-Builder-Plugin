<?php
/**
 * Chooses preview shell mode and applies compatibility scaffolding when requested.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend;

use AIOPageBuilder\Frontend\Theme_Compatibility\Body_Class_Service;
use AIOPageBuilder\Frontend\Theme_Compatibility\Preview_Shell_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Builds runtime flags for minimal vs compatibility live preview rendering.
 */
final class Preview_Context_Builder {

	/** @var bool */
	private $shell_applied = false;

	/**
	 * @param array<string, mixed> $ticket_record Normalized ticket record.
	 * @return array{shell: string, body_classes: list<string>}
	 */
	public function build( array $ticket_record ): array {
		$shell = isset( $ticket_record['shell'] ) && \is_string( $ticket_record['shell'] ) ? $ticket_record['shell'] : Template_Live_Preview_Ticket_Service::SHELL_MINIMAL;
		if ( $shell !== Template_Live_Preview_Ticket_Service::SHELL_COMPAT ) {
			return array(
				'shell'        => Template_Live_Preview_Ticket_Service::SHELL_MINIMAL,
				'body_classes' => array( 'aio-template-live-preview' ),
			);
		}

		if ( ! Preview_Shell_Service::apply() ) {
			return array(
				'shell'        => 'shell_failed',
				'body_classes' => array( 'aio-template-live-preview' ),
			);
		}

		$this->shell_applied = true;
		$extra               = Body_Class_Service::get_extra_classes();
		$body                = array_merge( array( 'aio-template-live-preview' ), $extra );

		return array(
			'shell'        => Template_Live_Preview_Ticket_Service::SHELL_COMPAT,
			'body_classes' => $body,
		);
	}

	/**
	 * @return bool
	 */
	public function was_shell_applied(): bool {
		return $this->shell_applied;
	}

	/**
	 * @return void
	 */
	public function teardown(): void {
		if ( $this->shell_applied ) {
			Preview_Shell_Service::restore();
			$this->shell_applied = false;
		}
	}
}
