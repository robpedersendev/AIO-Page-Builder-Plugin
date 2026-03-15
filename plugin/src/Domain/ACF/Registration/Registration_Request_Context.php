<?php
/**
 * Request context for ACF registration (acf-conditional-registration-contract).
 * Detects front-end vs admin so bootstrap can skip registration on public requests.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Determines whether the current request is public/front-end (no ACF registration)
 * or admin/tooling (registration allowed). Non-final so tests can mock it.
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
	 * Returns true when ACF registration should be skipped for this request (e.g. front-end).
	 * Extension point: later can add "admin but not a page edit screen" if needed.
	 *
	 * @return bool
	 */
	public function should_skip_registration(): bool {
		return $this->is_front_end();
	}
}
