<?php
/**
 * Persists onboarding state and draft across steps.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

final class Onboarding_State_Service {

	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_state(): array {
		$raw = $this->settings->get( Option_Names::PB_ONBOARDING_STATE );
		return is_array( $raw ) ? $raw : $this->default_state();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_draft(): array {
		$raw = $this->settings->get( Option_Names::PB_ONBOARDING_DRAFT );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	public function save_state( array $state ): void {
		$state['last_changed_at'] = gmdate( 'c' );
		$this->settings->set( Option_Names::PB_ONBOARDING_STATE, $state );
	}

	/**
	 * @param array<string, mixed> $draft
	 * @return void
	 */
	public function save_draft( array $draft ): void {
		$this->settings->set( Option_Names::PB_ONBOARDING_DRAFT, $draft );
	}

	/**
	 * @return array{current_step: string, completed_steps: array<int, string>, draft_status: string, last_changed_at: string, last_changed_by: string}
	 */
	public function default_state(): array {
		return array(
			'current_step'    => 'welcome',
			'completed_steps' => array(),
			'draft_status'    => 'not_started',
			'last_changed_at' => gmdate( 'c' ),
			'last_changed_by' => '',
		);
	}
}
