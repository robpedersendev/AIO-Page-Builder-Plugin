<?php
/**
 * Extra body classes for live preview compatibility shell (block themes, FSE-friendly hooks).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend\Theme_Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies supplemental classes merged into body_class() for compatibility preview mode.
 */
final class Body_Class_Service {

	/**
	 * @return list<string>
	 */
	public static function get_extra_classes(): array {
		return array(
			'aio-template-live-preview-shell',
			'page',
			'wp-singular',
		);
	}
}
