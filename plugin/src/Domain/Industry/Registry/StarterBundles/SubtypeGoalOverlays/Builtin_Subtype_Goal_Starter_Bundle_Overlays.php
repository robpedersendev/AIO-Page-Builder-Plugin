<?php
/**
 * Built-in combined subtype+goal starter-bundle overlay definitions (Prompt 552).
 * Returns overlay list for Subtype_Goal_Starter_Bundle_Overlay_Registry::load().
 * Seed set: mobile nail + booking, buyer realtor + consultation, commercial plumber + estimate, commercial restoration + calls.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry\StarterBundles\SubtypeGoalOverlays;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;

/**
 * Returns built-in combined subtype+goal overlay definitions (bounded high-value pairs only).
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Subtype_Goal_Starter_Bundle_Overlays {

	public static function get_definitions(): array {
		$dir   = __DIR__;
		$out   = array();
		$files = array(
			$dir . '/cosmetology_nail_mobile_tech-bookings.php',
			$dir . '/realtor_buyer_agent-consultations.php',
			$dir . '/plumber_commercial-estimates.php',
			$dir . '/disaster_recovery_commercial-calls.php',
		);
		foreach ( $files as $path ) {
			if ( ! \is_readable( $path ) ) {
				continue;
			}
			$loaded = require $path;
			if ( \is_array( $loaded ) ) {
				foreach ( $loaded as $ov ) {
					if ( \is_array( $ov ) ) {
						$out[] = $ov;
					}
				}
			}
		}
		return $out;
	}
}
