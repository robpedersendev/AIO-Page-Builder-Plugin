<?php
/**
 * Request context for ACF registration (acf-conditional-registration-contract).
 * Detects front-end vs admin and scripted contexts (WP-CLI, cron) so bootstrap skips registration when appropriate (Prompt 304).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Determines whether the current request is public/front-end, scripted (CLI/cron), or admin.
 * Non-final so tests can mock it.
 */
class Registration_Request_Context {

	/**
	 * Returns true when the current request is public/front-end (no ACF groups should be registered).
	 *
	 * @return bool
	 */
	public function is_front_end(): bool {
		return ! is_admin();
	}

	/**
	 * Returns true when the current request is in WordPress admin (registration may run, subject to further context).
	 *
	 * @return bool
	 */
	public function is_admin(): bool {
		return is_admin();
	}

	/**
	 * Returns true when running under WP-CLI. Scripted context: no scoped registration unless explicit tooling (Prompt 304).
	 *
	 * @return bool
	 */
	public function is_cli(): bool {
		return \defined( 'WP_CLI' ) && \WP_CLI;
	}

	/**
	 * Returns true when the request is a cron run. Scripted context: skip registration (Prompt 304).
	 *
	 * @return bool
	 */
	public function is_cron(): bool {
		return \defined( 'DOING_CRON' ) && \DOING_CRON;
	}

	/**
	 * Returns true when the execution context is scripted/automation (CLI, cron). No full registration from generic bootstrap.
	 *
	 * @return bool
	 */
	public function is_scripted_context(): bool {
		return $this->is_cli() || $this->is_cron();
	}

	/**
	 * Returns true when ACF registration should be skipped for this request.
	 * Skips: front-end, WP-CLI, cron. Admin page-edit gets scoped registration via bootstrap controller.
	 *
	 * @return bool
	 */
	public function should_skip_registration(): bool {
		if ( $this->is_front_end() ) {
			return true;
		}
		if ( $this->is_scripted_context() ) {
			return true;
		}
		return false;
	}
}
