<?php
/**
 * Thrown when {@see \AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen::redirect_and_end_request()}
 * runs in the PHPUnit harness with {@see $GLOBALS['_aio_pb_test_capture_redirect']} enabled (replaces wp_safe_redirect + exit).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Testing;

/**
 * Carries the redirect target URL for assertions.
 */
final class Redirect_Capture_Exception extends \RuntimeException {

	public function __construct( private readonly string $location ) {
		parent::__construct( 'redirect_capture' );
	}

	public function get_location(): string {
		return $this->location;
	}
}
